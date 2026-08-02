<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectComment;
use App\Services\AI\ClientProjectAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessClientMessageWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;
    public ProjectComment $comment;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, ProjectComment $comment)
    {
        $this->project = $project;
        $this->comment = $comment;
    }

    /**
     * Execute the job.
     */
    public function handle(ClientProjectAgentService $agentService): void
    {
        $agentService->processMessage($this->project, $this->comment);
    }
}
