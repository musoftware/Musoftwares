<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AdminSettings;
use App\Services\Privacy\ConsentAuditService;

class PrivacySettings
{
    private const DEFAULT_COMPANY_NAME = 'Musoftware Studio — شركة موسوفت ويرز لتطوير البرمجيات والأنظمة الرقمية';

    private const DEFAULT_REGISTRY = 'مسجل بالسجل التجاري والبطاقة الضريبية بجمهورية مصر العربية — محافظة السويس';

    private const DEFAULT_HOSTING = 'خوادم سحابية مؤمّنة خارج جمهورية مصر العربية (أوروبا والولايات المتحدة الأمريكية — Hetzner / AWS / Cloudflare)';

    /**
     * Legal controller identity, DPO contact, cross-border hosting, and retention configuration.
     *
     * @return array<string, mixed>
     */
    public static function legalInfo(): array
    {
        $settings = AdminSettings::GetValues([
            'legal_company_name' => self::DEFAULT_COMPANY_NAME,
            'legal_commercial_registry' => self::DEFAULT_REGISTRY,
            'legal_headquarters_address' => null,
            'business_address' => 'محافظة السويس، جمهورية مصر العربية',
            'dpo_email' => null,
            'business_email' => 'admin@musoftwares.com',
            'dpo_phone' => null,
            'business_phone' => '+20 101 521 8548',
            'hosting_regions_disclosure' => self::DEFAULT_HOSTING,
            'privacy_policy_version' => ConsentAuditService::DEFAULT_POLICY_VERSION,
            'data_retention_unverified_days' => 30,
            'data_retention_inactive_months' => 24,
            'encrypted_backups_enabled' => '1',
        ]);

        return [
            'legal_company_name' => (string) $settings['legal_company_name'],
            'legal_commercial_registry' => (string) $settings['legal_commercial_registry'],
            'legal_headquarters_address' => (string) ($settings['legal_headquarters_address'] ?? $settings['business_address']),
            'dpo_email' => (string) ($settings['dpo_email'] ?? $settings['business_email']),
            'dpo_phone' => (string) ($settings['dpo_phone'] ?? $settings['business_phone']),
            'hosting_regions_disclosure' => (string) $settings['hosting_regions_disclosure'],
            'privacy_policy_version' => (string) $settings['privacy_policy_version'],
            'data_retention_unverified_days' => (int) $settings['data_retention_unverified_days'],
            'data_retention_inactive_months' => (int) $settings['data_retention_inactive_months'],
            'encrypted_backups_enabled' => $settings['encrypted_backups_enabled'] === '1',
        ];
    }

    /**
     * Tracking script IDs — NEVER injected automatically; only passed as config for lazy loading after explicit consent.
     *
     * @return array<string, string|null>
     */
    public static function trackingConfig(): array
    {
        $settings = AdminSettings::GetValues([
            'google_analytics_id' => null,
            'google_tag_manager_id' => null,
            'meta_pixel_id' => null,
            'custom_head_scripts' => null,
            'custom_body_scripts' => null,
        ]);

        return [
            'ga_id' => $settings['google_analytics_id'] ?: null,
            'gtm_id' => $settings['google_tag_manager_id'] ?: null,
            'meta_pixel_id' => $settings['meta_pixel_id'] ?: null,
            'custom_head_scripts' => $settings['custom_head_scripts'] ?: null,
            'custom_body_scripts' => $settings['custom_body_scripts'] ?: null,
        ];
    }
}
