<?php

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Move existing valid tokens from users table to device_tokens
        $users = User::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();

        foreach ($users as $user) {
            DeviceToken::firstOrCreate([
                'user_id' => $user->id,
                'token' => $user->fcm_token,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do for rollback, tokens are kept in both places for now
    }
};
