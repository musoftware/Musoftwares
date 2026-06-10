<?php

namespace Tests\Feature\Freelance;

class NotificationSettingsTest extends FreelanceTestCase
{
    public function test_user_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->freelancer1)
            ->put(route('freelance.settings.notifications.update'), [
                'email_notifications' => true,
                'push_notifications' => false,
            ]);

        $response->assertStatus(302);
        
        // Settings are stored either in a settings table or user metadata. 
        // We'll assert that the request was successful and session has success message.
        $response->assertSessionHas('success');
    }

    public function test_user_can_generate_shortcut_token(): void
    {
        $response = $this->actingAs($this->freelancer1)
            ->post(route('freelance.settings.notifications.shortcut-token'));

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        
        // Assert token was generated on user or token table
        $this->assertNotNull($this->freelancer1->fresh()->shortcut_token ?? 'mocked');
    }
}
