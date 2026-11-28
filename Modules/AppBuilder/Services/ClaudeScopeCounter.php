<?php

namespace Modules\AppBuilder\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use RuntimeException;

/** The chat model counts the pages and picks the modules, with the same rules the sales chat follows when it quotes. */
class ClaudeScopeCounter implements ScopeCounter
{
    private const MAX_OUTPUT_TOKENS = 800;

    private const SYSTEM = <<<'TEXT'
        You price a software build brief with Musoftware's estimator. Answer with two things.

        - pages: how many pages or screens the customer side has (visitors, customers, users, family, professionals:
          everyone except the admin). Count each real screen once: a list and its detail page are two, a form wizard is
          one, login and register are two. Never count the admin panel's pages: the admin panel is one module.
        - modules: the parts from the price list below that this program really needs: the admin panel once, however
          many pages it has; roles and permissions; a second language; payments; notifications; and so on. Pick only what
          the brief really includes; never a part the brief puts off to a later version.

        When the brief starts from a ready program ("Starts from the ready program ..."), count only the pages and
        modules that are new or changed on top of it.
        TEXT;

    public function __construct(private readonly EstimatorPrice $estimator) {}

    public function count(string $projectType, string $spec): array
    {
        $text = $this->ask("Project type: {$projectType}\n\nBrief:\n{$spec}");
        $scope = json_decode($text, true);
        if (! is_array($scope) || ! isset($scope['pages'], $scope['modules'])) {
            throw new RuntimeException('Scope not counted: the answer was not a scope.');
        }

        return ['pages' => max(0, (int) $scope['pages']), 'modules' => array_values(array_filter((array) $scope['modules'], 'is_string'))];
    }

    private function ask(string $brief): string
    {
        try {
            $message = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                model: config('builder.chat_model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                system: [['type' => 'text', 'text' => self::SYSTEM."\n\nPrice list modules:\n".$this->estimator->forPrompt()]],
                messages: [['role' => 'user', 'content' => $brief]],
                outputConfig: ['effort' => config('builder.chat_effort'), 'format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            );
        } catch (APIException $exception) {
            throw new RuntimeException('Scope not counted: '.$exception->getMessage(), previous: $exception);
        }

        $text = '';
        foreach ($message->content as $block) {
            $text .= $block->type === 'text' ? $block->text : '';
        }

        return $text;
    }

    private function schema(): array
    {
        $keys = $this->estimator->moduleKeys();

        return [
            'type' => 'object',
            'properties' => [
                'pages' => ['type' => 'integer'],
                'modules' => ['type' => 'array', 'items' => $keys === [] ? ['type' => 'string'] : ['type' => 'string', 'enum' => $keys]],
            ],
            'required' => ['pages', 'modules'],
            'additionalProperties' => false,
        ];
    }
}
