<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Auth\SetPasswordController;
use App\Mail\PlanAccountCreatedMail;
use App\Models\Currency;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guest-payable links for plans sold from satellite systems (config `saas.external_plans`).
 *
 * The plan is copied into the link metadata at creation, so a later price change
 * never alters a link that was already handed out. When the link is paid, the payer
 * is resolved (known client, or found/created by the email typed at checkout) and
 * their subscription is extended.
 */
class PlanPaymentLinkService
{
    private const LINK_TTL_HOURS = 48;

    private const ADMIN_ROLES = ['super_admin', 'admin', 'superadmin', 'Admin'];

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * @return array<string, mixed>|null
     */
    public function plan(string $planId): ?array
    {
        $plan = config("saas.external_plans.{$planId}");

        return is_array($plan) ? ['id' => $planId] + $plan : null;
    }

    /**
     * @param  array<string, mixed>  $plan  from plan()
     */
    public function create(array $plan, ?User $client, ?string $returnUrl): PaymentLink
    {
        return PaymentLink::create([
            'user_id' => $this->owner()->id,
            'client_id' => $client?->id,
            'title' => (string) $plan['title'],
            'amount' => $plan['amount'],
            'currency_id' => $this->currencyId((string) $plan['currency']),
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => now()->addHours(self::LINK_TTL_HOURS),
            'metadata' => ['plan' => $plan, 'return_url' => $returnUrl],
        ]);
    }

    /**
     * Activate the plan bought through a just-paid link. No-op for ordinary links.
     */
    public function fulfil(PaymentLink $link): void
    {
        $plan = $link->metadata['plan'] ?? null;
        if (! is_array($plan) || empty($plan['object']) || empty($plan['days'])) {
            return;
        }

        $user = $this->resolvePayer($link);
        if (! $user) {
            Log::warning('Plan payment link paid but payer could not be resolved', ['payment_link_id' => $link->id]);

            return;
        }

        $link->forceFill(['client_id' => $user->id])->save();

        $this->subscriptions->processSubscription(
            $user,
            (float) $link->amount,
            (int) $plan['days'],
            [(string) $plan['object']],
            false,
            (string) $plan['title'],
            'payment_link',
        );

        Log::info('Plan payment link fulfilled', ['payment_link_id' => $link->id, 'user_id' => $user->id, 'object' => $plan['object']]);
    }

    private function resolvePayer(PaymentLink $link): ?User
    {
        if ($link->client_id) {
            return User::find($link->client_id);
        }

        $email = Str::lower(trim((string) ($link->metadata['payer']['email'] ?? '')));
        if ($email === '') {
            return null;
        }

        return User::where('email', $email)->first() ?? $this->createAccount($link, $email);
    }

    /**
     * Guest checkout: open an account for the payer and email a one-time link that sets
     * a password, signs them in and sends them straight back to the system they paid for.
     */
    private function createAccount(PaymentLink $link, string $email): User
    {
        $user = User::create([
            'name' => (string) ($link->metadata['payer']['name'] ?? Str::before($email, '@')),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'currency_id' => $link->currency_id,
        ]);

        $next = '/sso/'.($link->metadata['plan']['system'] ?? '');
        Mail::to($user->email)->send(new PlanAccountCreatedMail(
            $user,
            SetPasswordController::issueLink($user, null, $next),
            (string) $link->metadata['plan']['title'],
        ));

        return $user;
    }

    private function owner(): User
    {
        $email = config('saas.payment_link_owner_email');
        $owner = $email
            ? User::where('email', $email)->first()
            : User::whereHas('roles', fn ($q) => $q->whereIn('name', self::ADMIN_ROLES))->orderBy('id')->first();

        if (! $owner) {
            throw new RuntimeException('No owner account configured for system payment links (SYSTEM_PAYMENT_LINK_OWNER_EMAIL).');
        }

        return $owner;
    }

    private function currencyId(string $code): int
    {
        $currency = Currency::where('currency', strtoupper($code))->first();
        if (! $currency) {
            throw new RuntimeException("Currency {$code} is not configured.");
        }

        return (int) $currency->id;
    }
}
