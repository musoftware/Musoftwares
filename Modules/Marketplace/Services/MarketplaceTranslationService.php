<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\Service;

class MarketplaceTranslationService
{
    /**
     * Store multi-language translations for a service entity.
     */
    public function setTranslations(Service $service, array $translations): Service
    {
        $service->update([
            'title_translations' => $translations['title'] ?? $service->title_translations,
            'tagline_translations' => $translations['tagline'] ?? $service->tagline_translations,
            'description_translations' => $translations['description'] ?? $service->description_translations,
            'auto_reply_translations' => $translations['auto_reply'] ?? $service->auto_reply_translations,
        ]);

        return $service;
    }

    /**
     * Get locale-specific title for service.
     */
    public function getLocalizedTitle(Service $service, string $locale = 'ar'): string
    {
        $translations = $service->title_translations ?? [];
        return $translations[$locale] ?? $service->title ?? '';
    }
}
