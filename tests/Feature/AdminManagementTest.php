<?php

namespace Tests\Feature;

use App\Models\FoodCategory;
use App\Models\FoodLog;
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

    public function test_patients_cannot_reach_admin_management_pages(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/admin/patients')->assertForbidden();
        $this->actingAs($patient)->get('/admin/food-guidance')->assertForbidden();
        $this->actingAs($patient)->get('/admin/activity')->assertForbidden();
    }
}
