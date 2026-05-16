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

    // Wallet
    Route::get('/clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'show'])->name('wallet.show');
    Route::get('/clients/{client}/wallet/transactions', [\Modules\ERP\Http\Controllers\WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::post('/clients/{client}/wallet/credit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualCredit'])->name('wallet.credit');
    Route::post('/clients/{client}/wallet/debit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualDebit'])->name('wallet.debit');

    // Withdrawals
    Route::get('/withdrawals', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::post('/withdrawals/{withdrawal}/approve', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/mark-paid', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'markPaid'])->name('withdrawals.markPaid');
    Route::post('/withdrawals/{withdrawal}/reject', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawal}/cancel', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

    // Payment Methods
    Route::get('/payment-methods', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::patch('/payment-methods/{payment_method}', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('/payment-methods/{payment_method}', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::post('/payment-methods/{payment_method}/approve', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'approve'])->name('payment-methods.approve');
    Route::post('/payment-methods/{payment_method}/reject', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'reject'])->name('payment-methods.reject');
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

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports/pnl', [\App\Http\Controllers\Admin\ReportController::class, 'pnl'])->name('reports.pnl');

    // Clients
    Route::get('/clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [\App\Http\Controllers\Admin\ClientController::class, 'show'])->name('clients.show');
});

require __DIR__.'/auth.php';

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations/{id}', [\Modules\Core\Http\Controllers\ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/read', [\Modules\Core\Http\Controllers\ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\MessageController::class, 'store']);
});

// New API routes for polling
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations', [\Modules\Core\Http\Controllers\ConversationController::class, 'index']);
    Route::get('/timer/{id}', [\App\Http\Controllers\TimerController::class, 'show']);
});
