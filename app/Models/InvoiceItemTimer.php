<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItemTimer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['invoice_item_id', 'date_start', 'date_end', 'amount', 'project_id', 'user_id', 'currency_id', 'business_amount', 'business_calculated'];

    protected static function booted()
    {
        static::saved(function (InvoiceItemTimer $timer) {
            try {
                $client = $timer->invoiceItem?->invoice?->user
                    ?? $timer->invoiceItem?->invoice?->client
                    ?? $timer->user;

                if ($client && ! $client->hasAnyRole(['admin', 'super-admin', 'employee'])) {
                    $client->notify(new \App\Notifications\TimerSavedNotification($timer));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to dispatch TimerSavedNotification: '.$e->getMessage(), [
                    'timer_id' => $timer->id,
                ]);
            }
        });
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function diff()
    {
        if (! $this->date_end) {
            return 0;
        }

        return Carbon::parse($this->date_end)->diffInSeconds(Carbon::parse($this->date_start));
    }
}
