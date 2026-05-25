<?php

namespace Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\OnlinePage\Models\PublicPage;

class PublicPageSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!feature('booking.online_page')) {
                return response()->json(['message' => 'Feature locked. Upgrade to unlock Online Booking Pages.'], 403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $page = PublicPage::with('theme')->firstOrCreate(
            ['tenant_id' => auth()->user()->tenant_id],
            [
                'slug' => \Illuminate\Support\Str::slug('book-now-' . auth()->user()->tenant_id),
                'title' => 'Book Your Appointment',
                'is_active' => true,
            ]
        );

        return response()->json($page);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'string|unique:booking_public_pages,slug,' . auth()->user()->tenant_id . ',tenant_id',
            'title' => 'string',
            'description' => 'string|nullable',
            'is_active' => 'boolean',
        ]);

        $page = PublicPage::where('tenant_id', auth()->user()->tenant_id)->firstOrFail();
        $page->update($validated);

        return response()->json($page);
    }
}
