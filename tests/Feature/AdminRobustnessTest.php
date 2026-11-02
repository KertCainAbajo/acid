<?php

namespace Tests\Feature;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminRobustnessTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function seedRealisticData(): User
    {
        $admin = User::factory()->admin()->create(['name' => '<b>Admin</b>']);
        $patients = User::factory()->count(3)->create();
        User::factory()->create(['name' => '=cmd|calc', 'is_active' => false, 'locked_until' => now()->addMinutes(10)]);
        FoodCategory::create(['name' => 'Coffee', 'type' => 'avoid']);
        FoodCategory::create(['name' => 'Oatmeal', 'type' => 'safe']);

        foreach ($patients as $patient) {
            FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Coffee <script>x</script>', 'category' => 'acidic', 'time' => now()->subHours(3)]);
            SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 7, 'time' => now()->subHours(2)]);
            SleepLog::create(['user_id' => $patient->id, 'sleep_hours' => 5.5, 'sleep_quality' => 2, 'stress_level' => 8, 'logged_on' => today()]);
            SystemLog::create(['actor_id' => $patient->id, 'action' => 'user_logged_in', 'ip_address' => '203.0.113.9']);
        }
        SystemLog::create(['actor_id' => null, 'action' => 'login_failed', 'ip_address' => '198.51.100.7', 'metadata' => ['email' => 'x@example.com']]);
        SystemLog::create(['actor_id' => null, 'action' => 'some_unknown_future_action']);

        return $admin;
    }

    public function test_every_admin_page_renders_with_realistic_data(): void
    {
        $admin = $this->seedRealisticData();

        foreach (['/admin/dashboard', '/admin/patients', '/admin/activity', '/admin/food-guidance', '/admin/security', '/admin/settings'] as $page) {
            $response = $this->actingAs($admin)->get($page);

            $response->assertOk();
            $response->assertDontSee('<script>x</script>', false);
            $response->assertDontSee('<b>Admin</b>', false);
        }
    }

    public function test_admin_pages_survive_hostile_query_strings_without_errors_or_redirect_loops(): void
    {
        $admin = $this->seedRealisticData();
        $urls = [
            '/admin/patients?search=%27%20OR%201%3D1--&status=bogus&sort=drop',
            '/admin/patients?search[]=array&page=-5',
            '/admin/patients?page=999999',
            '/admin/patients?search='.str_repeat('a', 5000),
            '/admin/activity?group=nonexistent&from=not-a-date&to=yesterday',
            '/admin/activity?from=2026-12-01&to=2026-01-01',
            '/admin/activity?search[]=x&page=abc',
            '/admin/activity/export?group=%00',
            '/admin/patients/export?status[]=x',
            '/admin/dashboard?range=../../etc/passwd',
            '/admin/security?page=-1',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($admin)->get($url);

            $this->assertLessThan(500, $response->getStatusCode(), "Server error on {$url}");
            if ($response->isRedirect()) {
                $this->assertNotSame(url($url), $response->headers->get('Location'), "Redirect loop on {$url}");
            }
        }
    }

    public function test_patients_and_guests_cannot_use_any_admin_action(): void
    {
        $this->seedRealisticData();
        $patient = User::factory()->create();
        $victim = User::factory()->create();
        $category = FoodCategory::first();
        $actions = [
            ['get', '/admin/patients/export', []],
            ['get', '/admin/activity/export', []],
            ['patch', "/admin/patients/{$victim->id}/status", ['is_active' => false]],
            ['delete', "/admin/patients/{$victim->id}", ['confirm_email' => $victim->email, 'current_password' => 'password']],
            ['post', '/admin/food-guidance', ['name' => 'Pizza', 'type' => 'safe']],
            ['put', "/admin/food-guidance/{$category->id}", ['name' => 'Hacked', 'type' => 'safe']],
            ['delete', "/admin/food-guidance/{$category->id}", []],
            ['post', "/admin/security/unlock/{$victim->id}", []],
        ];

        foreach ($actions as [$method, $url, $data]) {
            $this->actingAs($patient)->{$method}($url, $data)->assertForbidden();
        }
        $this->post('/logout');
        Auth::guard('web')->logout();
        foreach ($actions as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertRedirect(route('login'));
        }

        $this->assertTrue($victim->fresh()->is_active);
        $this->assertSame('Coffee', $category->fresh()->name);
    }

    public function test_patient_search_ignores_letter_case_and_most_active_sort_ranks_by_total_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $quiet = User::factory()->create(['name' => 'Quiet Patient']);
        $busy = User::factory()->create(['name' => 'Maria Santos']);
        foreach (range(1, 3) as $hour) {
            FoodLog::create(['user_id' => $busy->id, 'food_name' => 'Rice', 'category' => 'acidic', 'time' => now()->subHours($hour)]);
        }

        $this->actingAs($admin)->get('/admin/patients?q=MARIA')->assertSee('Maria Santos')->assertDontSee('Quiet Patient');
        $this->actingAs($admin)->get('/admin/patients?sort=most-active')
            ->assertOk()
            ->assertSeeInOrder(['Maria Santos', 'Quiet Patient']);
    }

    public function test_reseeding_on_deploy_keeps_the_admins_food_guidance_edits(): void
    {
        $this->seed();
        $coffee = FoodCategory::where('type', 'avoid')->firstOrFail();
        $coffee->update(['type' => 'safe']);
        $removed = FoodCategory::where('id', '!=', $coffee->id)->firstOrFail();
        $removed->delete();

        $this->seed();

        $this->assertSame('safe', $coffee->fresh()->type);
        $this->assertModelMissing($removed);
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }

    public function test_events_without_a_person_name_the_account_they_were_about(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);
        SystemLog::create(['actor_id' => null, 'action' => 'password_reset_requested', 'target_type' => User::class, 'target_id' => $patient->id]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('Maria Santos · account');
        $this->actingAs($admin)->get('/admin/activity')->assertSee('Account: Maria Santos');
        $export = $this->actingAs($admin)->get('/admin/activity/export')->streamedContent();

        $this->assertStringContainsString('Maria Santos (maria@example.com)', $export);
    }
}
