<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Tenant extends Model
{
    use SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }

    protected $table = 'erp_tenants';

    protected $fillable = ['user_id', 'name', 'status', 'base_currency_id', 'trial_ends_at', 'subscription_ends_at'];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(TenantClient::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }


    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function smtpSetting()
    {
        return $this->hasOne(SmtpSetting::class, 'tenant_id');
    }

    public function baseCurrency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'base_currency_id');
    }

    /**
     * Get the designated Platform (Master) Tenant ID.
     */
    public static function platformId(): int
    {
        return (int) config('erp.platform_tenant_id', 1);
    }
}
