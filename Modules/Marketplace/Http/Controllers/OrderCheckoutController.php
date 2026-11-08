<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\MarketplaceAccountCreatedMail;
use App\Models\User;
use App\Services\Auth\NewAccountService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Services\MarketplaceOrderPayment;
use Throwable;

/** Lets a visitor order without an account, and brings any buyer back after paying at Kashier. */
class OrderCheckoutController extends Controller
{
    private const HOUR = 3600;

    public function __construct(private readonly MarketplaceOrderPayment $payment) {}

    public function guest(Request $request, NewAccountService $accounts): RedirectResponse
    {
        $data = $this->validatedGuest($request);
        $package = ServicePackage::with('service')->findOrFail($data['package_id']);
        $serviceUrl = route('marketplace.services.show', $package->service_id);

        if ($request->user()) {
            return redirect($serviceUrl);
        }
        if ($package->service?->status !== 'active') {
            return redirect($serviceUrl)->with('error', __('This service is currently unavailable.'));
        }
        if (User::where('email', $data['email'])->exists()) {
            return $this->askToSignIn($request, $serviceUrl);
        }
        if (! $this->allowNewAccount($request)) {
            return back()->withInput()->with('error', $this->say('جرب كمان شوية، أو سجل دخول.', 'Please try again later, or sign in.'));
        }

        return $this->sendToPayment($this->openAccount($request, $data, $accounts), $package, $serviceUrl);
    }

    public function resume(Request $request): View|RedirectResponse
    {
        $package = $this->payment->pendingPackage();
        if (! $package) {
            return redirect()->route('marketplace.orders.index');
        }

        return view('marketplace::orders.resume', [
            'package' => $package,
            'ready' => $this->payment->shortfall($request->user(), $package) <= 0,
        ]);
    }

    public function resumeFailed(): RedirectResponse
    {
        $package = $this->payment->pendingPackage();
        $this->payment->forgetPending();
        $target = $package ? route('marketplace.services.show', $package->service_id) : route('marketplace.services.index');

        return redirect($target)->with('error', $this->say('الدفع ما تمش. جرب تاني.', 'The payment did not go through. Please try again.'));
    }

    /** @return array{package_id: int, name: string, email: string, phone: ?string} */
    private function validatedGuest(Request $request): array
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        return $request->validate([
            'package_id' => ['required', 'exists:marketplace_packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'privacy_consent' => ['required', 'accepted'],
        ], [
            'privacy_consent.required' => __('privacy_compliance.register_privacy_required_error'),
            'privacy_consent.accepted' => __('privacy_compliance.register_privacy_required_error'),
        ]);
    }

    /** An email alone never signs anyone in: the owner of that email has to use their password. */
    private function askToSignIn(Request $request, string $serviceUrl): RedirectResponse
    {
        $request->session()->put('url.intended', $serviceUrl);

        return redirect()->route('login')->with('status', $this->say(
            'الإيميل ده عنده حساب عندنا. سجل دخول وكمل طلبك.',
            'This email already has an account. Sign in to continue your order.',
        ));
    }

    /** Stored on disk because production uses the array cache, which forgets every request. */
    private function allowNewAccount(Request $request): bool
    {
        $limits = config('services.marketplace_guest_orders');
        $limiter = new RateLimiter(Cache::store($limits['limiter_store']));
        $key = 'marketplace-guest-account:'.$request->ip();

        if ($limiter->tooManyAttempts($key, $limits['per_ip_per_hour'])) {
            return false;
        }
        $limiter->hit($key, self::HOUR);

        return true;
    }

    /** @param array{name: string, email: string, phone: ?string} $data */
    private function openAccount(Request $request, array $data, NewAccountService $accounts): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'mobile_1' => $data['phone'] ?? null,
            'whatsapp_number' => $data['phone'] ?? null,
        ]);

        $accounts->finish($user, $request);
        Auth::login($user);
        $request->session()->regenerate();
        $this->emailPasswordLink($user);

        return $user;
    }

    private function emailPasswordLink(User $user): void
    {
        try {
            $link = route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email]);
            Mail::to($user->email)->send(new MarketplaceAccountCreatedMail($user, $link));
        } catch (Throwable $e) {
            Log::error('Guest order: account email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendToPayment(User $user, ServicePackage $package, string $serviceUrl): RedirectResponse
    {
        try {
            return redirect()->away($this->payment->startCheckout($user, $package));
        } catch (Throwable $e) {
            Log::error('Guest order: could not start payment', ['user_id' => $user->id, 'package_id' => $package->id, 'error' => $e->getMessage()]);

            return redirect($serviceUrl)->with('error', $this->say('معرفناش نفتح صفحة الدفع. جرب تاني.', 'We could not open the payment page. Please try again.'));
        }
    }

    private function say(string $arabic, string $english): string
    {
        return app()->getLocale() === 'ar' ? $arabic : $english;
    }
}
