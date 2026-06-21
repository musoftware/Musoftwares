# API Routes and External Endpoints Report

## 1. Overview

This report details the API routes, controllers, and external endpoints discovered in the codebase.

## 2. API Controllers

The following controllers handle application endpoints:

- `App\Http\Controllers\AdminNoteController`
- `App\Http\Controllers\Admin\AdminBlogArticleController`
- `App\Http\Controllers\Admin\AdminBusyTimesController`
- `App\Http\Controllers\Admin\AdminCouponController`
- `App\Http\Controllers\Admin\AdminFreeDownloadController`
- `App\Http\Controllers\Admin\AdminLanguageLineController`
- `App\Http\Controllers\Admin\AdminPaymentMethodController`
- `App\Http\Controllers\Admin\AdminPointPackageController`
- `App\Http\Controllers\Admin\AdminPointsController`
- `App\Http\Controllers\Admin\AdminQuotationController`
- `App\Http\Controllers\Admin\AdminSettingController`
- `App\Http\Controllers\Admin\AdminTaskController`
- `App\Http\Controllers\Admin\AdminTicketController`
- `App\Http\Controllers\Admin\AdminTransactionController`
- `App\Http\Controllers\Admin\AdminUserLoanController`
- `App\Http\Controllers\Admin\AdminVoucherController`
- `App\Http\Controllers\Admin\AdminWithdrawRequestController`
- `App\Http\Controllers\Admin\AiEstimatorController`
- `App\Http\Controllers\Admin\BroadcastNotificationController`
- `App\Http\Controllers\Admin\BusinessController`
- `App\Http\Controllers\Admin\CharityCounterController`
- `App\Http\Controllers\Admin\ContractAiController`
- `App\Http\Controllers\Admin\DashboardController`
- `App\Http\Controllers\Admin\EmployeeTodoController`
- `App\Http\Controllers\Admin\FinancialOperationsController`
- `App\Http\Controllers\Admin\FreelanceContractController`
- `App\Http\Controllers\Admin\FreelanceJobController`
- `App\Http\Controllers\Admin\FreelanceProfileController`
- `App\Http\Controllers\Admin\FreelanceProposalController`
- `App\Http\Controllers\Admin\FreelanceSkillController`
- `App\Http\Controllers\Admin\GoogleCalendarIntegrationController`
- `App\Http\Controllers\Admin\GuestTicketController`
- `App\Http\Controllers\Admin\HoursCalendarController`
- `App\Http\Controllers\Admin\IncomingWebhooksController`
- `App\Http\Controllers\Admin\InvoiceController`
- `App\Http\Controllers\Admin\KycController`
- `App\Http\Controllers\Admin\MarketplaceOrderController`
- `App\Http\Controllers\Admin\MarketplaceServiceController`
- `App\Http\Controllers\Admin\PaymentLinkController`
- `App\Http\Controllers\Admin\PlanController`
- `App\Http\Controllers\Admin\ProjectContractController`
- `App\Http\Controllers\Admin\ProjectController`
- `App\Http\Controllers\Admin\RecurringBusinessController`
- `App\Http\Controllers\Admin\ReportController`
- `App\Http\Controllers\Admin\SecurityController`
- `App\Http\Controllers\Admin\SerialDeviceController`
- `App\Http\Controllers\Admin\SerialSoftwareController`
- `App\Http\Controllers\Admin\SerialUserDeviceController`
- `App\Http\Controllers\Admin\Tools\AdminResellerController`
- `App\Http\Controllers\Admin\Tools\AdminToolController`
- `App\Http\Controllers\Admin\UserFileController`
- `App\Http\Controllers\Admin\UserNoteController`
- `App\Http\Controllers\Admin\UsersController`
- `App\Http\Controllers\Admin\WebsiteServiceController`
- `App\Http\Controllers\Api\MobileAuthController`
- `App\Http\Controllers\Api\SerialDeviceController`
- `App\Http\Controllers\Auth\AuthenticatedSessionController`
- `App\Http\Controllers\Auth\ConfirmablePasswordController`
- `App\Http\Controllers\Auth\EmailVerificationNotificationController`
- `App\Http\Controllers\Auth\EmailVerificationPromptController`
- `App\Http\Controllers\Auth\NewPasswordController`
- `App\Http\Controllers\Auth\PasswordController`
- `App\Http\Controllers\Auth\PasswordResetLinkController`
- `App\Http\Controllers\Auth\RegisteredUserController`
- `App\Http\Controllers\Auth\SocialLoginController`
- `App\Http\Controllers\Auth\VerifyEmailController`
- `App\Http\Controllers\AutomationRuleController`
- `App\Http\Controllers\BackgroundTaskController`
- `App\Http\Controllers\Billing\InvoiceController`
- `App\Http\Controllers\BlogController`
- `App\Http\Controllers\ConversationController`
- `App\Http\Controllers\DashboardController`
- `App\Http\Controllers\DeviceTokenController`
- `App\Http\Controllers\FinancialController`
- `App\Http\Controllers\Frontend\ClientContractController`
- `App\Http\Controllers\GuestInvoiceController`
- `App\Http\Controllers\GuestPaymentLinkController`
- `App\Http\Controllers\GuestTicketSubmissionController`
- `App\Http\Controllers\HomeController`
- `App\Http\Controllers\ImpersonateController`
- `App\Http\Controllers\Isaas\ProjectProposalController`
- `App\Http\Controllers\KycController`
- `App\Http\Controllers\MessagesController`
- `App\Http\Controllers\NotificationController`
- `App\Http\Controllers\OnboardingController`
- `App\Http\Controllers\PayoutMethodController`
- `App\Http\Controllers\PointPurchaseController`
- `App\Http\Controllers\ProfileController`
- `App\Http\Controllers\ReferralController`
- `App\Http\Controllers\RuntimeDownloadController`
- `App\Http\Controllers\SearchController`
- `App\Http\Controllers\SitemapController`
- `App\Http\Controllers\SubscriptionController`
- `App\Http\Controllers\SupportTicketController`
- `App\Http\Controllers\TenantBackupController`
- `App\Http\Controllers\TrackerController`
- `App\Http\Controllers\TrackingController`
- `App\Http\Controllers\VoucherController`
- `App\Http\Controllers\WalletTransferController`
- `App\Http\Controllers\WebhookController`
- `App\Http\Controllers\iSaaS\ClientPortalController`
- `App\Http\Controllers\iSaaS\ContractController`
- `Illuminate\Broadcasting\BroadcastController`
- `Illuminate\Routing\RedirectController`
- `Laravel\Sanctum\Http\Controllers\CsrfCookieController`
- `Modules\AffiliatePos\Http\Controllers\Web\PosController`
- `Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliateOrderController`
- `Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController`
- `Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController`
- `Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController`
- `Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController`
- `Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController`
- `Modules\AffiliatePos\app\Features\Storefront\Controllers\CheckoutController`
- `Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController`
- `Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController`
- `Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController`
- `Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController`
- `Modules\Booking\Http\Controllers\BookingController`
- `Modules\Booking\Http\Controllers\BookingDashboardController`
- `Modules\Booking\Http\Controllers\BookingEventController`
- `Modules\Booking\Http\Controllers\BookingExceptionController`
- `Modules\Booking\Http\Controllers\BookingProviderController`
- `Modules\Booking\Http\Controllers\BookingPublicApiController`
- `Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController`
- `Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController`
- `Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleCalendarSettingsController`
- `Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController`
- `Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController`
- `Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController`
- `Modules\Booking\app\Features\MultiBranch\Http\Controllers\BranchStaffController`
- `Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin\PublicPageSettingsController`
- `Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController`
- `Modules\Booking\app\Features\PublicBooking\Http\Controllers\BookingPageSettingsController`
- `Modules\Booking\app\Features\PublicBooking\Http\Controllers\PublicBookingPageController`
- `Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController`
- `Modules\Booking\app\Features\Reminders\Http\Controllers\WaReminderLogController`
- `Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController`
- `Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsSettingController`
- `Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsTemplateController`
- `Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController`
- `Modules\Booking\app\Features\WaReminders\Http\Controllers\WaTemplateController`
- `Modules\Booking\app\Features\WaReminders\Http\Controllers\WaWebhookController`
- `Modules\Booking\app\Features\WhiteLabel\Http\Controllers\AssetController`
- `Modules\Booking\app\Features\WhiteLabel\Http\Controllers\BrandingController`
- `Modules\Booking\app\Features\WhiteLabel\Http\Controllers\DomainController`
- `Modules\Booking\app\Features\Widget\Http\Controllers\BookingWidgetController`
- `Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController`
- `Modules\CRM\Http\Controllers\Api\KanbanController`
- `Modules\CRM\Http\Controllers\Api\SearchController`
- `Modules\CRM\Http\Controllers\Api\WebhookReceiveController`
- `Modules\CRM\Http\Controllers\CampaignController`
- `Modules\CRM\Http\Controllers\CrmTeamAuthController`
- `Modules\CRM\Http\Controllers\CrmTeamController`
- `Modules\CRM\Http\Controllers\CrmWidgetCaptureController`
- `Modules\CRM\Http\Controllers\CrmWidgetController`
- `Modules\CRM\Http\Controllers\CustomerController`
- `Modules\CRM\Http\Controllers\DashboardController`
- `Modules\CRM\Http\Controllers\LeadCaptureController`
- `Modules\CRM\Http\Controllers\LeadController`
- `Modules\CRM\Http\Controllers\LeadNoteController`
- `Modules\CRM\Http\Controllers\LeadTagController`
- `Modules\CRM\Http\Controllers\PipelineController`
- `Modules\CRM\Http\Controllers\ReportController`
- `Modules\CRM\Http\Controllers\SearchController`
- `Modules\CRM\Http\Controllers\SequenceController`
- `Modules\CRM\Http\Controllers\SettingController`
- `Modules\CRM\Http\Controllers\WorkspaceController`
- `Modules\Core\Http\Controllers\CoreController`
- `Modules\ERP\Http\Controllers\Admin\ERPAdminController`
- `Modules\ERP\Http\Controllers\BackupController`
- `Modules\ERP\Http\Controllers\ClientController`
- `Modules\ERP\Http\Controllers\ClientNoteController`
- `Modules\ERP\Http\Controllers\ContractController`
- `Modules\ERP\Http\Controllers\DebtController`
- `Modules\ERP\Http\Controllers\DebtTransactionController`
- `Modules\ERP\Http\Controllers\ERPDashboardController`
- `Modules\ERP\Http\Controllers\ExpenseController`
- `Modules\ERP\Http\Controllers\FileController`
- `Modules\ERP\Http\Controllers\InventoryController`
- `Modules\ERP\Http\Controllers\InvoiceController`
- `Modules\ERP\Http\Controllers\Manager\ApprovalController`
- `Modules\ERP\Http\Controllers\Manager\ReportController`
- `Modules\ERP\Http\Controllers\PaymentMethodController`
- `Modules\ERP\Http\Controllers\PayrollController`
- `Modules\ERP\Http\Controllers\PosController`
- `Modules\ERP\Http\Controllers\ProductCategoryController`
- `Modules\ERP\Http\Controllers\ProductController`
- `Modules\ERP\Http\Controllers\RecurringController`
- `Modules\ERP\Http\Controllers\ReferralController`
- `Modules\ERP\Http\Controllers\SmtpSettingController`
- `Modules\ERP\Http\Controllers\StorageProviderController`
- `Modules\ERP\Http\Controllers\Team\TeamAuthController`
- `Modules\ERP\Http\Controllers\Team\TeamMemberController`
- `Modules\ERP\Http\Controllers\Team\TeamPortalController`
- `Modules\ERP\Http\Controllers\TenantNoteController`
- `Modules\ERP\Http\Controllers\TicketController`
- `Modules\ERP\Http\Controllers\TimerSessionController`
- `Modules\ERP\Http\Controllers\TransactionController`
- `Modules\ERP\Http\Controllers\WalletController`
- `Modules\ERP\Http\Controllers\WithdrawalController`
- `Modules\ERP\app\Features\Calendar\Controllers\CalendarController`
- `Modules\ERP\app\Features\MultiBranch\Controllers\BranchController`
- `Modules\ERP\app\Features\MultiBranch\Controllers\BranchTransferController`
- `Modules\ERP\app\Features\Projects\Controllers\ProjectController`
- `Modules\ERP\app\Features\Tasks\Controllers\TaskController`
- `Modules\Fbmb\Http\Controllers\FbmbLookupController`
- `Modules\Freelance\Http\Controllers\Api\MobileApiController`
- `Modules\Freelance\Http\Controllers\ContractController`
- `Modules\Freelance\Http\Controllers\DashboardController`
- `Modules\Freelance\Http\Controllers\FreelanceJobController`
- `Modules\Freelance\Http\Controllers\ProfileController`
- `Modules\Freelance\Http\Controllers\ProposalController`
- `Modules\Freelance\Http\Controllers\PublicFreelancerController`
- `Modules\Freelance\Http\Controllers\PublicPageController`
- `Modules\Freelance\Http\Controllers\ReviewController`
- `Modules\Freelance\Http\Controllers\SettingsController`
- `Modules\Freelance\Http\Controllers\ShortcutNotificationController`
- `Modules\Freelance\Http\Controllers\SkillController`
- `Modules\Freelance\Http\Controllers\UserSkillController`
- `Modules\GoldSavers\Http\Controllers\AnalyticsController`
- `Modules\GoldSavers\Http\Controllers\DashboardController`
- `Modules\GoldSavers\Http\Controllers\MarketController`
- `Modules\GoldSavers\Http\Controllers\ReportsController`
- `Modules\GoldSavers\Http\Controllers\WalletController`
- `Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController`
- `Modules\Marketplace\Http\Controllers\DashboardController`
- `Modules\Marketplace\Http\Controllers\OrderMessageController`
- `Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController`
- `Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAnalyticsController`
- `Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController`
- `Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPagePublicController`
- `Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController`
- `Modules\Marketplace\Http\Controllers\ServiceCategoryController`
- `Modules\Marketplace\Http\Controllers\ServiceController`
- `Modules\Marketplace\Http\Controllers\ServiceOrderController`
- `Modules\Marketplace\Http\Controllers\ServicePackageController`
- `Modules\Marketplace\Http\Controllers\ServiceReviewController`
- `Modules\PasswordSync\app\Http\Controllers\PasswordSyncController`
- `Modules\PaymentGateway\Http\Controllers\Admin\AdminGatewayClientController`
- `Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController`
- `Modules\SmsPaymentGateway\Http\Controllers\Api\CheckoutSessionController`
- `Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController`
- `Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController`
- `Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController`
- `Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController`
- `Modules\SmsPaymentGateway\Http\Controllers\HostedCheckoutController`
- `Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController`
- `Modules\SmsPaymentGateway\Http\Controllers\WidgetController`
- `Modules\Tools\Http\Controllers\Api\AgentPluginController`
- `Modules\Tools\Http\Controllers\Api\AuthController`
- `Modules\Tools\Http\Controllers\Api\LicenseController`
- `Modules\Tools\Http\Controllers\Api\UpdateController`
- `Modules\Tools\Http\Controllers\DownloadController`
- `Modules\Tools\Http\Controllers\MarketplaceController`
- `Modules\Tools\Http\Controllers\ResellerPortalController`
- `Modules\Tools\Http\Controllers\RuntimeAuthController`
- `Modules\Tools\Http\Controllers\SubscriptionController`
- `Modules\Tools\Http\Controllers\WhatsAppController`
- `Modules\WebTools\Http\Controllers\Financial\CalculatorController`
- `Modules\WebTools\Http\Controllers\Financial\GoldIndicatorController`
- `Modules\WebTools\Http\Controllers\Financial\GoldSaverController`
- `Modules\WebTools\Http\Controllers\Financial\PayGuestController`
- `Modules\WebTools\Http\Controllers\Financial\PayoutUsdController`
- `Modules\WebTools\Http\Controllers\Financial\SmartPricingCalculatorController`
- `Modules\WebTools\Http\Controllers\Financial\WithdrawInstapayController`
- `Modules\WebTools\Http\Controllers\Utilities\CipherIdentifierController`
- `Modules\WebTools\Http\Controllers\Utilities\CoordinatesConverterController`
- `Modules\WebTools\Http\Controllers\Utilities\JsObfuscatorController`
- `Modules\WebTools\Http\Controllers\Utilities\MultipleCountdownTimerController`
- `Modules\WebTools\Http\Controllers\WebToolsController`
- `Modules\WrittenCoursesEngine\app\Http\Controllers\AdminCourseApiController`
- `Modules\WrittenCoursesEngine\app\Http\Controllers\CourseApiController`

## 3. External Endpoints & Webhooks

The following endpoints serve as external interfaces, webhooks, or callbacks:

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET|HEAD | `/admin/google-calendar/callback` | admin.google-calendar.callback | App\Http\Controllers\Admin\GoogleCalendarIntegrationController@callback |
| GET|HEAD | `/admin/settings/incoming-webhooks` | admin.settings.incoming-webhooks.index | App\Http\Controllers\Admin\IncomingWebhooksController@index |
| GET|HEAD | `/admin/settings/incoming-webhooks/{id}` | admin.settings.incoming-webhooks.show | App\Http\Controllers\Admin\IncomingWebhooksController@show |
| GET|HEAD | `/api/auto-sms/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/auto-sms/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/auto-sms/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/auto-sms/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/auto-sms/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| POST | `/api/crm/webhook` | api.crm.webhook.receive | Modules\CRM\Http\Controllers\Api\WebhookReceiveController@handle |
| GET|HEAD | `/api/payment-gateway/webhook/failure/{internalOrderId}` | api.payment-gateway.webhook.failure | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierFailure |
| POST | `/api/payment-gateway/webhook/kashier` | api.payment-gateway.webhook.kashier | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierWebhook |
| GET|HEAD | `/api/payment-gateway/webhook/success/{internalOrderId}` | api.payment-gateway.webhook.success | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierSuccess |
| GET|HEAD | `/api/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/sms-payment-gateway/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| GET|HEAD | `/api/v1/gcal/auth/callback` | api. | Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController@callback |
| GET|HEAD | `/api/v1/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/v1/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/v1/sms-payment-gateway/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/v1/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/v1/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| POST | `/api/wa-webhook` | api.booking.wa.webhook | Modules\Booking\app\Features\WaReminders\Http\Controllers\WaWebhookController@handle |
| POST | `/api/webhooks/incoming/{source}` | api.webhooks.incoming | App\Http\Controllers\WebhookController@handle |
| GET|HEAD | `/auth/google/callback` | social.google.callback | App\Http\Controllers\Auth\SocialLoginController@callback |
| POST | `/billing/invoices/payment/webhook` | billing.invoices.payment.webhook | App\Http\Controllers\Billing\InvoiceController@paymentWebhook |
| POST | `/booking/webhook/kashier` | booking.webhook.kashier | Modules\Booking\Http\Controllers\BookingController@kashierWebhook |
| POST | `/financial/add-balance/webhook` | financial.add-balance.webhook | App\Http\Controllers\FinancialController@webhook |
| POST | `/freelance/point-purchases/webhook` | freelance.point-purchases.webhook | App\Http\Controllers\PointPurchaseController@webhook |
| POST | `/guest/invoices/payment/webhook` | guest.invoices.payment.webhook | App\Http\Controllers\GuestInvoiceController@paymentWebhook |
| POST | `/pay/webhook` | guest.payment-links.webhook | App\Http\Controllers\GuestPaymentLinkController@paymentWebhook |
| POST | `/sms-payment-gateway/test-mode/send-webhook` | sms-payment-gateway.test-mode.send-webhook | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@sendTestWebhook |
| POST | `/sms-payment-gateway/webhook` | sms-payment-gateway.webhook.update | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@updateWebhook |
| POST | `/sms-payment-gateway/webhook/test` | sms-payment-gateway.webhook.test | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@testWebhook |
| DELETE | `/sms-payment-gateway/webhook/{id}` | sms-payment-gateway.webhook.delete | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@deleteWebhook |
| GET|HEAD | `/sms-payment-gateway/webhooks` | sms-payment-gateway.webhooks | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@webhooks |
| GET|HEAD | `/sms-payment-gateway/webhooks/failed` | sms-payment-gateway.webhooks.failed | Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController@failedWebhooks |
| POST | `/subscriptions/kashier/webhook` | subscriptions.kashier.webhook | App\Http\Controllers\SubscriptionController@webhook |

## 4. API Endpoints

The following are the identified API routes:

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET|HEAD | `/api/admin-notes` |  | App\Http\Controllers\AdminNoteController@index |
| POST | `/api/admin-notes` |  | App\Http\Controllers\AdminNoteController@store |
| DELETE | `/api/admin-notes/{note}` |  | App\Http\Controllers\AdminNoteController@destroy |
| PATCH | `/api/admin-notes/{note}/pin` |  | App\Http\Controllers\AdminNoteController@togglePin |
| POST | `/api/admin/written-courses/generate` |  | Modules\WrittenCoursesEngine\app\Http\Controllers\AdminCourseApiController@generateCourse |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/cart` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController@index |
| POST | `/api/api/v1/affiliate-pos/storefront/cart` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController@add |
| PUT | `/api/api/v1/affiliate-pos/storefront/cart/{itemId}` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController@update |
| DELETE | `/api/api/v1/affiliate-pos/storefront/cart/{itemId}` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController@remove |
| PATCH | `/api/api/v1/affiliate-pos/storefront/cart/{itemId}/commission` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController@updateCommission |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/categories` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController@categories |
| POST | `/api/api/v1/affiliate-pos/storefront/checkout` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\CheckoutController@process |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/governorates` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController@governorates |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/governorates/{governorate}/cities` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController@cities |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/products` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController@index |
| GET|HEAD | `/api/api/v1/affiliate-pos/storefront/products/{product}` | api. | Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController@show |
| POST | `/api/auth/send-otp` |  | App\Http\Controllers\Api\MobileAuthController@sendOtp |
| POST | `/api/auth/verify-otp` |  | App\Http\Controllers\Api\MobileAuthController@verifyOtp |
| GET|HEAD | `/api/auto-sms/allowed-senders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getAllowedSenders |
| POST | `/api/auto-sms/connect` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@connect |
| GET|HEAD | `/api/auto-sms/debug/empty-phone-numbers` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@debugEmptyPhoneNumbers |
| GET|HEAD | `/api/auto-sms/get-random-wallet` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getRandomWallet |
| POST | `/api/auto-sms/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrder |
| POST | `/api/auto-sms/macrodroid/{token}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveMacrodroidSms |
| GET|HEAD | `/api/auto-sms/orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@index |
| POST | `/api/auto-sms/orders/create` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@create |
| POST | `/api/auto-sms/orders/create-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@createOrder |
| GET|HEAD | `/api/auto-sms/orders/status/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getOrderStatus |
| GET|HEAD | `/api/auto-sms/orders/user-orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getUserOrders |
| POST | `/api/auto-sms/orders/verify-order/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyOrder |
| POST | `/api/auto-sms/orders/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyPayment |
| GET|HEAD | `/api/auto-sms/orders/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@show |
| POST | `/api/auto-sms/orders/{id}/cancel` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@cancel |
| POST | `/api/auto-sms/public/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrderPublic |
| POST | `/api/auto-sms/sms` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveSms |
| GET|HEAD | `/api/auto-sms/transactions` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@index |
| GET|HEAD | `/api/auto-sms/transactions/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@show |
| POST | `/api/auto-sms/update-device` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@updateDevice |
| POST | `/api/auto-sms/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyPayment |
| POST | `/api/auto-sms/verify-transaction` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyTransaction |
| GET|HEAD | `/api/auto-sms/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/auto-sms/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/auto-sms/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/auto-sms/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/auto-sms/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| GET|HEAD | `/api/background-tasks` | api.background-tasks.index | App\Http\Controllers\BackgroundTaskController@index |
| GET|HEAD | `/api/background-tasks/{backgroundTask}` | api.background-tasks.show | App\Http\Controllers\BackgroundTaskController@show |
| GET|HEAD | `/api/bing-daily-images` | api.bing-daily-images | Closure |
| GET|HEAD | `/api/booking/event-type/{id}/slots` | api.booking.slots | Modules\Booking\Http\Controllers\BookingPublicApiController@getSlots |
| POST | `/api/booking/lock` | api.booking.lock | Modules\Booking\Http\Controllers\BookingPublicApiController@acquireLock |
| GET|HEAD | `/api/conversations/{id}/messages` |  | App\Http\Controllers\ConversationController@messages |
| POST | `/api/conversations/{id}/messages` |  | App\Http\Controllers\ConversationController@storeMessage |
| POST | `/api/conversations/{id}/read` |  | App\Http\Controllers\ConversationController@markAsRead |
| POST | `/api/crm/webhook` | api.crm.webhook.receive | Modules\CRM\Http\Controllers\Api\WebhookReceiveController@handle |
| GET|HEAD | `/api/freelance/mobile/jobs` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@getJobs |
| POST | `/api/freelance/mobile/jobs` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@storeJob |
| GET|HEAD | `/api/freelance/mobile/jobs/my` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@getMyJobs |
| GET|HEAD | `/api/freelance/mobile/jobs/{id}` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@getJobDetail |
| GET|HEAD | `/api/freelance/mobile/negotiations` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@getNegotiations |
| POST | `/api/freelance/mobile/proposals/{id}/accept` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@acceptProposal |
| POST | `/api/freelance/mobile/proposals/{id}/negotiate` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@negotiateProposal |
| POST | `/api/freelance/mobile/proposals/{id}/reject` |  | Modules\Freelance\Http\Controllers\Api\MobileApiController@rejectProposal |
| GET|HEAD | `/api/freelance/shortcut/notifications` |  | Modules\Freelance\Http\Controllers\ShortcutNotificationController@fetch |
| POST | `/api/payment-gateway/initiate` | api.payment-gateway.initiate | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@initiate |
| GET|HEAD | `/api/payment-gateway/status/{orderId}` | api.payment-gateway.status | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@status |
| GET|HEAD | `/api/payment-gateway/webhook/failure/{internalOrderId}` | api.payment-gateway.webhook.failure | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierFailure |
| POST | `/api/payment-gateway/webhook/kashier` | api.payment-gateway.webhook.kashier | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierWebhook |
| GET|HEAD | `/api/payment-gateway/webhook/success/{internalOrderId}` | api.payment-gateway.webhook.success | Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController@kashierSuccess |
| POST | `/api/public/widgets/{uuid}/book` | api. | Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController@book |
| GET|HEAD | `/api/public/widgets/{uuid}/embed.js` | api. | Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController@embed |
| POST | `/api/public/widgets/{uuid}/view` | api. | Modules\Booking\app\Features\Widget\Http\Controllers\PublicWidgetController@view |
| POST | `/api/public/{slug}/book` | api.public.booking.book | Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController@book |
| GET|HEAD | `/api/public/{slug}/init` | api.public.booking.init | Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController@init |
| GET|HEAD | `/api/public/{slug}/slots` | api.public.booking.slots | Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public\PublicBookingFlowController@slots |
| GET|HEAD | `/api/runtime/plugins` | api.runtime.plugins | Closure |
| GET|HEAD | `/api/runtime/version` | api.runtime.version | Closure |
| POST | `/api/serial/device` |  | App\Http\Controllers\Api\SerialDeviceController@register |
| GET|HEAD | `/api/sms-payment-gateway/allowed-senders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getAllowedSenders |
| POST | `/api/sms-payment-gateway/connect` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@connect |
| GET|HEAD | `/api/sms-payment-gateway/debug/empty-phone-numbers` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@debugEmptyPhoneNumbers |
| GET|HEAD | `/api/sms-payment-gateway/get-random-wallet` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getRandomWallet |
| POST | `/api/sms-payment-gateway/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrder |
| POST | `/api/sms-payment-gateway/macrodroid/{token}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveMacrodroidSms |
| GET|HEAD | `/api/sms-payment-gateway/orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@index |
| POST | `/api/sms-payment-gateway/orders/create` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@create |
| POST | `/api/sms-payment-gateway/orders/create-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@createOrder |
| GET|HEAD | `/api/sms-payment-gateway/orders/status/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getOrderStatus |
| GET|HEAD | `/api/sms-payment-gateway/orders/user-orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getUserOrders |
| POST | `/api/sms-payment-gateway/orders/verify-order/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyOrder |
| POST | `/api/sms-payment-gateway/orders/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyPayment |
| GET|HEAD | `/api/sms-payment-gateway/orders/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@show |
| POST | `/api/sms-payment-gateway/orders/{id}/cancel` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@cancel |
| POST | `/api/sms-payment-gateway/public/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrderPublic |
| POST | `/api/sms-payment-gateway/sms` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveSms |
| GET|HEAD | `/api/sms-payment-gateway/transactions` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@index |
| GET|HEAD | `/api/sms-payment-gateway/transactions/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@show |
| POST | `/api/sms-payment-gateway/update-device` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@updateDevice |
| POST | `/api/sms-payment-gateway/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyPayment |
| POST | `/api/sms-payment-gateway/verify-transaction` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyTransaction |
| GET|HEAD | `/api/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/sms-payment-gateway/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| GET|HEAD | `/api/t/click/{payload}` | api.tracker.click | App\Http\Controllers\TrackerController@click |
| GET|HEAD | `/api/t/open/{payload}.gif` | api.tracker.pixel | App\Http\Controllers\TrackerController@pixel |
| GET|HEAD | `/api/t/unsubscribe/{payload}` | api.tracker.unsubscribe | App\Http\Controllers\TrackerController@unsubscribe |
| GET|HEAD | `/api/timer/{id}` |  | Modules\ERP\Http\Controllers\TimerSessionController@show |
| GET|HEAD | `/api/tools/agent/plugins` | api.tools.agent.plugins | Modules\Tools\Http\Controllers\Api\AgentPluginController@index |
| GET|HEAD | `/api/tools/agent/plugins/{slug}/download` | api.tools.plugin.download | Modules\Tools\Http\Controllers\Api\AgentPluginController@download |
| POST | `/api/tools/auth/login` | api.tools.auth.login | Modules\Tools\Http\Controllers\Api\AuthController@login |
| POST | `/api/tools/auth/logout` | api.tools.auth.logout | Modules\Tools\Http\Controllers\Api\AuthController@logout |
| GET|HEAD | `/api/tools/auth/me` | api.tools.auth.me | Modules\Tools\Http\Controllers\Api\AuthController@me |
| POST | `/api/tools/license/activate` | api.tools.license.activate | Modules\Tools\Http\Controllers\Api\LicenseController@activate |
| POST | `/api/tools/license/check` | api.tools.license.check | Modules\Tools\Http\Controllers\Api\LicenseController@check |
| POST | `/api/tools/license/heartbeat` | api.tools.license.heartbeat | Modules\Tools\Http\Controllers\Api\LicenseController@heartbeat |
| GET|HEAD | `/api/tools/{slug}/releases` | api.tools.releases | Modules\Tools\Http\Controllers\Api\UpdateController@releases |
| GET|HEAD | `/api/tools/{slug}/update-check` | api.tools.update.check | Modules\Tools\Http\Controllers\Api\UpdateController@check |
| POST | `/api/tracker/sync` | api.tracker.sync | App\Http\Controllers\TrackerController@sync |
| GET|HEAD | `/api/user` |  | App\Http\Controllers\Api\MobileAuthController@me |
| PUT | `/api/user/profile` |  | App\Http\Controllers\Api\MobileAuthController@updateProfile |
| GET|HEAD | `/api/v1/booking-page/settings` | api.booking.page.settings.show | Modules\Booking\app\Features\PublicBooking\Http\Controllers\BookingPageSettingsController@show |
| POST | `/api/v1/booking-page/settings` | api.booking.page.settings.update | Modules\Booking\app\Features\PublicBooking\Http\Controllers\BookingPageSettingsController@update |
| GET|HEAD | `/api/v1/booking-rules` | api.booking-rules.index | Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController@index |
| POST | `/api/v1/booking-rules` | api.booking-rules.store | Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController@store |
| GET|HEAD | `/api/v1/booking-rules/{booking_rule}` | api.booking-rules.show | Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController@show |
| PUT|PATCH | `/api/v1/booking-rules/{booking_rule}` | api.booking-rules.update | Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController@update |
| DELETE | `/api/v1/booking-rules/{booking_rule}` | api.booking-rules.destroy | Modules\Booking\app\Features\BookingRules\Controllers\BookingAdvancedRulesController@destroy |
| GET|HEAD | `/api/v1/bookings` | api.booking.index | Modules\Booking\Http\Controllers\BookingController@index |
| POST | `/api/v1/bookings` | api.booking.store | Modules\Booking\Http\Controllers\BookingController@store |
| GET|HEAD | `/api/v1/bookings/{booking}` | api.booking.show | Modules\Booking\Http\Controllers\BookingController@show |
| PUT|PATCH | `/api/v1/bookings/{booking}` | api.booking.update | Modules\Booking\Http\Controllers\BookingController@update |
| DELETE | `/api/v1/bookings/{booking}` | api.booking.destroy | Modules\Booking\Http\Controllers\BookingController@destroy |
| GET|HEAD | `/api/v1/branches` | api.booking.branches.index | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController@index |
| POST | `/api/v1/branches` | api.booking.branches.store | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController@store |
| GET|HEAD | `/api/v1/branches/{branch}` | api.booking.branches.show | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController@show |
| PUT|PATCH | `/api/v1/branches/{branch}` | api.booking.branches.update | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController@update |
| DELETE | `/api/v1/branches/{branch}` | api.booking.branches.destroy | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BookingBranchController@destroy |
| POST | `/api/v1/branches/{branch}/staff` | api.booking.branches.staff.store | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BranchStaffController@store |
| DELETE | `/api/v1/branches/{branch}/staff/{user}` | api.booking.branches.staff.destroy | Modules\Booking\app\Features\MultiBranch\Http\Controllers\BranchStaffController@destroy |
| GET|HEAD | `/api/v1/cores` | api.core.index | Modules\Core\Http\Controllers\CoreController@index |
| POST | `/api/v1/cores` | api.core.store | Modules\Core\Http\Controllers\CoreController@store |
| GET|HEAD | `/api/v1/cores/{core}` | api.core.show | Modules\Core\Http\Controllers\CoreController@show |
| PUT|PATCH | `/api/v1/cores/{core}` | api.core.update | Modules\Core\Http\Controllers\CoreController@update |
| DELETE | `/api/v1/cores/{core}` | api.core.destroy | Modules\Core\Http\Controllers\CoreController@destroy |
| GET|HEAD | `/api/v1/custom-domains` | api.booking.custom-domains.index | Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController@index |
| POST | `/api/v1/custom-domains` | api.booking.custom-domains.store | Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController@store |
| DELETE | `/api/v1/custom-domains/{id}` | api.booking.custom-domains.destroy | Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController@destroy |
| PUT | `/api/v1/custom-domains/{id}/primary` | api.booking.custom-domains.set-primary | Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController@setPrimary |
| POST | `/api/v1/custom-domains/{id}/verify` | api.booking.custom-domains.verify | Modules\Booking\app\Features\CustomDomains\Http\Controllers\BookingCustomDomainController@verify |
| GET|HEAD | `/api/v1/gcal/accounts` | api. | Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleCalendarSettingsController@index |
| POST | `/api/v1/gcal/accounts/{account}/calendars` | api. | Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleCalendarSettingsController@configureCalendar |
| GET|HEAD | `/api/v1/gcal/auth/callback` | api. | Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController@callback |
| GET|HEAD | `/api/v1/gcal/auth/redirect` | api. | Modules\Booking\app\Features\GcalSync\Http\Controllers\GoogleOAuthController@redirect |
| GET|HEAD | `/api/v1/group-sessions` | api.group-sessions.index | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@index |
| POST | `/api/v1/group-sessions` | api.group-sessions.store | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@store |
| GET|HEAD | `/api/v1/group-sessions/{group_session}` | api.group-sessions.show | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@show |
| PUT|PATCH | `/api/v1/group-sessions/{group_session}` | api.group-sessions.update | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@update |
| DELETE | `/api/v1/group-sessions/{group_session}` | api.group-sessions.destroy | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@destroy |
| POST | `/api/v1/group-sessions/{id}/cancel` | api. | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@cancel |
| POST | `/api/v1/group-sessions/{id}/join` | api. | Modules\Booking\app\Features\GroupSessions\Http\Controllers\GroupSessionController@join |
| GET|HEAD | `/api/v1/passwordsyncs` | api.passwordsync.index | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@index |
| POST | `/api/v1/passwordsyncs` | api.passwordsync.store | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@store |
| GET|HEAD | `/api/v1/passwordsyncs/{passwordsync}` | api.passwordsync.show | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@show |
| PUT|PATCH | `/api/v1/passwordsyncs/{passwordsync}` | api.passwordsync.update | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@update |
| DELETE | `/api/v1/passwordsyncs/{passwordsync}` | api.passwordsync.destroy | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@destroy |
| GET|HEAD | `/api/v1/public-page/settings` | api.booking.public-page.settings.index | Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin\PublicPageSettingsController@index |
| PUT | `/api/v1/public-page/settings` | api.booking.public-page.settings.update | Modules\Booking\app\Features\OnlinePage\Http\Controllers\Admin\PublicPageSettingsController@update |
| GET|HEAD | `/api/v1/public/booking-page/{slug}` | api.public.booking.page.show | Modules\Booking\app\Features\PublicBooking\Http\Controllers\PublicBookingPageController@show |
| GET|HEAD | `/api/v1/recurring-series` | api.recurring-series.index | Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController@index |
| POST | `/api/v1/recurring-series` | api.recurring-series.store | Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController@store |
| POST | `/api/v1/recurring-series/{id}/cancel` | api. | Modules\Booking\app\Features\Recurring\Http\Controllers\RecurringSeriesController@cancel |
| POST | `/api/v1/sms-gateway/checkout/sessions` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\CheckoutSessionController@create |
| GET|HEAD | `/api/v1/sms-gateway/checkout/sessions/{sessionId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\CheckoutSessionController@show |
| POST | `/api/v1/sms-gateway/checkout/sessions/{sessionId}/expire` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\CheckoutSessionController@expire |
| GET|HEAD | `/api/v1/sms-gateway/checkout/sessions/{sessionId}/poll` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\CheckoutSessionController@poll |
| GET|HEAD | `/api/v1/sms-payment-gateway/allowed-senders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getAllowedSenders |
| POST | `/api/v1/sms-payment-gateway/connect` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@connect |
| GET|HEAD | `/api/v1/sms-payment-gateway/debug/empty-phone-numbers` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@debugEmptyPhoneNumbers |
| GET|HEAD | `/api/v1/sms-payment-gateway/get-random-wallet` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@getRandomWallet |
| POST | `/api/v1/sms-payment-gateway/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrder |
| POST | `/api/v1/sms-payment-gateway/macrodroid/{token}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveMacrodroidSms |
| GET|HEAD | `/api/v1/sms-payment-gateway/orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@index |
| POST | `/api/v1/sms-payment-gateway/orders/create` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@create |
| POST | `/api/v1/sms-payment-gateway/orders/create-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@createOrder |
| GET|HEAD | `/api/v1/sms-payment-gateway/orders/status/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getOrderStatus |
| GET|HEAD | `/api/v1/sms-payment-gateway/orders/user-orders` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@getUserOrders |
| POST | `/api/v1/sms-payment-gateway/orders/verify-order/{orderId}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyOrder |
| POST | `/api/v1/sms-payment-gateway/orders/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@verifyPayment |
| GET|HEAD | `/api/v1/sms-payment-gateway/orders/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@show |
| POST | `/api/v1/sms-payment-gateway/orders/{id}/cancel` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController@cancel |
| POST | `/api/v1/sms-payment-gateway/public/link-order` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@linkOrderPublic |
| POST | `/api/v1/sms-payment-gateway/sms` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@receiveSms |
| GET|HEAD | `/api/v1/sms-payment-gateway/transactions` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@index |
| GET|HEAD | `/api/v1/sms-payment-gateway/transactions/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController@show |
| POST | `/api/v1/sms-payment-gateway/update-device` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@updateDevice |
| POST | `/api/v1/sms-payment-gateway/verify-payment` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyPayment |
| POST | `/api/v1/sms-payment-gateway/verify-transaction` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController@verifyTransaction |
| GET|HEAD | `/api/v1/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@show |
| POST | `/api/v1/sms-payment-gateway/webhooks` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@register |
| POST | `/api/v1/sms-payment-gateway/webhooks/test` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@test |
| PUT | `/api/v1/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@update |
| DELETE | `/api/v1/sms-payment-gateway/webhooks/{id}` | api. | Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController@destroy |
| GET|HEAD | `/api/v1/sms-settings` | api.sms-settings.index | Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsSettingController@index |
| POST | `/api/v1/sms-settings` | api.sms-settings.store | Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsSettingController@store |
| GET|HEAD | `/api/v1/sms-templates` | api.sms-templates.index | Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsTemplateController@index |
| POST | `/api/v1/sms-templates` | api.sms-templates.store | Modules\Booking\app\Features\SmsNotifications\Http\Controllers\SmsTemplateController@store |
| GET|HEAD | `/api/v1/team-members` | api.booking.team-members.index | Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController@index |
| POST | `/api/v1/team-members` | api.booking.team-members.store | Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController@store |
| GET|HEAD | `/api/v1/team-members/{team_member}` | api.booking.team-members.show | Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController@show |
| PUT|PATCH | `/api/v1/team-members/{team_member}` | api.booking.team-members.update | Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController@update |
| DELETE | `/api/v1/team-members/{team_member}` | api.booking.team-members.destroy | Modules\Booking\app\Features\TeamMembers\Http\Controllers\BookingTeamMemberController@destroy |
| GET|HEAD | `/api/v1/wa-reminders/limits` | api.booking.wa.limits | Modules\Booking\app\Features\Reminders\Http\Controllers\WaReminderLogController@getLimits |
| GET|HEAD | `/api/v1/wa-reminders/logs` | api.booking.wa.logs.index | Modules\Booking\app\Features\Reminders\Http\Controllers\WaReminderLogController@index |
| GET|HEAD | `/api/v1/wa-reminders/templates` | api.booking.wa.templates.index | Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController@index |
| POST | `/api/v1/wa-reminders/templates` | api.booking.wa.templates.store | Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController@store |
| GET|HEAD | `/api/v1/wa-reminders/templates/{template}` | api.booking.wa.templates.show | Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController@show |
| PUT|PATCH | `/api/v1/wa-reminders/templates/{template}` | api.booking.wa.templates.update | Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController@update |
| DELETE | `/api/v1/wa-reminders/templates/{template}` | api.booking.wa.templates.destroy | Modules\Booking\app\Features\Reminders\Http\Controllers\WaTemplateController@destroy |
| GET|HEAD | `/api/v1/wa-templates` | api.wa-templates.index | Modules\Booking\app\Features\WaReminders\Http\Controllers\WaTemplateController@index |
| POST | `/api/v1/wa-templates` | api.wa-templates.store | Modules\Booking\app\Features\WaReminders\Http\Controllers\WaTemplateController@store |
| GET|HEAD | `/api/v1/webtools` | api.webtools.index | Modules\WebTools\Http\Controllers\WebToolsController@index |
| POST | `/api/v1/webtools` | api.webtools.store | Modules\WebTools\Http\Controllers\WebToolsController@store |
| GET|HEAD | `/api/v1/webtools/{webtool}` | api.webtools.show | Modules\WebTools\Http\Controllers\WebToolsController@show |
| PUT|PATCH | `/api/v1/webtools/{webtool}` | api.webtools.update | Modules\WebTools\Http\Controllers\WebToolsController@update |
| DELETE | `/api/v1/webtools/{webtool}` | api.webtools.destroy | Modules\WebTools\Http\Controllers\WebToolsController@destroy |
| GET|HEAD | `/api/v1/white-label/assets` | api.assets.index | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\AssetController@index |
| POST | `/api/v1/white-label/assets` | api.assets.store | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\AssetController@store |
| DELETE | `/api/v1/white-label/assets/{asset}` | api.assets.destroy | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\AssetController@destroy |
| GET|HEAD | `/api/v1/white-label/domains` | api.domains.index | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\DomainController@index |
| POST | `/api/v1/white-label/domains` | api.domains.store | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\DomainController@store |
| DELETE | `/api/v1/white-label/domains/{domain}` | api.domains.destroy | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\DomainController@destroy |
| GET|HEAD | `/api/v1/white-label/settings` | api. | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\BrandingController@getSettings |
| PUT | `/api/v1/white-label/settings` | api. | Modules\Booking\app\Features\WhiteLabel\Http\Controllers\BrandingController@updateSettings |
| GET|HEAD | `/api/v1/widgets` | api.widgets.index | Modules\Booking\app\Features\Widget\Http\Controllers\BookingWidgetController@index |
| POST | `/api/v1/widgets` | api.widgets.store | Modules\Booking\app\Features\Widget\Http\Controllers\BookingWidgetController@store |
| GET|HEAD | `/api/vault/sync` | api. | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@getVault |
| POST | `/api/vault/sync` | api. | Modules\PasswordSync\app\Http\Controllers\PasswordSyncController@updateVault |
| POST | `/api/wa-webhook` | api.booking.wa.webhook | Modules\Booking\app\Features\WaReminders\Http\Controllers\WaWebhookController@handle |
| POST | `/api/webhooks/incoming/{source}` | api.webhooks.incoming | App\Http\Controllers\WebhookController@handle |
| GET|HEAD | `/api/whatsapp/accounts` | wa.accounts.index | Modules\Tools\Http\Controllers\WhatsAppController@listAccounts |
| POST | `/api/whatsapp/accounts` | wa.accounts.create | Modules\Tools\Http\Controllers\WhatsAppController@createAccount |
| DELETE | `/api/whatsapp/accounts/{accountId}` | wa.accounts.delete | Modules\Tools\Http\Controllers\WhatsAppController@deleteAccount |
| POST | `/api/whatsapp/accounts/{accountId}/connect` | wa.accounts.connect | Modules\Tools\Http\Controllers\WhatsAppController@connectAccount |
| POST | `/api/whatsapp/accounts/{accountId}/disconnect` | wa.accounts.disconnect | Modules\Tools\Http\Controllers\WhatsAppController@disconnectAccount |
| GET|HEAD | `/api/whatsapp/accounts/{accountId}/health` | wa.accounts.health | Modules\Tools\Http\Controllers\WhatsAppController@accountHealth |
| GET|HEAD | `/api/whatsapp/campaigns` | wa.campaigns.index | Modules\Tools\Http\Controllers\WhatsAppController@listCampaigns |
| POST | `/api/whatsapp/campaigns` | wa.campaigns.create | Modules\Tools\Http\Controllers\WhatsAppController@createCampaign |
| GET|HEAD | `/api/whatsapp/campaigns/{id}/analytics` | wa.campaigns.analytics | Modules\Tools\Http\Controllers\WhatsAppController@campaignAnalytics |
| POST | `/api/whatsapp/campaigns/{id}/pause` | wa.campaigns.pause | Modules\Tools\Http\Controllers\WhatsAppController@pauseCampaign |
| POST | `/api/whatsapp/campaigns/{id}/start` | wa.campaigns.start | Modules\Tools\Http\Controllers\WhatsAppController@startCampaign |
| GET|HEAD | `/api/whatsapp/contacts` | wa.contacts.index | Modules\Tools\Http\Controllers\WhatsAppController@listContacts |
| POST | `/api/whatsapp/contacts/import` | wa.contacts.import | Modules\Tools\Http\Controllers\WhatsAppController@importContacts |
| GET|HEAD | `/api/whatsapp/inbox` | wa.inbox.index | Modules\Tools\Http\Controllers\WhatsAppController@listConversations |
| GET|HEAD | `/api/whatsapp/inbox/{phone}` | wa.inbox.show | Modules\Tools\Http\Controllers\WhatsAppController@getConversation |
| PATCH | `/api/whatsapp/inbox/{phone}` | wa.inbox.update | Modules\Tools\Http\Controllers\WhatsAppController@updateConversation |
| POST | `/api/whatsapp/inbox/{phone}/reply` | wa.inbox.reply | Modules\Tools\Http\Controllers\WhatsAppController@sendReply |
| GET|HEAD | `/api/whatsapp/quality` | wa.quality | Modules\Tools\Http\Controllers\WhatsAppController@qualityDashboard |
| GET|HEAD | `/api/whatsapp/workflows` | wa.workflows.index | Modules\Tools\Http\Controllers\WhatsAppController@listWorkflows |
| POST | `/api/whatsapp/workflows` | wa.workflows.create | Modules\Tools\Http\Controllers\WhatsAppController@createWorkflow |
| GET|HEAD | `/api/written-courses` |  | Modules\WrittenCoursesEngine\app\Http\Controllers\CourseApiController@index |
| GET|HEAD | `/api/written-courses/{courseSlug}` |  | Modules\WrittenCoursesEngine\app\Http\Controllers\CourseApiController@show |
| GET|HEAD | `/api/written-courses/{courseSlug}/modules/{moduleSlug}/lessons/{lessonSlug}` |  | Modules\WrittenCoursesEngine\app\Http\Controllers\CourseApiController@getLesson |
