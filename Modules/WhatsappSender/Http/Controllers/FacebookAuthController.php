<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
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
     * Redirect to Facebook / Meta Login for OAuth access.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.facebook.client_id');

        if (empty($clientId)) {
            return redirect()->route('whatsapp.index')->with(
                'error',
                __('whatsapp-sender::messages.facebook_login_not_configured')
            );
        }

        try {
            return Socialite::driver('facebook')
                ->scopes(['whatsapp_business_management', 'whatsapp_business_messaging'])
                ->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Handle callback from Facebook / Meta OAuth login.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $fbUser = Socialite::driver('facebook')->user();

            $token = $fbUser->token;
            $fbUserId = $fbUser->getId();
            $name = $fbUser->getName() ?? 'Meta Connected Account';

            // Store token and attempt to auto-detect WhatsApp accounts
            WhatsappAccount::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'facebook_user_id' => $fbUserId,
                ],
                [
                    'name' => $name,
                    'phone_number_id' => $request->input('phone_number_id', 'FB_CONNECTED_' . $fbUserId),
                    'waba_id' => $request->input('waba_id'),
                    'access_token' => $token,
                    'status' => 'active',
                    'metadata' => [
                        'email' => $fbUser->getEmail(),
                        'avatar' => $fbUser->getAvatar(),
                    ],
                ]
            );

            return redirect()->route('whatsapp.index')->with(
                'success',
                __('whatsapp-sender::messages.facebook_account_connected')
            );
        } catch (\Throwable $e) {
            return redirect()->route('whatsapp.index')->with('error', $e->getMessage());
        }
    }
}
