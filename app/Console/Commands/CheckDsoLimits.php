<?php

namespace App\Console\Commands;

use App\Models\AdminSettings;
use App\Models\Invoice;
use App\Models\SerialUserDevice;
use App\Models\User;
use App\Notifications\DsoSuspensionNotification;
use App\Notifications\DsoWarning1Notification;
use App\Notifications\DsoWarning2Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDsoLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-dso-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check clients oldest unpaid invoices against global DSO limit, send warnings, and suspend serials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) AdminSettings::GetValue('global_dso_limit', 30);
        $this->info("Current global DSO limit: {$limit} days.");

        // Find users who have unpaid or partially paid invoices that are not archived
        $users = User::whereHas('invoices', function ($q) {
            $q->where('unpaid', '>', 0)
              ->whereIn('status', ['unpaid', 'partially_paid'])
              ->where('archive', '0');
        })->get();

        $this->info("Found " . $users->count() . " client(s) with unpaid invoices to inspect.");

        foreach ($users as $user) {
            $oldestInvoice = $user->oldestUnpaidInvoice();
            if (!$oldestInvoice) {
                continue;
            }

            $createdAt = Carbon::parse($oldestInvoice->created_at)->timezone('Africa/Cairo');
            $age = (int) $createdAt->startOfDay()->diffInDays(now('Africa/Cairo')->startOfDay());

            $this->info("Client #{$user->id} ({$user->name}): Oldest unpaid invoice #{$oldestInvoice->id} created {$age} days ago.");

            $totalUnpaid = $user->totalUnpaidAmount();

            try {
                // If the limit L is 30, warning 1 is at L-2 (28), warning 2 is at L-1 (29), suspension is at >= L (30)
                if ($age >= ($limit - 2) && $age < ($limit - 1)) {
                    if (!$oldestInvoice->dso_warning_1_sent_at) {
                        $this->info("-> Sending Warning 1 to client #{$user->id} (Outstanding: {$totalUnpaid})");
                        $user->notify(new DsoWarning1Notification($totalUnpaid));
                        $oldestInvoice->update(['dso_warning_1_sent_at' => now('Africa/Cairo')]);
                    } else {
                        $this->info("-> Warning 1 already sent on {$oldestInvoice->dso_warning_1_sent_at}");
                    }
                } elseif ($age >= ($limit - 1) && $age < $limit) {
                    if (!$oldestInvoice->dso_warning_2_sent_at) {
                        $this->info("-> Sending Warning 2 to client #{$user->id} (Outstanding: {$totalUnpaid})");
                        $user->notify(new DsoWarning2Notification($totalUnpaid));
                        $oldestInvoice->update(['dso_warning_2_sent_at' => now('Africa/Cairo')]);
                    } else {
                        $this->info("-> Warning 2 already sent on {$oldestInvoice->dso_warning_2_sent_at}");
                    }
                } elseif ($age >= $limit) {
                    // Send suspension notification if not already sent
                    if (!$oldestInvoice->dso_deactivated_sent_at) {
                        $this->info("-> Sending Suspension Notice to client #{$user->id} (Outstanding: {$totalUnpaid})");
                        $user->notify(new DsoSuspensionNotification($totalUnpaid));
                        $oldestInvoice->update(['dso_deactivated_sent_at' => now('Africa/Cairo')]);
                    } else {
                        $this->info("-> Suspension Notice already sent on {$oldestInvoice->dso_deactivated_sent_at}");
                    }

                    // Suspend serials
                    $activeSerials = SerialUserDevice::where('user_id', $user->id)
                        ->where('status', SerialUserDevice::STATUS_ACTIVE)
                        ->get();

                    if ($activeSerials->isNotEmpty()) {
                        $this->info("-> Suspending {$activeSerials->count()} active serial(s) for client #{$user->id}");
                        foreach ($activeSerials as $serial) {
                            $serial->update([
                                'status' => SerialUserDevice::STATUS_INACTIVE,
                                'notes' => ($serial->notes ? $serial->notes.' | ' : '').
                                    'Auto-deactivated on '.now('Africa/Cairo')->format('Y-m-d H:i:s').
                                    ' due to unpaid invoice #'.$oldestInvoice->id.' exceeding DSO limit of '.$limit.' days. No rollback allowed until paid.',
                            ]);
                        }

                        Log::warning("Client serials suspended due to DSO limit violation", [
                            'user_id' => $user->id,
                            'oldest_invoice_id' => $oldestInvoice->id,
                            'invoice_age_days' => $age,
                            'dso_limit_days' => $limit,
                            'suspended_serials_count' => $activeSerials->count(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error checking DSO for client #{$user->id}: " . $e->getMessage());
                Log::error("Error checking DSO for client #{$user->id}: " . $e->getMessage(), [
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
        }

        $this->info("DSO checks completed.");
    }
}
