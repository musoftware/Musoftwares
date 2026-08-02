<?php

namespace App\Services\AI;

use App\Services\AI\Tools\AiToolInterface;
use App\Services\AI\Tools\AskCustomerQuestionsTool;
use App\Services\AI\Tools\CreateFeatureRequirementsTool;
use App\Services\AI\Tools\CreateMilestonesTool;
use App\Services\AI\Tools\CreateTodosTool;
use App\Services\AI\Tools\DetectConflictsTool;
use App\Services\AI\Tools\ExtractImportantFilesTool;
use App\Services\AI\Tools\FlagAdminInterventionTool;
use App\Services\AI\Tools\GenerateDailyReportTool;
use App\Services\AI\Tools\RemoveFeatureRequirementsTool;
use App\Services\AI\Tools\SendNotificationsTool;
use App\Services\AI\Tools\SummarizeDiscussionTool;
use App\Services\AI\Tools\UpdateDocumentationTool;
use App\Services\AI\Tools\UpdatePrioritiesTool;
use App\Services\AI\Tools\UpdateProjectDetailsTool;

use App\Services\AI\Tools\UpdateContextTool;
use App\Services\AI\Tools\CreateInvoiceTool;
use App\Services\AI\Tools\CreateContractTool;
use App\Services\AI\Tools\SearchConversationHistoryTool;

class AiToolRegistry
{
    /** @var array<string, AiToolInterface> */
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    protected function registerDefaultTools(): void
    {
        $defaultTools = [
            new UpdateContextTool(),
            new CreateContractTool(),
            new CreateInvoiceTool(),
            new UpdateProjectDetailsTool(),
            new CreateFeatureRequirementsTool(),
            new RemoveFeatureRequirementsTool(),
            new UpdatePrioritiesTool(),
            new CreateTodosTool(),
            new CreateMilestonesTool(),
            new AskCustomerQuestionsTool(),
            new SummarizeDiscussionTool(),
            new DetectConflictsTool(),
            new ExtractImportantFilesTool(),
            new UpdateDocumentationTool(),
            new SendNotificationsTool(),
            new GenerateDailyReportTool(),
            new FlagAdminInterventionTool(),
            new SearchConversationHistoryTool(),
        ];

        foreach ($defaultTools as $tool) {
            $this->registerTool($tool);
        }
    }

    public function registerTool(AiToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function getTool(string $name): ?AiToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return array<string, AiToolInterface>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Get array of tool parameter schemas suitable for LLM tool calling.
     */
    public function getSchemas(): array
    {
        $schemas = [];
        foreach ($this->tools as $tool) {
            $schemas[] = [
                'name'        => $tool->name(),
                'description' => $tool->description(),
                'parameters'  => $tool->parameters(),
            ];
        }
        return $schemas;
    }
}
