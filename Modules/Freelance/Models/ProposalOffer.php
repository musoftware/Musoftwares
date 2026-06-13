<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalOffer extends Model
{
    use HasFactory;

    protected $table = 'freelance_proposal_offers';
    
    protected $fillable = [
        'proposal_id', 
        'offered_by_user_id', 
        'amount', 
        'currency_id', 
        'status'
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function offeredBy()
    {
        return $this->belongsTo(User::class, 'offered_by_user_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
