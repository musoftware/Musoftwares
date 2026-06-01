<?php

namespace Modules\Booking\app\Features\PublicBooking\Services;

use Modules\Booking\app\Features\PublicBooking\Repositories\BookingPageRepository;
use Illuminate\Support\Str;

class BookingPageService
{
    protected $repository;

    public function __construct(BookingPageRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Generate a unique slug across all tenants.
     */
    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->repository->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Retrieve the public booking page configuration if it's active.
     */
    public function getPublicPageData(string $slug)
    {
        $settings = $this->repository->findBySlug($slug);

        if (!$settings) {
            abort(404, __('general.booking_page_not_found'));
        }

        if (!$settings->is_active) {
            abort(403, __('general.this_booking_page_is_currently_inactive'));
        }

        return [
            'tenant_id' => $settings->tenant_id,
            'slug' => $settings->slug,
            'title' => $settings->title,
            'description' => $settings->description,
            'primary_color' => $settings->primary_color,
            'logo_path' => $settings->logo_path ? asset('storage/' . $settings->logo_path) : null,
            // You can load relationships here, e.g., the public services for this tenant
        ];
    }
}
