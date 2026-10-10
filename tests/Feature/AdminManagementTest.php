<?php

namespace Tests\Feature;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SymptomLog;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_admin_can_search_and_filter_patients(): void
    {
        User::factory()->create(['name' => 'Alice Active', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Alan Inactive', 'email' => 'alan@example.com', 'is_active' => false]);
        User::factory()->create(['name' => 'Bob Active', 'email' => 'bob@example.com']);

        $response = $this->actingAs($this->admin())->get('/admin/patients?q=al&status=active');

        $response->assertOk()->assertSee('Alice Active')->assertDontSee('Alan Inactive')->assertDontSee('Bob Active');
    }

    public function test_dormant_segment_lists_active_patients_with_no_activity_for_fourteen_days(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $recent = User::factory()->create(['name' => 'Recently Active']);
        $dormant = User::factory()->create(['name' => 'Gone Quiet']);
        User::factory()->create(['name' => 'Deactivated Quiet', 'is_active' => false]);
        SystemLog::create(['actor_id' => $recent->id, 'action' => 'user_logged_in'])->forceFill(['created_at' => '2026-10-08 09:00:00'])->save();
        SystemLog::create(['actor_id' => $dormant->id, 'action' => 'user_logged_in'])->forceFill(['created_at' => '2026-09-01 09:00:00'])->save();

        $response = $this->actingAs($this->admin())->get('/admin/patients?status=dormant');

        $response->assertSee('Gone Quiet')->assertDontSee('Recently Active')->assertDontSee('Deactivated Quiet');
        $response->assertViewHas('totals', fn (array $totals): bool => $totals['dormant'] === 1 && $totals['all'] === 3);
    }

    public function test_deactivating_a_patient_blocks_them_and_revokes_their_tokens(): void
    {
        $admin = $this->admin();
        $patient = User::factory()->create(['name' => 'Pat']);
        $patient->createToken('mobile');

        $response = $this->actingAs($admin)->patch("/admin/patients/{$patient->id}/status", ['is_active' => 0]);

        $response->assertSessionHas('status', 'Pat has been deactivated and signed out of every device.');
        $this->assertFalse($patient->fresh()->is_active);
        $this->assertSame(0, $patient->tokens()->count());
        $this->assertDatabaseHas('system_logs', ['actor_id' => $admin->id, 'action' => 'patient_deactivated', 'target_id' => $patient->id]);
    }

    public function test_admin_can_reactivate_a_patient(): void
    {
        $patient = User::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin())->patch("/admin/patients/{$patient->id}/status", ['is_active' => 1]);

        $this->assertTrue($patient->fresh()->is_active);
    }

    public function test_deleting_a_patient_requires_typing_their_email_and_removes_their_logs(): void
    {
        $admin = $this->admin();
        $patient = User::factory()->create(['email' => 'remove-me@example.com']);
        $meal = FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Toast', 'category' => 'other', 'time' => now()->subHour()]);

        $this->actingAs($admin)->delete("/admin/patients/{$patient->id}", ['confirm_email' => 'wrong@example.com'])
            ->assertSessionHasErrorsIn('delete', ['confirm_email']);
        $this->assertModelExists($patient);

        $this->actingAs($admin)->delete("/admin/patients/{$patient->id}", ['confirm_email' => 'remove-me@example.com'])
            ->assertRedirect(route('admin.patients'));
        $this->assertModelMissing($patient);
        $this->assertModelMissing($meal);
        $this->assertDatabaseHas('system_logs', ['actor_id' => $admin->id, 'action' => 'patient_deleted']);
    }

    public function test_patient_management_actions_cannot_target_an_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin)->patch("/admin/patients/{$otherAdmin->id}/status", ['is_active' => 0])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/patients/{$otherAdmin->id}", ['confirm_email' => $otherAdmin->email])->assertNotFound();

        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_patient_export_only_contains_the_filtered_patients(): void
    {
        User::factory()->create(['name' => 'Kept Patient', 'email' => 'kept@example.com']);
        User::factory()->create(['name' => 'Hidden Patient', 'email' => 'hidden@example.com', 'is_active' => false]);

        $response = $this->actingAs($this->admin())->get('/admin/patients/export?status=active');

        $csv = $response->assertDownload()->streamedContent();
        $this->assertStringContainsString('"Kept Patient",kept@example.com,Active', $csv);
        $this->assertStringNotContainsString('Hidden Patient', $csv);
    }

    public function test_admin_can_manage_food_guidance(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/food-guidance', ['name' => 'Ginger tea', 'type' => 'safe'])->assertRedirect(route('admin.food-guidance'));
        $category = FoodCategory::where('name', 'Ginger tea')->firstOrFail();
        $this->actingAs($admin)->post('/admin/food-guidance', ['name' => 'Ginger tea', 'type' => 'avoid'])->assertSessionHasErrors('name');
        $this->actingAs($admin)->put("/admin/food-guidance/{$category->id}", ['name' => 'Ginger tea', 'type' => 'avoid']);
        $this->assertSame('avoid', $category->fresh()->type);

        $this->actingAs($admin)->delete("/admin/food-guidance/{$category->id}");

        $this->assertModelMissing($category);
        $this->assertSame(['food_category_created', 'food_category_updated', 'food_category_deleted'], SystemLog::where('actor_id', $admin->id)->orderBy('id')->pluck('action')->all());
    }

    public function test_food_guidance_suggests_foods_from_anonymised_patient_data(): void
    {
        $this->travelTo('2026-10-10 20:00:00');
        FoodCategory::create(['name' => 'Coffee', 'type' => 'avoid']);
        [$first, $second] = User::factory()->count(2)->create();
        foreach ([$first, $second] as $patient) {
            FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Pizza', 'category' => 'oily', 'time' => '2026-10-09 12:00:00']);
            SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 6, 'time' => '2026-10-09 14:00:00']);
            FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Black coffee', 'category' => 'acidic', 'time' => '2026-10-08 08:00:00']);
            SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 5, 'time' => '2026-10-08 09:00:00']);
        }
        foreach (range(1, 3) as $day) {
            FoodLog::create(['user_id' => $first->id, 'food_name' => 'Rice porridge', 'category' => 'other', 'time' => "2026-10-0{$day} 19:00:00"]);
        }

        $response = $this->actingAs($this->admin())->get('/admin/food-guidance');
        $response->assertOk();

        $response->assertViewHas('suggestedTriggers', fn ($suggestions): bool => $suggestions->pluck('name')->all() === ['Pizza']);
        $response->assertViewHas('suggestedGentle', fn ($suggestions): bool => $suggestions->pluck('name')->all() === ['Rice porridge']);
        $response->assertViewHas('avoidFoods', fn ($foods): bool => $foods->first()['evidence']['triggerPatients'] === 2);
        $response->assertSee('Confirmed by patient data');
    }

    public function test_activity_log_can_be_filtered_and_exported(): void
    {
        $admin = $this->admin();
        $patient = User::factory()->create(['name' => 'Searchable Person']);
        SystemLog::create(['actor_id' => $patient->id, 'action' => 'user_logged_in', 'ip_address' => '10.0.0.1']);
        SystemLog::create(['actor_id' => $patient->id, 'action' => 'food_log_created']);
        SystemLog::create(['actor_id' => $admin->id, 'action' => 'food_category_created']);

        $response = $this->actingAs($admin)->get('/admin/activity?group=entries&q=searchable');

        $response->assertOk();
        $response->assertViewHas('activities', fn ($activities): bool => $activities->pluck('action')->all() === ['food_log_created']);

        $csv = $this->actingAs($admin)->get('/admin/activity/export?group=sign-ins')->streamedContent();
        $this->assertStringContainsString('user_logged_in,"Searchable Person"', $csv);
        $this->assertStringNotContainsString('food_category_created', $csv);
    }

    public function test_activity_page_summarises_the_last_seven_days_by_category(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $admin = $this->admin();
        $patient = User::factory()->create();
        SystemLog::create(['actor_id' => $patient->id, 'action' => 'user_logged_in', 'ip_address' => '10.0.0.1', 'metadata' => ['channel' => 'google']]);
        SystemLog::create(['actor_id' => $patient->id, 'action' => 'user_registered', 'ip_address' => '10.0.0.2']);
        SystemLog::create(['actor_id' => $patient->id, 'action' => 'symptom_log_created', 'ip_address' => '10.0.0.1']);
        SystemLog::create(['actor_id' => $admin->id, 'action' => 'patient_deactivated', 'ip_address' => '10.0.0.3']);
        $old = SystemLog::create(['actor_id' => $patient->id, 'action' => 'user_logged_in']);
        $old->forceFill(['created_at' => '2026-09-01 10:00:00'])->save();

        $response = $this->actingAs($admin)->get('/admin/activity');

        $response->assertViewHas('groupCounts', ['sign-ins' => 2, 'entries' => 1, 'accounts' => 1, 'guidance' => 0]);
        $response->assertViewHas('security', ['signInsToday' => 1, 'newAccounts' => 1, 'accountChanges' => 1, 'uniqueIps' => 3]);
        $response->assertViewHas('topActors', fn ($actors): bool => $actors->first()->actor_id === $patient->id && (int) $actors->first()->total === 3);
        $response->assertSee('Google');
    }

    public function test_patients_cannot_reach_admin_management_pages(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/admin/patients')->assertForbidden();
        $this->actingAs($patient)->get('/admin/food-guidance')->assertForbidden();
        $this->actingAs($patient)->get('/admin/activity')->assertForbidden();
    }
}
