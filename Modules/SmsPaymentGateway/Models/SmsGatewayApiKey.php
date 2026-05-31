<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SmsGatewayApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sms_gateway_api_keys';

    protected $fillable = [
        'user_id',
        'name',
        'publishable_key',
        'secret_key_hash',
        'secret_key_last_four',
        'is_test',
        'is_active',
        'last_used_at',
        'permissions',
    ];

    protected $casts = [
        'is_test' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * Hidden from serialization — never expose hashes
     */
    protected $hidden = [
        'secret_key_hash',
    ];

    // ─── Relationships ─────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function checkoutSessions()
    {
        return $this->hasMany(SmsGatewayCheckoutSession::class, 'api_key_id');
    }

    // ─── Key Generation ────────────────────────────────

    /**
     * Generate a new API key pair (publishable + secret).
     * Returns both raw keys — the secret is only available at creation time.
     */
    public static function generateKeyPair(int $userId, string $name, bool $isTest = false): array
    {
        $prefix = $isTest ? 'test' : 'live';
        $publishableKey = 'pk_' . $prefix . '_' . Str::random(32);
        $secretKey = 'sk_' . $prefix . '_' . Str::random(32);

        $apiKey = static::create([
            'user_id' => $userId,
            'name' => $name,
            'publishable_key' => $publishableKey,
            'secret_key_hash' => hash('sha256', $secretKey),
            'secret_key_last_four' => substr($secretKey, -4),
            'is_test' => $isTest,
            'is_active' => true,
        ]);

        return [
            'api_key' => $apiKey,
            'publishable_key' => $publishableKey,
            'secret_key' => $secretKey, // Only returned ONCE at creation
        ];
    }

    /**
     * Roll (regenerate) the secret key for an existing key pair.
     * Returns the new secret — only shown once.
     */
    public function rollSecretKey(): string
    {
        $prefix = $this->is_test ? 'test' : 'live';
        $newSecret = 'sk_' . $prefix . '_' . Str::random(32);

        $this->update([
            'secret_key_hash' => hash('sha256', $newSecret),
            'secret_key_last_four' => substr($newSecret, -4),
        ]);

        return $newSecret;
    }

    // ─── Authentication ────────────────────────────────

    /**
     * Find an active API key by its publishable key.
     */
    public static function findByPublishableKey(string $key): ?self
    {
        return static::where('publishable_key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Find an active API key by verifying a raw secret key.
     */
    public static function findBySecretKey(string $rawSecret): ?self
    {
        $hash = hash('sha256', $rawSecret);

        return static::where('secret_key_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Determine key type from the raw key string.
     * Returns: 'publishable' | 'secret' | null
     */
    public static function determineKeyType(string $key): ?string
    {
        if (Str::startsWith($key, 'pk_')) {
            return 'publishable';
        }

        if (Str::startsWith($key, 'sk_')) {
            return 'secret';
        }

        return null;
    }

    /**
     * Determine if a key is a test key from the raw key string.
     */
    public static function isTestKey(string $key): bool
    {
        return Str::startsWith($key, ['pk_test_', 'sk_test_']);
    }

    // ─── Scopes ────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLive($query)
    {
        return $query->where('is_test', false);
    }

    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    // ─── Helpers ───────────────────────────────────────

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
