<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Modules\Booking\app\Features\WaConfirm\Models\BookingWaActionToken;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class ConfirmationTokenManager
{
    /**
     * Generate a securely hashed token for a specific action (e.g. 'confirm', 'cancel').
     */
    public function generateActionToken(BookingWaConfirmation $confirmation, string $actionType, int $expiresInHours = 48): BookingWaActionToken
    {
        $rawToken = Str::random(64);
        
        // We hash the token in the DB to prevent database leak attacks
        $hashedToken = hash('sha256', $rawToken);

        $token = BookingWaActionToken::create([
            'tenant_id' => $confirmation->tenant_id,
            'confirmation_id' => $confirmation->id,
            'token_hash' => $hashedToken,
            'action_type' => $actionType,
            'expires_at' => Carbon::now()->addHours($expiresInHours),
        ]);

        // We return the RAW token string to the caller so it can be emailed/sent, but the DB only stores the hash
        $token->raw_token = $rawToken;

        return $token;
    }

    /**
     * Validates a raw token from a URL and returns the model if valid.
     * Throws an exception if invalid, expired, or already used.
     */
    public function validateAndConsumeToken(string $rawToken): BookingWaActionToken
    {
        $hashedToken = hash('sha256', $rawToken);

        $tokenModel = BookingWaActionToken::where('token_hash', $hashedToken)->first();

        if (!$tokenModel) {
            throw new Exception("Invalid or spoofed action token.");
        }

        if ($tokenModel->isExpired()) {
            throw new Exception("This confirmation link has expired.");
        }

        if ($tokenModel->isUsed()) {
            throw new Exception("This action has already been performed.");
        }

        $tokenModel->used_at = now();
        $tokenModel->save();

        return $tokenModel;
    }
}
