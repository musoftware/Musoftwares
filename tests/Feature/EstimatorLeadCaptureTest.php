<?php

namespace Tests\Feature;

use App\Models\GuestTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The estimator (new project + change request) and the system builder send a `scope` object
 * and no email. Before this, the endpoint rejected those requests with 422.
 */
class EstimatorLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_request_without_email_is_saved_as_guest_ticket(): void
    {
        $this->postJson('/tools/lead-capture', [
            'name' => 'Sara',
            'mobile' => '01000000000',
            'tool_name' => 'Estimator - change request',
            'scope' => [
                'request_type' => 'Change to an existing project',
                'system' => 'web',
                'changes' => ['Fix bugs and errors x 3'],
                'details' => 'Checkout button does not work',
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $ticket = GuestTicket::firstOrFail();
        $this->assertSame('', $ticket->email);
        $this->assertStringContainsString('Estimator - change request', $ticket->subject);
        $this->assertStringContainsString('Fix bugs and errors x 3', $ticket->body);
        $this->assertStringContainsString('Checkout button does not work', $ticket->body);
    }

    public function test_system_builder_business_field_is_used_in_subject(): void
    {
        $this->postJson('/tools/lead-capture', [
            'name' => 'Omar',
            'email' => 'omar@example.com',
            'mobile' => '0100',
            'business' => 'Omar Foods',
            'scope' => ['archetype' => 'erp'],
        ])->assertOk();

        $this->assertStringContainsString('Omar Foods', GuestTicket::firstOrFail()->subject);
    }

    public function test_request_needs_scope_or_breakdown(): void
    {
        $this->postJson('/tools/lead-capture', ['name' => 'X', 'mobile' => '0100'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scope', 'breakdown']);
    }
}
