<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPatientController extends Controller
{
    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.patients', [
            'title' => 'Patients',
            'filters' => $filters,
            'patients' => $this->query($filters)->paginate(15)->withQueryString(),
            'totals' => [
                'all' => User::where('role', User::ROLE_PATIENT)->count(),
                'active' => User::where('role', User::ROLE_PATIENT)->where('is_active', true)->count(),
                'inactive' => User::where('role', User::ROLE_PATIENT)->where('is_active', false)->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $patients = $this->query($this->filters($request))->get();

        return response()->streamDownload(function () use ($patients): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Email', 'Status', 'Joined', 'Food entries', 'Symptom entries', 'Sleep entries', 'Last activity']);
            foreach ($patients as $patient) {
                fputcsv($output, [
                    $patient->name,
                    $patient->email,
                    $patient->is_active ? 'Active' : 'Inactive',
                    $patient->created_at->toDateString(),
                    $patient->food_logs_count,
                    $patient->symptom_logs_count,
                    $patient->sleep_logs_count,
                    $patient->last_active_at ?? '',
                ]);
            }
            fclose($output);
        }, 'refluxcare-patients-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateStatus(Request $request, User $patient): RedirectResponse
    {
        abort_unless($patient->isPatient(), 404);
        $isActive = $request->validate(['is_active' => ['required', 'boolean']])['is_active'];
        $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);

        $patient->update(['is_active' => $isActive]);
        if (! $isActive) {
            $this->signOutEverywhere($patient);
        }
        $this->systemLogService->record($request, $isActive ? 'patient_reactivated' : 'patient_deactivated', $patient);

        return back()->with('status', $isActive
            ? "{$patient->name} can sign in again."
            : "{$patient->name} has been deactivated and signed out of every device.");
    }

    public function destroy(Request $request, User $patient): RedirectResponse
    {
        abort_unless($patient->isPatient(), 404);
        $request->validateWithBag('delete', [
            'confirm_email' => ['required', 'string', Rule::in([$patient->email])],
        ], [
            'confirm_email.in' => 'Type the patient\'s email address exactly to confirm.',
        ]);

        $name = $patient->name;
        $this->signOutEverywhere($patient);
        $patient->delete();
        $this->systemLogService->record($request, 'patient_deleted', null, ['deleted_user_id' => $patient->id]);

        return redirect()->route('admin.patients')->with('status', "{$name}'s account and all of their logs were permanently deleted.");
    }

    /**
     * @return array{q: string, status: string, sort: string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'name', 'most-active'])],
        ]);

        return [
            'q' => trim($validated['q'] ?? ''),
            'status' => $validated['status'] ?? 'all',
            'sort' => $validated['sort'] ?? 'newest',
        ];
    }

    /**
     * @param  array{q: string, status: string, sort: string}  $filters
     * @return Builder<User>
     */
    private function query(array $filters): Builder
    {
        return User::query()
            ->where('role', User::ROLE_PATIENT)
            ->withCount(['foodLogs', 'symptomLogs', 'sleepLogs'])
            ->addSelect(['last_active_at' => DB::table('system_logs')->selectRaw('MAX(created_at)')->whereColumn('actor_id', 'users.id')])
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(fn (Builder $search) => $search
                ->where('name', 'like', "%{$filters['q']}%")
                ->orWhere('email', 'like', "%{$filters['q']}%")))
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['sort'] === 'newest', fn (Builder $query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn (Builder $query) => $query->oldest())
            ->when($filters['sort'] === 'name', fn (Builder $query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'most-active', fn (Builder $query) => $query->orderByRaw('(food_logs_count + symptom_logs_count + sleep_logs_count) desc'));
    }

    private function signOutEverywhere(User $patient): void
    {
        $patient->tokens()->delete();
        $patient->forceFill(['remember_token' => null])->save();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $patient->id)->delete();
        }
    }
}
