<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsAgreement extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'ip_address',
        'user_agent',
        'agreed_at',
        'agreement_snapshot',
        'is_latest_version',
        'version',
    ];

    public $timestamps = true;

}
