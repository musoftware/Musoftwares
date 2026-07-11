<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($payout) {
            if (empty($payout->uuid)) {
                $payout->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function sub_total()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->total;
        }

        return (float) $total;
    }

    public function getTotalAttribute()
    {
        return round($this->sub_total() + $this->tax, 2);
    }

    public function total()
    {
        return $this->getTotalAttribute();
    }

    public function total_str()
    {
        return FinanceHelper::instance()->format_money($this->total(), $this->currency_id);
    }

    public function mark_as_paid()
    {
        DB::transaction(function () {
            if ($this->status === 'paid') {
                return;
            }

            $total = $this->total();

            if ($total > 0) {
                $project = null;
                if (! empty($this->project_id)) {
                    $project = Project::find($this->project_id);
                }

                $this->user->add_balance($total, 'Payout #'.$this->id, 'received', $this->currency_id, $project);
                $this->user->add_balance(-1 * $total, 'Payout #'.$this->id, 'used', $this->currency_id, $project);
            }

            $this->paid_amount = $total;
            $this->status = 'paid';
            $this->save();
        });
    }
}
