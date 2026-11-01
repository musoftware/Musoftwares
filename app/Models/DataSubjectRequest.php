<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSubjectRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const SLA_BUSINESS_DAYS = 6;

    protected $table = 'data_subject_requests';

    protected $fillable = [
        'user_id',
        'request_type',
        'status',
        'details',
        'requested_at',
        'due_at',
        'completed_at',
        'processed_by_admin_id',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => DataSubjectRequestType::class,
            'status' => DataSubjectRequestStatus::class,
            'requested_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Compute the statutory 6 Egyptian business-day deadline (skipping Friday and Saturday in Africa/Cairo).
     */
    public static function calculateDueAt(?CarbonInterface $from = null): Carbon
    {
        $cursor = ($from ? Carbon::instance($from) : now())->copy()->timezone('Africa/Cairo');
        $addedDays = 0;

        while ($addedDays < self::SLA_BUSINESS_DAYS) {
            $cursor->addDay();
            if (! in_array($cursor->dayOfWeek, [CarbonInterface::FRIDAY, CarbonInterface::SATURDAY], true)) {
                $addedDays++;
            }
        }

        return $cursor->setTimezone('UTC');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_admin_id');
    }
}
