<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AdminSettings;
use App\Services\Privacy\ConsentAuditService;

class PrivacySettings
{
    /**
     * Legal controller identity, DPO contact, cross-border hosting, and retention configuration.
     *
     * @return array<string, mixed>
     */
    public static function legalInfo(): array
    {
        $hasSettings = class_exists(AdminSettings::class);

        return [
            'legal_company_name' => $hasSettings
                ? (string) AdminSettings::GetValue('legal_company_name', 'Musoftware Studio — شركة موسوفت ويرز لتطوير البرمجيات والأنظمة الرقمية')
                : 'Musoftware Studio — شركة موسوفت ويرز لتطوير البرمجيات والأنظمة الرقمية',
            'legal_commercial_registry' => $hasSettings
                ? (string) AdminSettings::GetValue('legal_commercial_registry', 'مسجل بالسجل التجاري والبطاقة الضريبية بجمهورية مصر العربية — محافظة السويس')
                : 'مسجل بالسجل التجاري والبطاقة الضريبية بجمهورية مصر العربية — محافظة السويس',
            'legal_headquarters_address' => $hasSettings
                ? (string) AdminSettings::GetValue('legal_headquarters_address', AdminSettings::GetValue('business_address', 'محافظة السويس، جمهورية مصر العربية'))
                : 'محافظة السويس، جمهورية مصر العربية',
            'dpo_email' => $hasSettings
                ? (string) AdminSettings::GetValue('dpo_email', AdminSettings::GetValue('business_email', 'admin@musoftwares.com'))
                : 'admin@musoftwares.com',
            'dpo_phone' => $hasSettings
                ? (string) AdminSettings::GetValue('dpo_phone', AdminSettings::GetValue('business_phone', '+20 101 521 8548'))
                : '+20 101 521 8548',
            'hosting_regions_disclosure' => $hasSettings
                ? (string) AdminSettings::GetValue('hosting_regions_disclosure', 'خوادم سحابية مؤمّنة خارج جمهورية مصر العربية (أوروبا والولايات المتحدة الأمريكية — Hetzner / AWS / Cloudflare)')
                : 'خوادم سحابية مؤمّنة خارج جمهورية مصر العربية (أوروبا والولايات المتحدة الأمريكية — Hetzner / AWS / Cloudflare)',
            'privacy_policy_version' => $hasSettings
                ? (string) AdminSettings::GetValue('privacy_policy_version', ConsentAuditService::DEFAULT_POLICY_VERSION)
                : ConsentAuditService::DEFAULT_POLICY_VERSION,
            'data_retention_unverified_days' => $hasSettings
                ? (int) AdminSettings::GetValue('data_retention_unverified_days', 30)
                : 30,
            'data_retention_inactive_months' => $hasSettings
                ? (int) AdminSettings::GetValue('data_retention_inactive_months', 24)
                : 24,
            'encrypted_backups_enabled' => $hasSettings
                ? AdminSettings::GetValue('encrypted_backups_enabled', '1') === '1'
                : true,
        ];
    }

    /**
     * Tracking script IDs — NEVER injected automatically; only passed as config for lazy loading after explicit consent.
     *
     * @return array<string, string|null>
     */
    public static function trackingConfig(): array
    {
        if (! class_exists(AdminSettings::class)) {
            return [
                'ga_id' => null,
                'gtm_id' => null,
                'meta_pixel_id' => null,
                'custom_head_scripts' => null,
                'custom_body_scripts' => null,
            ];
        }

        return [
            'ga_id' => AdminSettings::GetValue('google_analytics_id') ?: null,
            'gtm_id' => AdminSettings::GetValue('google_tag_manager_id') ?: null,
            'meta_pixel_id' => AdminSettings::GetValue('meta_pixel_id') ?: null,
            'custom_head_scripts' => AdminSettings::GetValue('custom_head_scripts') ?: null,
            'custom_body_scripts' => AdminSettings::GetValue('custom_body_scripts') ?: null,
        ];
    }
}

