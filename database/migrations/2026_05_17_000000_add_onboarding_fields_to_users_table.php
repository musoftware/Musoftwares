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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false)->index();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('mobile_1')->nullable();
            $table->string('mobile_2')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('preferred_currency')->nullable();
            $table->timestamp('preferred_currency_locked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_completed',
                'country',
                'city',
                'mobile_1',
                'mobile_2',
                'telegram_username',
                'whatsapp_number',
                'preferred_currency',
                'preferred_currency_locked_at',
            ]);
        });
    }
};
