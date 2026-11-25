<?php

namespace App\Services\ClientSystems;

use RuntimeException;

/**
 * The key pair that signs a client system's licenses: ECDSA P-256 with SHA-256 through OpenSSL, built into every
 * PHP (the live server has no sodium extension) and verifiable in any language. The signature is DER-encoded,
 * the public key is PEM (SubjectPublicKeyInfo).
 */
final class LicenseKeys
{
    /** @return array{public: string, private: string} PEM strings */
    public static function create(): array
    {
        $options = self::options() + ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        $key = openssl_pkey_new($options);
        if ($key === false || ! openssl_pkey_export($key, $private, null, $options)) {
            throw new RuntimeException('Could not create a license key pair: '.self::lastError());
        }

        return ['public' => openssl_pkey_get_details($key)['key'], 'private' => $private];
    }

    public static function sign(string $data, string $privatePem): string
    {
        if (! openssl_sign($data, $signature, $privatePem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign a license: '.self::lastError());
        }

        return $signature;
    }

    /**
     * PHP 8.3 (the live site's web PHP) checks private_key_bits even for EC keys and the minimal config leaves it
     * at 0, so it refused with "Private key length must be at least 384 bits". EC ignores the value.
     */
    private static function options(): array
    {
        return ['config' => resource_path('openssl/minimal.cnf'), 'private_key_bits' => 2048];
    }

    private static function lastError(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return implode('; ', $errors) ?: 'unknown error';
    }
}
