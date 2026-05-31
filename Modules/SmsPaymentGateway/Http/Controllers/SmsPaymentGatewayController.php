<?php

namespace Modules\SmsPaymentGateway\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWebhook;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWallet;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SmsPaymentGatewayController extends Controller
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Display the AutoSMS dashboard/overview page
     * Shows quick stats, QR code generation, and quick links
     */
    public function index()
    {
        $user = Auth::user();

        $devices = SmsPaymentGatewayDevice::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $recentTransactions = SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $stats = [
            'total_devices' => $devices->count(),
            'connected_devices' => $devices->where('status', 'connected')->count(),
            'total_transactions' => SmsPaymentGatewayTransaction::where('user_id', $user->id)->count(),
            'webhook_configured' => $webhook ? true : false,
        ];

        // Get user's most recent API token for AutoSMS
        // Try to find an existing token, or create one if none exists
        $apiToken = null;
        $token = $user->tokens()
            ->where('name', 'like', '%sms-payment-gateway%')
            ->orWhere('name', 'like', '%sms%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            // Try to get any token
            $token = $user->tokens()->orderBy('created_at', 'desc')->first();
        }

        // Note: We can't get the plain text token after creation, so we'll prompt user to create one
        // or use the token ID to reference it (but that won't work for API calls)
        // For now, we'll set it to null and show a message to create one

        return \Inertia\Inertia::render('TextPaymentGateway', [
            'devices' => $devices,
            'webhook' => $webhook,
            'token' => $token,
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'androidAppUrl' => config('sms-payment-gateway.android_app_url'),
        ]);
    }

    /**
     * Display the classic (legacy) AutoSMS Payment Hub view.
     * Same data as index; preserves old UI for existing integrations.
     */
    public function indexClassic()
    {
        $user = Auth::user();

        $devices = SmsPaymentGatewayDevice::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $token = $user->tokens()
            ->where('name', 'like', '%sms-payment-gateway%')
            ->orWhere('name', 'like', '%sms%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            $token = $user->tokens()->orderBy('created_at', 'desc')->first();
        }

        return view('client.sms-payment-gateway.index-old', compact('devices', 'webhook', 'token'));
    }

    /**
     * Generate QR code for device connection
     */
    public function generateQrCode(Request $request, \Modules\SmsPaymentGateway\Services\DeviceManagementService $deviceService)
    {
        $user = Auth::user();

        try {
            $connectionData = $deviceService->generateConnectionData($user);
            $connectionCode = $connectionData['connection_code'];
            $expiresAt = $connectionData['expires_at'];
            $qrData = json_encode($connectionData['qr_data']);
            
            $qrCodeImage = $this->qrCodeService->generateQrCodePng($qrData, [
                'size' => 300,
                'error_correction' => 'M',
                'margin' => 10,
            ]);

            $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($qrCodeImage);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'qr_code' => $qrCodeDataUri,
                    'connection_code' => $connectionCode,
                    'expires_at' => $expiresAt->toIso8601String(),
                ]);
            }

            return redirect()->route('sms-payment-gateway.index')
                ->with('qr_code', $qrCodeDataUri)
                ->with('connection_code', $connectionCode)
                ->with('qr_expires_at', $expiresAt);
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 403);
            }

            return redirect()->route('sms-payment-gateway.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show device details and transactions
     */
    public function showDevice($id)
    {
        $device = SmsPaymentGatewayDevice::where('user_id', Auth::id())
            ->with([
                'transactions' => function ($query) {
                    $query->orderByRaw('COALESCE(sms_timestamp, UNIX_TIMESTAMP(transaction_date) * 1000, UNIX_TIMESTAMP(created_at) * 1000) DESC');
                }
            ])
            ->findOrFail($id);

        $transactions = SmsPaymentGatewayTransaction::where('device_id', $device->id)
            ->orderByRaw('COALESCE(sms_timestamp, UNIX_TIMESTAMP(transaction_date) * 1000, UNIX_TIMESTAMP(created_at) * 1000) DESC')
            ->paginate(20);

        return \Inertia\Inertia::render('SmsPaymentGateway/Device', compact('device', 'transactions'));
    }

    /**
     * Display all transactions across all devices
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        $query = SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->with('device');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('sender', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByRaw('COALESCE(sms_timestamp, UNIX_TIMESTAMP(transaction_date) * 1000, UNIX_TIMESTAMP(created_at) * 1000) DESC')
            ->paginate(20)
            ->withQueryString();

        return \Inertia\Inertia::render('SmsPaymentGateway/Transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'search'])
        ]);
    }

    /**
     * Delete/disconnect a device
     */
    public function deleteDevice($id, \Modules\SmsPaymentGateway\Services\DeviceManagementService $deviceService)
    {
        $device = SmsPaymentGatewayDevice::where('user_id', Auth::id())
            ->findOrFail($id);

        $deviceService->deleteDevice($device);

        return redirect()->route('sms-payment-gateway.index')
            ->with('success', __('messages.device_disconnected_success'));
    }

    /**
     * Update device settings (e.g. gateway type, allowed sender)
     */
    public function updateDevice(Request $request, $id)
    {
        $device = SmsPaymentGatewayDevice::where('user_id', Auth::id())
            ->findOrFail($id);

        $request->validate([
            'device_name' => 'nullable|string|max:255',
            'sim1_configs' => 'nullable|array',
            'sim1_configs.*.allowed_sender' => 'nullable|string|max:100',
            'sim2_configs' => 'nullable|array',
            'sim2_configs.*.allowed_sender' => 'nullable|string|max:100',
        ]);

        $metadata = $device->metadata ?? [];
        
        if ($request->has('sim1_configs')) {
            $metadata['sim1_configs'] = $request->sim1_configs;
        }
        
        if ($request->has('sim2_configs')) {
            $metadata['sim2_configs'] = $request->sim2_configs;
        }

        $updateData = ['metadata' => $metadata];
        if ($request->has('device_name') && !empty($request->device_name)) {
            $updateData['device_name'] = $request->device_name;
        }

        $device->update($updateData);

        return redirect()->back()
            ->with('success', __('erp.settings_saved_success'));
    }

    /**
     * Get transactions for a device (AJAX)
     */
    public function getTransactions($deviceId)
    {
        $device = SmsPaymentGatewayDevice::where('user_id', Auth::id())
            ->findOrFail($deviceId);

        $transactions = SmsPaymentGatewayTransaction::where('device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }

    /**
     * Clear all transactions for a device
     */
    public function clearTransactions($id, \Modules\SmsPaymentGateway\Services\DeviceManagementService $deviceService)
    {
        $device = SmsPaymentGatewayDevice::where('user_id', Auth::id())
            ->findOrFail($id);

        $deletedCount = $deviceService->clearDeviceTransactions($device);

        return redirect()->route('sms-payment-gateway.device', $device->id)
            ->with('success', __('messages.all_transactions_cleared_success', ['count' => $deletedCount]));
    }


    /**
     * Register or update webhook
     */
    public function updateWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url|max:500',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Check if user already has an active webhook
        $existingWebhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($existingWebhook) {
            // Update existing webhook
            $existingWebhook->update([
                'webhook_url' => $request->webhook_url,
                'webhook_secret' => $request->webhook_secret ?? $existingWebhook->webhook_secret ?? 'whsec_' . Str::random(32),
            ]);

            return redirect()->route('sms-payment-gateway.index')
                ->with('success', __('messages.webhook_updated_success'));
        }

        // Create new webhook
        SmsPaymentGatewayWebhook::create([
            'user_id' => $user->id,
            'webhook_url' => $request->webhook_url,
            'webhook_secret' => $request->webhook_secret ?? 'whsec_' . Str::random(32),
            'is_active' => true,
        ]);

        return redirect()->route('sms-payment-gateway.index')
            ->with('success', __('messages.webhook_registered_success_v2'));
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook($id)
    {
        $webhook = SmsPaymentGatewayWebhook::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $webhook->delete();

        return redirect()->route('sms-payment-gateway.index')
            ->with('success', __('messages.webhook_deleted_success'));
    }

    /**
     * Test webhook
     */
    public function testWebhook()
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return redirect()->route('sms-payment-gateway.index')
                ->with('error', __('messages.no_active_webhook_configured'));
        }

        try {
            // Send test webhook using Guzzle
            $testPayload = [
                'event' => 'test',
                'message' => 'This is a test webhook from AutoSMS Payment Hub',
                'timestamp' => now()->toIso8601String(),
            ];

            $payloadJson = json_encode($testPayload);
            $signature = hash_hmac('sha256', $payloadJson, $webhook->webhook_secret);

            $client = new \GuzzleHttp\Client();
            $response = $client->post($webhook->webhook_url, [
                'json' => $testPayload,
                'headers' => [
                    'X-AutoSMS-Signature' => $signature,
                    'X-AutoSMS-Event' => 'test',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'AutoSMS-Payment-Hub/1.0',
                ],
                'timeout' => 10,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $success = $statusCode >= 200 && $statusCode < 300;

            if ($success) {
                $webhook->increment('success_count');
                $webhook->update(['last_triggered_at' => now()]);
                return redirect()->route('sms-payment-gateway.index')
                    ->with('success', __('messages.test_webhook_sent_success'));
            } else {
                $webhook->increment('failure_count');
                return redirect()->route('sms-payment-gateway.index')
                    ->with('error', __('messages.webhook_endpoint_returned_error') . ': ' . $statusCode);
            }
        } catch (\Exception $e) {
            $webhook->increment('failure_count');
            return redirect()->route('sms-payment-gateway.index')
                ->with('error', __('messages.failed_to_send_test_webhook') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get verification secret for HMAC signing
     * GET /client/sms-payment-gateway/verification-secret
     */
    public function getVerificationSecret(Request $request)
    {
        $user = Auth::user();
        $secret = $user->getSmsPaymentGatewayVerificationSecret();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'verification_secret' => $secret,
                'message' => 'Verification secret retrieved successfully'
            ]);
        }

        return redirect()->route('sms-payment-gateway.verification-secret-page')
            ->with('verification_secret', $secret)
            ->with('success', __('messages.verification_secret_retrieved_success'));
    }

    /**
     * Regenerate verification secret
     * POST /client/sms-payment-gateway/verification-secret/regenerate
     */
    public function regenerateVerificationSecret(Request $request)
    {
        $user = Auth::user();
        $newSecret = $user->regenerateSmsPaymentGatewayVerificationSecret();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'verification_secret' => $newSecret,
                'message' => 'Verification secret regenerated successfully'
            ]);
        }

        return redirect()->route('sms-payment-gateway.verification-secret-page')
            ->with('verification_secret', $newSecret)
            ->with('success', __('messages.verification_secret_regenerated_success'));
    }

    /**
     * Display devices management page
     * GET /client/sms-payment-gateway/devices
     */
    public function devices()
    {
        $user = Auth::user();

        $devices = SmsPaymentGatewayDevice::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $androidAppUrl = config('sms-payment-gateway.android_app_url');

        return \Inertia\Inertia::render('SmsPaymentGateway/Devices', compact('devices', 'androidAppUrl'));
    }

    /**
     * Display webhooks configuration page
     * GET /client/sms-payment-gateway/webhooks
     */
    public function webhooks()
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return \Inertia\Inertia::render('SmsPaymentGateway/Webhooks', compact('webhook'));
    }

    /**
     * Display client-side verification page
     * GET /client/sms-payment-gateway/verification
     */
    public function verification()
    {
        $user = Auth::user();

        // Get user's most recent API token for AutoSMS
        $apiToken = null;
        $token = $user->tokens()
            ->where('name', 'like', '%sms-payment-gateway%')
            ->orWhere('name', 'like', '%sms%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            $token = $user->tokens()
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return \Inertia\Inertia::render('SmsPaymentGateway/Verification', compact('token'));
    }

    /**
     * Display documentation page
     * GET /client/sms-payment-gateway/documentation
     */
    public function documentation()
    {
        return \Inertia\Inertia::render('SmsPaymentGateway/Documentation');
    }

    /**
     * Display integration tester page
     * GET /client/sms-payment-gateway/integration-tester
     */
    public function integrationTester()
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $token = $user->tokens()
            ->where('name', 'like', '%sms-payment-gateway%')
            ->orWhere('name', 'like', '%sms%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            $token = $user->tokens()->orderBy('created_at', 'desc')->first();
        }

        $verificationSecret = $user->getSmsPaymentGatewayVerificationSecret();

        return \Inertia\Inertia::render('SmsPaymentGateway/IntegrationTester', compact('webhook', 'token', 'verificationSecret'));
    }

    /**
     * Run server-side integration test
     * POST /client/sms-payment-gateway/integration-tester/run
     */
    public function runIntegrationTest(Request $request)
    {
        $request->validate([
            'endpoint_url' => 'required|url',
            'phone_number' => 'required|string',
            'auth_token' => 'nullable|string',
        ]);

        $payload = [
            'phone_number' => $request->phone_number,
            'test_mode' => true,
            'transaction_id' => 'TEST_' . Str::random(8),
            'amount' => 100.00,
            'currency' => 'USD',
            'sender_name' => 'Integration Tester',
            'timestamp' => now()->toIso8601String(),
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'AutoSMS-Integration-Tester/1.0',
        ];

        if ($request->auth_token) {
            $headers['Authorization'] = 'Bearer ' . $request->auth_token;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($request->endpoint_url, [
                'json' => $payload,
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => 15,
                'verify' => false,
            ]);

            $bodyStart = (string) $response->getBody();
            $jsonBody = json_decode($bodyStart);

            return response()->json([
                'success' => true,
                'status' => $response->getStatusCode(),
                'payload_sent' => $payload,
                'response_body' => $jsonBody ?? $bodyStart,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display verification secret page
     * GET /client/sms-payment-gateway/verification-secret-page
     */
    public function verificationSecretPage()
    {
        $user = Auth::user();

        // If secret is in session (from regenerate/get action), use it
        // Otherwise, get from database
        $secret = session('verification_secret') ?? $user->getSmsPaymentGatewayVerificationSecret();

        return \Inertia\Inertia::render('SmsPaymentGateway/VerificationSecret', compact('secret'));
    }

    /**
     * Display the simple settings page
     */
    public function settings()
    {
        $user = Auth::user();
        $settings = \Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_instapay_enabled' => true,
                'is_vodafone_cash_enabled' => true,
            ]
        );

        $devices = \Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice::where('user_id', $user->id)->get();

        return \Inertia\Inertia::render('SmsPaymentGateway/Settings', [
            'settings' => $settings,
            'devices' => $devices
        ]);
    }

    /**
     * Store settings
     */
    public function storeSettings(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'wallet_phone_number' => 'nullable|string|max:20',
            'instapay_phone_number' => 'nullable|string|max:20',
            'vodafone_cash_phone_number' => 'nullable|string|max:20',
            'is_instapay_enabled' => 'boolean',
            'is_vodafone_cash_enabled' => 'boolean',
            'whitelist_senders' => 'nullable|string|max:1000',
            'vodafone_cash_device_id' => 'nullable|integer|exists:sms_payment_gateway_devices,id',
            'vodafone_cash_allowed_sender' => 'nullable|string|max:100',
            'instapay_device_id' => 'nullable|integer|exists:sms_payment_gateway_devices,id',
            'instapay_allowed_sender' => 'nullable|string|max:100',
        ]);

        $settings = \Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting::firstOrCreate(
            ['user_id' => $user->id]
        );

        $settings->update([
            'wallet_phone_number' => $request->wallet_phone_number,
            'instapay_phone_number' => $request->instapay_phone_number,
            'vodafone_cash_phone_number' => $request->vodafone_cash_phone_number,
            'is_instapay_enabled' => $request->has('is_instapay_enabled') ? $request->is_instapay_enabled : true,
            'is_vodafone_cash_enabled' => $request->has('is_vodafone_cash_enabled') ? $request->is_vodafone_cash_enabled : true,
            'whitelist_senders' => $request->whitelist_senders,
            'vodafone_cash_device_id' => $request->vodafone_cash_device_id,
            'vodafone_cash_allowed_sender' => $request->vodafone_cash_allowed_sender,
            'instapay_device_id' => $request->instapay_device_id,
            'instapay_allowed_sender' => $request->instapay_allowed_sender,
        ]);

        return redirect()->back()->with('success', __('erp.settings_saved_success'));
    }

    /**
     * Display the Create Payment Link page
     */
    public function paymentLinks()
    {
        $user = Auth::user();
        $links = \App\Models\PaymentOrder::with('currency')
            ->where('user_id', $user->id)
            ->whereNotNull('metadata->is_sms_gateway')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return \Inertia\Inertia::render('SmsPaymentGateway/PaymentLinks', [
            'links' => $links
        ]);
    }

    /**
     * Display the API Checkout Sessions page
     */
    public function checkoutSessions()
    {
        $user = Auth::user();
        $sessions = \Modules\SmsPaymentGateway\Models\SmsGatewayCheckoutSession::with('currency')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return \Inertia\Inertia::render('SmsPaymentGateway/CheckoutSessions', [
            'sessions' => $sessions
        ]);
    }

    /**
     * Generate a new payment link
     */
    public function createPaymentLink(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $orderNumber = 'SMS-' . strtoupper(Str::random(10));

        $order = \App\Models\PaymentOrder::create([
            'user_id' => $user->id,
            'mobile_number' => '00000000000', // Dummy as not used here
            'amount' => $request->amount,
            'currency_id' => \App\Models\AdminSettings::business_currency(),
            'status' => 'pending',
            'metadata' => [
                'is_sms_gateway' => true,
                'order_number' => $orderNumber,
                'customer_name' => $request->customer_name,
                'description' => $request->description,
            ]
        ]);

        return redirect()->back()->with('success', __('erp.created_successfully'));
    }

    /**
     * Display wallets management page
     * GET /client/sms-payment-gateway/wallets
     */
    public function wallets()
    {
        $user = Auth::user();

        $wallets = SmsPaymentGatewayWallet::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return \Inertia\Inertia::render('SmsPaymentGateway/Wallets', compact('wallets'));
    }

    /**
     * Store a new wallet
     * POST /client/sms-payment-gateway/wallets
     */
    public function storeWallet(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|in:Wallet,Instapay',
            'phone_number' => 'required|string|max:20',
        ]);

        $user = Auth::user();

        $wallet = SmsPaymentGatewayWallet::create([
            'user_id' => $user->id,
            'payment_type' => $request->payment_type,
            'phone_number' => $request->phone_number,
            'is_active' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'wallet' => $wallet,
                'message' => 'Wallet added successfully'
            ]);
        }

        return redirect()->route('sms-payment-gateway.wallets')
            ->with('success', __('messages.wallet_added_success'));
    }

    /**
     * Delete a wallet
     * DELETE /client/sms-payment-gateway/wallets/{id}
     */
    public function deleteWallet($id)
    {
        $wallet = SmsPaymentGatewayWallet::where('user_id', Auth::id())
            ->findOrFail($id);

        $wallet->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Wallet deleted successfully'
            ]);
        }

        return redirect()->route('sms-payment-gateway.wallets')
            ->with('success', __('messages.wallet_deleted_success'));
    }

    /**
     * Display test mode page
     * GET /client/sms-payment-gateway/test-mode
     */
    public function testMode()
    {
        $user = Auth::user();

        $testModeEnabled = $user->sms_payment_gateway_test_mode ?? false;

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // Get test data statistics
        $testTransactionsCount = SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->where('is_test', true)
            ->count();

        $testOrdersCount = \App\Models\PaymentOrder::where('user_id', $user->id)
            ->where('is_test', true)
            ->count();

        // Get recent test transactions
        $testTransactions = SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->where('is_test', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return \Inertia\Inertia::render('SmsPaymentGateway/TestMode', compact(
            'testModeEnabled',
            'webhook',
            'testTransactionsCount',
            'testOrdersCount',
            'testTransactions'
        ));
    }

    /**
     * Display SMS Simulator page
     * GET /client/sms-payment-gateway/sms-simulator
     */
    public function smsSimulator()
    {
        $user = Auth::user();

        // Get user's devices to select from
        $devices = SmsPaymentGatewayDevice::where('user_id', $user->id)
            ->where('status', 'connected')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get webhook to show if it's configured
        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // Get the token needed for testing via API
        $token = $user->tokens()
            ->where('name', 'like', '%sms-payment-gateway%')
            ->orWhere('name', 'like', '%sms%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            $token = $user->tokens()->orderBy('created_at', 'desc')->first();
        }

        return \Inertia\Inertia::render('SmsPaymentGateway/SmsSimulator', compact('devices', 'webhook', 'token'));
    }

    /**
     * Toggle test mode
     * POST /client/sms-payment-gateway/test-mode/toggle
     */
    public function toggleTestMode()
    {
        $user = Auth::user();

        $user->sms_payment_gateway_test_mode = !($user->sms_payment_gateway_test_mode ?? false);
        $user->save();

        $message = $user->sms_payment_gateway_test_mode
            ? __('messages.test_mode_enabled')
            : __('messages.test_mode_disabled');

        return redirect()->route('sms-payment-gateway.test-mode')
            ->with('success', $message);
    }

    /**
     * Create test transaction
     * POST /client/sms-payment-gateway/test-mode/create-transaction
     */
    /**
     * Create test transaction
     * POST /client/sms-payment-gateway/test-mode/create-transaction
     */
    public function createTestTransaction(Request $request)
    {
        $user = Auth::user();

        if (!$user->sms_payment_gateway_test_mode) {
            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('error', __('messages.test_mode_required_for_transaction'));
        }

        // Sanitize input to ensure valid UTF-8
        $input = $request->all();
        array_walk_recursive($input, function (&$item) {
            if (is_string($item)) {
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
                // Remove control characters (except newline, return, tab)
                $item = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $item);
            }
        });
        $request->merge($input);


        $request->validate([
            'sms_text' => 'required|string',
            'sender' => 'nullable|string|max:255',
        ]);



        // Get first device or create a test device
        $device = SmsPaymentGatewayDevice::where('user_id', $user->id)->first();
        if (!$device) {
            $device = SmsPaymentGatewayDevice::create([
                'user_id' => $user->id,
                'device_name' => 'Test Device',
                'device_token' => Str::random(32),
                'status' => 'connected',
            ]);
        }

        // Prepare request for API controller simulation
        // We act exactly as the mobile app would
        try {
            $apiController = app(\Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController::class);

            $allowedSenders = config('text-payment-gateway.allowed_senders', ['Test-Sender']);
            $defaultSender = !empty($allowedSenders) ? $allowedSenders[array_rand($allowedSenders)] : 'Test-Sender';
            $sender = $request->sender ?? $defaultSender;

            $simulatedRequest = new \Illuminate\Http\Request();
            $simulatedRequest->replace([
                'sender' => $sender,
                'message' => $request->sms_text,
                'timestamp' => (int) (microtime(true) * 1000), // Javascript timestamp format (ms)
                'device_token' => $device->device_token,
                'is_test' => true,
                'simSlot' => 0,
                'name' => $sender, // API sometimes uses 'name'
            ]);


            // Execute the API logic
            // Note: receiveSms calls validate() which might throw ValidationException if something is wrong
            // We'll catch it to be safe
            $response = $apiController->receiveSms($simulatedRequest);

            // Response is a JsonResponse
            $data = $response->getData();

            if (isset($data->success) && $data->success) {
                if (isset($data->transaction_detected) && $data->transaction_detected) {
                    $msg = __('messages.sms_processed_transaction_created');
                    if (isset($data->duplicate) && $data->duplicate) {
                        $msg = __('messages.sms_processed_duplicate', ['id' => $data->existing_transaction_id ?? '']);
                        return redirect()->route('sms-payment-gateway.test-mode')
                            ->with('warning', $msg);
                    }
                    return redirect()->route('sms-payment-gateway.test-mode')
                        ->with('success', $msg);
                } else {
                    // Transaction not detected (parsing failed or filtered)
                    $debugMsg = '';
                    if (isset($data->debug)) {
                        $reasons = isset($data->debug->reasons) ? implode(', ', $data->debug->reasons) : 'Unknown reason';
                        $debugMsg = "Reason: $reasons";
                    }
                    return redirect()->route('sms-payment-gateway.test-mode')
                        ->with('warning', __('messages.sms_received_no_transaction') . ' ' . $debugMsg);
                }
            } else {
                return redirect()->route('sms-payment-gateway.test-mode')
                    ->with('error', __('messages.processing_failed') . ': ' . ($data->message ?? 'Unknown error'));
            }

        } catch (\Exception $e) {

            echo $e->getTraceAsString();
            exit();


            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('error', __('messages.system_error') . ': ' . $e->getMessage());
        }
    }

    /**
     * Send test webhook
     * POST /client/sms-payment-gateway/test-mode/send-webhook
     */
    public function sendTestWebhook(Request $request)
    {
        $user = Auth::user();

        if (!$user->sms_payment_gateway_test_mode) {
            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('error', __('messages.test_mode_required_for_webhook'));
        }

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('error', __('messages.no_webhook_configured'));
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'phone_number' => 'required|string|max:20',
        ]);

        // Create test transaction for webhook
        $device = SmsPaymentGatewayDevice::where('user_id', $user->id)->first();
        $transaction = SmsPaymentGatewayTransaction::create([
            'user_id' => $user->id,
            'device_id' => $device->id ?? null,
            'phone_number' => $request->phone_number,
            'amount' => $request->amount,
            'currency' => 'EGP',
            'sender' => 'Test-Webhook',
            'sender_name' => 'Test Webhook Sender',
            'transaction_date' => now()->toISOString(),
            'status' => 'pending',
            'is_test' => true,
            'sms_message' => 'Test webhook: Received ' . $request->amount . ' EGP from Test Webhook Sender',
            'raw_sms' => 'Test webhook transaction',
        ]);

        // Send webhook
        try {
            $this->triggerWebhookEvent($webhook, $transaction);

            // Increment test events counter
            $webhook->increment('test_events_sent');

            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('success', __('messages.test_webhook_sent_success_v2'));
        } catch (\Exception $e) {
            return redirect()->route('sms-payment-gateway.test-mode')
                ->with('error', __('messages.webhook_send_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Clear test data
     * DELETE /client/sms-payment-gateway/test-mode/clear-data
     */
    public function clearTestData()
    {
        $user = Auth::user();

        // Delete test transactions
        SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->where('is_test', true)
            ->delete();

        // Delete test orders
        \App\Models\PaymentOrder::where('user_id', $user->id)
            ->where('is_test', true)
            ->delete();

        // Reset test webhook counter
        SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->update(['test_events_sent' => 0]);

        return redirect()->route('sms-payment-gateway.test-mode')
            ->with('success', __('messages.all_test_data_cleared_success'));
    }

    /**
     * Trigger webhook event helper method
     */
    private function triggerWebhookEvent($webhook, $transaction)
    {
        $payload = [
            'transaction_id' => $transaction->id,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'phone_number' => $transaction->phone_number,
            'sender_name' => $transaction->sender_name,
            'sender' => $transaction->sender,
            'transaction_date' => $transaction->transaction_date,
            'status' => $transaction->status,
            'device_id' => $transaction->device_id,
            'is_test' => true, // Important: Mark as test
        ];

        $signature = hash_hmac('sha256', json_encode($payload), $webhook->webhook_secret);

        $ch = curl_init($webhook->webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $signature,
            'X-Test-Event: true', // Additional header to indicate test
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Webhook returned HTTP {$httpCode}");
        }

        return true;
    }

    // ─── API Keys Management ───────────────────────────

    /**
     * Display the API Keys management page
     * GET /sms-payment-gateway/api-keys
     */
    public function apiKeys()
    {
        $user = Auth::user();

        $apiKeys = \Modules\SmsPaymentGateway\Models\SmsGatewayApiKey::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'publishable_key' => $key->publishable_key,
                    'secret_key_last_four' => $key->secret_key_last_four,
                    'is_test' => $key->is_test,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            });

        return \Inertia\Inertia::render('SmsPaymentGateway/ApiKeys', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Create a new API key pair
     * POST /sms-payment-gateway/api-keys
     */
    public function createApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_test' => 'boolean',
        ]);

        $user = Auth::user();

        // Limit: max 10 active keys per user
        $activeCount = \Modules\SmsPaymentGateway\Models\SmsGatewayApiKey::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        if ($activeCount >= 10) {
            return redirect()->back()->with('error', __('sms_gateway.api_key_limit_reached'));
        }

        $result = \Modules\SmsPaymentGateway\Models\SmsGatewayApiKey::generateKeyPair(
            $user->id,
            $request->name,
            $request->is_test ?? false
        );

        // Flash the secret key — it will only be shown ONCE
        return redirect()->back()
            ->with('success', __('sms_gateway.api_key_created'))
            ->with('new_publishable_key', $result['publishable_key'])
            ->with('new_secret_key', $result['secret_key']);
    }

    /**
     * Delete (deactivate) an API key
     * DELETE /sms-payment-gateway/api-keys/{id}
     */
    public function deleteApiKey($id)
    {
        $apiKey = \Modules\SmsPaymentGateway\Models\SmsGatewayApiKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $apiKey->deactivate();
        $apiKey->delete(); // Soft delete

        return redirect()->back()->with('success', __('sms_gateway.api_key_deleted'));
    }

    /**
     * Roll (regenerate) the secret key
     * POST /sms-payment-gateway/api-keys/{id}/roll
     */
    public function rollApiKey($id)
    {
        $apiKey = \Modules\SmsPaymentGateway\Models\SmsGatewayApiKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $newSecret = $apiKey->rollSecretKey();

        return redirect()->back()
            ->with('success', __('sms_gateway.api_key_rolled'))
            ->with('new_secret_key', $newSecret);
    }
}
