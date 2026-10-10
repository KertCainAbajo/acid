<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_can_read_the_privacy_policy_and_terms(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy')->assertSee('Information we collect')->assertSee('Back to sign in');
        $this->get('/terms')->assertOk()->assertSee('Terms of Service')->assertSee('Not a medical service');
    }

    public function test_signed_in_patients_are_offered_a_way_back_to_their_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/privacy');

        $response->assertSee('Back to dashboard');
    }

    public function test_sign_in_and_registration_pages_link_to_the_legal_pages(): void
    {
        $this->get('/login')->assertSee(route('privacy'))->assertSee(route('terms'));
        $this->get('/register')->assertSee(route('privacy'))->assertSee(route('terms'));
    }

    public function test_contact_email_is_shown_when_configured(): void
    {
        config(['app.contact_email' => 'privacy@refluxcare.test']);

        $this->get('/privacy')->assertSee('mailto:privacy@refluxcare.test', false);
    }
}
