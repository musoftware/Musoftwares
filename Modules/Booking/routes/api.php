<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('bookings', BookingController::class)->names('booking');

    // WA Reminders Feature
    Route::prefix('wa-reminders')->group(function () {
        Route::apiResource('templates', \Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController::class)->names('booking.wa.templates');
        Route::get('logs', [\Modules\Booking\app\Features\Reminders\Http\Controllers\WaReminderLogController::class, 'index'])->name('booking.wa.logs.index');
        Route::get('limits', [\Modules\Booking\app\Features\Reminders\Http\Controllers\WaReminderLogController::class, 'getLimits'])->name('booking.wa.limits');
    });

    // Public Booking Page Admin Configurations
    Route::prefix('booking-page')->group(function () {
        Route::get('settings', [\Modules\Booking\app\Features\PublicBooking\Http\Controllers\BookingPageSettingsController::class, 'show'])->name('booking.page.settings.show');
        Route::post('settings', [\Modules\Booking\app\Features\PublicBooking\Http\Controllers\BookingPageSettingsController::class, 'update'])->name('booking.page.settings.update');
    });

    // Custom Domains feature
    Route::prefix('custom-domains')->group(function () {
        Route::get('/', [\Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController::class, 'index'])->name('booking.custom-domains.index');
        Route::post('/', [\Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController::class, 'store'])->name('booking.custom-domains.store');
        Route::delete('/{id}', [\Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController::class, 'destroy'])->name('booking.custom-domains.destroy');
        Route::post('/{id}/verify', [\Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController::class, 'verify'])->name('booking.custom-domains.verify');
        Route::put('/{id}/primary', [\Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController::class, 'setPrimary'])->name('booking.custom-domains.set-primary');
    });

    // Multi Branch feature
    Route::apiResource('branches', \Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController::class)->names('booking.branches');
    Route::post('branches/{branch}/staff', [\Modules\Booking\app\Features\MultiBranch\Http\Controllers\BranchStaffController::class, 'store'])->name('booking.branches.staff.store');
    Route::delete('branches/{branch}/staff/{user}', [\Modules\Booking\app\Features\MultiBranch\Http\Controllers\BranchStaffController::class, 'destroy'])->name('booking.branches.staff.destroy');

    // Team Members feature
    Route::apiResource('team-members', \Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController::class)->names('booking.team-members');

    // WhatsApp Reminders Feature
    Route::apiResource('wa-templates', \Modules\Booking\app\Features\WaReminders\Http\Controllers\WaTemplateController::class)->only(['index', 'store']);

    // Online Booking Page Admin
    Route::get('public-page/settings', [\Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin\PublicPageSettingsController::class, 'index'])->name('booking.public-page.settings.index');
    Route::put('public-page/settings', [\Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin\PublicPageSettingsController::class, 'update'])->name('booking.public-page.settings.update');

    // Google Calendar Sync
    Route::prefix('gcal')->group(function () {
        Route::get('auth/redirect', [\Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController::class, 'redirect']);
        Route::get('auth/callback', [\Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController::class, 'callback']);
        Route::get('accounts', [\Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleCalendarSettingsController::class, 'index']);
        Route::post('accounts/{account}/calendars', [\Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleCalendarSettingsController::class, 'configureCalendar']);
    });

    // Group Sessions
    Route::apiResource('group-sessions', \Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController::class);
    Route::post('group-sessions/{id}/join', [\Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController::class, 'join']);
    Route::post('group-sessions/{id}/cancel', [\Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController::class, 'cancel']);

    // Recurring Appointments
    Route::apiResource('recurring-series', \Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController::class)->only(['index', 'store']);
    Route::post('recurring-series/{id}/cancel', [\Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController::class, 'cancel']);

    // SMS Notifications
    Route::apiResource('sms-templates', \Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsTemplateController::class)->only(['index', 'store']);
    Route::apiResource('sms-settings', \Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsSettingController::class)->only(['index', 'store']);

    // Booking Widgets (Admin)
    Route::apiResource('widgets', \Modules\Booking\app\Features\Widget\Http\Controllers\BookingWidgetController::class)->only(['index', 'store']);
});

// Unauthenticated Webhooks & Public Routes
Route::post('/webhooks/whatsapp', [\Modules\Booking\app\Features\WhatsAppReminders\Http\Controllers\WaWebhookController::class, 'handleWebhook'])->name('webhook.whatsapp');

// Booking Widgets (Public CORS Guarded)
Route::group(['prefix' => 'public/widgets/{uuid}', 'middleware' => [\Modules\Booking\app\Features\Widget\Http\Middleware\ValidateWidgetDomain::class]], function () {
    Route::get('embed.js', [\Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController::class, 'embed']);
    Route::post('view', [\Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController::class, 'view']);
    Route::post('book', [\Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController::class, 'book']);
});

Route::prefix('public')->group(function () {
    Route::get('{slug}/init', [\Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController::class, 'init'])->name('public.booking.init');
    Route::get('{slug}/slots', [\Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController::class, 'slots'])->name('public.booking.slots');
    Route::post('{slug}/book', [\Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController::class, 'book'])->name('public.booking.book');
});

// Unauthenticated Public Routes
Route::prefix('v1/public/booking-page')->group(function () {
    Route::get('{slug}', [\Modules\Booking\app\Features\PublicBooking\Http\Controllers\PublicBookingPageController::class, 'show'])->name('public.booking.page.show');
});
