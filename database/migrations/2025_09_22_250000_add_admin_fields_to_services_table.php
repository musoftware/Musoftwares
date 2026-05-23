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
        Schema::table('services', function (Blueprint $table) {
            // Admin tracking fields
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            
            // Rejection tracking
            $table->text('rejection_reason')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            
            // Featured flag for homepage
            $table->boolean('featured')->default(false)->after('rejected_at');
            
            // Suspension tracking
            $table->timestamp('suspended_at')->nullable()->after('featured');
            $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
            $table->foreign('suspended_by')->references('id')->on('users')->nullOnDelete();
        });

        // Update status enum - skip on SQLite (no enum support needed)
        if (config('database.default') !== 'sqlite') {
            Schema::table('services', function (Blueprint $table) {
                $table->enum('status', ['pending', 'reviewing', 'approved', 'declined', 'active', 'paused', 'rejected', 'suspended'])->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'approved_by', 'rejection_reason', 'rejected_at', 'featured', 'suspended_at', 'suspended_by']);
            $table->enum('status', ['pending', 'reviewing', 'approved', 'declined'])->default('pending')->change();
        });
    }
};
