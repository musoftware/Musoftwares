<?php

namespace Modules\AppBuilder\Services\Study;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use RuntimeException;

/** Asks the strong model for the study as a small, safe HTML fragment. */
class ClaudeStudyWriter implements StudyWriter
{
    private const MAX_OUTPUT_TOKENS = 16000;

    public function write(string $system, string $brief): array
    {
        try {
            $message = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
                model: config('builder.study.model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                system: [['type' => 'text', 'text' => $system]],
                messages: [['role' => 'user', 'content' => $brief]],
                outputConfig: ['effort' => config('builder.study.effort')],
            );
        } catch (APIException $exception) {
            throw new RuntimeException('Study not written: '.$exception->getMessage(), previous: $exception);
        }

        if ($message->stopReason !== 'end_turn') {
            throw new RuntimeException('Study stopped early: '.$message->stopReason);
        }

        $html = '';
        foreach ($message->content as $block) {
            $html .= $block->type === 'text' ? $block->text : '';
        }

        return ['html' => $html, 'input_tokens' => (int) $message->usage->inputTokens, 'output_tokens' => (int) $message->usage->outputTokens];
    }
}
