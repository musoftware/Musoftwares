<?php

namespace Modules\AppBuilder\Services;

use App\Helpers\FinanceHelper;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AppBuilder\Models\BuilderProject;

/** A signed-in customer pays the open price from their site wallet instead of a card. */
class WalletPayment
{
    public function __construct(private readonly ProjectPayment $payment) {}

    /**
     * What the page shows next to the card button: the wallet balance and whether it covers the price.
     *
     * @return array{balance: string, enough: bool, url: string, top_up_url: string}|null
     */
    public function offer(BuilderProject $project, ?User $user): ?array
    {
        $due = $user ? $this->payment->openAmount($project) : null;
        if ($user === null || $due === null) {
            return null;
        }

        $balance = (float) $user->available_balance();

        return [
            'balance' => FinanceHelper::instance()->format_money($balance, $user->currency_id),
            'enough' => $balance >= $this->inWalletCurrency($due, $project, $user),
            'url' => route('builder.pay.wallet', $project),
            'top_up_url' => route('financial.add-balance'),
        ];
    }

    /** Takes the open price from the wallet and starts the build. False when the balance does not cover it. */
    public function pay(BuilderProject $project, User $user): bool
    {
        return DB::transaction(function () use ($project, $user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $due = $this->payment->openAmount($project);
            if ($due === null || (float) $locked->available_balance() < $this->inWalletCurrency($due, $project, $locked)) {
                return false;
            }

            $transactionId = $locked->add_balance(-1 * $due, 'AI builder: '.($project->title ?: 'project #'.$project->id), 'used', $this->currencyId($project));
            $project->update($this->customerDetails($project, $locked));

            return $this->payment->payOutsideGateway($project, $due, 'wallet-'.$transactionId);
        });
    }

    private function inWalletCurrency(float $amount, BuilderProject $project, User $user): float
    {
        return (float) CurrenciesExchange::RateToday($amount, $this->currencyId($project), $user->currency_id);
    }

    private function currencyId(BuilderProject $project): ?int
    {
        return Currency::resolve($project->currency)?->id;
    }

    /** The account's details fill what the card form would have asked for; the project joins the account. */
    private function customerDetails(BuilderProject $project, User $user): array
    {
        return [
            'user_id' => $project->user_id ?? $user->id,
            'client_name' => $project->client_name ?: $user->name,
            'client_email' => $project->client_email ?: $user->email,
            'client_whatsapp' => $project->client_whatsapp ?: ($user->mobile_1 ?: $user->whatsapp_number),
        ];
    }
}
