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

    /**
     * Tools exposed per pipeline stage.
     * Only the tools a stage actually needs are sent to the LLM,
     * reducing token count and hallucination surface.
     */
    private const STAGE_TOOLS = [
        'GREETING'  => [
            'search_conversation_history',
        ],
        'DISCOVERY' => [
            'update_context',
            'ask_customer_questions',
            'search_conversation_history',
        ],
        'VALUATION' => [
            'update_context',
            'search_conversation_history',
        ],
        'PROPOSAL'  => [
            'update_context',
            'create_contract',
            'create_invoice',
            'search_conversation_history',
        ],
        'EXECUTION' => [
            'update_context',
            'create_todos',
            'create_milestones',
            'flag_admin_intervention',
            'send_notifications',
            'search_conversation_history',
        ],
        'COMPLETED' => [
            'search_conversation_history',
        ],
    ];

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
     * Return only the tools appropriate for the given pipeline stage.
     * Falls back to all tools if the stage is unknown.
     *
     * @return array<string, AiToolInterface>
     */
    public function toolsForStage(string $stage): array
    {
        $stage    = strtoupper(trim($stage));
        $allowed  = self::STAGE_TOOLS[$stage] ?? null;

        if ($allowed === null) {
            return $this->tools;
        }

        return array_filter(
            $this->tools,
            fn (AiToolInterface $tool) => in_array($tool->name(), $allowed, true)
        );
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
