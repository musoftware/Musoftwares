<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuCoinApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_key',
        'api_secret',
        'passphrase',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'passphrase',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encrypt the API key before saving.
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = encrypt($value);
    }

    /**
     * Decrypt the API key when retrieving.
     */
    public function getApiKeyAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Encrypt the API secret before saving.
     */
    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = encrypt($value);
    }

    /**
     * Decrypt the API secret when retrieving.
     */
    public function getApiSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Encrypt the API passphrase before saving.
     */
    public function setPassphraseAttribute($value)
    {
        $this->attributes['passphrase'] = encrypt($value);
    }

    /**
     * Decrypt the API passphrase when retrieving.
     */
    public function getPassphraseAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
}
