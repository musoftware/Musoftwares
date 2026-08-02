<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\AI\AiProjectOrchestratorService;
use App\Services\AI\AiToolRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AiProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $projectId,
        public readonly string $messageBody,
        public readonly int $authorId,
    ) {}

    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (!$project || !$project->ai_enabled) {
            return;
        }

        $orchestrator = new AiProjectOrchestratorService(new AiToolRegistry());
        $orchestrator->processClientMessage($project, $this->messageBody, $this->authorId);
    }
}
