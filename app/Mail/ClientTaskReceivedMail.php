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

class ClientTaskReceivedMail extends Mailable implements ShouldQueue
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
        $projectName = $this->project ? ($this->project->project_name ?? $this->project->name) : 'مشروعك';

        return new Envelope(
            subject: "تم استلام مهمتك بنجاح: {$this->taskTitle} — مشروع {$projectName}",
        );
    }

    public function content(): Content
    {
        $clientName = $this->client->name ?? 'عميلنا العزيز';
        $projectName = $this->project ? ($this->project->project_name ?? $this->project->name) : null;
        $createdAtCairo = Carbon::now('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $projectUrl = $this->project ? "{$baseUrl}/client/projects/{$this->project->id}" : "{$baseUrl}/client/projects";

        return new Content(
            view: 'emails.tasks.created_client',
            with: [
                'taskTitle' => $this->taskTitle,
                'taskDescription' => $this->taskDescription,
                'clientName' => $clientName,
                'projectName' => $projectName,
                'priority' => $this->priority ? ucfirst($this->priority) : 'Normal',
                'dueDate' => $this->dueDate,
                'createdAtCairo' => $createdAtCairo,
                'projectUrl' => $projectUrl,
            ],
        );
    }
}
