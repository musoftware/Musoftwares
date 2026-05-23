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
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('users', 'mobile_1')) {
                $table->string('mobile_1')->nullable();
            }
            if (!Schema::hasColumn('users', 'mobile_2')) {
                $table->string('mobile_2')->nullable();
            }
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable();
            }
            if (!Schema::hasColumn('users', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable();
            }
            if (!Schema::hasColumn('users', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'city',
                'mobile_1',
                'mobile_2',
                'telegram_username',
                'whatsapp_number',
                'onboarding_completed'
            ]);
        });
    }
};
