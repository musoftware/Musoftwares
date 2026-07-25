<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Services\MetaWhatsappService;

class FacebookAuthController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Redirect to Facebook / Meta Login for OAuth access without invalid scopes (e.g. email).
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('whatsapp.index')->with(
                'error',
                'Facebook App ID or App Secret is not configured in .env (FACEBOOK_CLIENT_ID / FACEBOOK_CLIENT_SECRET).'
            );
        }

        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');

            return $driver
                ->setScopes(['public_profile', 'whatsapp_business_management', 'whatsapp_business_messaging'])
                ->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index')->with('error', $this->formatErrorMessage($e));
        }
    }

    /**
     * Handle callback from Facebook / Meta OAuth login.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');

            $fbUser = $driver->stateless()->user();

            $token = $fbUser->token;
            $fbUserId = $fbUser->getId();
            $name = $fbUser->getName() ?? 'Meta Connected Account';

            // Clean up any placeholder accounts from previous attempts
            WhatsappAccount::where('user_id', $request->user()->id)
                ->where('phone_number_id', 'like', 'FB_CONNECTED_%')
                ->delete();

            // Auto-detect real WhatsApp Business Accounts & Phone Number IDs from Meta Graph API
            $metaAccounts = $this->whatsappService->fetchWhatsAppAccountsFromMetaToken($token);

            if (empty($metaAccounts)) {
                session(['fb_oauth_token' => $token]);

                return redirect()->route('whatsapp.index')->with(
                    'info',
                    "Facebook Login succeeded! We saved your OAuth Access Token. Please enter your Meta Phone Number ID below to complete your connection."
                );
            }

            foreach ($metaAccounts as $acc) {
                WhatsappAccount::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'phone_number_id' => $acc['phone_number_id'],
                    ],
                    [
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

            return redirect()->route('whatsapp.index')->with(
                'success',
                __('whatsapp-sender::messages.facebook_account_connected')
            );
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index')->with('error', $this->formatErrorMessage($e));
        }
    }

    /**
     * Format OAuth and API exception messages cleanly for the user.
     */
    protected function formatErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        if ($e instanceof ClientException && $e->hasResponse()) {
            try {
                $body = json_decode((string) $e->getResponse()->getBody(), true);
                if (isset($body['error']['message'])) {
                    $message = $body['error']['message'];
                }
            } catch (\Throwable) {
                // fallback to original message
            }
        }

        if (str_contains(strtolower($message), 'client secret') || str_contains(strtolower($message), 'error validating client secret')) {
            $clientId = config('services.facebook.client_id');
            return "Invalid Facebook App Secret: The App Secret in your .env file (FACEBOOK_CLIENT_SECRET) does not match App ID '{$clientId}'. Please copy the correct App Secret from your Meta Developer Console (App Settings -> Basic -> App Secret) and paste it into .env, or use manual credentials input below.";
        }

        return $message;
    }
}
