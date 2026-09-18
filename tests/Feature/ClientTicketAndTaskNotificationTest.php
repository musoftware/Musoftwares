<?php

namespace Tests\Feature;

use App\Mail\AdminTaskCreatedMail;
use App\Mail\AdminTicketCreatedMail;
use App\Mail\ClientTaskReceivedMail;
use App\Mail\ClientTicketReceivedMail;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientTicketAndTaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@musoftwares.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->client = User::factory()->create([
            'email' => 'client@musoftwares.com',
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->client->id,
            'project_name' => 'Demo Client Project',
            'archived' => false,
        ]);
    }

    public function test_client_ticket_creation_sends_email_to_client_and_admins(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->client)->post(route('tickets.store'), [
            'subject' => 'Need help with payment setup',
            'priority' => 'High',
            'description' => 'Please connect our payment gateway credentials.',
            'project_id' => $this->project->id,
        ]);

        $response->assertRedirect();

        // 1. Client receives confirmation email
        Mail::assertQueued(ClientTicketReceivedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com')
                && $mail->ticket->ticket_subject === 'Need help with payment setup';
        });

        // 2. Admin receives notification email
        Mail::assertQueued(AdminTicketCreatedMail::class, function ($mail) {
            return $mail->hasTo('admin@musoftwares.com')
                && $mail->ticket->ticket_subject === 'Need help with payment setup';
        });
    }

    public function test_client_task_creation_sends_email_to_client_and_admins(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->client)->post(route('client.projects.board.store-task', $this->project), [
            'for_date' => '2026-09-25',
            'task_name' => 'Implement Stripe Webhook',
            'task_description' => 'Listen to invoice.paid and activate subscription',
            'priority' => 'high',
            'lane' => 'backlog',
        ]);

        $response->assertStatus(200);

        // 1. Client receives confirmation email
        Mail::assertQueued(ClientTaskReceivedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com')
                && $mail->taskTitle === 'Implement Stripe Webhook';
        });

        // 2. Admin receives notification email
        Mail::assertQueued(AdminTaskCreatedMail::class, function ($mail) {
            return $mail->hasTo('admin@musoftwares.com')
                && $mail->taskTitle === 'Implement Stripe Webhook';
        });
    }

    public function test_client_todo_creation_sends_email_to_client_and_admins(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->client)->post(route('client.projects.board.store-todo', $this->project), [
            'for_date' => '2026-09-25',
            'title' => 'Design new checkout modal',
            'description' => 'High conversion UI for mobile screens',
            'priority' => 'normal',
            'lane' => 'backlog',
        ]);

        $response->assertStatus(200);

        // 1. Client receives confirmation email
        Mail::assertQueued(ClientTaskReceivedMail::class, function ($mail) {
            return $mail->hasTo('client@musoftwares.com')
                && $mail->taskTitle === 'Design new checkout modal';
        });

        // 2. Admin receives notification email
        Mail::assertQueued(AdminTaskCreatedMail::class, function ($mail) {
            return $mail->hasTo('admin@musoftwares.com')
                && $mail->taskTitle === 'Design new checkout modal';
        });
    }

    public function test_client_ticket_creation_dispatches_fcm_to_client_and_admins(): void
    {
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\SendFcmNotificationJob::class]);

        $this->admin->deviceTokens()->create(['token' => 'fcm_admin_token_xyz']);
        $this->client->deviceTokens()->create(['token' => 'fcm_client_token_abc']);

        $response = $this->actingAs($this->client)->post(route('tickets.store'), [
            'subject' => 'Need help with payment setup',
            'priority' => 'High',
            'description' => 'Please connect our payment gateway credentials.',
            'project_id' => $this->project->id,
        ]);

        $response->assertRedirect();

        // Check FCM jobs dispatched
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendFcmNotificationJob::class, function ($job) {
            return in_array('fcm_admin_token_xyz', $job->tokens) || in_array('fcm_client_token_abc', $job->tokens);
        });
    }

    public function test_client_task_creation_dispatches_fcm_to_client_and_admins(): void
    {
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\SendFcmNotificationJob::class]);

        $this->admin->deviceTokens()->create(['token' => 'fcm_admin_token_xyz']);
        $this->client->deviceTokens()->create(['token' => 'fcm_client_token_abc']);

        $response = $this->actingAs($this->client)->post(route('client.projects.board.store-task', $this->project), [
            'for_date' => '2026-09-25',
            'task_name' => 'Implement Stripe Webhook',
            'task_description' => 'Listen to invoice.paid and activate subscription',
            'priority' => 'high',
            'lane' => 'backlog',
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendFcmNotificationJob::class, function ($job) {
            return in_array('fcm_admin_token_xyz', $job->tokens) || in_array('fcm_client_token_abc', $job->tokens);
        });
    }
}
