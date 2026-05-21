<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'balance_before',
        'balance_after',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع عداد الخير
     */
    public function charityCounter(): BelongsTo
    {
        return $this->belongsTo(CharityCounter::class, 'user_id', 'user_id');
    }

    /**
     * فلترة المعاملات حسب النوع
     */
    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * فلترة المعاملات حسب المرجع
     */
    public function scopeByReference($query, string $referenceType, string $referenceId = null)
    {
        $query = $query->where('reference_type', $referenceType);
        
        if ($referenceId) {
            $query->where('reference_id', $referenceId);
        }
        
        return $query;
    }

    /**
     * الحصول على نص نوع المعاملة
     */
    public function getTypeTextAttribute(): string
    {
        return $this->type === 'credit' ? 'إضافة' : 'صرف';
    }

    /**
     * الحصول على لون نوع المعاملة
     */
    public function getTypeColorAttribute(): string
    {
        return $this->type === 'credit' ? 'success' : 'danger';
    }

    /**
     * الحصول على أيقونة نوع المعاملة
     */
    public function getTypeIconAttribute(): string
    {
        return $this->type === 'credit' ? 'ti ti-plus' : 'ti ti-minus';
    }
}
