<?php

namespace Modules\Core\Models;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantOrder extends Model
{
    use HasFactory;

    public function amount_str()
    {
        return FinanceHelper::instance()->format_money($this->amount, $this->currency);
    }

    public function color()
    {
        if ($this->status == 'pending'){
            return 'badge bg-primary';
        }
        if ($this->status == 'failed'){
            return 'badge bg-danger';
        }
        if ($this->status == 'success'){
            return 'badge bg-success';
        }
    }




}
