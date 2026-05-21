<?php

namespace App\Models;

use App\Helpers\BalancesHelper;
use App\Trait\ChatModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceOrder extends Model
{
    use HasFactory;

    use ChatModelTrait;

    protected $fillable = [
        'status',
        'started_at',
        'delivery_message',
        'serial_key',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by_user_id',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    public function ChatName()
    {
        return ServiceOrder::class;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cancelled_by()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function auth_owner()
    {
        if (Auth::user()->id == $this->user->id) {
            return true;
        } else {
            return false;
        }
    }

    public function auth_provider()
    {
        if (Auth::user()->id == $this->service->user->id) {
            return true;
        } else {
            return false;
        }
    }


    public function user_view()
    {
        if ($this->service->user_id == \Illuminate\Support\Facades\Auth::id()) {
            return $this->user;
        }
        if ($this->user_id == \Illuminate\Support\Facades\Auth::id()) {
            return $this->service->user;
        }
    }

    public function buyer_transaction()
    {
        return $this->belongsTo(Transaction::class, 'buyer_transaction_id');
    }


    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function serials()
    {
        return $this->hasMany(ServiceSerial::class, 'order_id');
    }

    public function extras()
    {
        return $this->hasMany(ServiceOrderExtra::class, 'service_order_id');
    }

    public function deliveryFiles()
    {
        return $this->hasMany(OrderDeliveryFile::class, 'service_order_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeBySeller($query, $sellerId)
    {
        return $query->whereHas('service', function ($serviceQuery) use ($sellerId) {
            $serviceQuery->where('user_id', $sellerId);
        });
    }

    public function getDeadlineAttribute()
    {
        return $this->created_at->addDays($this->delivery_days ?? 7);
    }

    public function isOverdue()
    {
        return $this->deadline < now() && !in_array($this->status, ['delivered', 'completed', 'cancelled']);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getTotalPriceAttribute()
    {
        return ($this->buyer_service_amount ?? 0) * ($this->qty ?? 1);
    }

    public function total_view()
    {
        if ($this->service->user_id == \Illuminate\Support\Facades\Auth::id()) {
            return $this->seller_service_amount;
        }
        if ($this->user_id == \Illuminate\Support\Facades\Auth::id()) {
            return $this->buyer_service_amount;
        }
    }


    public function total_view_str()
    {
        if ($this->service->user_id == \Illuminate\Support\Facades\Auth::id()) {
            //            return $this->seller_service_amount;
            return \App\Helpers\FinanceHelper::instance()->format_money($this->total_view(), $this->seller_currency);
        }
        if ($this->user_id == \Illuminate\Support\Facades\Auth::id()) {
            return \App\Helpers\FinanceHelper::instance()->format_money($this->total_view(), $this->buyer_currency);
        }
    }

    public function orderClosed()
    {
        if ($this->cancelled_at != null && Carbon::now() > $this->cancelled_at) {
            return true;
        }
    }

    public function accept_order()
    {
        if ($this->auth_owner()) {
            if ($this->completed_at != null && Carbon::now() < $this->completed_at) {
                $this->completed_at = Carbon::now();
                $this->save();
            }
        }
    }

    public function perform_cancel()
    {
        if ($this->cancelled_at != null && Carbon::now() > $this->cancelled_at) {
            DB::transaction(function () {
                $trans = $this->buyer_transaction;
                $this->buyer_transaction_id = null;
                $this->save();
                $trans->delete();

                $this->status = 'cancelled';
                $this->save();
            });

            BalancesHelper::UpdateBalance($this->user, null);
        }
    }

    /** Total platform fee for this order (total buyer paid minus total seller receives). */
    public function total_min_cost()
    {
        $total_buyer = ($this->buyer_service_amount ?? 0) * ($this->qty ?? 1);
        return $total_buyer - ($this->seller_service_amount ?? 0);
    }

    public function perform_completed()
    {
        if ($this->completed_at != null && Carbon::now() > $this->completed_at) {
            DB::transaction(function () {

                $paid = $this->seller_service_amount;
                $paid_currency = $this->seller_currency;
                $seller_profit = new Earning();
                $seller_profit->user_id = $this->service->user->id;
                $seller_profit->referred_user_id = null;
                $seller_profit->referred_invoice_id = null;
                $seller_profit->currency = $paid_currency;
                $seller_profit->amount = CurrenciesExchange::RateToday($paid, $paid_currency, $this->service->user_id);
                if ($paid > 0) {
                    $seller_profit->convert_to_balance_on = date('Y-m-d H:i:s', strtotime('+2 days'));
                } else {
                    $seller_profit->convert_to_balance_on = date('Y-m-d H:i:s');
                }
                $seller_profit->save();
                $this->seller_earning_id = $seller_profit->id;

                // Use affiliate amount set at order creation (respects service owner's "from fee" vs "from seller price" choice)
                if ($this->affiliate_user_id && (float) $this->affiliate_service_amount > 0) {
                    $affiliate_earning = new Earning();
                    $affiliate_earning->user_id = $this->affiliate_user_id;
                    $affiliate_earning->referred_user_id = $this->user_id;
                    $affiliate_earning->referred_invoice_id = null;
                    $affiliate_earning->currency = $this->affiliate_currency;
                    $affiliate_earning->amount = $this->affiliate_service_amount;
                    $affiliate_earning->convert_to_balance_on = date('Y-m-d H:i:s', strtotime('+14 days'));
                    $affiliate_earning->save();
                    $this->affiliate_earning_id = $affiliate_earning->id;
                } else {
                    $affiliate = $this->user->calc_ref($this->total_min_cost(), null, $this->buyer_currency, 1.5);
                    if ($affiliate != null) {
                        $this->affiliate_earning_id = $affiliate->id;
                        $this->affiliate_user_id = $affiliate->user_id;
                        $this->affiliate_service_amount = $affiliate->amount;
                        $this->affiliate_currency = $affiliate->currency;
                    }
                }
                $this->status = 'completed';
                $this->save();
            });

            BalancesHelper::UpdateBalance($this->user, null);
        }
    }


    //

    public function remove_cancel()
    {
        if ($this->cancelled_at != null) {
            $this->cancelled_at = null;
            $this->save();
        }
    }

    public function request_modification()
    {
        if ($this->completed_at != null) {
            $this->completed_at = null;
            $this->save();
        }
    }

    //

    public function cancel($user_id = null)
    {
        if ($this->cancelled_at == null) {
            $this->cancelled_at = Carbon::now()->addDays(3);
            $this->cancelled_by_user_id = $user_id;
            $this->save();
        }
    }

    public function mark_as_completed()
    {
        if ($this->completed_at == null) {
            $this->completed_at = Carbon::now()->addDays(3);
            $this->save();
        }
    }

    /**
     * Get a granular status label for the UI based on logical critique.
     */
    public function getDetailedStatus(): array
    {
        if ($this->cancelled_at != null && $this->status != 'cancelled') {
            return [
                'label' => __('service_orders.detailed_status.pending_cancellation.label'),
                'icon' => 'clock-pause',
                'color' => 'warning',
                'description' => __('service_orders.detailed_status.pending_cancellation.description_prefix') . $this->cancelled_at->diffForHumans()
            ];
        }

        if ($this->completed_at != null && $this->status != 'completed') {
            return [
                'label' => __('service_orders.detailed_status.awaiting_review.label'),
                'icon' => 'clock-check',
                'color' => 'info',
                'description' => __('service_orders.detailed_status.awaiting_review.description_prefix') . $this->completed_at->diffForHumans()
            ];
        }

        return match ($this->status) {
            'completed' => [
                'label' => __('Completed'),
                'icon' => 'check',
                'color' => 'success',
                'description' => null
            ],
            'cancelled' => [
                'label' => __('Cancelled'),
                'icon' => 'x',
                'color' => 'danger',
                'description' => null
            ],
            'active' => [
                'label' => __('In Progress'),
                'icon' => 'player-play',
                'color' => 'primary',
                'description' => null
            ],
            default => [
                'label' => ucfirst($this->status),
                'icon' => 'dots',
                'color' => 'secondary',
                'description' => null
            ]
        };
    }

}
