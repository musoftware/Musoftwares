<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTaskCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $taskTitle,
        public readonly User $client,
        public readonly ?Project $project = null,
        public readonly ?string $taskDescription = null,
        public readonly ?string $priority = null,
        public readonly ?string $dueDate = null
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->client->name ?? 'العميل';

        return new Envelope(
            subject: "مهمة جديدة من العميل {$clientName}: {$this->taskTitle}",
        );
    }

    public function content(): Content
    {
        $clientName = $this->client->name ?? 'العميل';
        $clientEmail = $this->client->email ?? 'غير محدد';
        $projectName = $this->project ? ($this->project->project_name ?? $this->project->name) : null;
        $createdAtCairo = Carbon::now('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $adminTaskUrl = $this->project ? "{$baseUrl}/admin/projects/{$this->project->id}" : "{$baseUrl}/admin/tasks/pending";

        return new Content(
            view: 'emails.tasks.created_admin',
            with: [
                'taskTitle' => $this->taskTitle,
                'taskDescription' => $this->taskDescription,
                'clientName' => $clientName,
                'clientEmail' => $clientEmail,
                'projectName' => $projectName,
                'priority' => $this->priority ? ucfirst($this->priority) : 'Normal',
                'dueDate' => $this->dueDate,
                'createdAtCairo' => $createdAtCairo,
                'adminTaskUrl' => $adminTaskUrl,
            ],
        );
    }
}
