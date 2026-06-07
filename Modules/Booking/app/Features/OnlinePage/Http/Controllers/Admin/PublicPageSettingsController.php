<?php

namespace Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\OnlinePage\Models\PublicPage;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PublicPageSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!feature('booking.online_page')) {
                    return response()->json(['message' => 'Feature locked. Upgrade to unlock Online Booking Pages.'], 403);
                }
                return $next($request);
            }),
        ];
    }

    public function index()
    {
        $page = PublicPage::with('theme')->firstOrCreate(
            ['tenant_id' => (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())],
            [
                'slug' => \Illuminate\Support\Str::slug('book-now-' . (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())),
                'title' => 'Book Your Appointment',
                'is_active' => true,
            ]
        );

        return response()->json($page);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'string|unique:booking_public_pages,slug,' . (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()) . ',tenant_id',
            'title' => 'string',
            'description' => 'string|nullable',
            'is_active' => 'boolean',
        ]);

        $page = PublicPage::where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))->firstOrFail();
        $page->update($validated);

        return response()->json($page);
    }
}
