<?php

namespace Tests\Feature\Auth;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_without_optional_marketing_consent(): void
    {
        $email = 'test_unique_'.uniqid().'@example.com';

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_consent' => true,
            'marketing_consent' => false,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.wizard', absolute: false));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertDatabaseHas('user_consents_log', [
            'user_id' => $user->id,
            'consent_type' => ConsentType::PrivacyPolicy->value,
            'action' => ConsentAction::Granted->value,
        ]);
        $this->assertDatabaseMissing('user_consents_log', [
            'user_id' => $user->id,
            'consent_type' => ConsentType::DirectMarketingEmail->value,
            'action' => ConsentAction::Granted->value,
        ]);
    }

    public function test_registration_requires_explicit_privacy_consent(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'No Consent User',
            'email' => 'noconsent_'.uniqid().'@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_consent' => false,
        ]);

        $this->assertGuest();
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('privacy_consent');
    }
}
