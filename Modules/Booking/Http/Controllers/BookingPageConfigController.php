<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\BookingPageConfig;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BookingPageConfigController extends Controller
{
    public function __construct()
    {
        // Enforce the SaaS feature flag
        $this->middleware('feature:booking-online-page');
    }

    public function edit()
    {
        $tenantId = Auth::user()->tenant_id;
        
        $config = BookingPageConfig::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'slug' => Str::slug(Auth::user()->name . '-' . rand(1000, 9999)),
                'page_title' => Auth::user()->name . ' Bookings',
                'primary_color' => '#000000',
            ]
        );

        return Inertia::render('Booking/PageConfig/Edit', [
            'config' => $config
        ]);
    }

    public function update(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $config = BookingPageConfig::where('tenant_id', $tenantId)->firstOrFail();

        $request->validate([
            'slug' => 'required|string|max:255|unique:booking_page_configs,slug,' . $config->id,
            'page_title' => 'nullable|string|max:255',
            'welcome_message' => 'nullable|string|max:1000',
            'primary_color' => 'required|string|max:20',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $config->update($request->only(
            'slug', 'page_title', 'welcome_message', 'primary_color', 'seo_title', 'seo_description'
        ));

        // Handling file uploads for logo and banner would go here
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/booking_pages/logos');
            $config->logo_path = str_replace('public/', 'storage/', $path);
            $config->save();
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('public/booking_pages/banners');
            $config->banner_path = str_replace('public/', 'storage/', $path);
            $config->save();
        }

        return back()->with('success', 'Booking page configuration updated successfully.');
    }
}
