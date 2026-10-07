<?php

namespace App\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use RuntimeException;

class PricingService extends BaseService
{
    /**
     * Currency of every price in config('saas.modules') and getServiceItems() (when no converter is given).
     */
    public const BASE_CURRENCY_CODE = 'EGP';

    /**
     * Convert a base (EGP) price into another currency at today's rate.
     *
     * @throws RuntimeException when the base currency row is missing
     */
    public function convertBasePrice(float $basePrice, int $toCurrencyId): float
    {
        $baseCurrency = Currency::resolve(self::BASE_CURRENCY_CODE);
        if (! $baseCurrency) {
            throw new RuntimeException('Pricing base currency '.self::BASE_CURRENCY_CODE.' is missing from the currencies table.');
        }

        return (float) CurrenciesExchange::RateToday($basePrice, $baseCurrency->id, $toCurrencyId);
    }

    /**
     * Get all modules, addons, and tools configured for pricing,
     * converted to a uniform ServiceItem format.
     *
     * @param  callable|null  $convertPrice
     * @return array
     */
    public function getServiceItems($convertPrice = null)
    {
        // Default converter if none provided (returns raw EGP)
        if (! $convertPrice) {
            $convertPrice = function ($price) {
                return $price;
            };
        }

        $items = [];
        $basePricesEGP = config('saas.modules', []);

        // Helper to find the correct icon
        $getIcon = function ($slug) {
            $map = [
                'erp' => 'Building2',
                'crm' => 'MessageSquare',
                'marketing' => 'Zap',
                'pos' => 'Store',
                'maintenance' => 'Wrench',
            ];

            return $map[$slug] ?? null;
        };

        $moduleMetadata = [
            'erp' => ['name' => 'ERP', 'description' => 'Enterprise Resource Planning system for full business management.', 'icon' => 'Building2'],
            'crm' => ['name' => 'CRM', 'description' => 'Customer Relationship Management for leads and tickets.', 'icon' => 'MessageSquare'],
            'sms-payment-gateway' => ['name' => 'SMS Payment Gateway', 'description' => 'Automated SMS marketing and gateway integration.', 'icon' => 'Zap'],
            'gold-saver' => ['name' => 'Gold Saver', 'description' => 'Gold savings and investment tracking system.', 'icon' => 'Sparkles'],
            'booking' => ['name' => 'Booking', 'description' => 'Appointment and reservation booking engine.', 'icon' => 'Check'],
            'affiliate-pos' => ['name' => 'Affiliate & POS', 'description' => 'Online Storefront and Point of Sale system.', 'icon' => 'Store'],
        ];

        // 1. Core Modules
        foreach ($basePricesEGP as $slug => $price) {
            if ($slug === 'tool') {
                continue;
            } // Handled separately
            $monthly = $price / 10;
            $meta = $moduleMetadata[$slug] ?? ['name' => ucfirst(str_replace('-', ' ', $slug)), 'description' => '', 'icon' => 'Layers'];

            $items[] = [
                'id' => $slug,
                'slug' => $slug,
                'name' => $meta['name'],
                'type' => 'module',
                'description' => $meta['description'],
                'monthly_price' => $convertPrice($monthly),
                'yearly_price' => $convertPrice($price),
                'icon' => $meta['icon'],
            ];
        }

        // 2. Addons — Gold Saver is sold as a single bundle with every feature included,
        // so any stale gold-* entries in the config (from before the removal) are skipped here.
        $addonsConfig = config('saas.addons', []);
        foreach ($addonsConfig as $id => $configItem) {
            if (str_starts_with((string) $id, 'gold-')) {
                continue;
            }
            $monthly = $configItem['price'] / 10;
            $items[] = [
                'id' => $id,
                'slug' => $id,
                'parent_id' => $configItem['parent'],
                'name' => $configItem['name'],
                'type' => 'addon',
                'description' => $configItem['desc'],
                'monthly_price' => $convertPrice($monthly),
                'yearly_price' => $convertPrice($configItem['price']),
                'icon' => 'Sparkles',
            ];
        }

        // 3. Tools
        $configTools = config('tools', []);
        $toolBasePrice = $basePricesEGP['tool'] ?? 1000;
        foreach ($configTools as $guid => $tool) {
            if (! isset($tool['is_active']) || ! $tool['is_active']) {
                continue;
            }

            $isFree = $tool['is_free'] ?? false;

            $monthlyPriceEGP = $toolBasePrice / 10;
            $yearlyPriceEGP = $toolBasePrice;

            if (isset($tool['plans']) && is_array($tool['plans']) && count($tool['plans']) > 0) {
                $firstPlan = reset($tool['plans']);
                if (isset($firstPlan['price_monthly'])) {
                    $monthlyPriceEGP = $firstPlan['price_monthly'];
                }
                if (isset($firstPlan['price_yearly'])) {
                    $yearlyPriceEGP = $firstPlan['price_yearly'];
                }
            }

            $items[] = [
                'id' => 'tool-'.$guid,
                'slug' => $tool['slug'] ?? $guid,
                'name' => $tool['title'] ?? 'Unknown Tool',
                'type' => 'tool',
                'description' => null,
                'monthly_price' => $isFree ? 0 : $convertPrice($monthlyPriceEGP),
                'yearly_price' => $isFree ? 0 : $convertPrice($yearlyPriceEGP),
                'icon' => 'Wrench',
            ];
        }

        return $items;
    }
}
