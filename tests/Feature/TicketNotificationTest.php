<?php

namespace Tests\Feature;

use App\Jobs\SendFcmNotificationJob;
use App\Mail\AdminGuestTicketCreatedMail;
use App\Mail\AdminTicketCreatedMail;
use App\Mail\AdminTicketRepliedMail;
use App\Mail\ClientTicketPricedMail;
use App\Mail\ClientTicketReceivedMail;
use App\Mail\ClientTicketRepliedMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\Conversation;
use App\Models\GuestTicket;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $moderator;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@musoftwares.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->moderator = User::factory()->create([
            'email' => 'moderator@musoftwares.com',
            'onboarding_completed' => true,
        ]);
        $this->moderator->assignRole('moderator');

        $this->client = User::factory()->create([
            'email' => 'client@musoftwares.com',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');
    }

    public function test_ticket_creation_dispatches_email_and_fcm_to_client_and_admins(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->client->deviceTokens()->create(['token' => 'client_fcm_token_123']);
        $this->admin->deviceTokens()->create(['token' => 'admin_fcm_token_456']);

        $response = $this->actingAs($this->client)->post(route('tickets.store'), [
            'subject' => 'مشكلة في تحميل لوحة التحكم',
            'priority' => 'High',
            'description' => 'اللوحة لا تفتح وتظهر شاشة بيضاء',
        ]);

        $response->assertRedirect();

        // Verify emails
        Mail::assertQueued(ClientTicketReceivedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com');
        });

        Mail::assertQueued(AdminTicketCreatedMail::class, function ($mail) {
            return $mail->hasTo('admin@musoftwares.com');
        });

        // Verify FCM push notification
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('client_fcm_token_123', $job->tokens) || in_array('admin_fcm_token_456', $job->tokens);
        });
    }

    public function test_admin_reply_dispatches_email_and_fcm_to_client(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->client->deviceTokens()->create(['token' => 'client_fcm_token_123']);

        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'استفسار عن الفاتورة',
            'ticket_message' => 'تفاصيل الاستفسار',
            'ticket_status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket->id), [
            'body' => 'تمت مراجعة الفاتورة وتصحيح الحساب.',
            'is_internal' => false,
        ]);

        $response->assertRedirect();

        // Verify client received email
        Mail::assertQueued(ClientTicketRepliedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com')
                && str_contains($mail->ticketMessage->body, 'تمت مراجعة الفاتورة');
        });

        // Verify client received FCM
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('client_fcm_token_123', $job->tokens);
        });

        // Ticket status should be agent_replied
        $ticket->refresh();
        $this->assertEquals('agent_replied', $ticket->ticket_status);
    }

    public function test_client_chat_reply_dispatches_email_and_fcm_to_assigned_agent_or_admins(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->moderator->deviceTokens()->create(['token' => 'moderator_fcm_token_789']);

        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'assigned_employee_id' => $this->moderator->id,
            'ticket_subject' => 'تعديل دومين الموقع',
            'ticket_message' => 'أريد تغيير الدومين',
            'ticket_status' => 'agent_replied',
            'priority' => 'low',
        ]);

        $conversation = Conversation::create([
            'conversable_type' => Ticket::class,
            'conversable_id' => $ticket->id,
            'type' => 'support_ticket',
            'status' => 'open',
        ]);

        $conversation->participants()->create(['user_id' => $this->client->id, 'role' => 'client']);
        $conversation->participants()->create(['user_id' => $this->moderator->id, 'role' => 'admin']);

        $response = $this->actingAs($this->client)->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'الدومين الجديد هو mydomain.com',
        ]);

        $response->assertStatus(200);

        // Verify assigned employee received email
        Mail::assertQueued(AdminTicketRepliedMail::class, function ($mail) {
            return $mail->hasTo('moderator@musoftwares.com')
                && str_contains($mail->ticketMessage->body, 'mydomain.com');
        });

        // Verify FCM dispatched to assigned employee
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('moderator_fcm_token_789', $job->tokens);
        });

        // Ticket status should update to user_replied
        $ticket->refresh();
        $this->assertEquals('user_replied', $ticket->ticket_status);
    }

    public function test_ticket_status_change_dispatches_email_and_fcm_to_client(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->client->deviceTokens()->create(['token' => 'client_fcm_token_123']);

        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'مشكلة في البريد',
            'ticket_message' => 'تفاصيل المشكلة',
            'ticket_status' => 'open',
            'priority' => 'medium',
        ]);

        // Close ticket via Admin
        $response = $this->actingAs($this->admin)->put(route('admin.tickets.update', $ticket->id), [
            'action' => 'close',
            'comment' => 'تم حل المشكلة وتجربة الإرسال بنجاح.',
        ]);

        $response->assertRedirect();

        Mail::assertQueued(TicketStatusUpdatedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com')
                && $mail->statusArabic === 'مغلقة';
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('client_fcm_token_123', $job->tokens);
        });
    }

    public function test_ticket_assignment_dispatches_email_and_fcm_to_agent(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->moderator->deviceTokens()->create(['token' => 'moderator_fcm_token_789']);

        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'تثبيت شهادة SSL',
            'ticket_message' => 'يرجى التثبيت بأسرع وقت',
            'ticket_status' => 'open',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.assign', $ticket->id), [
            'assigned_employee_id' => $this->moderator->id,
        ]);

        $response->assertRedirect();

        Mail::assertQueued(TicketAssignedMail::class, function ($mail) {
            return $mail->hasTo('moderator@musoftwares.com')
                && $mail->assignedAgent->id === $this->moderator->id;
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('moderator_fcm_token_789', $job->tokens);
        });
    }

    public function test_ticket_pricing_dispatches_email_and_fcm_to_client(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->client->deviceTokens()->create(['token' => 'client_fcm_token_123']);

        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'برمجة ميزة مخصصة',
            'ticket_message' => 'تفاصيل الميزة',
            'ticket_status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.pricing', $ticket->id), [
            'price' => 1200.00,
            'pricing_notes' => 'يشمل التطوير والاختبار وربط الـ API.',
        ]);

        $response->assertRedirect();

        Mail::assertQueued(ClientTicketPricedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com');
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('client_fcm_token_123', $job->tokens);
        });
    }

    public function test_guest_ticket_creation_dispatches_fcm_and_email_to_admins(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);

        $this->admin->deviceTokens()->create(['token' => 'admin_fcm_token_456']);

        $response = $this->post(route('guest-tickets.submit'), [
            'name' => 'زائر تجريبي',
            'email' => 'guest@example.com',
            'mobile' => '+201000000000',
            'body' => 'استفسار بخصوص خدمات البرمجة',
            'subject' => 'استفسار عام',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('guest_tickets', ['email' => 'guest@example.com']);

        Mail::assertQueued(AdminGuestTicketCreatedMail::class, function ($mail) {
            return $mail->hasTo('admin@musoftwares.com');
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) {
            return in_array('admin_fcm_token_456', $job->tokens);
        });
    }
}
