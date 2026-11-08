<?php

namespace App\Services\AiAssistant;

/** The language model behind the site assistant. Kept as a port so a paid API is not called in tests. */
interface AssistantModel
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array{text: string, tool_calls: list<array{id: string, name: string}>, content: mixed}
     */
    public function reply(string $system, array $messages, array $tools): array;
}
