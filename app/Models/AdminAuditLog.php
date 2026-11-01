<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminAuditLog extends Model
{
    use ScopesToLegalWindow, SoftDeletes;

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'actor_user_id',
        'actor_ip',
        'actor_user_agent',
        'action',
        'severity',
        'target_type',
        'target_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * In-memory mirror kept for backwards compatibility with existing unit and feature assertions.
     *
     * @var list<self>
     */
    public static array $logs = [];

    public static function create(array $attributes = []): self
    {
        $payload = array_merge([
            'severity' => self::SEVERITY_INFO,
        ], $attributes);

        Log::info("Admin Audit [{$payload['action']}]: ".json_encode($payload, JSON_UNESCAPED_UNICODE));

        try {
            if (Schema::hasTable('admin_audit_logs')) {
                /** @var self $persisted */
                $persisted = static::query()->create($payload);
                self::$logs[] = $persisted;

                return $persisted;
            }
        } catch (Throwable $e) {
            Log::warning('AdminAuditLog DB persistence skipped', ['error' => $e->getMessage()]);
        }

        $fallback = new self($payload);
        $fallback->created_at = now();
        self::$logs[] = $fallback;

        return $fallback;
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
