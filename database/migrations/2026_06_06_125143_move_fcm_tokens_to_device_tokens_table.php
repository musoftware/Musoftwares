<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Move existing valid tokens from users table to device_tokens
        $users = \App\Models\User::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        
        foreach ($users as $user) {
            \App\Models\DeviceToken::firstOrCreate([
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
