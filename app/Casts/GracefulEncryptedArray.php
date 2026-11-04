<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts JSON array attributes at rest using AES-256 while safely reading legacy JSON rows.
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|string|null>
 */
class GracefulEncryptedArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $raw = (string) $value;

        try {
            $decrypted = Crypt::decryptString($raw);
            $decoded = json_decode($decrypted, true);

            return is_array($decoded) ? $decoded : [];
        } catch (DecryptException) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $json = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return Crypt::encryptString((string) $json);
    }
}

