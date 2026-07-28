<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Services\MetaWhatsappService;
use Illuminate\Support\Facades\Log;

class GuestClientRegisterController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Display landing page for guest connection.
     */
    public function showLandingPage(string $uuid): InertiaResponse
    {
        $business = WhatsappBusiness::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('WhatsappSender/GuestRegister', [
            'business' => [
                'name' => $business->name,
                'uuid' => $business->uuid,
            ],
            'facebookLoginUrl' => route('whatsapp.guest.facebook.redirect', ['uuid' => $uuid]),
        ]);
    }

    /**
     * Redirect to Facebook OAuth with the Business UUID as state.
     */
    public function redirectFacebook(string $uuid): RedirectResponse
    {
        $business = WhatsappBusiness::where('uuid', $uuid)->firstOrFail();
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('whatsapp.guest.register', ['uuid' => $uuid])->with(
                'error',
                'Facebook configuration is missing. Contact the site administrator.'
            );
        }

        session(['guest_register_business_uuid' => $uuid]);

        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');

            return $driver
                ->setScopes(['public_profile', 'whatsapp_business_management', 'whatsapp_business_messaging'])
                ->with(['state' => $uuid])
                ->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.guest.register', ['uuid' => $uuid])->with('error', $e->getMessage());
        }
    }

    /**
     * Handle Facebook OAuth Callback.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $uuid = $request->input('state') ?? session('guest_register_business_uuid');

        if (!$uuid) {
            return redirect()->route('whatsapp.index')->with('error', 'Invalid request: state or business UUID is missing.');
        }

        $business = WhatsappBusiness::where('uuid', $uuid)->first();
        if (!$business) {
            return redirect()->route('whatsapp.index')->with('error', 'Business profile not found.');
        }

        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');

            $fbUser = $driver->stateless()->user();

            $token = $fbUser->token;
            $fbUserId = $fbUser->getId();
            $name = $fbUser->getName() ?? 'Connected Client Account';

            // Fetch real WhatsApp Business Accounts & Phone Number IDs from Meta Graph API
            $metaAccounts = $this->whatsappService->fetchWhatsAppAccountsFromMetaToken($token);

            if (empty($metaAccounts)) {
                // If direct detection fails, create a placeholder using the access token
                WhatsappAccount::updateOrCreate(
                    [
                        'whatsapp_business_id' => $business->id,
                        'phone_number_id' => 'FB_CONNECTED_GUEST_' . $fbUserId,
                    ],
                    [
                        'user_id' => $business->user_id, // Owner of the business
                        'name' => $name,
                        'access_token' => $token,
                        'status' => 'unregistered',
                        'facebook_user_id' => $fbUserId,
                        'metadata' => [
                            'email' => $fbUser->getEmail(),
                            'avatar' => $fbUser->getAvatar(),
                        ],
                    ]
                );

                return redirect()->route('whatsapp.guest.success', ['uuid' => $uuid]);
            }

            foreach ($metaAccounts as $acc) {
                WhatsappAccount::updateOrCreate(
                    [
                        'whatsapp_business_id' => $business->id,
                        'phone_number_id' => $acc['phone_number_id'],
                    ],
                    [
                        'user_id' => $business->user_id, // Owner of the business
                        'name' => $acc['verified_name'] ?? $acc['waba_name'] ?? $name,
                        'waba_id' => $acc['waba_id'],
                        'access_token' => $token,
                        'status' => 'active',
                        'facebook_user_id' => $fbUserId,
                        'metadata' => [
                            'display_phone_number' => $acc['display_phone_number'] ?? null,
                            'email' => $fbUser->getEmail(),
                            'avatar' => $fbUser->getAvatar(),
                        ],
                    ]
                );
            }

            return redirect()->route('whatsapp.guest.success', ['uuid' => $uuid]);
        } catch (\Throwable $e) {
            Log::error('[GuestClientRegisterController] OAuth callback error: ' . $e->getMessage());
            return redirect()->route('whatsapp.guest.register', ['uuid' => $uuid])->with('error', $e->getMessage());
        }
    }

    /**
     * Show connection success page.
     */
    public function showSuccessPage(string $uuid): InertiaResponse
    {
        $business = WhatsappBusiness::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('WhatsappSender/GuestSuccess', [
            'business' => [
                'name' => $business->name,
            ],
        ]);
    }
}
