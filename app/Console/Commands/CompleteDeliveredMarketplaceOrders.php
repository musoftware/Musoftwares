<?php

namespace App\Console\Commands;

use App\Events\MarketplaceOrderCompleted;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Http\Controllers\ServiceOrderController;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\ServiceOrder;

class CompleteDeliveredMarketplaceOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:auto-complete-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically completes marketplace orders that have been delivered and passed the auto-completion window.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orders = ServiceOrder::where('status', 'delivered')
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', now())
            ->get();

        $count = 0;

        // We will mock the buyer being authenticated or manually process the logic
        // Because ServiceOrderController::complete requires auth()->id() === buyer_id
        // We shouldn't use the controller for cron jobs, we should use the service.

        foreach ($orders as $order) {
            DB::beginTransaction();
            try {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Release Escrow: Credit Seller balance
                $seller = User::find($order->seller_id);
                if ($seller) {
                    $sellerCredit = $order->amount - $order->commission_amount;
                    $transactionId = $seller->add_balance($sellerCredit, "Earnings from service order #{$order->id} (Escrow Auto-Released)", 'received', $order->currency_id);

                    // Update Escrow Record
                    $escrow = MarketplaceEscrow::where('order_id', $order->id)->first();
                    if ($escrow) {
                        $escrow->update([
                            'status' => 'released',
                            'seller_wallet_transaction_id' => $transactionId,
                            'released_at' => now(),
                        ]);
                    }
                }

                DB::commit();

                event(new MarketplaceOrderCompleted($order));

                $this->info("Order #{$order->id} auto-completed successfully.");
                $count++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to auto-complete order #{$order->id}: ".$e->getMessage());
            }
        }

        $this->info("Total {$count} orders auto-completed.");

        return 0;
    }
}
