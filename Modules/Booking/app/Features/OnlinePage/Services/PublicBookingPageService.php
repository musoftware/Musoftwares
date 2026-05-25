<?php

namespace Modules\Booking\app\Features\OnlinePage\Services;

use Modules\Booking\app\Features\OnlinePage\Models\PublicPage;

class PublicBookingPageService
{
    /**
     * Get active page by slug, ensuring tenant is active.
     */
    public function getActivePageBySlug(string $slug)
    {
        return PublicPage::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Setup a tenant's default public page
     */
    public function setupDefaultPage(int $tenantId, string $title)
    {
        $slug = \Illuminate\Support\Str::slug($title . '-' . $tenantId);
        
        $page = PublicPage::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'slug' => $slug,
                'title' => $title,
                'is_active' => true
            ]
        );

        $page->theme()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['primary_color' => '#000000']
        );

        return $page;
    }
}
