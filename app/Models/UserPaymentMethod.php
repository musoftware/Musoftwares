<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentMethod extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function type_name()
    {
        $t = $this->type;
        $t = str_replace('-', ' ', $t);
        $t = str_replace('_', ' ', $t);
        return ucfirst($t);
    }

    public function icon()
    {
        if ($this->type == 'bank') {
            return 'bank.png';
        }
        if ($this->type == 'mobile_wallet') {
            return 'wallet.png';
        }
        if ($this->type == 'wallet') {
            return 'wallet.png';
        }
        if ($this->type == 'paypal') {
            return 'paypal.png';
        }
        if ($this->type == 'instapay') {
            return 'instapay.png';
        }
    }

    public function method_data()
    {
        if ($this->type == 'bank') {
            return ucwords($this->bank_name);
        }
        if ($this->type == 'mobile_wallet') {
            return ucwords($this->mobile);
        }
        if ($this->type == 'wallet') {
            return ucwords($this->mobile);
        }
        if ($this->type == 'paypal') {
            return ucwords($this->payee_email);
        }
        if (strtolower($this->type) == 'instapay') {
            return ucwords($this->ewallet_provider);
        }
    }
    public function method_details()
    {
        if ($this->type == 'bank') {
            return "Bank Name: " . ucwords($this->bank_name)
                . "\r\nAccount Number: " . $this->bank_number. "\r\nAccount Name: " . $this->bank
                . "\r\nPayee Email: " . $this->payee_email;
        }
        if ($this->type == 'mobile_wallet') {
            return ucwords($this->mobile);
        }
        if ($this->type == 'wallet') {
            return ucwords($this->mobile);
        }
        if ($this->type == 'paypal') {
            return ucwords($this->payee_email);
        }
        if (strtolower($this->type) == 'instapay') {
            return ucwords($this->ewallet_provider);
        }
    }

    /**
     * Scope to get recently added payment methods
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Scope to get payment methods added within specified days
     */
    public function scopeRecentDays($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }
}

