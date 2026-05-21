<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharityCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_received',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_received' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع معاملات عداد الخير
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CharityTransaction::class, 'user_id', 'user_id');
    }

    /**
     * إضافة مبلغ لعداد الخير
     */
    public function addAmount(float $amount, string $description, string $referenceType = null, string $referenceId = null): CharityTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->total_received += $amount;
        $this->save();

        return CharityTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
        ]);
    }

    /**
     * خصم مبلغ من عداد الخير
     */
    public function subtractAmount(float $amount, string $description, string $referenceType = null, string $referenceId = null): CharityTransaction
    {
        if (round($this->balance, 2) < round($amount, 2)) {
            throw new \Exception('الرصيد غير كافي في عداد الخير');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->total_spent += $amount;
        $this->save();

        return CharityTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
        ]);
    }

    /**
     * إنشاء عداد خير جديد للمستخدم إذا لم يكن موجود
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0,
                'total_received' => 0,
                'total_spent' => 0,
            ]
        );
    }

    /**
     * الحصول على العداد العام لجميع المستخدمين (من المعاملات)
     */
    public static function getGlobalBalance(): float
    {
        $totalCredit = CharityTransaction::where('type', 'credit')->sum('amount');
        $totalDebit = CharityTransaction::where('type', 'debit')->sum('amount');
        return $totalCredit - $totalDebit;
    }

    /**
     * الحصول على إجمالي ما تم استلامه (التبرعات)
     */
    public static function getGlobalTotalReceived(): float
    {
        return CharityTransaction::where('type', 'credit')->sum('amount');
    }

    /**
     * الحصول على إجمالي ما تم صرفه
     */
    public static function getGlobalTotalSpent(): float
    {
        return CharityTransaction::where('type', 'debit')->sum('amount');
    }

    /**
     * خصم مبلغ من العداد العام (للإدارة فقط) - مجرد transaction بالسالب
     */
    public static function subtractFromGlobalCounter(float $amount, string $description, int $adminUserId): CharityTransaction
    {
        $globalBalance = self::getGlobalBalance();
        
        if (round($globalBalance, 2) < round($amount, 2)) {
            throw new \Exception('الرصيد العام غير كافي لهذا الخصم');
        }

        // إنشاء معاملة خصم بسيطة (transaction بالسالب)
        return CharityTransaction::create([
            'user_id' => $adminUserId, // الأدمن الذي قام بالخصم
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => 'admin_subtract',
            'reference_id' => 'admin_' . $adminUserId,
            'balance_before' => $globalBalance,
            'balance_after' => $globalBalance - $amount,
        ]);
    }

    /**
     * إضافة مبلغ للعداد العام (للإدارة فقط) - مجرد transaction بالموجب
     */
    public static function addToGlobalCounterByAdmin(float $amount, string $description, int $adminUserId): CharityTransaction
    {
        $globalBalance = self::getGlobalBalance();

        // إنشاء معاملة إضافة بسيطة (transaction بالموجب)
        return CharityTransaction::create([
            'user_id' => $adminUserId, // الأدمن الذي قام بالإضافة
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => 'admin_add',
            'reference_id' => 'admin_' . $adminUserId,
            'balance_before' => $globalBalance,
            'balance_after' => $globalBalance + $amount,
        ]);
    }
}
