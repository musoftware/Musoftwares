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
        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_businesses', 'is_test_mode')) {
                $table->boolean('is_test_mode')->default(false)->after('status');
                $table->string('test_phone_number_id')->nullable()->after('is_test_mode');
                $table->string('test_waba_id')->nullable()->after('test_phone_number_id');
                $table->text('test_access_token')->nullable()->after('test_waba_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_businesses', 'is_test_mode')) {
                $table->dropColumn(['is_test_mode', 'test_phone_number_id', 'test_waba_id', 'test_access_token']);
            }
        });
    }
};
