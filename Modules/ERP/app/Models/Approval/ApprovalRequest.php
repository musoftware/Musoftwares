<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Approval/ApprovalRequestFactory;

class ApprovalRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Approval/ApprovalRequestFactory
    // {
    //     // return Approval/ApprovalRequestFactory::new();
    // }
}
