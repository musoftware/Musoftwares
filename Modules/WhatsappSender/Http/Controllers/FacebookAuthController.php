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
        $businessId = $request->query('business_id');
        if ($businessId) {
            session(['facebook_oauth_business_id' => $businessId]);
        }

        $business = null;
        if ($businessId) {
            $business = \Modules\WhatsappSender\Models\WhatsappBusiness::find($businessId);
        }

        $clientId = $business?->facebook_client_id;
        $clientSecret = $business?->facebook_client_secret;

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('whatsapp.index')->with(
                'error',
                'يرجى إعداد معرف تطبيق فيسبوك (Facebook App ID) والمفتاح السري (App Secret) أولاً من الإعدادات للتمكن من ربط الحساب.'
            );
        }

        config([
            'services.facebook.client_id' => $clientId,
            'services.facebook.client_secret' => $clientSecret,
            'services.facebook.redirect' => route('whatsapp.auth.facebook.callback'),
        ]);

        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');

            return $driver
                ->setScopes(['public_profile', 'whatsapp_business_management', 'whatsapp_business_messaging', 'business_management'])
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
        $businessId = session('facebook_oauth_business_id');
        $business = null;
        if ($businessId) {
            $business = \Modules\WhatsappSender\Models\WhatsappBusiness::find($businessId);
        }

        $clientId = $business?->facebook_client_id;
        $clientSecret = $business?->facebook_client_secret;

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('whatsapp.index')->with(
                'error',
                'بيانات تطبيق فيسبوك غير متوفرة أو غير مكتملة لهذا البيزنس.'
            );
        }

        config([
            'services.facebook.client_id' => $clientId,
            'services.facebook.client_secret' => $clientSecret,
            'services.facebook.redirect' => route('whatsapp.auth.facebook.callback'),
        ]);

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
            $metaAccounts = $this->whatsappService->fetchWhatsAppAccountsFromMetaToken($token, $clientId, $clientSecret);

            $redirectTarget = $businessId
                ? redirect()->route('whatsapp.businesses.workspace', $businessId)
                : redirect()->back();

            if (empty($metaAccounts)) {
                session(['fb_oauth_token' => $token]);

                return $redirectTarget->with(
                    'info',
                    'تم تسجيل الدخول بحساب فيسبوك وحفظ توكن الصلاحية بنجاح! لم يتم العثور على أرقام واتساب تجارية مرتبطة تلقائياً. تأكد أن تطبيق فيسبوك في وضع Live أو أن حساب الفيسبوك يملك حساب WhatsApp Business مفعل، أو يمكنك إضافة Phone Number ID يدويًا.'
                );
            }

            foreach ($metaAccounts as $acc) {
                WhatsappAccount::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'phone_number_id' => $acc['phone_number_id'],
                    ],
                    [
                        'whatsapp_business_id' => $businessId,
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

            return $redirectTarget->with(
                'success',
                __('whatsapp-sender::messages.facebook_account_connected')
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $this->formatErrorMessage($e));
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
