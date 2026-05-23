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
        Schema::table('auto_sms_transactions', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->nullable()->after('amount');
            $table->boolean('is_spoofed')->default(false)->after('status');
            $table->text('spoofing_reason')->nullable()->after('is_spoofed');
            
            $table->index('is_spoofed');
            $table->index(['phone_number', 'balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_sms_transactions', function (Blueprint $table) {
            $table->dropIndex(['phone_number', 'balance']);
            $table->dropIndex(['is_spoofed']);
            $table->dropColumn(['balance', 'is_spoofed', 'spoofing_reason']);
        });
    }
};

