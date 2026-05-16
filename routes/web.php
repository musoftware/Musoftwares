<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ERP Routes
Route::middleware(['auth', 'verified'])->prefix('erp')->name('erp.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('ERP/Dashboard'); })->name('dashboard');
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');

    // Referrals
    Route::get('/referrals', [\Modules\ERP\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/tree/{client}', [\Modules\ERP\Http\Controllers\ReferralController::class, 'tree'])->name('referrals.tree');
    Route::get('/referrals/earnings', [\Modules\ERP\Http\Controllers\ReferralController::class, 'earnings'])->name('referrals.earnings');

    // Recurring
    Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);
    Route::post('/recurring/{recurring}/pause', [\Modules\ERP\Http\Controllers\RecurringController::class, 'pause'])->name('recurring.pause');
    Route::post('/recurring/{recurring}/resume', [\Modules\ERP\Http\Controllers\RecurringController::class, 'resume'])->name('recurring.resume');
    Route::get('/recurring/{recurring}/logs', [\Modules\ERP\Http\Controllers\RecurringController::class, 'logs'])->name('recurring.logs');
});

// Freelance Routes
Route::middleware(['auth', 'verified'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('Freelance/Dashboard'); })->name('dashboard');
    Route::get('/jobs', [\Modules\Freelance\Http\Controllers\JobController::class, 'index'])->name('jobs.index');
});

// Marketplace Routes
Route::middleware(['auth', 'verified'])->prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('Marketplace/Dashboard'); })->name('dashboard');
    Route::get('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
});

require __DIR__.'/auth.php';
