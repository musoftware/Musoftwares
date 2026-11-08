<?php

namespace App\Services\AiAssistant;

use App\Models\User;
use App\Services\Client\ClientHomeService;
use App\Services\ProjectEstimatorDataService;

/**
 * What the assistant can look up. Every tool only reads. The account tool takes no input: it always
 * uses the signed-in user from the session, so the model can never ask for someone else's data.
 */
class AssistantTools
{
    private const NO_INPUT = ['type' => 'object'];

    private const PUBLIC_TOOLS = [
        'get_project_price_guide' => 'Price list for custom projects: the price per page or screen for each kind of project, and the price of each add-on, in Egyptian pounds. Use it to work out a rough estimate.',
    ];

    private const ACCOUNT_TOOLS = [
        'get_my_account' => 'The signed-in visitor\'s own account: how much they owe, unpaid and late invoices, wallet balance, next renewal, their projects and subscriptions, and links to pay.',
    ];

    public function __construct(
        private readonly ClientHomeService $clientHome,
        private readonly ProjectEstimatorDataService $estimator,
    ) {}

    /** @return list<array<string, mixed>> */
    public function definitions(?User $user): array
    {
        $available = $user ? self::PUBLIC_TOOLS + self::ACCOUNT_TOOLS : self::PUBLIC_TOOLS;

        return array_map(
            fn (string $name, string $description) => ['name' => $name, 'description' => $description, 'inputSchema' => self::NO_INPUT],
            array_keys($available),
            array_values($available),
        );
    }

    public function run(string $name, ?User $user): string
    {
        $result = match (true) {
            $name === 'get_project_price_guide' => $this->priceGuide(),
            $name === 'get_my_account' && $user !== null => $this->account($user),
            default => ['error' => 'This information is not available.'],
        };

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    private function priceGuide(): array
    {
        $data = $this->estimator->getEstimatorData($this->estimator->usdToEgpRate());
        $title = fn (array $row) => app()->getLocale() === 'ar' ? ($row['title_ar'] ?? $row['title']) : $row['title'];

        return [
            'project_types' => array_values(array_map(fn (array $platform) => [
                'key' => $platform['key'],
                'name' => $title($platform),
                'price_egp_per_unit' => $platform['rate_egp'],
                'unit' => app()->getLocale() === 'ar' ? $platform['unit_ar'] : $platform['unit'],
            ], $data['platforms'])),
            'add_ons_by_project_type' => array_map(
                fn (array $modules) => array_map(fn (array $module) => ['name' => $title($module), 'price_egp' => $module['price_egp']], $modules),
                $data['modules'],
            ),
        ];
    }

    /** @return array<string, mixed> only the fields a client needs, with no email, phone or card data */
    private function account(User $user): array
    {
        $home = $this->clientHome->build($user);

        return ['money' => $home['money'], 'needs_attention' => $home['attention'], 'services' => $home['services']];
    }
}
