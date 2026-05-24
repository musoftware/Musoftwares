<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;
use Modules\Booking\Http\Controllers\BookingEventController;
use Modules\Booking\Http\Controllers\BookingProviderController;
use Modules\Booking\Http\Controllers\BookingDashboardController;
use Modules\Booking\Http\Controllers\BookingExceptionController;
use Modules\Booking\Http\Controllers\BookingPublicApiController;

// Authenticated Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:booking'])->prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingDashboardController::class, 'index'])->name('index');

    // ── Event Types (Booking types the host creates) ─────────────────
    Route::get('/events', [BookingEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [BookingEventController::class, 'create'])->name('events.create');
    Route::post('/events', [BookingEventController::class, 'store'])->name('events.store');
    Route::get('/events/{slug}/edit', [BookingEventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{slug}', [BookingEventController::class, 'update'])->name('events.update');

    // ── Appointments (Bookings made by visitors) ──────────────────────
    Route::get('/appointments', [BookingController::class, 'appointments'])->name('appointments');
    Route::post('/appointments/{id}/status', [BookingController::class, 'updateStatus'])->name('appointments.status');
    Route::post('/appointments/{id}/reschedule', [BookingController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('/appointments/{id}/notes', [BookingController::class, 'updateNotes'])->name('appointments.notes');
    Route::post('/appointments/{id}/create-project', [BookingController::class, 'createProject'])->name('appointments.create-project');
    Route::post('/appointments/{id}/create-invoice', [BookingController::class, 'createInvoice'])->name('appointments.create-invoice');

    // ── Providers (Doctors / Staff) ──────────────────────────────────
    Route::get('/providers', [BookingProviderController::class, 'index'])->name('providers.index');
    Route::post('/providers', [BookingProviderController::class, 'store'])->name('providers.store');
    Route::put('/providers/{id}', [BookingProviderController::class, 'update'])->name('providers.update');
    Route::post('/providers/{id}/availability', [BookingProviderController::class, 'saveAvailability'])->name('providers.availability');

    // ── Exceptions / Blocked Dates ───────────────────────────────────
    Route::get('/exceptions', [BookingExceptionController::class, 'index'])->name('exceptions.index');
    Route::post('/exceptions', [BookingExceptionController::class, 'store'])->name('exceptions.store');
    Route::delete('/exceptions/{id}', [BookingExceptionController::class, 'destroy'])->name('exceptions.destroy');
});

// Public Routes
Route::get('/book/{username}/{slug}', [BookingController::class, 'showPublic'])->name('booking.public.show');
Route::post('/book/store', [BookingController::class, 'store'])->name('booking.public.store');
Route::get('/booking/api/slots', [BookingController::class, 'getSlotsApi'])->name('api.slots');

Route::get('/booking/checkout/{id}', [BookingController::class, 'checkout'])->name('booking.checkout');
Route::post('/booking/checkout/{id}/wallet', [BookingController::class, 'payWithWallet'])->name('booking.pay.wallet');
Route::post('/booking/checkout/{id}/kashier', [BookingController::class, 'payWithKashier'])->name('booking.pay.kashier');
Route::get('/booking/success/{id}', [BookingController::class, 'success'])->name('booking.success');

// Webhook
Route::post('/booking/webhook/kashier', [BookingController::class, 'kashierWebhook'])->name('booking.webhook.kashier');

// Public API Routes
Route::prefix('api/booking')->name('api.booking.')->group(function () {
    Route::get('/event-type/{id}/slots', [\Modules\Booking\Http\Controllers\BookingPublicApiController::class, 'getSlots'])->name('slots');
    Route::post('/lock', [\Modules\Booking\Http\Controllers\BookingPublicApiController::class, 'acquireLock'])->name('lock');
});
