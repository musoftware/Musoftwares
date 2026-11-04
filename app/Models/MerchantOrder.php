<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantOrder extends Model
{
    use HasFactory, ScopesToLegalWindow, SoftDeletes;

    public function amount_str()
    {
        return FinanceHelper::instance()->format_money($this->amount, $this->currency);
    }

    public function color()
    {
        if ($this->status == 'pending') {
            return 'badge bg-primary';
        }
        if ($this->status == 'failed') {
            return 'badge bg-danger';
        }
        if ($this->status == 'success') {
            return 'badge bg-success';
        }
    }
}
