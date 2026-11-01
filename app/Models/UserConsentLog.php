<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class UserConsentLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_consents_log';

    protected $fillable = [
        'user_id',
        'session_identifier',
        'consent_type',
        'action',
        'policy_version',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_type' => ConsentType::class,
            'action' => ConsentAction::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Consent audit log entries are append-only and cannot be modified.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Consent audit log entries are append-only and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
