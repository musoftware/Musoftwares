<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Public/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
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

    // Skills
    Route::resource('skills', \Modules\Freelance\Http\Controllers\SkillController::class)->except(['create', 'show', 'edit']);

    // User Skills
    Route::post('/user-skills', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'store'])->name('user-skills.store');
    Route::delete('/user-skills/{skill_id}', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'destroy'])->name('user-skills.destroy');

    // Points
    Route::get('/points', function(Illuminate\Http\Request $request) {
        $packages = \Modules\Freelance\Models\PointPackage::all();
        $transactions = \Modules\Freelance\Models\PointTransaction::where('user_id', $request->user()->id)->latest()->paginate(10);
        return Inertia::render('Freelance/Points/Index', ['packages' => $packages, 'transactions' => $transactions]);
    })->name('points.index');
    Route::resource('point-packages', \Modules\Freelance\Http\Controllers\PointPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('/point-purchases', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');

    // Jobs
    Route::get('/jobs/browse', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'index'])->name('jobs.browse');
    Route::get('/jobs/my-jobs', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'myJobs'])->name('my-jobs');
    Route::resource('jobs', \Modules\Freelance\Http\Controllers\FreelanceJobController::class)->except(['index']);

    // Proposals
    Route::post('/jobs/{job}/proposals', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'store'])->name('proposals.store');
    Route::post('/proposals/{proposal}/accept', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'accept'])->name('proposals.accept');
    Route::post('/proposals/{proposal}/reject', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'reject'])->name('proposals.reject');
    Route::delete('/proposals/{proposal}/withdraw', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'withdraw'])->name('proposals.withdraw');

    // Contracts
    Route::get('/contracts/{contract}', [\Modules\Freelance\Http\Controllers\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/complete', [\Modules\Freelance\Http\Controllers\ContractController::class, 'complete'])->name('contracts.complete');
    Route::post('/contracts/{contract}/dispute', [\Modules\Freelance\Http\Controllers\ContractController::class, 'dispute'])->name('contracts.dispute');
});

// Marketplace Routes (Public/Logged in)
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{id}', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'show'])->name('services.show');
});

// Marketplace Authenticated Routes
Route::middleware(['auth', 'verified'])->prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/dashboard', function () { return Inertia::render('Marketplace/Dashboard'); })->name('dashboard');

    Route::post('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'store'])->name('services.store');

    // Packages
    Route::post('/services/{service}/packages', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'store'])->name('packages.store');
    Route::put('/services/{service}/packages/{package}', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'update'])->name('packages.update')->scopeBindings();
    Route::delete('/services/{service}/packages/{package}', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'destroy'])->name('packages.destroy')->scopeBindings();

    // Orders
    Route::get('/orders', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/deliver', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/complete', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/dispute', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'dispute'])->name('orders.dispute');

    // Messages
    Route::post('/orders/{order}/messages', [\Modules\Marketplace\Http\Controllers\OrderMessageController::class, 'store'])->name('orders.messages.store');
});

// Marketplace Admin Routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
    // Categories
    Route::get('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

    // Services Admin Actions
    Route::post('/services/{id}/approve', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'approve'])->name('services.approve');
    Route::post('/services/{id}/reject', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'reject'])->name('services.reject');
    Route::post('/services/{id}/feature', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'feature'])->name('services.feature');

    // Admin Views mapping to components later
    Route::get('/pending-services', function () {
        return Inertia::render('Admin/Marketplace/Pending', [
            'services' => \Modules\Marketplace\Models\Service::with(['seller', 'category'])->where('status', 'draft')->paginate(15)
        ]);
    })->name('services.pending');

    Route::get('/all-services', function () {
        return Inertia::render('Admin/Marketplace/All', [
            'services' => \Modules\Marketplace\Models\Service::with(['seller', 'category'])->paginate(15)
        ]);
    })->name('services.all');
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
