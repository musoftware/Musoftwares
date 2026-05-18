<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Public/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'onboarding'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding-wizard', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.wizard');
    Route::post('/onboarding-wizard', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/product-tour/status', [\App\Http\Controllers\OnboardingController::class, 'updateTourStatus'])->name('product-tour.status');
});

// ERP Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:erp'])->prefix('erp')->name('erp.')->group(function () {
    Route::get('/dashboard', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('dashboard');
    Route::post('/clients', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'storeClient'])->name('clients.store');
    Route::put('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateClient'])->name('clients.update');
    Route::delete('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'destroyClient'])->name('clients.destroy');
    Route::get('/clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}/status', [\Modules\ERP\Http\Controllers\ClientController::class, 'updateStatus'])->name('clients.updateStatus');
    Route::get('/onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'completeOnboarding'])->name('onboarding.store');
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/send', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/mark-paid', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::post('/invoices/{invoice}/partial-payment', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'partialPayment'])->name('invoices.partialPayment');
    Route::post('/invoices/{invoice}/cancel', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/duplicate', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('/invoices/{invoice}/pdf', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Wallet
    Route::get('/wallet/add-balance', [\Modules\ERP\Http\Controllers\WalletController::class, 'addBalance'])->name('wallet.add-balance');
    Route::post('/wallet/deposit', [\Modules\ERP\Http\Controllers\WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::get('/clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'show'])->name('wallet.show');
    Route::get('/clients/{client}/wallet/transactions', [\Modules\ERP\Http\Controllers\WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::post('/clients/{client}/wallet/credit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualCredit'])->name('wallet.credit');
    Route::post('/clients/{client}/wallet/debit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualDebit'])->name('wallet.debit');
    Route::post('/clients/{client}/wallet/lock', [\Modules\ERP\Http\Controllers\WalletController::class, 'lockFunds'])->name('wallet.lock');
    Route::post('/clients/{client}/wallet/unlock', [\Modules\ERP\Http\Controllers\WalletController::class, 'unlockFunds'])->name('wallet.unlock');

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

    // ── ERP Task System ──────────────────────────────────────────────
    // Recovered from old project: Admin/TaskController + Admin/TodoController
    // Admin/tenant creates tasks for TenantClients and manages todo items.
    Route::get('/tasks', [\Modules\ERP\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [\Modules\ERP\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/archive', [\Modules\ERP\Http\Controllers\TaskController::class, 'archive'])->name('tasks.archive');
    Route::post('/tasks/{task}/unarchive', [\Modules\ERP\Http\Controllers\TaskController::class, 'unarchive'])->name('tasks.unarchive');
    // Todo items
    Route::post('/tasks/{task}/items', [\Modules\ERP\Http\Controllers\TaskController::class, 'storeItem'])->name('tasks.items.store');
    Route::put('/tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'updateItem'])->name('tasks.items.update');
    Route::post('/tasks/{task}/items/{item}/complete', [\Modules\ERP\Http\Controllers\TaskController::class, 'completeItem'])->name('tasks.items.complete');
    Route::post('/tasks/{task}/items/sort', [\Modules\ERP\Http\Controllers\TaskController::class, 'sortItems'])->name('tasks.items.sort');
    Route::post('/tasks/{task}/items/{item}/pause', [\Modules\ERP\Http\Controllers\TaskController::class, 'pauseItem'])->name('tasks.items.pause');
    Route::post('/tasks/{task}/items/{item}/resume', [\Modules\ERP\Http\Controllers\TaskController::class, 'resumeItem'])->name('tasks.items.resume');
    Route::delete('/tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroyItem'])->name('tasks.items.destroy');

    // ── ERP Client Notes ─────────────────────────────────────────────
    // Recovered from old project: Admin/UserNotesController (per-user notes)
    // Parallel system: tenant manages notes on their TenantClients.
    Route::post('/clients/{client}/notes', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'store'])->name('clients.notes.store');
    Route::delete('/clients/{client}/notes/{note}', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'destroy'])->name('clients.notes.destroy');
    Route::post('/clients/{client}/notes/{note}/archive', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'archive'])->name('clients.notes.archive');
    Route::post('/clients/{client}/notes/{note}/unarchive', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'unarchive'])->name('clients.notes.unarchive');
});

// ── Client-Facing ERP Routes ─────────────────────────────────────────
// Platform users (subscribers) view their invoices and tasks created by admin/tenants.
// Recovered from old project: Client/InvoicesController + Client/TaskController
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('my')->name('erp.client-')->group(function () {
    // Client Invoice List + Payment
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'clientIndex'])->name('invoices.index');
    Route::get('/invoices/{uuid}/pay', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'show'])->name('invoices.pay');
    Route::post('/invoices/{uuid}/pay/wallet', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'processWalletPayment'])->name('invoices.pay.wallet');
});


// Freelance Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:freelance'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', [\Modules\Freelance\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

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
    Route::post('/point-purchases/wallet', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    Route::get('/point-purchases/success', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'success'])->name('point-purchases.success');
    Route::get('/point-purchases/failure', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'failure'])->name('point-purchases.failure');

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
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/dashboard', [\Modules\Marketplace\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/services/create', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'create'])->name('services.create');
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
Route::middleware(['auth', 'verified', 'onboarding', 'role:admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
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
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports/pnl', [\App\Http\Controllers\Admin\ReportController::class, 'pnl'])->name('reports.pnl');

    // Clients (thin ERP-linked view)
    Route::get('/clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [\App\Http\Controllers\Admin\ClientController::class, 'show'])->name('clients.show');

    // KYC Review
    Route::get('/kyc', [\App\Http\Controllers\Admin\KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/{id}/approve', [\App\Http\Controllers\Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{id}/reject', [\App\Http\Controllers\Admin\KycController::class, 'reject'])->name('kyc.reject');

    // ── User Management (Full Admin Control) ────────────────────────
    // Recovered from old project: Admin/UsersController
    Route::get('/users', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Admin\UsersController::class, 'create'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Admin\UsersController::class, 'store'])->name('users.store');
    Route::get('/users/problematic', [\App\Http\Controllers\Admin\UsersController::class, 'problematic'])->name('users.problematic');
    Route::get('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\UsersController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/toggle-block', [\App\Http\Controllers\Admin\UsersController::class, 'toggleBlock'])->name('users.toggleBlock');
    Route::get('/users/{id}/login-as', [\App\Http\Controllers\Admin\UsersController::class, 'loginAs'])->name('users.loginAs');

    // ── User Notes ───────────────────────────────────────────────────
    // Recovered from old project: Admin/UserNotesController
    Route::get('/users/{userId}/notes', [\App\Http\Controllers\Admin\UserNoteController::class, 'index'])->name('users.notes.index');
    Route::post('/users/{userId}/notes', [\App\Http\Controllers\Admin\UserNoteController::class, 'store'])->name('users.notes.store');
    Route::delete('/users/{userId}/notes/{noteId}', [\App\Http\Controllers\Admin\UserNoteController::class, 'destroy'])->name('users.notes.destroy');
    Route::post('/users/{userId}/notes/{noteId}/archive', [\App\Http\Controllers\Admin\UserNoteController::class, 'archive'])->name('users.notes.archive');
    Route::post('/users/{userId}/notes/{noteId}/unarchive', [\App\Http\Controllers\Admin\UserNoteController::class, 'unarchive'])->name('users.notes.unarchive');

    // ── User Files ───────────────────────────────────────────────────
    // Recovered from old project: Admin/FileController
    Route::get('/users/{userId}/files', [\App\Http\Controllers\Admin\UserFileController::class, 'index'])->name('users.files.index');
    Route::post('/users/{userId}/files/upload', [\App\Http\Controllers\Admin\UserFileController::class, 'upload'])->name('users.files.upload');
    Route::post('/users/{userId}/files/folder', [\App\Http\Controllers\Admin\UserFileController::class, 'newFolder'])->name('users.files.folder');
    Route::get('/users/{userId}/files/download', [\App\Http\Controllers\Admin\UserFileController::class, 'download'])->name('users.files.download');
    Route::post('/users/{userId}/files/rename', [\App\Http\Controllers\Admin\UserFileController::class, 'rename'])->name('users.files.rename');
    Route::post('/users/{userId}/files/move', [\App\Http\Controllers\Admin\UserFileController::class, 'move'])->name('users.files.move');
    Route::delete('/users/{userId}/files', [\App\Http\Controllers\Admin\UserFileController::class, 'delete'])->name('users.files.delete');

    // ARCHITECTURE NOTE:
    // Admin does NOT have separate invoice or task panels.
    // The ERP system IS the admin's tool for managing platform users (their "clients").
    // Admin uses Login-As (/admin/users/{id}/login-as) to enter a user's ERP context
    // and access their invoices, tasks, projects, etc. from within the ERP workspace.
    // ERP routes live at /erp/* and are available to any authenticated+subscribed user.

    // ── Serial License System ─────────────────────────────────────────
    // Copied from old project. Fully internal — admin only.
    // API check-in lives in routes/api.php (no auth, throttled).

    // Software registry (auto-created by API, admin manages default_status)
    Route::get('/serial-softwares', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'index'])->name('serial-softwares.index');
    Route::post('/serial-softwares', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'store'])->name('serial-softwares.store');
    Route::patch('/serial-softwares/{serialSoftware}/status', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'updateStatus'])->name('serial-softwares.status');
    Route::delete('/serial-softwares/{serialSoftware}', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'destroy'])->name('serial-softwares.destroy');

    // Device registry (auto-created by API check-in, admin manages status)
    Route::get('/serial-devices', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'index'])->name('serial-devices.index');
    Route::patch('/serial-devices/{serialDevice}/status', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'updateStatus'])->name('serial-devices.status');
    Route::delete('/serial-devices/{serialDevice}', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'destroy'])->name('serial-devices.destroy');

    // User-Device assignments (admin maps device → user)
    Route::get('/serial-user-devices', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'index'])->name('serial-user-devices.index');
    Route::get('/serial-user-devices/by-user', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'byUser'])->name('serial-user-devices.by-user');
    Route::get('/serial-user-devices/assign', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'assign'])->name('serial-user-devices.assign');
    Route::post('/serial-user-devices', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'store'])->name('serial-user-devices.store');
    Route::patch('/serial-user-devices/{serialUserDevice}/status', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateStatus'])->name('serial-user-devices.status');
    Route::patch('/serial-user-devices/users/{user}/status', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateUserStatus'])->name('serial-user-devices.update-user-status');
    Route::patch('/serial-user-devices/users/{user}/temp-valid', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateUserTempValid'])->name('serial-user-devices.update-user-temp-valid');
    Route::delete('/serial-user-devices/{serialUserDevice}', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'destroy'])->name('serial-user-devices.destroy');
});



// SaaS Subscription & Billing Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscriptions/plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::post('/subscriptions/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::get('/subscriptions/manage', [\App\Http\Controllers\SubscriptionController::class, 'manage'])->name('subscriptions.manage');
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/renew', [\App\Http\Controllers\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/kashier/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkoutKashier'])->name('subscriptions.kashier.checkout');
    Route::get('/subscriptions/kashier/success', [\App\Http\Controllers\SubscriptionController::class, 'kashierSuccess'])->name('subscriptions.kashier.success');
    Route::get('/subscriptions/kashier/failure', [\App\Http\Controllers\SubscriptionController::class, 'kashierFailure'])->name('subscriptions.kashier.failure');
});

require __DIR__.'/auth.php';

// Support Ticket Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\SupportTicketController::class, 'resolve'])->name('tickets.resolve');
});

// KYC Routes
Route::middleware(['auth', 'verified'])->prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [\App\Http\Controllers\KycController::class, 'index'])->name('index');
    Route::post('/upload', [\App\Http\Controllers\KycController::class, 'uploadDocument'])->name('upload');
    Route::post('/submit', [\App\Http\Controllers\KycController::class, 'submit'])->name('submit');
    Route::delete('/{id}', [\App\Http\Controllers\KycController::class, 'deleteDocument'])->name('delete');
    Route::get('/{id}/download', [\App\Http\Controllers\KycController::class, 'downloadDocument'])->name('download');
});

// Core Financial Routes
Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
    Route::get('/transactions', [\App\Http\Controllers\FinancialController::class, 'transactions'])->name('transactions');
    Route::get('/withdrawals', [\App\Http\Controllers\FinancialController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals', [\App\Http\Controllers\FinancialController::class, 'requestWithdrawal'])->name('withdrawals.store');
    Route::get('/payout-methods', [\App\Http\Controllers\PayoutMethodController::class, 'index'])->name('payout-methods.index');
    Route::post('/payout-methods', [\App\Http\Controllers\PayoutMethodController::class, 'store'])->name('payout-methods.store');
    Route::patch('/payout-methods/{payout_method}', [\App\Http\Controllers\PayoutMethodController::class, 'update'])->name('payout-methods.update');
    Route::delete('/payout-methods/{payout_method}', [\App\Http\Controllers\PayoutMethodController::class, 'destroy'])->name('payout-methods.destroy');

    Route::get('/add-balance', [\App\Http\Controllers\FinancialController::class, 'addBalance'])->name('add-balance');
    Route::post('/add-balance/kashier', [\App\Http\Controllers\FinancialController::class, 'depositKashier'])->name('add-balance.kashier');
    Route::get('/add-balance/success', [\App\Http\Controllers\FinancialController::class, 'success'])->name('add-balance.success');
    Route::get('/add-balance/failure', [\App\Http\Controllers\FinancialController::class, 'failure'])->name('add-balance.failure');

    // P2P Wallet Transfer Routes
    Route::get('/transfer', [\App\Http\Controllers\WalletTransferController::class, 'create'])->name('transfer.create');
    Route::post('/transfer', [\App\Http\Controllers\WalletTransferController::class, 'store'])->name('transfer.store');
    Route::get('/transfer/history', [\App\Http\Controllers\WalletTransferController::class, 'history'])->name('transfer.history');
    Route::get('/transfer/{id}', [\App\Http\Controllers\WalletTransferController::class, 'show'])->name('transfer.show');

    // P2P Wallet Transfer Live APIs
    Route::get('/transfer-api/calculate-fee', [\App\Http\Controllers\WalletTransferController::class, 'calculateFee'])->name('transfer.calculate-fee');
    Route::get('/transfer-api/search-users', [\App\Http\Controllers\WalletTransferController::class, 'searchUsers'])->name('transfer.search-users');
});

// Kashier Webhook (No Auth required)
Route::post('/financial/add-balance/webhook', [\App\Http\Controllers\FinancialController::class, 'webhook'])->name('financial.add-balance.webhook');
Route::post('/freelance/point-purchases/webhook', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'webhook'])->name('freelance.point-purchases.webhook');
Route::post('/subscriptions/kashier/webhook', [\App\Http\Controllers\SubscriptionController::class, 'webhook'])->name('subscriptions.kashier.webhook');

// General Messages Route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', [\App\Http\Controllers\MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages/direct', [\App\Http\Controllers\MessagesController::class, 'storeDirectMessage'])->name('messages.direct.store');
});

// Global Search
Route::middleware(['auth', 'verified'])->get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations/{id}', [\Modules\Core\Http\Controllers\ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/read', [\Modules\Core\Http\Controllers\ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\MessageController::class, 'store']);

    // Admin Notes
    Route::get('/admin-notes', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'index']);
    Route::post('/admin-notes', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'store']);
    Route::patch('/admin-notes/{note}/pin', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'togglePin']);
    Route::delete('/admin-notes/{note}', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'destroy']);
});

// New API routes for polling
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations', [\Modules\Core\Http\Controllers\ConversationController::class, 'index']);
    Route::get('/timer/{id}', [\App\Http\Controllers\TimerController::class, 'show']);
});
