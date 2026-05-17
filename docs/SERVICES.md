# Domain Services Architecture Guide

## The Service Layer Pattern

In standard MVC web applications, developers frequently suffer from "Fat Controller" or "Fat Model" syndromes, where complex business rules, external API calls, financial calculations, and event dispatching are crammed into controller actions or ORM models.

The ERP System strictly enforces the **Service Layer Pattern**. Controllers are responsible only for validating incoming HTTP requests and returning Inertia or JSON responses. Models are responsible only for representing database relationships and query scopes. All actual business logic is encapsulated inside dedicated Service classes located in `Modules/{ModuleName}/Services/`.

## Core Platform Services

### 1. `ExchangeRateService` (`Modules/Core/Services/ExchangeRateService.php`)
Manages daily currency snapshots, external exchange rate API fetching, and runtime financial conversions.

```php
namespace Modules\Core\Services;

use Modules\Core\Models\ExchangeRate;
use Modules\Core\Models\Currency;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ExchangeRateService
{
    public function convert(float $amount, string $fromCode, string $toCode, ?Carbon $date = null): float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $targetDate = ($date ?? Carbon::now())->toDateString();

        // 1. Check local database snapshot for the requested date
        $rateRecord = ExchangeRate::where('from_currency', $fromCode)
            ->where('to_currency', $toCode)
            ->where('effective_date', $targetDate)
            ->first();

        if ($rateRecord) {
            return round($amount * (float) $rateRecord->rate, 2);
        }

        // 2. Fetch live rate from external API if missing
        $rate = $this->fetchFromApi($fromCode, $toCode);
        
        // 3. Store snapshot in local database
        ExchangeRate::create([
            'from_currency' => $fromCode,
            'to_currency' => $toCode,
            'rate' => $rate,
            'effective_date' => $targetDate,
            'source' => 'api_auto',
        ]);

        return round($amount * $rate, 2);
    }

    protected function fetchFromApi(string $from, string $to): float
    {
        $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");
        return (float) $response->json()['rates'][$to];
    }
}
```

### 2. `WalletService` (`Modules/ERP/Services/WalletService.php`)
Governs client balance updates, escrow holds, and writing immutable records to `client_wallet_transactions`.

```php
namespace Modules\ERP\Services;

use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\ClientWalletTransaction;
use Modules\Core\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    public function __construct(protected ExchangeRateService $exchangeService) {}

    public function credit(int $walletId, float $amount, string $currency, string $note, $reference = null): ClientWalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be strictly positive.");
        }

        return DB::transaction(function () use ($walletId, $amount, $currency, $note, $reference) {
            $wallet = ClientWallet::lockForUpdate()->findOrFail($walletId);
            
            // Convert to base business currency snapshot
            $businessAmount = $this->exchangeService->convert($amount, $currency, 'USD');
            $rate = $this->exchangeService->convert(1, $currency, 'USD');

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            return ClientWalletTransaction::create([
                'tenant_id' => $wallet->tenant_id,
                'wallet_id' => $wallet->id,
                'type' => 'manual_credit',
                'direction' => 'credit',
                'amount' => $amount,
                'amount_currency' => $currency,
                'business_amount' => $businessAmount,
                'business_currency' => 'USD',
                'exchange_rate' => $rate,
                'exchange_rate_date' => now()->toDateString(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'note' => $note,
                'created_by' => auth()->id() ?? 1,
            ]);
        });
    }

    public function debit(int $walletId, float $amount, string $currency, string $note, $reference = null): ClientWalletTransaction
    {
        return DB::transaction(function () use ($walletId, $amount, $currency, $note, $reference) {
            $wallet = ClientWallet::lockForUpdate()->findOrFail($walletId);
            
            if ($wallet->balance < $amount) {
                throw new Exception("Insufficient wallet funds to complete debit.");
            }

            $businessAmount = $this->exchangeService->convert($amount, $currency, 'USD');
            $rate = $this->exchangeService->convert(1, $currency, 'USD');

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            return ClientWalletTransaction::create([
                'tenant_id' => $wallet->tenant_id,
                'wallet_id' => $wallet->id,
                'type' => 'manual_debit',
                'direction' => 'debit',
                'amount' => $amount,
                'amount_currency' => $currency,
                'business_amount' => $businessAmount,
                'business_currency' => 'USD',
                'exchange_rate' => $rate,
                'exchange_rate_date' => now()->toDateString(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'note' => $note,
                'created_by' => auth()->id() ?? 1,
            ]);
        });
    }
}
```

### 3. `RecurringBillingService` (`Modules/ERP/Services/RecurringBillingService.php`)
Executes daily cron evaluations to automatically generate repeating invoices and log execution outcomes in `recurring_execution_logs`.

```php
namespace Modules\ERP\Services;

use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\RecurringExecutionLog;
use Modules\ERP\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringBillingService
{
    public function processDueEntries(): int
    {
        $today = Carbon::today()->toDateString();
        $entries = RecurringEntry::where('status', 'active')
            ->where('next_run_at', '<=', $today)
            ->get();

        $processedCount = 0;

        foreach ($entries as $entry) {
            DB::transaction(function () use ($entry, &$processedCount) {
                try {
                    // Create actual billable invoice copy
                    $invoice = Invoice::create([
                        'tenant_id' => $entry->tenant_id,
                        'invoice_number' => 'REC-' . time() . '-' . $entry->id,
                        'client_id' => $entry->client_id,
                        'status' => 'draft',
                        'amount' => $entry->amount,
                        'amount_currency' => $entry->amount_currency,
                        'business_amount' => $entry->business_amount,
                        'business_currency' => $entry->business_currency,
                        'exchange_rate' => $entry->exchange_rate,
                        'exchange_rate_date' => now()->toDateString(),
                        'due_date' => now()->addDays(14)->toDateString(),
                        'issued_at' => now(),
                        'notes' => 'Automated recurring billing generation.',
                    ]);

                    // Write success execution audit log
                    RecurringExecutionLog::create([
                        'recurring_entry_id' => $entry->id,
                        'executed_at' => now(),
                        'status' => 'success',
                        'amount' => $entry->amount,
                        'amount_currency' => $entry->amount_currency,
                        'business_amount' => $entry->business_amount,
                        'business_currency' => $entry->business_currency,
                        'exchange_rate' => $entry->exchange_rate,
                        'exchange_rate_date' => now()->toDateString(),
                        'note' => "Successfully generated invoice #{$invoice->id}",
                    ]);

                    // Advance next_run_at timestamp based on frequency rule
                    $entry->last_run_at = now()->toDateString();
                    $entry->next_run_at = match ($entry->frequency) {
                        'daily' => now()->addDay()->toDateString(),
                        'weekly' => now()->addWeek()->toDateString(),
                        'monthly' => now()->addMonth()->toDateString(),
                        'yearly' => now()->addYear()->toDateString(),
                    };
                    $entry->save();

                    $processedCount++;
                } catch (\Exception $e) {
                    RecurringExecutionLog::create([
                        'recurring_entry_id' => $entry->id,
                        'executed_at' => now(),
                        'status' => 'failed',
                        'amount' => $entry->amount,
                        'amount_currency' => $entry->amount_currency,
                        'business_amount' => $entry->business_amount,
                        'business_currency' => $entry->business_currency,
                        'exchange_rate' => $entry->exchange_rate,
                        'exchange_rate_date' => now()->toDateString(),
                        'note' => "Execution failure: " . $e->getMessage(),
                    ]);
                }
            });
        }

        return $processedCount;
    }
}
```

## Dependency Injection in Controllers

When developing controllers, always inject domain services into the constructor or method signatures rather than instantiating them manually with `new Service()`. This allows seamless mocking during unit and feature testing.

```php
namespace Modules\ERP\Http\Controllers;

use Modules\ERP\Services\WalletService;
use Illuminate\Http\Request;

class WalletManagementController
{
    public function __construct(protected WalletService $walletService) {}

    public function creditBalance(Request $request, int $walletId)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->walletService->credit($walletId, $validated['amount'], 'USD', $validated['note']);

        return back()->with('success', 'Wallet balance successfully credited.');
    }
}
```
