<?php

namespace App\Services\Admin;

use App\Models\AdminSettings;
use App\Models\Ticket;
use App\Models\UserReferralRequestWithdraw;
use Illuminate\Support\Facades\Artisan;
use Modules\Marketplace\Models\Service as MarketplaceService;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * The steps that still need the admin, for the admin Setup page.
 *
 * Automatic items read the real state of the site. Manual items happen outside the site
 * (Google, Bing), so the admin ticks them and the tick is kept in admin settings.
 */
class SetupChecklist
{
    /** key => [group, link, check method or null for a manual item] */
    private const ITEMS = [
        'migrations' => ['site', '/admin/system/migrations', 'pendingMigrations'],
        'analytics' => ['site', '/admin/settings', 'missingAnalytics'],
        'payments' => ['site', null, 'testPaymentKeys'],
        'mail' => ['site', null, 'mailNotSending'],
        'product_screenshots' => ['site', '/admin/software-products', 'productsWithoutScreenshots'],
        'search_console_sitemap' => ['seo', 'https://search.google.com/search-console', null],
        'search_console_reindex' => ['seo', 'https://search.google.com/search-console', null],
        'bing_webmaster' => ['seo', 'https://www.bing.com/webmasters', null],
        'google_business_profile' => ['seo', 'https://business.google.com', null],
        'open_tickets' => ['queue', '/admin/tickets', 'openTickets'],
        'pending_services' => ['queue', '/admin/marketplace/pending-services', 'pendingServices'],
        'withdrawals' => ['queue', '/admin/withdraw-requests?status=pending', 'pendingWithdrawals'],
    ];

    private const GROUPS = ['site', 'seo', 'queue'];

    /** @return list<array{key: string, title: string, items: list<array<string, mixed>>}> */
    public function groups(): array
    {
        $items = array_map(fn (string $key) => $this->item($key), array_keys(self::ITEMS));

        return array_map(fn (string $group) => [
            'key' => $group,
            'title' => __("setup.group_{$group}"),
            'items' => array_values(array_filter($items, fn (array $item) => $item['group'] === $group)),
        ], self::GROUPS);
    }

    public function isManual(string $key): bool
    {
        return isset(self::ITEMS[$key]) && self::ITEMS[$key][2] === null;
    }

    public function markManual(string $key, bool $done): void
    {
        AdminSettings::SetValue($this->settingKey($key), $done ? '1' : '0');
    }

    /** @return array<string, mixed> */
    private function item(string $key): array
    {
        [$group, $url, $check] = self::ITEMS[$key];
        $problem = $check === null ? $this->manualProblem($key) : $this->{$check}();

        return [
            'key' => $key,
            'group' => $group,
            'title' => __("setup.{$key}"),
            'note' => $problem ?? ($check === null ? $this->note($key) : null),
            'url' => $url,
            'manual' => $check === null,
            'done' => $problem === null,
        ];
    }

    private function manualProblem(string $key): ?string
    {
        return AdminSettings::GetValue($this->settingKey($key)) === '1' ? null : $this->note($key);
    }

    private function note(string $key): string
    {
        return __("setup.{$key}_note", ['url' => route('sitemap')]);
    }

    private function settingKey(string $key): string
    {
        return "setup_done_{$key}";
    }

    private function pendingMigrations(): ?string
    {
        Artisan::call('migrate:status', ['--pending' => true]);
        $count = substr_count(Artisan::output(), 'Pending');

        return $count > 0 ? __('setup.migrations_note', ['count' => $count]) : null;
    }

    private function missingAnalytics(): ?string
    {
        $hasTracking = AdminSettings::GetValue('google_analytics_id') || AdminSettings::GetValue('google_tag_manager_id');

        return $hasTracking ? null : __('setup.analytics_note');
    }

    private function testPaymentKeys(): ?string
    {
        $isTest = config('services.kashier.merchant_id') === 'MID-12345' || config('services.kashier.secret_key') === 'secret';

        return $isTest ? __('setup.payments_note') : null;
    }

    private function mailNotSending(): ?string
    {
        $mailer = (string) config('mail.default');

        return in_array($mailer, ['log', 'array'], true) ? __('setup.mail_note', ['mailer' => $mailer]) : null;
    }

    private function productsWithoutScreenshots(): ?string
    {
        $names = SoftwareProduct::where('is_active', true)->get()
            ->filter(fn (SoftwareProduct $product) => $product->galleryPaths() === [])
            ->map(fn (SoftwareProduct $product) => $product->localized('name'));

        return $names->isEmpty() ? null : __('setup.product_screenshots_note', ['names' => $names->implode(app()->getLocale() === 'ar' ? '، ' : ', ')]);
    }

    private function openTickets(): ?string
    {
        return $this->countNote('open_tickets', Ticket::whereIn('ticket_status', ['open', 'in_progress', 'agent_replied'])->count());
    }

    private function pendingServices(): ?string
    {
        return $this->countNote('pending_services', MarketplaceService::where('status', 'draft')->count());
    }

    private function pendingWithdrawals(): ?string
    {
        return $this->countNote('withdrawals', UserReferralRequestWithdraw::where('status', 'pending')->count());
    }

    private function countNote(string $key, int $count): ?string
    {
        return $count > 0 ? __("setup.{$key}_note", ['count' => $count]) : null;
    }
}
