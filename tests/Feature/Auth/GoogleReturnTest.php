<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Sign in with Google" on a page (the AI builder chat) brings the person back to that same page, never elsewhere. */
class GoogleReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sign_in_remembers_the_page_to_come_back_to(): void
    {
        config(['services.google.client_id' => 'client-id']);

        $this->get('/auth/google/redirect?redirect=/ai/1d46571e-b2aa-40fe-93ab-42f085d0826a')
            ->assertRedirectContains('accounts.google.com')
            ->assertSessionHas('url.intended', url('/ai/1d46571e-b2aa-40fe-93ab-42f085d0826a'));
    }

    public function test_an_outside_address_is_never_kept(): void
    {
        config(['services.google.client_id' => 'client-id']);

        $this->get('/auth/google/redirect?redirect=//evil.example.com/x')->assertSessionMissing('url.intended');
    }
}
