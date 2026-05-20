<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;
use Modules\Booking\Http\Controllers\BookingEventController;

// Authenticated Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:booking'])->prefix('booking')->name('booking.')->group(function () {

    // ── Event Types (Booking types the host creates) ─────────────────
    Route::get('/', [BookingEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [BookingEventController::class, 'create'])->name('events.create');
    Route::post('/events', [BookingEventController::class, 'store'])->name('events.store');
    Route::get('/events/{slug}/edit', [BookingEventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{slug}', [BookingEventController::class, 'update'])->name('events.update');

    // ── Appointments (Bookings made by visitors) ──────────────────────
    Route::get('/appointments', [BookingController::class, 'appointments'])->name('appointments');
    Route::post('/appointments/{id}/status', [BookingController::class, 'updateStatus'])->name('appointments.status');
    Route::post('/appointments/{id}/notes', [BookingController::class, 'updateNotes'])->name('appointments.notes');
    Route::post('/appointments/{id}/create-project', [BookingController::class, 'createProject'])->name('appointments.create-project');
    Route::post('/appointments/{id}/create-invoice', [BookingController::class, 'createInvoice'])->name('appointments.create-invoice');
});

// Public Routes
Route::get('/book/{username}/{slug}', [BookingController::class, 'showPublic'])->name('booking.public.show');
Route::post('/book/store', [BookingController::class, 'store'])->name('booking.public.store');

Route::get('/booking/checkout/{id}', [BookingController::class, 'checkout'])->name('booking.checkout');
Route::post('/booking/checkout/{id}/wallet', [BookingController::class, 'payWithWallet'])->name('booking.pay.wallet');
Route::post('/booking/checkout/{id}/kashier', [BookingController::class, 'payWithKashier'])->name('booking.pay.kashier');
Route::get('/booking/success/{id}', [BookingController::class, 'success'])->name('booking.success');

// Webhook
Route::post('/booking/webhook/kashier', [BookingController::class, 'kashierWebhook'])->name('booking.webhook.kashier');
