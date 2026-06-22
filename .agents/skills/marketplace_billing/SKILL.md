---
name: marketplace_billing
description: >
  Authoritative rules for the Marketplace purchase flow. The Marketplace is a first-class
  module of the Musoftware platform. Every purchase MUST deduct from the user's real wallet
  balance using DB transactions. No sandbox, no mock, no free bypass. Covers tool subscriptions,
  wallet deduction, currency conversion, insufficient-balance handling, and subscription activation.
---

# Marketplace Billing — Integrated Purchase Flow

## Core Principle (NON-NEGOTIABLE)

> **The Marketplace is NOT a separate system. It is an integral part of the Musoftware billing engine.**
> Every purchase made in the Marketplace MUST deduct real credits from the user's real wallet balance.
> There is NO demo mode, sandbox toggle, or test-bypass for production purchases.

---

## Activation Conditions

This skill applies automatically whenever you are:
- Implementing or modifying the "Subscribe" / "Buy Now" flow for any tool in the Marketplace.
- Building or editing `ToolSubscriptionController`, `MarketplaceController`, or any purchase handler.
- Deducting wallet credits for tool purchases, renewals, or plan upgrades.
- Displaying tool pricing, wallet balance, or affordability checks in the Marketplace UI.
- Writing tests that cover marketplace purchase flows.

---

## 1. The Real Balance Rule

### ❌ FORBIDDEN — Never Do This
```php
// Skipping wallet deduction entirely
$subscription->status = 'active';
$subscription->save();

// Using a fake/mocked balance
$user->user_balance = 9999999;

// Granting access without checking balance
if ($user->hasRole('admin')) { $subscription->activate(); }

// Deducting outside a DB transaction
$user->user_balance -= $cost;
$user->save();
```

### ✅ REQUIRED — Always Do This
```php
DB::transaction(function () use ($user, $tool, $cost) {
    // 1. Re-fetch with a write lock to prevent race conditions
    $user = User::lockForUpdate()->findOrFail($user->id);

    // 2. Use available_balance() — NOT user_balance — for the UI check
    //    Use user_balance for the actual deduction after confirming availability
    $availableBalance = $user->available_balance();

    // 3. Convert cost to user's wallet currency before comparing
    $costInWalletCurrency = app(CurrenciesExchange::class)
        ->convert($cost, $sourceCurrencyId, $user->currency_id);

    // 4. Enforce balance sufficiency
    if ($availableBalance < $costInWalletCurrency) {
        throw new InsufficientBalanceException(
            'Insufficient wallet balance to purchase this tool.'
        );
    }

    // 5. Deduct from real wallet
    $user->user_balance -= $costInWalletCurrency;
    $user->save();

    // 6. Record the ledger transaction
    WalletTransaction::create([
        'user_id'     => $user->id,
        'amount'      => -$costInWalletCurrency,
        'type'        => 'debit',
        'description' => "Tool purchase: {$tool->title}",
        'reference'   => $tool->slug,
    ]);

    // 7. Create or extend the tool subscription
    ToolSubscription::updateOrCreate(
        ['user_id' => $user->id, 'tool_id' => $tool->id],
        [
            'status'     => 'active',
            'expires_at' => now()->addDays($billingDays),
            'auto_renew' => $request->boolean('auto_renew', false),
        ]
    );
});
```

---

## 2. Balance Display Rules in the Marketplace UI

Always show `available_balance()`, never raw `user_balance`.

### Backend — Controller / Resource
```php
// MarketplaceController.php
return Inertia::render('Marketplace/Index', [
    'tools'             => ToolResource::collection($tools),
    'available_balance' => (float) $user->available_balance(),   // ✅ available
    'wallet_currency'   => $user->currency_name(),
]);
```

### Frontend — React/TSX
```tsx
// Marketplace/Index.tsx
export default function MarketplaceIndex({ tools, available_balance, wallet_currency }) {
    return (
        <div>
            <WalletBadge
                balance={available_balance ?? 0}
                currency={wallet_currency ?? 'USD'}
            />
            {tools.map(tool => (
                <ToolCard
                    key={tool.id}
                    tool={tool}
                    canAfford={available_balance >= tool.monthly_price}
                />
            ))}
        </div>
    );
}
```

> **Rule**: If `available_balance < tool.price`, show a disabled "Insufficient Balance" button
> with a "Top Up Wallet" CTA — never silently hide the button or crash.

---

## 3. Currency Conversion Before Deduction

All tool prices are stored in EGP (the platform base currency).
Before deducting from a user's wallet, **always convert** the price to the user's wallet currency.

```php
// Correct conversion flow
$toolPriceEgp   = $tool->price;                     // Always in EGP
$egpCurrencyId  = Currency::where('code', 'EGP')->value('id');
$costConverted  = app(CurrenciesExchange::class)
    ->convert($toolPriceEgp, $egpCurrencyId, $user->currency_id);
```

> **Never** compare or deduct without conversion. A user with a USD wallet should have the
> USD equivalent deducted, not the raw EGP amount.

---

## 4. Insufficient Balance — Graceful Fallback

When `available_balance < cost`, do NOT silently fail or grant access.
Follow this strict fallback order:

1. **Show clear UI error**: "Your wallet balance is insufficient. You need X more."
2. **Offer a Top-Up CTA**: Link to the wallet top-up page (Kashier / Stripe gateway).
3. **Redirect if via API**: Return `422 Unprocessable Entity` with a structured JSON error.

```php
// Controller error response
if ($user->available_balance() < $costConverted) {
    return back()->withErrors([
        'balance' => __('marketplace.insufficient_balance', [
            'needed'    => format_currency($costConverted, $user->currency_name()),
            'available' => format_currency($user->available_balance(), $user->currency_name()),
        ]),
    ]);
}
```

```tsx
// React error rendering
{errors.balance && (
    <Alert variant="destructive">
        <AlertTitle>{t('marketplace.insufficient_balance_title')}</AlertTitle>
        <AlertDescription>{errors.balance}</AlertDescription>
        <Button asChild variant="outline" className="mt-2">
            <Link href={route('wallet.topup')}>{t('marketplace.top_up_wallet')}</Link>
        </Button>
    </Alert>
)}
```

---

## 5. Tool Subscription Model Rules

Tool subscriptions live in the `tool_subscriptions` table (Marketplace-specific),
NOT in `user_subscriptions` (which is for modules like ERP, CRM).

| Column       | Type     | Description                                    |
|--------------|----------|------------------------------------------------|
| `id`         | int      | Primary key                                    |
| `user_id`    | int      | FK to users.id                                 |
| `tool_id`    | int      | FK to tools.id                                 |
| `status`     | string   | `'active'`, `'cancelled'`, `'expired'`         |
| `started_at` | datetime | When subscription began                        |
| `expires_at` | datetime | When subscription expires (null = lifetime)    |
| `auto_renew` | boolean  | Whether to auto-renew via wallet deduction      |

### Key Rules
- **One row per user-tool pair** — Use `updateOrCreate` to avoid duplicates.
- **Extending**: If user re-subscribes, add `$billingDays` to the current `expires_at`, not `now()`.
- **Expiry check**: Always validate `expires_at > now()` AND `status = 'active'`.
- **Never grant perpetual access** without a valid `expires_at` unless the tool is explicitly `is_free = true`.

```php
// Correct: Extending an existing subscription
$existing = ToolSubscription::where('user_id', $user->id)
    ->where('tool_id', $tool->id)
    ->first();

$newExpiry = $existing && $existing->expires_at?->isFuture()
    ? $existing->expires_at->addDays($billingDays)
    : now()->addDays($billingDays);

ToolSubscription::updateOrCreate(
    ['user_id' => $user->id, 'tool_id' => $tool->id],
    ['status' => 'active', 'expires_at' => $newExpiry, 'started_at' => $existing?->started_at ?? now()]
);
```

---

## 6. Free Tools — No Wallet Deduction

Tools with `is_free = true` in `config/tools.php` are installed without wallet deduction.
They still create a `ToolSubscription` row for tracking, but no financial transaction occurs.

```php
if ($tool->is_free) {
    // Directly create subscription — no balance check, no deduction
    ToolSubscription::updateOrCreate(
        ['user_id' => $user->id, 'tool_id' => $tool->id],
        ['status' => 'active', 'expires_at' => null, 'started_at' => now()]
    );
    return back()->with('success', __('marketplace.tool_installed'));
}
// Else: proceed through the full real-balance deduction flow above
```

---

## 7. Auto-Renewal — Wallet Deduction via Scheduler

Auto-renewal MUST run via a Laravel scheduled job, NOT inline during web requests.

```php
// app/Console/Commands/RenewToolSubscriptions.php
class RenewToolSubscriptions extends Command
{
    public function handle(): void
    {
        $expiring = ToolSubscription::query()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', now()->addDay())
            ->with(['user', 'tool'])
            ->get();

        foreach ($expiring as $sub) {
            try {
                DB::transaction(function () use ($sub) {
                    $user = User::lockForUpdate()->findOrFail($sub->user_id);
                    $cost = app(CurrenciesExchange::class)->convert(
                        $sub->tool->price,
                        Currency::where('code', 'EGP')->value('id'),
                        $user->currency_id
                    );

                    if ($user->available_balance() < $cost) {
                        // Cancel instead of partial deduct
                        $sub->update(['status' => 'cancelled']);
                        // Notify user
                        $user->notify(new ToolSubscriptionCancelledNotification($sub->tool));
                        return;
                    }

                    $user->user_balance -= $cost;
                    $user->save();

                    WalletTransaction::create([
                        'user_id'     => $user->id,
                        'amount'      => -$cost,
                        'type'        => 'debit',
                        'description' => "Auto-renewal: {$sub->tool->title}",
                        'reference'   => $sub->tool->slug,
                    ]);

                    $sub->update([
                        'expires_at' => $sub->expires_at->addDays(30),
                        'status'     => 'active',
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error("Auto-renewal failed for subscription #{$sub->id}: {$e->getMessage()}");
            }
        }
    }
}
```

> **Rule**: Auto-renewal failures must **never** silently fail. Log every failure with the
> subscription ID, user ID, tool slug, and exception message.

---

## 8. Access Gate (Runtime Enforcement)

The Runtime Agent must check `ToolSubscription` status from the cloud before activating any paid plugin.
See the `/plugin-distribution` workflow for the full signed-URL + local SQLite caching flow.

The backend subscription check endpoint must always enforce:
```php
->where('status', 'active')
->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
```

A subscription with `status = 'active'` but `expires_at` in the past is **EXPIRED** and must
be treated as inactive in all access checks.

---

## 9. Pricing Source of Truth

All marketplace tool prices come from `config/tools.php`. **Never hardcode prices in controllers or views.**

```php
// config/tools.php
return [
    'tools' => [
        'whatsapp-automation' => [
            'price'      => 500,   // Monthly price in EGP
            'is_free'    => false,
            'name'       => 'WhatsApp Automation',
            // ...
        ],
        'basic-crm-connector' => [
            'price'      => 0,
            'is_free'    => true,
            'name'       => 'Basic CRM Connector',
        ],
    ],
];
```

```php
// Controller — always read from config
$toolConfig = config("tools.tools.{$tool->slug}");
$price = $toolConfig['price'] ?? 0;
```

---

## 10. Security Rules

- **NEVER trust the price from the request payload.** Always read the canonical price from `config/tools.php` or the `tools` database table. A user could forge the POST body to claim a tool costs `0`.
- **ALWAYS use `lockForUpdate()`** when reading `user_balance` inside a transaction to prevent concurrent deductions.
- **ALWAYS verify** that `ToolSubscription.user_id === auth()->id()` when checking subscription status on the frontend. Never expose another user's subscription state.
- **API middleware**: All purchase endpoints MUST be behind `auth:sanctum` AND the user must have a verified email (`verified` middleware).

```php
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/marketplace/tools/{tool}/subscribe', [ToolSubscriptionController::class, 'subscribe']);
    Route::delete('/marketplace/tools/{tool}/cancel',  [ToolSubscriptionController::class, 'cancel']);
});
```

---

## 11. Git Workflow (REQUIRED FOR AI AGENTS)

- Every marketplace billing change MUST be developed on a dedicated branch:
  `feature/marketplace-billing-<short-description>` or `fix/marketplace-<short-description>`.
- Commit after each logical step: guard setup, deduction logic, subscription upsert, tests.
- Never commit directly to `main` or `master`.

---

## Summary Checklist

- [ ] Is the wallet deduction wrapped in `DB::transaction()`?
- [ ] Is `User::lockForUpdate()` used inside the transaction to prevent race conditions?
- [ ] Is the tool price read from `config/tools.php`, NOT from the request payload?
- [ ] Is the price converted to the user's wallet currency before comparison and deduction?
- [ ] Is `available_balance()` used for the sufficiency check (not raw `user_balance`)?
- [ ] Is a `WalletTransaction` ledger entry created for every deduction?
- [ ] Does the UI show an "Insufficient Balance" error with a Top-Up CTA when balance is low?
- [ ] Are free tools (`is_free = true`) correctly bypassing the balance check?
- [ ] Is auto-renewal handled via a scheduled job and NOT inline during web requests?
- [ ] Does the access gate enforce BOTH `status = 'active'` AND `expires_at > now()`?
- [ ] Are all purchase endpoints protected by `auth:sanctum` and `verified` middleware?
- [ ] Is the subscription extended (not reset) when a user re-subscribes before expiry?
