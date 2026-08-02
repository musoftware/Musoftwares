<?php

use App\Http\Controllers\Admin\AdminBlogArticleController;
use App\Http\Controllers\Admin\AdminBusyTimesController;
use App\Http\Controllers\Admin\AdminContractController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminCurrencyController;
use App\Http\Controllers\Admin\AdminCurrencyExchangeController;
use App\Http\Controllers\Admin\AdminFreeDownloadController;
use App\Http\Controllers\Admin\AdminLanguageLineController;
use App\Http\Controllers\Admin\AdminPaymentMethodController;
use App\Http\Controllers\Admin\AdminPointPackageController;
use App\Http\Controllers\Admin\AdminPointsController;
use App\Http\Controllers\Admin\AdminQuotationController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminTaskController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminUserLoanController;
use App\Http\Controllers\Admin\AdminVoucherController;
use App\Http\Controllers\Admin\AdminWithdrawRequestController;
use App\Http\Controllers\Admin\AiEstimatorController;
use App\Http\Controllers\Admin\BroadcastNotificationController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\CharityCounterController;
use App\Http\Controllers\Admin\ContractAiController;
use App\Http\Controllers\Admin\ContractPriceItemController;
use App\Http\Controllers\Admin\EmployeeTodoController;
use App\Http\Controllers\Admin\FinancialOperationsController;
use App\Http\Controllers\Admin\GoogleCalendarIntegrationController;
use App\Http\Controllers\Admin\GuestTicketController;
use App\Http\Controllers\Admin\HoursCalendarController;
use App\Http\Controllers\Admin\IncomingWebhooksController;
use App\Http\Controllers\Admin\PaymentLinkController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProjectContractController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectAdminNoteController;
use App\Http\Controllers\Admin\ProjectFileController;
use App\Http\Controllers\Admin\ProjectReportController;
use App\Http\Controllers\Admin\RecurringBusinessController;
use App\Http\Controllers\Admin\RecurringInvoiceController;
use App\Http\Controllers\Admin\RecurringNoticeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SerialDeviceController;
use App\Http\Controllers\Admin\SerialSoftwareController;
use App\Http\Controllers\Admin\SerialUserDeviceController;
use App\Http\Controllers\Admin\UserFileController;
use App\Http\Controllers\Admin\UserNoteController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\WebsiteServiceController;
use App\Http\Controllers\AdminNoteController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\BackgroundTaskController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Client\ClientProjectBoardController;
use App\Http\Controllers\Client\ClientProjectCalendarController;
use App\Http\Controllers\Client\ClientProjectCommentController;
use App\Http\Controllers\Client\ClientProjectController;
use App\Http\Controllers\Client\ClientProjectFileController;
use App\Http\Controllers\Client\ClientProjectReportController;
use App\Http\Controllers\Client\ClientProjectTaskController;
use App\Http\Controllers\Client\ClientTasksAggregatorController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\Frontend\ClientContractController;
use App\Http\Controllers\GuestInvoiceController;
use App\Http\Controllers\GuestPaymentLinkController;
use App\Http\Controllers\GuestTicketSubmissionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\iSaaS\ClientPortalController;
use App\Http\Controllers\iSaaS\ContractController;
use App\Http\Controllers\iSaaS\ProjectProposalController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayoutMethodController;
use App\Http\Controllers\PointPurchaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WalletTransferController;
use App\Services\AmcAcademyApiService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController;
use Modules\Marketplace\Http\Controllers\Admin\MarketplaceOrderController;
use Modules\Marketplace\Http\Controllers\Admin\MarketplaceServiceController;
use Modules\Marketplace\Http\Controllers\OrderMessageController;
use Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController;
use Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAnalyticsController;
use Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController;
use Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPagePublicController;
use Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController;
use Modules\Marketplace\Http\Controllers\ServiceCategoryController;
use Modules\Marketplace\Http\Controllers\ServiceController;
use Modules\Marketplace\Http\Controllers\ServiceOrderController;
use Modules\Marketplace\Http\Controllers\ServicePackageController;
use Modules\Marketplace\Http\Controllers\ServiceReviewController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/portfolio', [HomeController::class, 'portfolio'])->name('portfolio');
Route::get('/portfolio/{slug}', [HomeController::class, 'portfolioShow'])->name('portfolio.show');
Route::get('/services/{slug}', [HomeController::class, 'websiteServiceShow'])->name('website-services.show');

// Public Contract Links
Route::get('/c/{uuid}', [ClientContractController::class, 'show'])->name('client.contract.show');
Route::post('/c/{uuid}/sign', [ClientContractController::class, 'sign'])->name('client.contract.sign');

Route::get('/install-app', function () {
    return Inertia\Inertia::render('PWA/InstallGuide');
})->name('install-app');

Route::get('/test-amc-api', function (AmcAcademyApiService $service) {
    // Array of mock FBIDs to test bulk lookup and deduction
    $testFbids = ['10000000000001', '10000000000002'];
    $result = $service->searchFbidsBulk($testFbids);

    return response()->json([
        'message' => 'API Test Completed',
        'fbids_sent' => $testFbids,
        'result' => $result,
    ]);
});

Route::get('/test22', function () {
    Artisan::call('optimize:clear');
    Artisan::call('migrate');
});

Route::get('/test233', function () {
    Artisan::call('schedule:run');
});
// Platforms
Route::get('/platforms', [HomeController::class, 'platforms'])->name('platforms');
Route::get('/platforms/crm', [HomeController::class, 'platformCrm'])->name('platforms.crm');
Route::get('/platforms/erp', [HomeController::class, 'platformErp'])->name('platforms.erp');
Route::get('/platforms/cloud', [HomeController::class, 'platformCloud'])->name('platforms.cloud');

// Solutions
Route::get('/solutions', [HomeController::class, 'solutions'])->name('solutions');
Route::get('/solutions/healthcare', [HomeController::class, 'solutionHealthcare'])->name('solutions.healthcare');
Route::get('/solutions/education', [HomeController::class, 'solutionEducation'])->name('solutions.education');
Route::get('/solutions/ecommerce', [HomeController::class, 'solutionEcommerce'])->name('solutions.ecommerce');
Route::get('/solutions/real-estate', [HomeController::class, 'solutionRealEstate'])->name('solutions.real-estate');
Route::get('/solutions/finance', [HomeController::class, 'solutionFinance'])->name('solutions.finance');

// Company
Route::get('/company', [HomeController::class, 'company'])->name('company');
Route::get('/company/about', [HomeController::class, 'companyAbout'])->name('company.about');
Route::get('/company/careers', [HomeController::class, 'companyCareers'])->name('company.careers');
Route::get('/company/contact', [HomeController::class, 'companyContact'])->name('company.contact');

// Legal
Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('legal.privacy');
Route::get('/terms-of-service', [HomeController::class, 'termsOfService'])->name('legal.terms');
Route::get('/cookie-policy', [HomeController::class, 'cookiePolicy'])->name('legal.cookies');

// Short Alias Redirects (301) to prevent 404
Route::redirect('/privacy', '/privacy-policy', 301);
Route::redirect('/terms', '/terms-of-service', 301);
Route::redirect('/cookies', '/cookie-policy', 301);
Route::redirect('/legal/privacy', '/privacy-policy', 301);
Route::redirect('/legal/terms', '/terms-of-service', 301);
Route::redirect('/legal/cookies', '/cookie-policy', 301);

// Pricing
Route::get('/pricing', [HomeController::class, 'pricing'])->name('pricing');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Shared project board (public guest view)
Route::get('/shared-board/{token}/{date}', [\App\Http\Controllers\PublicProjectBoardController::class, 'show'])
    ->name('shared-board.show')
    ->middleware('shared_board_access');

// Guest comments on shared-board cards (notes, tasks, todos, reports, files).
// Auth is the unguessable share_token — guests do not need to register.
Route::prefix('/shared-board/{token}')->name('public.comments.')->group(function () {
    Route::get('/comments/{type}/{id}', [\App\Http\Controllers\PublicProjectCommentController::class, 'index'])
        ->name('index');
    Route::post('/comments', [\App\Http\Controllers\PublicProjectCommentController::class, 'store'])
        ->name('store');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'onboarding'])->name('dashboard');

Route::get('/dashboard/directory', [DashboardController::class, 'directory'])
    ->middleware(['auth', 'verified'])->name('dashboard.directory');

// ── Client Projects Portal ────────────────────────────────────────
// Platform users (clients) view the admin-managed projects assigned to them, including a
// per-day visual workflow board. Ownership is enforced per-route via ProjectPolicy.
Route::middleware(['auth', 'verified', 'onboarding'])->name('client.projects.')->group(function () {
    Route::get('/projects', [ClientProjectController::class, 'index'])->name('index');
    Route::get('/projects/tasks', [ClientTasksAggregatorController::class, 'index'])->name('all-tasks');
    Route::get('/projects/board', [ClientProjectCalendarController::class, 'allProjectsBoardIndex'])->name('all-projects-board.index');
    Route::get('/projects/board/{date}', [ClientProjectCalendarController::class, 'allProjectsBoardDate'])->name('all-projects-board.date');
    Route::get('/projects/create-new', [ClientProjectController::class, 'create'])->name('create-new');
    Route::post('/projects/store-new', [ClientProjectController::class, 'store'])->name('store-new');
    Route::post('/projects/{project}/ai/activate', [ClientProjectController::class, 'activateAi'])->name('ai.activate');
    Route::get('/projects/{project}', [ClientProjectController::class, 'show'])->name('show');
    Route::get('/projects/{project}/tasks', [ClientProjectTaskController::class, 'tasksIndex'])->name('tasks.index');
    Route::get('/projects/{project}/reports/{report}', [ClientProjectReportController::class, 'show'])->name('reports.show');

    // Comments / feedback (polymorphic: notes, tasks, reports)
    Route::get('/projects/{project}/comments/{type}/{id}', [ClientProjectCommentController::class, 'commentsIndex'])->name('comments.index');
    Route::post('/projects/{project}/comments', [ClientProjectCommentController::class, 'store'])->name('comments.store');
    Route::get('/projects/{project}/files', [ClientProjectFileController::class, 'index'])->name('files.index');
    Route::get('/projects/{project}/files/{file}/download', [ClientProjectFileController::class, 'download'])->name('files.download');
    Route::get('/projects/{project}/calendar', [ClientProjectCalendarController::class, 'calendarIndex'])->name('calendar.index');
    Route::get('/projects/{project}/calendar/{date}', [ClientProjectCalendarController::class, 'calendarDate'])->name('calendar.date');

    // AI Workspace — message send (text + optional file) + AI question dismiss
    Route::post('/projects/{project}/messages', [ClientProjectController::class, 'storeMessage'])->name('messages.store');
    Route::patch('/projects/{project}/ai-questions/{questionId}/dismiss', [ClientProjectController::class, 'dismissAiQuestion'])->name('ai-questions.dismiss');
});

// Board mutations (JSON) — both client and admin and guest with write-access use these endpoints.
Route::middleware([\App\Http\Middleware\VerifyBoardAccess::class])->name('client.projects.')->group(function () {
    Route::post('/projects/{project}/board/ai-questions', [ClientProjectBoardController::class, 'generateAiQuestions'])->name('board.ai-questions');
    Route::post('/projects/{project}/board/add-with-ai', [ClientProjectBoardController::class, 'addWithAi'])->name('board.add-with-ai');
    Route::post('/projects/{project}/board/notes', [ClientProjectBoardController::class, 'storeNote'])->name('board.store-note');
    Route::put('/projects/{project}/board/notes/{note}', [ClientProjectBoardController::class, 'updateNote'])->name('board.update-note');
    Route::delete('/projects/{project}/board/notes/{note}', [ClientProjectBoardController::class, 'destroyNote'])->name('board.destroy-note');

    Route::post('/projects/{project}/board/tasks', [ClientProjectBoardController::class, 'storeTask'])->name('board.store-task');
    Route::put('/projects/{project}/board/tasks/{task}', [ClientProjectBoardController::class, 'updateTask'])->name('board.update-task');
    Route::delete('/projects/{project}/board/tasks/{task}', [ClientProjectBoardController::class, 'destroyTask'])->name('board.destroy-task');

    Route::post('/projects/{project}/board/todos', [ClientProjectBoardController::class, 'storeTodo'])->name('board.store-todo');
    Route::put('/projects/{project}/board/todos/{todo}', [ClientProjectBoardController::class, 'updateTodo'])->name('board.update-todo');
    Route::delete('/projects/{project}/board/todos/{todo}', [ClientProjectBoardController::class, 'destroyTodo'])->name('board.destroy-todo');

    Route::post('/projects/{project}/board/files', [ClientProjectBoardController::class, 'storeFile'])->name('board.store-file');
    Route::delete('/projects/{project}/board/files/{file}', [ClientProjectBoardController::class, 'destroyFile'])->name('board.destroy-file');

    Route::post('/projects/{project}/board/reports', [ClientProjectBoardController::class, 'storeReport'])->name('board.store-report');
    Route::put('/projects/{project}/board/reports/{report}', [ClientProjectBoardController::class, 'updateReport'])->name('board.update-report');
    Route::delete('/projects/{project}/board/reports/{report}', [ClientProjectBoardController::class, 'destroyReport'])->name('board.destroy-report');
    Route::get('/projects/{project}/board/reports/{report}/export-pdf', [ClientProjectBoardController::class, 'exportReportPdf'])->name('board.export-report-pdf');
    Route::post('/projects/{project}/board/reports/generate-draft', [ClientProjectBoardController::class, 'generateReportDraft'])->name('board.reports.generate-draft');

    Route::post('/projects/{project}/board/move', [ClientProjectBoardController::class, 'moveCard'])->name('board.move-card');
    Route::post('/projects/{project}/board/reorder', [ClientProjectBoardController::class, 'reorderCards'])->name('board.reorder-cards');
    Route::post('/projects/{project}/board/reschedule', [ClientProjectBoardController::class, 'rescheduleCard'])->name('board.reschedule-card');
    Route::post('/projects/{project}/board/bring-undone', [ClientProjectBoardController::class, 'bringUndone'])->name('board.bring-undone');
    Route::post('/projects/{project}/board/items/{boardItem}/approval', [ClientProjectBoardController::class, 'updateApproval'])->name('board.items.approval');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::patch('/profile/workspace-settings', [ProfileController::class, 'updateWorkspaceSettings'])->name('profile.workspace-settings.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/emails', [ProfileController::class, 'storeEmail'])->name('profile.emails.store');
    Route::delete('/profile/emails/{userEmail}', [ProfileController::class, 'destroyEmail'])->name('profile.emails.destroy');
    Route::patch('/profile/emails/{userEmail}', [ProfileController::class, 'updateEmail'])->name('profile.emails.update');
    Route::post('/profile/emails/{userEmail}/make-primary', [ProfileController::class, 'makePrimaryEmail'])->name('profile.emails.make-primary');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // FCM Device Token
    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.wizard');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->middleware('throttle:60,1')->name('onboarding.store');
    Route::post('/product-tour/status', [OnboardingController::class, 'updateTourStatus'])->name('product-tour.status');
    Route::get('/onboarding/cities/{countryName}', [OnboardingController::class, 'getCities'])->middleware('throttle:60,1')->name('onboarding.cities');

    // Tenant Onboarding

    // Vouchers (Client Facing)
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');

    // Referral System
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/earnings', [ReferralController::class, 'earns'])->name('referrals.earns');
    Route::get('/referrals/registers', [ReferralController::class, 'registers'])->name('referrals.registers');
    Route::post('/referrals', [ReferralController::class, 'store_referral'])->name('referrals.store');
    Route::post('/referrals/update-slug', [ReferralController::class, 'update_slug'])->name('referrals.update_slug');
    Route::post('/referrals/activate', [ReferralController::class, 'activate_ref'])->name('referrals.activate');
    Route::post('/referrals/generate-embed-key', [ReferralController::class, 'generate_embed_key'])->name('referrals.generate_embed_key');
});

// Public Referral Redirect Route
Route::get('/r/{ref}', [ReferralController::class, 'referral_redirect'])
    ->middleware([\App\Http\Middleware\ReferralRedirectHeaders::class, 'throttle:30,1'])
    ->name('ref');

// Tracking Route for Campaigns
Route::get('/track/campaign/{id}', [TrackingController::class, 'trackCampaign'])->name('track.campaign');
Route::get('/track/campaign/{id}/view.png', [TrackingController::class, 'trackCampaignView'])->name('track.campaign.view');

Route::get('/fix-cities', function () {
    DB::table('cities')->truncate();
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CitySeeder', '--force' => true]);

    return 'Done';
});

/* // ERP Routes (Migrated to Modules/ERP/routes/web.php)
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:erp', 'erp.team.permissions'])->prefix('erp')->name('erp.')->group(function () {
    // Team Member Management (Tenant Owner Only)
    Route::get('/team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'index'])->name('team-members.index');
    Route::post('/team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'store'])->name('team-members.store');
    Route::put('/team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'update'])->name('team-members.update');
    Route::delete('/team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'destroy'])->name('team-members.destroy');

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


    // Recurring
    Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);
    Route::post('/recurring/{recurring}/pause', [\Modules\ERP\Http\Controllers\RecurringController::class, 'pause'])->name('recurring.pause');
    Route::post('/recurring/{recurring}/resume', [\Modules\ERP\Http\Controllers\RecurringController::class, 'resume'])->name('recurring.resume');
    Route::get('/recurring/{recurring}/logs', [\Modules\ERP\Http\Controllers\RecurringController::class, 'logs'])->name('recurring.logs');

    // ── ERP Task System ──────────────────────────────────────────────
    // Recovered from old project: Admin/TaskController + Admin/TodoController
    // Admin/tenant creates tasks for TenantClients and manages todo items.
    Route::get('/tasks', [\Modules\ERP\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/as-list', [\Modules\ERP\Http\Controllers\TaskController::class, 'asList'])->name('tasks.as_list');
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

    // ── ERP Support Tickets ───────────────────────────────────────────
    Route::post('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\SupportTicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/close', [\App\Http\Controllers\SupportTicketController::class, 'close'])->name('tickets.close');
    Route::delete('/tickets/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'destroy'])->name('tickets.destroy');

    // ── ERP Workspace Notes ───────────────────────────────────────────
    // Tenant-scoped scratchpad notes for the ERP dashboard.
    Route::post('/notes', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'update'])->name('notes.update');
    Route::post('/notes/{note}/toggle-pin', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'togglePin'])->name('notes.togglePin');
    Route::delete('/notes/{note}', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'destroy'])->name('notes.destroy');
}); */

// ── Client-Facing Billing Routes ─────────────────────────────────────
// Platform users (subscribers) view their invoices issued by admin and pay them.
// These are CORE platform billing routes — NOT related to the ERP module.
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{uuid}/pay', [InvoiceController::class, 'show'])->name('invoices.pay');
    Route::post('/invoices/{uuid}/pay', [InvoiceController::class, 'processPayment'])->name('invoices.pay.process');
    Route::get('/invoices/payment/success', [InvoiceController::class, 'paymentSuccess'])->name('invoices.payment.success');
    Route::get('/invoices/payment/failure', [InvoiceController::class, 'paymentFailure'])->name('invoices.payment.failure');
    Route::get('/invoices/{uuid}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
});

// Platform invoice payment webhook (unprotected)
Route::post('/billing/invoices/payment/webhook', [InvoiceController::class, 'paymentWebhook'])->name('billing.invoices.payment.webhook');

// Marketplace Routes defined in Modules/Marketplace/routes/web.php

// -- Seller Landing Pages ------------------------------------------
Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        // CRUD
        Route::get('/landing-pages', [ServiceLandingPageController::class, 'index'])->name('landing-pages.index');
        Route::get('/landing-pages/create/{service}', [ServiceLandingPageController::class, 'create'])->name('landing-pages.create');
        Route::post('/landing-pages/{service}', [ServiceLandingPageController::class, 'store'])->name('landing-pages.store');
        Route::get('/landing-pages/{service}/edit/{landingPage?}', [ServiceLandingPageController::class, 'edit'])->name('landing-pages.edit');
        Route::put('/landing-pages/{service}/{landingPage?}', [ServiceLandingPageController::class, 'update'])->name('landing-pages.update');
        Route::post('/landing-pages/{landingPage}/duplicate', [ServiceLandingPageController::class, 'duplicate'])->name('landing-pages.duplicate');

        // Submissions
        Route::get('/landing-pages/{service}/submissions', [ServiceLandingPageSubmissionController::class, 'submissions'])->name('landing-pages.submissions');
        Route::delete('/landing-pages/submissions/{submission}', [ServiceLandingPageSubmissionController::class, 'destroySubmission'])->name('landing-pages.submissions.destroy');
        Route::get('/landing-pages/{service}/submissions/export', [ServiceLandingPageSubmissionController::class, 'exportSubmissions'])->name('landing-pages.submissions.export');

        // Analytics
        Route::get('/landing-pages/{service}/analytics', [ServiceLandingPageAnalyticsController::class, 'analytics'])->name('landing-pages.analytics');

        // AI Generation
        Route::post('/landing-pages/{service}/generate-questions', [ServiceLandingPageAIController::class, 'generateQuestions'])->name('landing-pages.generate-questions');
        Route::post('/landing-pages/{service}/generate-faqs', [ServiceLandingPageAIController::class, 'generateFAQs'])->name('landing-pages.generate-faqs');
        Route::post('/landing-pages/{service}/generate-pricing', [ServiceLandingPageAIController::class, 'generatePricingTables'])->name('landing-pages.generate-pricing');
        Route::post('/landing-pages/{service}/generate-content', [ServiceLandingPageAIController::class, 'generateLandingPageContent'])->name('landing-pages.generate-content');
    });

// -- Public Landing Page Routes ------------------------------------
Route::middleware(['web'])
    ->name('services.')
    ->group(function () {
        Route::get('/s/{slug}', [ServiceLandingPagePublicController::class, 'show'])->name('landing-page.show');
        Route::get('/s/preview/{template}', [ServiceLandingPagePublicController::class, 'previewTemplate'])->name('landing-page.preview');
        Route::post('/s/{slug}/submit', [ServiceLandingPageSubmissionController::class, 'submitForm'])->name('landing-page.submit');

        // Analytics Tracking endpoints
        Route::post('/s/track/cta', [ServiceLandingPageAnalyticsController::class, 'trackCtaClick'])->name('landing-page.track.cta');
        Route::post('/s/track/scroll', [ServiceLandingPageAnalyticsController::class, 'trackScroll'])->name('landing-page.track.scroll');
    });

// Marketplace Admin Routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
    // Categories
    Route::get('/categories', [ServiceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [ServiceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [ServiceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

    // Services Admin Actions
    Route::get('/all-services', [MarketplaceServiceController::class, 'allServices'])->name('services.all');
    Route::get('/pending-services', [MarketplaceServiceController::class, 'pendingServices'])->name('services.pending');
    Route::get('/services/{service}/edit', [MarketplaceServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [MarketplaceServiceController::class, 'update'])->name('services.update');
    Route::post('/services/{service}/approve', [MarketplaceServiceController::class, 'approve'])->name('services.approve');
    Route::post('/services/{service}/reject', [MarketplaceServiceController::class, 'reject'])->name('services.reject');
    Route::post('/services/{service}/suspend', [MarketplaceServiceController::class, 'suspend'])->name('services.suspend');
    Route::post('/services/{service}/feature', [MarketplaceServiceController::class, 'feature'])->name('services.feature');
    Route::delete('/services/{service}', [MarketplaceServiceController::class, 'destroy'])->name('services.destroy');

    // Orders
    Route::get('/orders', [MarketplaceOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [MarketplaceOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/dispute', [MarketplaceOrderController::class, 'resolveDispute'])->name('orders.dispute.resolve');

    // Admin Service Landing Pages
    Route::get('/service-landing-pages', [AdminServiceLandingPageController::class, 'index'])->name('service-landing-pages.index');
    Route::post('/service-landing-pages/{landingPage}/toggle-status', [AdminServiceLandingPageController::class, 'toggleStatus'])->name('service-landing-pages.toggle-status');
    Route::delete('/service-landing-pages/{landingPage}', [AdminServiceLandingPageController::class, 'destroy'])->name('service-landing-pages.destroy');

    // Admin Views
});



if (file_exists(base_path('Modules/CRM/routes/web.php'))) {
    require base_path('Modules/CRM/routes/web.php');
}

// Admin Tickets (Accessible by Admin and Moderator)
Route::middleware(['auth', 'verified', 'onboarding', 'moderator'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tickets', AdminTicketController::class)->only(['index', 'show', 'update']);
    Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/canned-responses', [AdminTicketController::class, 'addCannedResponse'])->name('tickets.canned-responses.store');
    Route::resource('guest-tickets', GuestTicketController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('guest-tickets/{guest_ticket}/reply', [GuestTicketController::class, 'reply'])->name('guest-tickets.reply');
    Route::post('guest-tickets/{guest_ticket}/status', [GuestTicketController::class, 'updateStatus'])->name('guest-tickets.updateStatus');
});

// Admin Routes
// Public Guest Tickets
Route::post('/guest-tickets/submit', [GuestTicketSubmissionController::class, 'store'])->name('guest-tickets.submit');

Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Website Services
    Route::resource('website-services', WebsiteServiceController::class)->except(['show']);

    // AI Estimator
    Route::get('/tools/ai-estimator', [AiEstimatorController::class, 'index'])->name('tools.ai-estimator');
    Route::post('/tools/ai-estimator/estimate', [AiEstimatorController::class, 'estimate'])->name('tools.ai-estimator.estimate');

    // Broadcast Notifications
    Route::get('/notifications/broadcast', [BroadcastNotificationController::class, 'index'])->name('notifications.broadcast');
    Route::post('/notifications/broadcast/send', [BroadcastNotificationController::class, 'send'])
        ->middleware('throttle:30,1')
        ->name('notifications.broadcast.send');
    Route::get('/notifications/search-users', [BroadcastNotificationController::class, 'searchUsers'])->name('notifications.search_users');
    Route::get('/notifications/broadcast/{id}', [BroadcastNotificationController::class, 'show'])->name('notifications.broadcast.show');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Clients (thin ERP-linked view) - removed as per request

    // KYC Review
    Route::get('/kyc', [App\Http\Controllers\Admin\KycController::class, 'index'])->name('kyc.index');
    Route::get('/users/{user}/kyc/documents', [App\Http\Controllers\Admin\KycController::class, 'showUserDocuments'])->name('users.kyc-documents');
    Route::post('/kyc/{user}/approve', [App\Http\Controllers\Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{user}/reject', [App\Http\Controllers\Admin\KycController::class, 'reject'])->name('kyc.reject');

    // ── Admin Blog Articles ─────────────────────────────────────────
    Route::resource('/blog-articles', AdminBlogArticleController::class);

    // ── Admin Employee Todos ────────────────────────────────────────
    Route::get('/employee-todos', [EmployeeTodoController::class, 'index'])->name('employee-todos.index');
    Route::post('/employee-todos', [EmployeeTodoController::class, 'store'])->name('employee-todos.store');
    Route::put('/employee-todos/{employeeTodo}', [EmployeeTodoController::class, 'update'])->name('employee-todos.update');
    Route::delete('/employee-todos/{employeeTodo}', [EmployeeTodoController::class, 'destroy'])->name('employee-todos.destroy');

    // ── Admin Projects ──────────────────────────────────────────────
    Route::get('/projects/search-clients', [ProjectController::class, 'searchClients'])->name('projects.search-clients');
    Route::get('/projects/{project}/shares', [ProjectController::class, 'listShares'])->name('projects.shares.index');
    Route::post('/projects/{project}/shares', [ProjectController::class, 'addShare'])->name('projects.shares.store');
    Route::delete('/projects/{project}/shares/{share}', [ProjectController::class, 'removeShare'])->name('projects.shares.destroy');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'boardIndex'])->name('projects.edit');
    Route::resource('/projects', ProjectController::class)->except(['create', 'edit', 'show']);
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::post('/projects/bulk-action', [ProjectController::class, 'bulkAction'])->name('projects.bulk-action');
    Route::get('/projects-export', [ProjectController::class, 'export'])->name('projects.export');

    // Project Contracts
    Route::get('/projects/{project}/contracts', [ProjectContractController::class, 'index'])->name('projects.contracts.index');
    Route::post('/projects/{project}/contracts', [ProjectContractController::class, 'store'])->name('projects.contracts.store');
    Route::put('/projects/{project}/contracts/{contract}', [ProjectContractController::class, 'update'])->name('projects.contracts.update');
    Route::post('/projects/{project}/contracts/{contract}/invoice', [ProjectContractController::class, 'generateInvoice'])->name('projects.contracts.invoice');

    // ── Project Reports (scheduled/published progress reports for clients) ──
    Route::get('/projects/{project}/reports', [ProjectReportController::class, 'index'])->name('projects.reports.index');
    Route::get('/projects/{project}/reports/create', [ProjectReportController::class, 'create'])->name('projects.reports.create');
    Route::post('/projects/{project}/reports', [ProjectReportController::class, 'store'])->name('projects.reports.store');
    Route::get('/projects/{project}/reports/{report}/edit', [ProjectReportController::class, 'edit'])->name('projects.reports.edit');
    Route::put('/projects/{project}/reports/{report}', [ProjectReportController::class, 'update'])->name('projects.reports.update');
    Route::delete('/projects/{project}/reports/{report}', [ProjectReportController::class, 'destroy'])->name('projects.reports.destroy');

    // ── Project Files (attachments shared with the client) ──
    Route::get('/projects/{project}/files', [ProjectFileController::class, 'index'])->name('projects.files.index');
    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');

    // ── Project Board (canvas + lanes) — admin "view project" landing ──
    Route::get('/projects/{project}/board', [ProjectController::class, 'boardIndex'])->name('projects.board.index');
    Route::get('/projects/{project}/board/{date}', [ProjectController::class, 'board'])->name('projects.board');
    Route::put('/projects/{project}/board/preferences', [ProjectController::class, 'updateBoardPreferences'])->name('projects.board.preferences.update');

    // ── Project Admin Notes (internal notes) ──
    Route::get('/projects/{project}/admin-notes', [ProjectAdminNoteController::class, 'index'])->name('projects.admin-notes.index');
    Route::post('/projects/{project}/admin-notes', [ProjectAdminNoteController::class, 'store'])->name('projects.admin-notes.store');
    Route::put('/projects/{project}/admin-notes/{note}', [ProjectAdminNoteController::class, 'update'])->name('projects.admin-notes.update');
    Route::delete('/projects/{project}/admin-notes/{note}', [ProjectAdminNoteController::class, 'destroy'])->name('projects.admin-notes.destroy');

    // ── Project Finance / Cost Analysis (cost transactions + paid/pending invoices) ──
    Route::get('/projects/{project}/finance', [ProjectController::class, 'finance'])->name('projects.finance.index');

    // ── Project Board Categories (admin manages the project's label taxonomy) ──
    Route::get('/projects/{project}/board-categories', [\App\Http\Controllers\Admin\BoardCategoryController::class, 'index'])->name('projects.board.categories.index');
    Route::post('/projects/{project}/board-categories', [\App\Http\Controllers\Admin\BoardCategoryController::class, 'store'])->name('projects.board.categories.store');
    Route::put('/projects/{project}/board-categories/{category}', [\App\Http\Controllers\Admin\BoardCategoryController::class, 'update'])->name('projects.board.categories.update');
    Route::delete('/projects/{project}/board-categories/{category}', [\App\Http\Controllers\Admin\BoardCategoryController::class, 'destroy'])->name('projects.board.categories.destroy');

    // Contract AI generator
    Route::post('/contracts/ai/generate', [ContractAiController::class, 'generate'])->name('contracts.ai.generate');
    Route::post('/contracts/ai/review', [ContractAiController::class, 'review'])->name('contracts.ai.review');

    // Standalone Contracts
    Route::resource('/contracts', AdminContractController::class);
    Route::resource('/contract-price-items', ContractPriceItemController::class)->except(['create', 'show', 'edit']);

    // ── Admin Plans ───────────────────────────────────────────────
    Route::get('/plans/search-users', [PlanController::class, 'searchUsers'])->name('plans.search-users');
    Route::resource('/plans', PlanController::class)->except(['edit', 'show']);

    // ── Google Calendar Integrations ─────────────────────────────────
    Route::prefix('google-calendar')->name('google-calendar.')->group(function () {
        Route::get('/connect', [GoogleCalendarIntegrationController::class, 'connect'])->name('connect');
        Route::get('/callback', [GoogleCalendarIntegrationController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [GoogleCalendarIntegrationController::class, 'disconnect'])->name('disconnect');
    });

    // ── Admin Busy Times ──────────────────────────────────────────────
    Route::get('/busy-times', [AdminBusyTimesController::class, 'index'])->name('busy-times.index');
    Route::post('/busy-times/{busyTime}/toggle-active', [AdminBusyTimesController::class, 'toggleActive'])->name('busy-times.toggle-active');
    Route::delete('/busy-times/{busyTime}', [AdminBusyTimesController::class, 'destroy'])->name('busy-times.destroy');

    // ── Admin Phase 4 (Settings & Localization) ───────────────────
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingController::class, 'store'])->name('settings.store');
    Route::post('settings/do-update-prices', [AdminSettingController::class, 'doUpdatePrices'])->name('settings.do-update-prices');
    Route::post('settings/calculate-hourly-rate', [AdminSettingController::class, 'calculateHourlyRate'])->name('settings.calculate-hourly-rate');

    Route::post('settings/sync-exchange-rates', [AdminSettingController::class, 'syncExchangeRates'])->name('settings.sync-exchange-rates');

    Route::resource('language-lines', AdminLanguageLineController::class)->except(['create', 'show', 'edit']);
    Route::post('language-lines/auto-translate', [AdminLanguageLineController::class, 'autoTranslate'])->name('language-lines.auto-translate');
    Route::post('language-lines/import', [AdminLanguageLineController::class, 'import'])->name('language-lines.import');

    Route::get('quotations/{contract}/print', [AdminQuotationController::class, 'print'])->name('quotations.print');

    Route::resource('free-downloads', AdminFreeDownloadController::class)->except(['create', 'show', 'edit']);

    // ── Incoming Webhooks (Admin Settings) ────────────────────────
    Route::get('settings/incoming-webhooks', [IncomingWebhooksController::class, 'index'])->name('settings.incoming-webhooks.index');
    Route::get('settings/incoming-webhooks/{id}', [IncomingWebhooksController::class, 'show'])->name('settings.incoming-webhooks.show');

    // ── Security & Rate Limits (Admin Settings) ───────────────────
    Route::get('settings/security', [SecurityController::class, 'index'])->name('settings.security.index');
    Route::delete('settings/security/unblock-ip/{id}', [SecurityController::class, 'unblockIp'])->name('settings.security.unblock-ip');
    Route::post('settings/security/rate-limits', [SecurityController::class, 'storeRateLimit'])->name('settings.security.rate-limits.store');
    Route::delete('settings/security/rate-limits/{id}', [SecurityController::class, 'deleteRateLimit'])->name('settings.security.rate-limits.destroy');

    // ── User Management (Full Admin Control) ────────────────────────
    // Recovered from old project: Admin/UsersController
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
    Route::get('/users/bulk-create', [UsersController::class, 'bulkCreate'])->name('users.bulk-create');
    Route::post('/users/bulk-create', [UsersController::class, 'bulkStore'])->name('users.bulk-store');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::get('/users/problematic', [UsersController::class, 'problematic'])->name('users.problematic');
    Route::get('/users/co-work', [UsersController::class, 'coWork'])->name('users.co-work');
    Route::get('/users/earning-analyze', [UsersController::class, 'earningAnalyze'])->name('users.earning-analyze');
    Route::get('/users/legacy-coworker/{id}', [UsersController::class, 'showLegacyCoWorker'])->name('users.legacy-coworker.show');
    Route::get('/users/legacy-coworker/{id}/edit', [UsersController::class, 'editLegacyCoWorker'])->name('users.legacy-coworker.edit');
    Route::put('/users/legacy-coworker/{id}', [UsersController::class, 'updateLegacyCoWorker'])->name('users.legacy-coworker.update');
    Route::delete('/users/legacy-coworker/{id}', [UsersController::class, 'deleteLegacyCoWorker'])->name('users.legacy-coworker.destroy');
    Route::post('/users/legacy-coworker/{id}/create-user', [UsersController::class, 'createUserFromCoWorker'])->name('users.legacy-coworker.create-user');
    Route::post('/users/legacy-coworker/{id}/reset-password', [UsersController::class, 'resetPasswordAndSendCredentialsForCoWorker'])->name('users.legacy-coworker.reset-password');

    // Matches exact old links
    Route::get('/users/{id}/login-as', [UsersController::class, 'loginAs'])->name('users.login-as');
    Route::post('/users/{id}/login-as', [UsersController::class, 'loginAs']); // Allows POST for React compatibility
    Route::get('/users/reset-password/{id}', [UsersController::class, 'reset_password'])->name('users.reset-password');
    Route::post('/users/{id}/reset-password', [UsersController::class, 'reset_password']); // Allows POST for React compatibility
    Route::post('/users/{id}/generate-password', [UsersController::class, 'reset_password'])->name('users.generate-password');
    Route::get('/users/{id}/referrals', [UsersController::class, 'referrals'])->name('users.referrals');
    Route::delete('/users/{user}/referrals/{referred_user}/unlink', [UsersController::class, 'unlink_referral'])->name('users.referrals.unlink');
    Route::get('/users/files/{id}', [UsersController::class, 'files'])->name('users.files');
    Route::get('/users/{id}/reports', [UsersController::class, 'reports'])->name('users.reports');
    Route::get('/users/{id}/projects', [UsersController::class, 'projects'])->name('users.projects');
    Route::get('/users/{id}/tasks/add', [UsersController::class, 'create_task'])->name('users.tasks.add');
    Route::post('/users/{id}/tasks/add', [UsersController::class, 'add_task'])->name('users.tasks.store');
    Route::get('/users/{id}/balance-sheet', [UsersController::class, 'balanceSheetPrint'])->name('users.balance-sheet');

    Route::get('/users/{id}', [UsersController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');

    Route::get('/users/{user}/merge', [App\Http\Controllers\Admin\UserMergeController::class, 'preview'])->name('users.merge.preview');
    Route::get('/users/{user}/merge-select', [App\Http\Controllers\Admin\UserMergeController::class, 'select'])->name('users.merge.select');
    Route::post('/users/{user}/merge/confirm', [App\Http\Controllers\Admin\UserMergeController::class, 'confirm'])->name('users.merge.confirm');

    // User email aliases
    Route::get   ('/users/{user}/emails',                          [App\Http\Controllers\Admin\UserEmailController::class, 'index'])->name('users.emails.index');
    Route::post  ('/users/{user}/emails',                          [App\Http\Controllers\Admin\UserEmailController::class, 'store'])->name('users.emails.store');
    Route::delete('/users/{user}/emails/{email}',                  [App\Http\Controllers\Admin\UserEmailController::class, 'destroy'])->name('users.emails.destroy');
    Route::post  ('/users/{user}/emails/{email}/verify',           [App\Http\Controllers\Admin\UserEmailController::class, 'verify'])->name('users.emails.verify');
    Route::post  ('/users/{user}/emails/{email}/make-primary',     [App\Http\Controllers\Admin\UserEmailController::class, 'makePrimary'])->name('users.emails.make-primary');

    // User Loans
    Route::post('/users/{user}/loans', [AdminUserLoanController::class, 'store'])->name('users.loans.store');
    Route::put('/users/{user}/loans/{loan}', [AdminUserLoanController::class, 'update'])->name('users.loans.update');
    Route::delete('/users/{user}/loans/{loan}', [AdminUserLoanController::class, 'destroy'])->name('users.loans.destroy');
    Route::post('/users/{user}/loans/{loan}/repayments', [AdminUserLoanController::class, 'storeRepayment'])->name('users.loans.repayments.store');

    Route::post('/users/{id}/toggle-block', [UsersController::class, 'toggleBlock'])->name('users.toggleBlock');
    Route::get('/users/{id}/subscriptions/create', [UsersController::class, 'createSubscription'])->name('users.subscriptions.create');
    Route::post('/users/{id}/membership', [UsersController::class, 'activateMembership'])->name('users.membership.activate');
    Route::put('/users/{id}/membership/{sub_id}', [UsersController::class, 'updateMembership'])->name('users.membership.update');
    Route::delete('/users/{id}/membership/{sub_id}', [UsersController::class, 'deleteMembership'])->name('users.membership.delete');
    Route::post('/users/{id}/update-role', [UsersController::class, 'updateRole'])->name('users.update-role');

    // ── Points Control ───────────────────────────────────────────────
    Route::get('/points_controller', [AdminPointsController::class, 'index'])->name('points.index');
    Route::get('/points_controller/{user}/add', [AdminPointsController::class, 'create'])->name('points.create');
    Route::post('/points_controller/{user}/adjust', [AdminPointsController::class, 'adjustPoints'])->name('points.adjust');
    Route::get('/points_controller/{user}/history', [AdminPointsController::class, 'history'])->name('points.history');

    // ── Admin Point Packages ───────────────────────────────────────
    Route::resource('point-packages', AdminPointPackageController::class)->except(['show']);

    // ── Admin Currencies ───────────────────────────────────────────
    Route::resource('currencies', AdminCurrencyController::class)->except(['show']);

    // ── Admin Currency Exchanges ───────────────────────────────────
    Route::resource('currency-exchanges', AdminCurrencyExchangeController::class)
        ->except(['show'])
        ->parameters(['currency-exchanges' => 'currencyExchange']);

    // ── Charity Counter ──────────────────────────────────────────────
    Route::get('/charity-counter', [CharityCounterController::class, 'index'])->name('charity-counter.index');
    Route::post('/charity-counter/add-amount', [CharityCounterController::class, 'addAmount'])->name('charity-counter.add-amount');
    Route::post('/charity-counter/subtract-amount', [CharityCounterController::class, 'subtractAmount'])->name('charity-counter.subtract-amount');

    // ── User Notes ───────────────────────────────────────────────────
    // Recovered from old project: Admin/UserNotesController
    Route::get('/users/{userId}/notes/json', [UserNoteController::class, 'indexJson'])->name('users.notes.json');
    Route::get('/users/{userId}/notes', [UserNoteController::class, 'index'])->name('users.notes.index');
    Route::post('/users/{userId}/notes', [UserNoteController::class, 'store'])->name('users.notes.store');
    Route::put('/users/{userId}/notes/{noteId}', [UserNoteController::class, 'update'])->name('users.notes.update');
    Route::delete('/users/{userId}/notes/{noteId}', [UserNoteController::class, 'destroy'])->name('users.notes.destroy');
    Route::post('/users/{userId}/notes/{noteId}/archive', [UserNoteController::class, 'archive'])->name('users.notes.archive');
    Route::post('/users/{userId}/notes/{noteId}/unarchive', [UserNoteController::class, 'unarchive'])->name('users.notes.unarchive');
    Route::post('/users/{userId}/notes/{noteId}/toggle-pin', [UserNoteController::class, 'togglePin'])->name('users.notes.togglePin');
    Route::post('/users/{userId}/notes/{noteId}/reveal', [UserNoteController::class, 'reveal'])->name('users.notes.reveal');
    Route::post('/users/{userId}/notes/bulk', [UserNoteController::class, 'bulkAction'])->name('users.notes.bulk');

    // ── User Files ───────────────────────────────────────────────────
    // Recovered from old project: Admin/FileController
    Route::get('/users/{userId}/files', [UserFileController::class, 'index'])->name('users.files.index');
    Route::post('/users/{userId}/files/upload', [UserFileController::class, 'upload'])->name('users.files.upload');
    Route::post('/users/{userId}/files/folder', [UserFileController::class, 'newFolder'])->name('users.files.folder');
    Route::get('/users/{userId}/files/download', [UserFileController::class, 'download'])->name('users.files.download');
    Route::get('/users/{userId}/files/edit', [UserFileController::class, 'edit'])->name('users.files.edit');
    Route::post('/users/{userId}/files/edit', [UserFileController::class, 'updateContent'])->name('users.files.updateContent');
    Route::post('/users/{userId}/files/rename', [UserFileController::class, 'rename'])->name('users.files.rename');
    Route::post('/users/{userId}/files/move', [UserFileController::class, 'move'])->name('users.files.move');
    Route::delete('/users/{userId}/files', [UserFileController::class, 'delete'])->name('users.files.delete');

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
    Route::get('/serial-softwares', [SerialSoftwareController::class, 'index'])->name('serial-softwares.index');
    Route::get('/serial-softwares/export', [SerialSoftwareController::class, 'export'])->name('serial-softwares.export');
    Route::post('/serial-softwares', [SerialSoftwareController::class, 'store'])->name('serial-softwares.store');
    Route::patch('/serial-softwares/{serialSoftware}/status', [SerialSoftwareController::class, 'updateStatus'])->name('serial-softwares.status');
    Route::delete('/serial-softwares/{serialSoftware}', [SerialSoftwareController::class, 'destroy'])->name('serial-softwares.destroy');

    // Device registry (auto-created by API check-in, admin manages status)
    Route::get('/serial-devices', [SerialDeviceController::class, 'index'])->name('serial-devices.index');
    Route::get('/serial-devices/export', [SerialDeviceController::class, 'export'])->name('serial-devices.export');
    Route::post('/serial-devices/bulk-status', [SerialDeviceController::class, 'bulkUpdateStatus'])->name('serial-devices.bulk-status');
    Route::post('/serial-devices/bulk-delete', [SerialDeviceController::class, 'bulkDelete'])->name('serial-devices.bulk-delete');
    Route::patch('/serial-devices/{serialDevice}/status', [SerialDeviceController::class, 'updateStatus'])->name('serial-devices.status');
    Route::delete('/serial-devices/{serialDevice}', [SerialDeviceController::class, 'destroy'])->name('serial-devices.destroy');
    Route::post('/serial-devices/{serialDevice}/assign-user', [SerialDeviceController::class, 'assignUser'])->name('serial-devices.assign-user');

    // User-Device assignments (admin maps device → user)
    Route::get('/serial-user-devices', [SerialUserDeviceController::class, 'index'])->name('serial-user-devices.index');
    Route::get('/serial-user-devices/by-user', [SerialUserDeviceController::class, 'byUser'])->name('serial-user-devices.by-user');
    Route::get('/serial-user-devices/assign', [SerialUserDeviceController::class, 'assign'])->name('serial-user-devices.assign');
    Route::post('/serial-user-devices', [SerialUserDeviceController::class, 'store'])->name('serial-user-devices.store');
    Route::patch('/serial-user-devices/{serialUserDevice}/status', [SerialUserDeviceController::class, 'updateStatus'])->name('serial-user-devices.status');
    Route::patch('/serial-user-devices/users/{user}/status', [SerialUserDeviceController::class, 'updateUserStatus'])->name('serial-user-devices.update-user-status');
    Route::patch('/serial-user-devices/users/{user}/temp-valid', [SerialUserDeviceController::class, 'updateUserTempValid'])->name('serial-user-devices.update-user-temp-valid');
    Route::delete('/serial-user-devices/{serialUserDevice}', [SerialUserDeviceController::class, 'destroy'])->name('serial-user-devices.destroy');

    // Admin Tasks List (platform checklist items)
    Route::get('/tasks/as_list', [AdminTaskController::class, 'asList'])->name('tasks.as_list');
    Route::get('/tasks/board-explorer', [AdminTaskController::class, 'boardExplorer'])->name('tasks.board-explorer');
    Route::get('/tasks/as_list/export', [AdminTaskController::class, 'exportAsList'])->name('tasks.as_list.export');
    Route::post('/tasks/todos/bulk-complete', [AdminTaskController::class, 'bulkCompleteTodos'])->name('tasks.todos.bulk-complete');
    Route::post('/tasks/todos/{todo}/complete', [AdminTaskController::class, 'completeTodo'])->name('tasks.todos.complete');
    Route::get('/tasks/calendar', [AdminTaskController::class, 'calendar'])->name('tasks.calendar');
    Route::post('/tasks/calendar/store-and-bill', [AdminTaskController::class, 'storeAndBillCalendarTodo'])->name('tasks.calendar.store-and-bill');
    Route::get('/tasks/client-tasks', [AdminTaskController::class, 'clientTasks'])->name('tasks.client-tasks');
    Route::post('/tasks/client-tasks/{client}/todos', [AdminTaskController::class, 'storeClientTodo'])->name('tasks.client-tasks.store');
    Route::post('/tasks/client-tasks/{client}/todos-unpaid', [AdminTaskController::class, 'storeUnpaidTodo'])->name('tasks.client-tasks.store-unpaid');
    Route::post('/tasks/todos/{todo}/pay-schedule', [AdminTaskController::class, 'payAndScheduleTodo'])->name('tasks.todos.pay-schedule');
    Route::delete('/tasks/todos/{todo}', [AdminTaskController::class, 'destroyTodo'])->name('tasks.todos.destroy');
    Route::post('/tasks/todos/{todo}/refund', [AdminTaskController::class, 'refundTodo'])->name('tasks.todos.refund');
    Route::post('/tasks/todos/{todo}/schedule', [AdminTaskController::class, 'scheduleTodo'])->name('tasks.todos.schedule');
    Route::put('/tasks/todos/{todo}', [AdminTaskController::class, 'updateTodo'])->name('tasks.todos.update');

    Route::get('/erp/{id}/impersonate', [ImpersonateController::class, 'impersonate'])->name('erp.impersonate');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/stop-impersonate', [ImpersonateController::class, 'stopImpersonating'])->name('admin.stop-impersonate');
});

// Public SaaS Routes
Route::post('/subscriptions/calculate-custom', [SubscriptionController::class, 'calculateCustomPrice'])->name('subscriptions.calculate-custom');

// SaaS Subscription & Billing Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Points System
    Route::get('/points', [PointPurchaseController::class, 'index'])->name('points.index');
    Route::post('/points/purchase', [PointPurchaseController::class, 'store'])->name('point-purchases.store');
    Route::post('/points/purchase-wallet', [PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    Route::get('/points/kashier/success', [PointPurchaseController::class, 'success'])->name('points.kashier.success');
    Route::get('/points/kashier/failure', [PointPurchaseController::class, 'failure'])->name('points.kashier.failure');

    Route::redirect('/contracts', '/isaas/contracts');

    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/subscribe-custom', [SubscriptionController::class, 'subscribeCustom'])->name('subscriptions.subscribe-custom');
    Route::get('/subscriptions/manage', [SubscriptionController::class, 'manage'])->name('subscriptions.manage');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/kashier/checkout', [SubscriptionController::class, 'checkoutKashier'])->name('subscriptions.kashier.checkout');
    Route::get('/subscriptions/kashier/success', [SubscriptionController::class, 'kashierSuccess'])->name('subscriptions.kashier.success');
    Route::get('/subscriptions/kashier/failure', [SubscriptionController::class, 'kashierFailure'])->name('subscriptions.kashier.failure');
});

require __DIR__.'/auth.php';

// Guest Support Ticket Routes
Route::post('/support/guest-tickets', [SupportTicketController::class, 'guestStore'])->name('tickets.guest.store');

// Support Ticket Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/resolve', [SupportTicketController::class, 'resolve'])->name('tickets.resolve');
});

// KYC Routes
Route::middleware(['auth', 'verified'])->prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [KycController::class, 'index'])->name('index');
    Route::post('/upload', [KycController::class, 'uploadDocument'])->name('upload');
    Route::post('/submit', [KycController::class, 'submit'])->name('submit');
    Route::delete('/{id}', [KycController::class, 'deleteDocument'])->name('delete');
    Route::get('/{id}/download', [KycController::class, 'downloadDocument'])->name('download');
});

// Core Financial Routes
Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
    Route::get('/transactions', [FinancialController::class, 'transactions'])->name('transactions');
    Route::get('/withdrawals', [FinancialController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals', [FinancialController::class, 'requestWithdrawal'])->name('withdrawals.store');
    Route::get('/payout-methods', [PayoutMethodController::class, 'index'])->name('payout-methods.index');
    Route::post('/payout-methods', [PayoutMethodController::class, 'store'])->name('payout-methods.store');
    Route::patch('/payout-methods/{payout_method}', [PayoutMethodController::class, 'update'])->name('payout-methods.update');
    Route::delete('/payout-methods/{payout_method}', [PayoutMethodController::class, 'destroy'])->name('payout-methods.destroy');

    Route::get('/add-balance', [FinancialController::class, 'addBalance'])->name('add-balance');
    Route::post('/add-balance/kashier', [FinancialController::class, 'depositKashier'])->name('add-balance.kashier');
    Route::get('/add-balance/success', [FinancialController::class, 'success'])->name('add-balance.success');
    Route::get('/add-balance/failure', [FinancialController::class, 'failure'])->name('add-balance.failure');

    // P2P Wallet Transfer Routes
    Route::get('/transfer', [WalletTransferController::class, 'create'])->name('transfer.create');

    Route::get('/messages', function () {
        return Inertia::render('Client/Messages/Index');
    })->name('messages.index');

    Route::get('/notifications', function () {
        return Inertia::render('Client/Notifications/Index');
    })->name('notifications.index');

    Route::get('/transfer-api/calculate-fee', [WalletTransferController::class, 'calculateFee'])->name('transfer.calculate-fee');
    Route::get('/transfer-api/search-users', [WalletTransferController::class, 'searchUsers'])->name('transfer.search-users');
    Route::post('/transfer', [WalletTransferController::class, 'store'])->name('transfer.store');
    Route::get('/transfer/history', [WalletTransferController::class, 'history'])->name('transfer.history');
    Route::get('/transfer/{id}', [WalletTransferController::class, 'show'])->name('transfer.show');
});

// iSAAS Connected Apps Routes
Route::middleware(['auth', 'verified'])->prefix('isaas')->name('isaas.')->group(function () {
    // Project Proposals (AI Estimator)
    Route::get('/proposals', [ProjectProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [ProjectProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals/estimate', [ProjectProposalController::class, 'estimate'])->name('proposals.estimate');
    Route::get('/proposals/{id}', [ProjectProposalController::class, 'show'])->name('proposals.show');
    Route::put('/proposals/{id}', [ProjectProposalController::class, 'update'])->name('proposals.update');
    Route::post('/proposals/{id}/convert', [ProjectProposalController::class, 'convert'])->name('proposals.convert');
    Route::delete('/proposals/{id}', [ProjectProposalController::class, 'destroy'])->name('proposals.destroy');

    // Contracts
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/search-users', [ContractController::class, 'searchUsers'])->name('contracts.search-users');
    Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
    Route::post('/contracts/{contract}/status', [ContractController::class, 'updateStatus'])->name('contracts.update-status');
    Route::post('/contracts/ai-generate', [ContractController::class, 'aiGenerate'])->name('contracts.ai.generate');
    Route::post('/contracts/ai-review', [ContractController::class, 'aiReview'])->name('contracts.ai.review');
});

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/read', [ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'storeMessage']);
});

// ── App Invoice Redirect Route ─────────────────
Route::get('/app/invoices/{invoice}', [InvoiceController::class, 'redirectAppInvoice'])->name('invoices.view-app');

// ── Guest Invoice Pay ─────────────────────────
Route::get('/guest/invoices/{invoice}', [GuestInvoiceController::class, 'show'])->name('guest.invoices.show')->middleware('signed');
Route::post('/guest/invoices/{invoice}/pay', [GuestInvoiceController::class, 'initiatePay'])->name('guest.invoices.pay')->middleware('signed');
Route::get('/guest/invoices/payment/success', [GuestInvoiceController::class, 'paymentSuccess'])->name('guest.invoices.payment.success');
Route::get('/guest/invoices/payment/failure', [GuestInvoiceController::class, 'paymentFailure'])->name('guest.invoices.payment.failure');
Route::post('/guest/invoices/payment/webhook', [GuestInvoiceController::class, 'paymentWebhook'])->name('guest.invoices.payment.webhook');

// Kashier Webhook (No Auth required)
Route::post('/financial/add-balance/webhook', [FinancialController::class, 'webhook'])->name('financial.add-balance.webhook');
Route::post('/subscriptions/kashier/webhook', [SubscriptionController::class, 'webhook'])->name('subscriptions.kashier.webhook');
Route::post('/points/kashier/webhook', [PointPurchaseController::class, 'webhook'])->name('points.kashier.webhook');

// ── Public Client Portal (No Auth Required) ──
Route::prefix('c')->name('client-portal.')->group(function () {
    Route::get('/contracts/{uuid}', [ClientPortalController::class, 'showContract'])->name('contracts.show');
    Route::post('/contracts/{uuid}/sign', [ClientPortalController::class, 'signContract'])->name('contracts.sign');
});

// General Messages Route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages/direct', [MessagesController::class, 'storeDirectMessage'])->name('messages.direct.store');
});

// Global Search
Route::middleware(['auth', 'verified'])->get('/search', [SearchController::class, 'index'])->name('search');

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    // Route::get('/conversations/{id}', [\App\Http\Controllers\ConversationController::class, 'show']);
    // Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'messages']);
    // Route::post('/conversations/{id}/read', [\App\Http\Controllers\ConversationController::class, 'markAsRead']);
    // Route::post('/conversations/{id}/messages', [\App\Http\Controllers\MessageController::class, 'store']);

    // Admin Notes
    Route::get('/admin-notes', [AdminNoteController::class, 'index']);
    Route::post('/admin-notes', [AdminNoteController::class, 'store']);
    Route::patch('/admin-notes/{note}/pin', [AdminNoteController::class, 'togglePin']);
    Route::delete('/admin-notes/{note}', [AdminNoteController::class, 'destroy']);
});

// ── Activity Engine ───────────────────────────────────────────────────────────
// The heartbeat of the iSAAS ecosystem. Full-page operational activity log.
Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');
});

// ── Guest Payment Links ────────────────────────────────────────────────────────
Route::prefix('pay')->name('guest.payment-links.')->group(function () {
    Route::get('/{uuid}', [GuestPaymentLinkController::class, 'show'])->name('show');
    Route::post('/{uuid}/initiate', [GuestPaymentLinkController::class, 'initiatePay'])->name('pay');

    Route::get('/success', [GuestPaymentLinkController::class, 'paymentSuccess'])->name('success');
    Route::get('/failure', [GuestPaymentLinkController::class, 'paymentFailure'])->name('failure');
    Route::post('/webhook', [GuestPaymentLinkController::class, 'paymentWebhook'])->name('webhook')->withoutMiddleware([VerifyCsrfToken::class]);
});

require __DIR__.'/auth.php';

// ── Google Socialite Login ───────────────────────────────────────────
Route::get('/auth/google/redirect', [SocialLoginController::class, 'redirect'])->name('social.google.redirect');
Route::get('/auth/google/callback', [SocialLoginController::class, 'callback'])->name('social.google.callback');

Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
    Route::get('/background-tasks', [BackgroundTaskController::class, 'index'])->name('background-tasks.index');
    Route::get('/background-tasks/{backgroundTask}', [BackgroundTaskController::class, 'show'])->name('background-tasks.show');
});

// ── Admin Accountant & Finance Routes ─────────────────────────────
Route::middleware(['auth', 'verified', 'onboarding', 'accountant'])->prefix('admin')->name('admin.')->group(function () {
    // ── Admin Invoices (Platform Billing) ─────────────────────────
    Route::get('/invoices', [App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/unpaid', [App\Http\Controllers\Admin\InvoiceController::class, 'unpaid'])->name('invoices.unpaid');
    Route::get('/invoices/dues', [App\Http\Controllers\Admin\InvoiceController::class, 'duesBoard'])->name('invoices.dues');
    Route::post('/invoices/dues/{user}/send-reminder', [App\Http\Controllers\Admin\InvoiceController::class, 'sendDuesReminder'])->name('invoices.dues.send-reminder');
    Route::get('/invoices/archive', [App\Http\Controllers\Admin\InvoiceController::class, 'archive'])->name('invoices.archive');
    Route::get('/invoices/create', [App\Http\Controllers\Admin\InvoiceController::class, 'create'])->name('invoices.create');
    Route::get('/invoices/timer-details/{item_id}', [App\Http\Controllers\Admin\InvoiceController::class, 'timerDetails'])->name('invoices.timer-details');
    Route::post('/invoices/timer-details/{item_id}/store', [App\Http\Controllers\Admin\InvoiceController::class, 'storeTimerDetails'])->name('invoices.timer-details.store');
    Route::delete('/invoices/timer-details/{item_id}/{timer_id}', [App\Http\Controllers\Admin\InvoiceController::class, 'destroyTimerDetails'])->name('invoices.timer-details.destroy');
    Route::post('/invoices/{invoice}/create-timer', [App\Http\Controllers\Admin\InvoiceController::class, 'createTimerItem'])->name('invoices.create-timer');
    Route::post('/invoices/bulk-action', [App\Http\Controllers\Admin\InvoiceController::class, 'bulkAction'])->name('invoices.bulk-action');
    Route::get('/invoices/{invoice}', [App\Http\Controllers\Admin\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/linked-transactions', [App\Http\Controllers\Admin\InvoiceController::class, 'linkedTransactions'])->name('invoices.linked-transactions');
    Route::get('/invoices/{invoice}/download-pdf', [App\Http\Controllers\Admin\InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
    Route::get('/invoices/{invoice}/print-pdf', [App\Http\Controllers\Admin\InvoiceController::class, 'printPdf'])->name('invoices.print-pdf');

    // ── Admin Payouts ───────────────────────
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/create', [PayoutController::class, 'create'])->name('payouts.create');
    Route::get('/payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
    Route::put('/payouts/{payout}', [PayoutController::class, 'update'])->name('payouts.update');
    Route::delete('/payouts/{payout}', [PayoutController::class, 'destroy'])->name('payouts.destroy');
    Route::post('/payouts/{payout}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');

    Route::put('/invoices/{invoice}', [App\Http\Controllers\Admin\InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/mark-paid', [App\Http\Controllers\Admin\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/cancel', [App\Http\Controllers\Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/change-status', [App\Http\Controllers\Admin\InvoiceController::class, 'changeStatus'])->name('invoices.change-status');
    Route::post('/invoices/{invoice}/change-job-status', [App\Http\Controllers\Admin\InvoiceController::class, 'changeJobStatus'])->name('invoices.change-job-status');
    Route::post('/invoices/{invoice}/notify', [App\Http\Controllers\Admin\InvoiceController::class, 'notify'])->name('invoices.notify');
    Route::post('/invoices/{invoice}/partial-pay', [App\Http\Controllers\Admin\InvoiceController::class, 'partialPay'])->name('invoices.partial-pay');
    Route::post('/invoices/{invoice}/external-pay', [App\Http\Controllers\Admin\InvoiceController::class, 'externalPay'])->name('invoices.external-pay');
    Route::post('/invoices/{invoice}/cost-lines/{line}/record-paid', [App\Http\Controllers\Admin\InvoiceController::class, 'recordCostLinePaid'])->name('invoices.cost-lines.record-paid');
    Route::post('/invoices/{invoice}/pay-service/calculate', [App\Http\Controllers\Admin\InvoiceController::class, 'calculatePayService'])->name('invoices.pay-service.calculate');
    Route::post('/invoices/{invoice}/pay-service/store', [App\Http\Controllers\Admin\InvoiceController::class, 'storePayService'])->name('invoices.pay-service.store');
    Route::post('/invoices/{invoice}/share-link', [App\Http\Controllers\Admin\InvoiceController::class, 'shareLink'])->name('invoices.share-link');
    Route::post('/invoices/{invoice}/reschedule', [App\Http\Controllers\Admin\InvoiceController::class, 'reschedule'])->name('invoices.reschedule');
    Route::post('/invoices/{invoice}/assign-project', [App\Http\Controllers\Admin\InvoiceController::class, 'assignProject'])->name('invoices.assign-project');

    // ── Admin Invoice Board ───────────────────────────────────────
    Route::post('/invoices/{invoice}/board/notes', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'storeNote'])->name('invoices.board.store-note');
    Route::put('/invoices/{invoice}/board/notes/{note}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'updateNote'])->name('invoices.board.update-note');
    Route::delete('/invoices/{invoice}/board/notes/{note}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'destroyNote'])->name('invoices.board.destroy-note');
    Route::post('/invoices/{invoice}/board/tasks', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'storeTask'])->name('invoices.board.store-task');
    Route::put('/invoices/{invoice}/board/tasks/{task}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'updateTask'])->name('invoices.board.update-task');
    Route::delete('/invoices/{invoice}/board/tasks/{task}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'destroyTask'])->name('invoices.board.destroy-task');
    Route::post('/invoices/{invoice}/board/todos', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'storeTodo'])->name('invoices.board.store-todo');
    Route::put('/invoices/{invoice}/board/todos/{todo}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'updateTodo'])->name('invoices.board.update-todo');
    Route::delete('/invoices/{invoice}/board/todos/{todo}', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'destroyTodo'])->name('invoices.board.destroy-todo');
    Route::post('/invoices/{invoice}/board/move', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'moveCard'])->name('invoices.board.move-card');
    Route::post('/invoices/{invoice}/board/reorder', [App\Http\Controllers\Admin\AdminInvoiceBoardController::class, 'reorderCards'])->name('invoices.board.reorder-cards');

    // ── Platform Users Transactions ───────────────────────────────
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [AdminTransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [AdminTransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/transactions/{id}', [AdminTransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transactions/transfer', [AdminTransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::post('/transactions/transfer', [AdminTransactionController::class, 'start_transfer'])->name('transactions.start_transfer');
    Route::post('/project/current_timer', [AdminTransactionController::class, 'current_timer'])->name('project.current_timer');
    Route::post('/transactions/recalc-balance/{user_id}', [AdminTransactionController::class, 'regenerate'])->name('transactions.recalc-balance');

    // ── Third Party Integration Hooks ───────────────────────────────────────
    Route::resource('payment-links', PaymentLinkController::class)->except(['create', 'edit', 'show']);
    Route::put('payment-links/{paymentLink}/cancel', [PaymentLinkController::class, 'cancel'])->name('payment-links.cancel');
    Route::post('payment-links/{paymentLink}/mark-paid', [PaymentLinkController::class, 'markPaid'])->name('payment-links.mark-paid');
    Route::post('payment-links/bulk-destroy', [PaymentLinkController::class, 'bulkDestroy'])->name('payment-links.bulk-destroy');

    // ── Admin Financial Operations ────────────────────────────────
    Route::get('/finance', [FinancialOperationsController::class, 'index'])->name('finance.index');
    Route::get('/finance/export', [FinancialOperationsController::class, 'export'])->name('finance.report.export');
    Route::post('/finance', [FinancialOperationsController::class, 'store'])->name('finance.store');
    Route::put('/finance/{entry}', [FinancialOperationsController::class, 'update'])->name('finance.update');
    Route::delete('/finance/{entry}', [FinancialOperationsController::class, 'destroy'])->name('finance.destroy');
    Route::post('/finance/{entry}/mark-paid', [FinancialOperationsController::class, 'markAsPaid'])->name('finance.mark-paid');

    // Legacy Business Routes mapped to new BusinessController
    Route::prefix('business')->group(function () {
        Route::get('/income', [BusinessController::class, 'income'])->name('income.index');
        Route::get('/costs', [BusinessController::class, 'costs'])->name('costs.index');
        Route::get('/costs/export', [BusinessController::class, 'export_costs'])->name('costs.export');
        Route::post('/costs/bulk-delete', [BusinessController::class, 'bulk_delete_costs'])->name('costs.bulk_delete');
        Route::get('/costs/create', [BusinessController::class, 'create_cost'])->name('costs.create');
        Route::post('/costs', [BusinessController::class, 'store_cost'])->name('costs.store');
        Route::get('/costs/{id}', [BusinessController::class, 'show_cost'])->name('costs.show');
        Route::get('/costs/edit/{id}', [BusinessController::class, 'edit_cost'])->name('costs.edit');
        Route::put('/costs/{id}', [BusinessController::class, 'update_cost'])->name('costs.update');
        Route::delete('/costs/{id}/delete', [BusinessController::class, 'delete_cost'])->name('costs.delete');
        Route::post('/costs/{id}/restore', [BusinessController::class, 'restore_cost'])->name('costs.restore');
        Route::post('/costs/{id}/duplicate', [BusinessController::class, 'duplicate_cost'])->name('costs.duplicate');

        Route::delete('/income/{id}/delete', [BusinessController::class, 'delete_income'])->name('income.delete');
        Route::post('/income/{id}/reverse', [BusinessController::class, 'reverse_income'])->name('income.reverse');

        Route::get('/reports', [BusinessController::class, 'reports'])->name('business.reports');
        Route::get('/balance-report', [BusinessController::class, 'balance'])->name('reports.balance');
    });

    // Recurring Business Operations (Recovered from legacy project)
    Route::prefix('business/recurring')->group(function () {
        // Costs
        Route::get('costs', [RecurringBusinessController::class, 'recurring_costs'])->name('recurring_costs.index');
        Route::get('costs/create', [RecurringBusinessController::class, 'create_recurring_costs'])->name('recurring_costs.create');
        Route::post('costs', [RecurringBusinessController::class, 'store_recurring_costs'])->name('recurring_costs.store');
        Route::get('costs/edit/{id}', [RecurringBusinessController::class, 'edit_recurring_costs'])->name('recurring_costs.edit');
        Route::put('costs/{id}', [RecurringBusinessController::class, 'update_recurring_costs'])->name('recurring_costs.update');
        Route::get('costs/{id}', [RecurringBusinessController::class, 'recurring_costs_view'])->name('recurring_costs.view');
        Route::delete('costs/{id}/delete', [RecurringBusinessController::class, 'recurring_costs_delete'])->name('recurring_costs.delete');
        Route::delete('costs/{id}/delete-with-transaction', [RecurringBusinessController::class, 'recurring_costs_delete_with_transaction'])->name('recurring_costs.delete_with_transaction');
        Route::post('costs/{id}/toggle-status', [RecurringBusinessController::class, 'toggle_recurring_costs'])->name('recurring_costs.toggle');

        // Income
        Route::get('income', [RecurringBusinessController::class, 'recurring_income'])->name('recurring_income.index');
        Route::post('income', [RecurringBusinessController::class, 'store_recurring_income'])->name('recurring_income.store');
        Route::get('income/edit/{id}', [RecurringBusinessController::class, 'edit_recurring_income'])->name('recurring_income.edit');
        Route::put('income/{id}', [RecurringBusinessController::class, 'update_recurring_income'])->name('recurring_income.update');
        Route::get('income/{id}', [RecurringBusinessController::class, 'recurring_income_view'])->name('recurring_income.view');
        Route::delete('income/{id}/delete', [RecurringBusinessController::class, 'recurring_income_delete'])->name('recurring_income.delete');
        Route::delete('income/{id}/delete-with-transaction', [RecurringBusinessController::class, 'recurring_income_delete_with_transaction'])->name('recurring_income.delete_with_transaction');
        Route::post('income/{id}/toggle-status', [RecurringBusinessController::class, 'toggle_recurring_income'])->name('recurring_income.toggle');

        // Salaries
        Route::get('salaries', [RecurringBusinessController::class, 'recurring_salaries'])->name('recurring_salaries.index');
        Route::post('salaries', [RecurringBusinessController::class, 'store_recurring_salaries'])->name('recurring_salaries.store');
        Route::get('salaries/edit/{id}', [RecurringBusinessController::class, 'edit_recurring_salaries'])->name('recurring_salaries.edit');
        Route::put('salaries/{id}', [RecurringBusinessController::class, 'update_recurring_salaries'])->name('recurring_salaries.update');
        Route::get('salaries/{id}', [RecurringBusinessController::class, 'recurring_salaries_view'])->name('recurring_salaries.view');
        Route::delete('salaries/{id}/delete', [RecurringBusinessController::class, 'recurring_salaries_delete'])->name('recurring_salaries.delete');
        Route::post('salaries/{id}/toggle-status', [RecurringBusinessController::class, 'toggle_recurring_salaries'])->name('recurring_salaries.toggle');

        // Invoices
        Route::get('invoices', [RecurringInvoiceController::class, 'index'])->name('recurring_invoices.index');
        Route::post('invoices', [RecurringInvoiceController::class, 'store'])->name('recurring_invoices.store');
        Route::get('invoices/create', [RecurringInvoiceController::class, 'create'])->name('recurring_invoices.create');
        Route::get('invoices/edit/{id}', [RecurringInvoiceController::class, 'edit'])->name('recurring_invoices.edit');
        Route::put('invoices/{id}', [RecurringInvoiceController::class, 'update'])->name('recurring_invoices.update');
        Route::get('invoices/{id}', [RecurringInvoiceController::class, 'view'])->name('recurring_invoices.view');
        Route::delete('invoices/{id}/delete', [RecurringInvoiceController::class, 'delete'])->name('recurring_invoices.delete');
        Route::post('invoices/{id}/toggle-status', [RecurringInvoiceController::class, 'toggle'])->name('recurring_invoices.toggle');
        Route::delete('invoices/{invoice}/records/{record}', [RecurringInvoiceController::class, 'deleteRecord'])->name('recurring_invoices.records.delete');

        // Notices — managed inline from the Board page (no admin CRUD pages).
        Route::get('notices', [RecurringNoticeController::class, 'index'])->name('recurring_notices.index');
        Route::get('notices/json', [RecurringNoticeController::class, 'index'])->name('recurring_notices.json');
        Route::post('notices', [RecurringNoticeController::class, 'store'])->name('recurring_notices.store');
        Route::get('notices/{id}', [RecurringNoticeController::class, 'show'])->name('recurring_notices.show');
        Route::put('notices/{id}', [RecurringNoticeController::class, 'update'])->name('recurring_notices.update');
        Route::delete('notices/{id}/delete', [RecurringNoticeController::class, 'destroy'])->name('recurring_notices.delete');
        Route::post('notices/{id}/toggle-status', [RecurringNoticeController::class, 'toggle'])->name('recurring_notices.toggle');
    });

    // ── Admin Hours Calendar ──────────────────────────────────────
    Route::get('/hours-calendar', [HoursCalendarController::class, 'index'])->name('hours-calendar.index');
    Route::post('/hours-calendar/data', [HoursCalendarController::class, 'getData'])->name('hours-calendar.data');

    Route::resource('vouchers', AdminVoucherController::class);

    Route::resource('coupons', AdminCouponController::class);

    Route::resource('payment-methods', AdminPaymentMethodController::class)->only(['index', 'show', 'update']);

    Route::resource('withdraw-requests', AdminWithdrawRequestController::class)->only(['index', 'show', 'update']);

});

Route::get('/sso/{system}', [SsoController::class, 'redirect'])->name('sso.redirect');
