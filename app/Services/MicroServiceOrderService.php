<?php

namespace App\Services;

use App\Helpers\FinanceHelper;
use App\Mail\AdminMicroServiceOrderMail;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\MicroService;
use App\Models\MicroServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MicroServiceOrderService extends BaseService
{
    /**
     * Purchase a micro-service with client's available wallet balance.
     */
    public function purchase(User $user, MicroService $microService, string $requirements): MicroServiceOrder
    {
        if (! $microService->is_active) {
            throw ValidationException::withMessages([
                'service' => ['هذه الخدمة غير متاحة للطلب حالياً.'],
            ]);
        }

        return DB::transaction(function () use ($user, $microService, $requirements) {
            /** @var User $lockedUser */
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $baseCurrencyId = $microService->currency_id ?: (CurrenciesExchange::BusinessCurrency() ?: 1);
            $userCurrencyId = $lockedUser->currency_id ?: 1;

            // Calculate cost in client's wallet currency
            $costInUserCurrency = (float) CurrenciesExchange::RateToday(
                (float) $microService->price,
                $baseCurrencyId,
                $userCurrencyId
            );

            // Available balance check (takes unpaid invoices & reserves into account)
            $availableBalance = (float) $lockedUser->available_balance();

            if ($availableBalance < $costInUserCurrency) {
                $neededFormatted = FinanceHelper::instance()->format_money($costInUserCurrency, $userCurrencyId);
                $availableFormatted = FinanceHelper::instance()->format_money($availableBalance, $userCurrencyId);

                throw ValidationException::withMessages([
                    'balance' => [
                        "رصيدك المتاح ({$availableFormatted}) غير كافٍ لشراء هذه الخدمة التي تتطلب ({$neededFormatted}). يرجى شحن رصيد محفظتك أولاً.",
                    ],
                ]);
            }

            // Deduct from wallet using standard platform add_balance method
            $reason = "شراء خدمة مصغرة: {$microService->title}";
            $transaction = $lockedUser->add_balance(
                -1 * (float) $microService->price,
                $reason,
                'used',
                $baseCurrencyId
            );

            // Create Order
            $order = MicroServiceOrder::create([
                'user_id' => $lockedUser->id,
                'micro_service_id' => $microService->id,
                'amount_paid' => $costInUserCurrency,
                'currency_id' => $userCurrencyId,
                'base_amount' => $microService->price,
                'requirements' => trim($requirements),
                'status' => 'pending',
                'transaction_id' => is_numeric($transaction) ? (int) $transaction : $transaction?->id,
            ]);

            // Notify Admin via Email
            $this->notifyAdmins($order);

            return $order;
        });
    }

    /**
     * Mark an order as completed with optional admin delivery notes.
     */
    public function completeOrder(MicroServiceOrder $order, ?string $adminNotes = null): MicroServiceOrder
    {
        $order->update([
            'status' => 'completed',
            'admin_notes' => $adminNotes ? trim($adminNotes) : $order->admin_notes,
            'completed_at' => now(),
        ]);

        return $order;
    }

    /**
     * Dispatch notification emails to admins.
     */
    protected function notifyAdmins(MicroServiceOrder $order): void
    {
        try {
            $admins = User::role(['admin', 'moderator'])->get(['id', 'email', 'name']);

            if ($admins->isNotEmpty()) {
                foreach ($admins as $admin) {
                    if (! empty($admin->email)) {
                        Mail::to($admin->email)->queue(new AdminMicroServiceOrderMail($order));
                    }
                }
            } else {
                $adminEmail = config('mail.from.address', 'support@musoftwares.com');
                Mail::to($adminEmail)->queue(new AdminMicroServiceOrderMail($order));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch micro service order admin email: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }
}
