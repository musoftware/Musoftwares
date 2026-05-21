<?php

namespace App\Helpers;

use App\Services\PromptGenerator\PromptBuilderService;
use App\Services\PromptGenerator\CategoryConfigService;

class PromptGeneratorHelper
{
    /**
     * Build unified prompt for AI generation based on type and parameters
     * 
     * @param string $type The generation type (e.g., 'from_template', 'multi_prompt_planner')
     * @param array $params The parameters for the generation
     * @return array The formatted prompt ready for the AI provider
     */
    public static function buildPromptForAI(string $type, array $params): array
    {
        return app(PromptBuilderService::class)->buildPromptForAI($type, $params);
    }

    /**
     * Extract JSON from AI response safely
     * 
     * @param string $text The raw text response from AI
     * @return array|null The parsed JSON array or null if parsing fails
     */
    public static function extractJsonSafely(string $text): ?array
    {
        return app(PromptBuilderService::class)->extractJsonSafely($text);
    }

    /**
     * Get category configuration
     * 
     * @return array The category configuration array
     */
    public static function getCategoryConfig(): array
    {
        return app(CategoryConfigService::class)->getCategoryConfig();
    }
}
