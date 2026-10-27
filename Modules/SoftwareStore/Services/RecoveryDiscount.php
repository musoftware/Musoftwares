<?php

namespace Modules\SoftwareStore\Services;

use App\Models\AdminSettings;
use Modules\SoftwareStore\Models\SoftwareOrder;

/**
 * The one discount of the abandoned-order reminder. It is real: the link in the email applies it to
 * the order, once, and only while that link is valid. The percent lives in AdminSettings (0 turns it off).
 */
class RecoveryDiscount
{
    public const SETTING = 'software_recovery_discount_percent';

    public const DEFAULT_PERCENT = 10;

    public const MAX_PERCENT = 30;

    /** How long after the reminder the discount can still be used; the signed link expires at the same time. */
    public const VALID_HOURS = 72;

    public function percent(): int
    {
        $percent = (int) AdminSettings::GetValue(self::SETTING, self::DEFAULT_PERCENT);

        return max(0, min(self::MAX_PERCENT, $percent));
    }

    /** The price after the discount, or null when this order is not eligible. Always counted from the plan price, so it never stacks. */
    public function discountedAmount(SoftwareOrder $order): ?float
    {
        $percent = $this->percent();

        if ($percent === 0 || ! $this->eligible($order)) {
            return null;
        }

        $price = $order->product->salePrice($order->plan_key);

        return $price > 0 ? round($price * (100 - $percent) / 100, 2) : null;
    }

    /** Sets the discounted amount on an eligible unpaid order. Returns true when the amount was changed or already discounted. */
    public function apply(SoftwareOrder $order): bool
    {
        $amount = $this->discountedAmount($order);

        if ($amount === null) {
            return false;
        }

        if (round((float) $order->amount, 2) !== $amount) {
            $order->forceFill(['amount' => $amount])->save();
        }

        return true;
    }

    private function eligible(SoftwareOrder $order): bool
    {
        return ! $order->isPaid()
            && $order->white_label_partner_id === null
            && $order->product !== null
            && $order->reminder_sent_at !== null
            && $order->reminder_sent_at->copy()->addHours(self::VALID_HOURS)->isFuture();
    }
}
