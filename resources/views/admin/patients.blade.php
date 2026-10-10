@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $deleteErrors = $errors->getBag('delete');
    $statusTabs = ['all' => ['All', $totals['all']], 'active' => ['Active', $totals['active']], 'inactive' => ['Deactivated', $totals['inactive']]];
@endphp

<div class="lp">
    @if (session('status'))
        <div class="au-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif

    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Patients</span>
            <h1>Patients</h1>
            <p>Search patient accounts, deactivate accounts that misuse the service, and handle deletion requests.</p>
        </div>
        <div class="lp-hero-stats">
            <div class="lp-hero-stat"><span>Total patients</span><strong>{{ $totals['all'] }}</strong><small>registered accounts</small></div>
            <div class="lp-hero-stat"><span>Active</span><strong>{{ $totals['active'] }}</strong><small>can sign in</small></div>
            <div class="lp-hero-stat"><span>Deactivated</span><strong>{{ $totals['inactive'] }}</strong><small>signed out &amp; blocked</small></div>
        </div>
    </section>

    <section class="lp-panel">
        <form class="au-toolbar" method="GET" action="{{ route('admin.patients') }}">
            <nav class="au-tabs" aria-label="Filter by status">
                @foreach ($statusTabs as $value => [$label, $count])
                    <a href="{{ route('admin.patients', [...$filters, 'status' => $value, 'page' => null]) }}" class="{{ $filters['status'] === $value ? 'is-active' : '' }}">{{ $label }} <b>{{ $count }}</b></a>
                @endforeach
            </nav>
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <label class="au-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="au-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search by name or email…" aria-label="Search patients"></label>
            <select class="au-select" name="sort" aria-label="Sort patients" onchange="this.form.submit()">
                @foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z', 'most-active' => 'Most entries'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="au-btn" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg> Search</button>
            <a class="au-btn" href="{{ route('admin.patients.export', $filters) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg> Export CSV</a>
        </form>

        @if ($patients->isEmpty())
            <div class="lp-empty"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/></svg><strong>No patients found</strong>{{ $filters['q'] !== '' ? 'Try a different search.' : 'Patient accounts will appear here.' }}</div>
        @else
            <div style="overflow-x:auto">
                <table class="au-table">
                    <thead><tr><th>Patient</th><th class="au-hide-sm">Joined</th><th class="au-hide-sm">Entries</th><th class="au-hide-sm">Last activity</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            <tr>
                                <td><div class="au-person"><span class="au-avatar {{ $patient->is_active ? '' : 'is-off' }}">{{ strtoupper(substr($patient->name, 0, 1)) }}</span><div><strong>{{ $patient->name }}</strong><small>{{ $patient->email }}</small></div></div></td>
                                <td class="au-hide-sm"><span class="au-muted" title="{{ $patient->created_at->format('M j, Y g:i A') }}">{{ $patient->created_at->format('M j, Y') }}</span></td>
                                <td class="au-hide-sm"><span class="au-muted" title="Food · Symptoms · Sleep">{{ $patient->food_logs_count }} · {{ $patient->symptom_logs_count }} · {{ $patient->sleep_logs_count }}</span></td>
                                <td class="au-hide-sm"><span class="au-muted">{{ $patient->last_active_at ? \Illuminate\Support\Carbon::parse($patient->last_active_at)->diffForHumans() : 'Never' }}</span></td>
                                <td><span class="au-pill lp-tone-{{ $patient->is_active ? 'green' : 'red' }}"><i></i>{{ $patient->is_active ? 'Active' : 'Deactivated' }}</span></td>
                                <td>
                                    <div class="au-row-actions">
                                        <form method="POST" action="{{ route('admin.patients.status', $patient) }}" onsubmit="return confirm(@js($patient->is_active ? "Deactivate {$patient->name}? They will be signed out of every device and blocked from signing in." : "Reactivate {$patient->name}? They will be able to sign in again."))">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $patient->is_active ? 0 : 1 }}">
                                            <button class="au-btn is-sm" type="submit">{{ $patient->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                                        </form>
                                        <button class="au-btn is-sm is-danger" type="button" data-delete-patient="{{ $patient->id }}" data-name="{{ $patient->name }}" data-email="{{ $patient->email }}" data-action="{{ route('admin.patients.destroy', $patient) }}">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="au-pagination">
                <span>Showing {{ $patients->firstItem() }}–{{ $patients->lastItem() }} of {{ $patients->total() }}</span>
                <nav aria-label="Pages">
                    @if ($patients->previousPageUrl())<a class="au-btn is-sm" href="{{ $patients->previousPageUrl() }}">← Previous</a>@endif
                    @if ($patients->nextPageUrl())<a class="au-btn is-sm" href="{{ $patients->nextPageUrl() }}">Next →</a>@endif
                </nav>
            </div>
        @endif
    </section>
</div>

<div class="au-modal" id="delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-title">
    <form class="au-modal-card" method="POST" id="delete-form" action="{{ old('patient_action') }}">
        @csrf
        @method('DELETE')
        <h3 id="delete-title"><span class="lp-badge-icon lp-tone-red"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></span> Delete <span id="delete-name">{{ old('patient_name') }}</span>?</h3>
        <p>This permanently deletes the account and <strong>all of its food, symptom and sleep logs and insights</strong>. It cannot be undone. Only do this when the patient asks for their account to be removed.</p>
        <input type="hidden" name="patient_action" id="delete-action" value="{{ old('patient_action') }}">
        <input type="hidden" name="patient_name" id="delete-name-input" value="{{ old('patient_name') }}">
        <label for="confirm_email">Type <strong id="delete-email">{{ old('patient_email') }}</strong> to confirm</label>
        <input type="hidden" name="patient_email" id="delete-email-input" value="{{ old('patient_email') }}">
        <input class="au-input" id="confirm_email" name="confirm_email" autocomplete="off" required @if ($deleteErrors->any()) aria-invalid="true" @endif>
        @if ($deleteErrors->any())<p class="au-error">{{ $deleteErrors->first() }}</p>@endif
        <div class="au-modal-actions">
            <button class="au-btn" type="button" data-close-modal>Cancel</button>
            <button class="au-btn is-solid-danger" type="submit" id="delete-submit" disabled>Delete permanently</button>
        </div>
    </form>
</div>

<script>
    (() => {
        const modal = document.getElementById('delete-modal');
        const form = document.getElementById('delete-form');
        const confirmInput = document.getElementById('confirm_email');
        const submit = document.getElementById('delete-submit');
        let expectedEmail = @json(old('patient_email', ''));

        const open = () => { modal.classList.add('is-open'); confirmInput.focus(); };
        const close = () => { modal.classList.remove('is-open'); confirmInput.value = ''; submit.disabled = true; };
        const sync = () => { submit.disabled = confirmInput.value.trim() !== expectedEmail; };

        document.querySelectorAll('[data-delete-patient]').forEach((button) => button.addEventListener('click', () => {
            expectedEmail = button.dataset.email;
            form.action = button.dataset.action;
            document.getElementById('delete-action').value = button.dataset.action;
            document.getElementById('delete-name').textContent = button.dataset.name;
            document.getElementById('delete-name-input').value = button.dataset.name;
            document.getElementById('delete-email').textContent = button.dataset.email;
            document.getElementById('delete-email-input').value = button.dataset.email;
            sync();
            open();
        }));
        confirmInput.addEventListener('input', sync);
        modal.querySelector('[data-close-modal]').addEventListener('click', close);
        modal.addEventListener('click', (event) => { if (event.target === modal) { close(); } });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('is-open')) { close(); } });

        @if ($deleteErrors->any())
            open();
        @endif
    })();
</script>
@endsection
