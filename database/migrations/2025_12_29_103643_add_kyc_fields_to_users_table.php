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
            $table->boolean('kyc_verified')->default(false)->after('email_verified_at');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_verified');
            $table->unsignedBigInteger('kyc_verified_by')->nullable()->after('kyc_verified_at');
            $table->string('kyc_provider')->nullable()->after('kyc_verified_by')->comment('KYC provider name or service');
            $table->string('kyc_reference_id')->nullable()->after('kyc_provider')->comment('Reference ID from KYC provider');
            $table->text('kyc_notes')->nullable()->after('kyc_reference_id')->comment('Admin notes about KYC verification');
            
            $table->foreign('kyc_verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kyc_verified_by']);
            $table->dropColumn([
                'kyc_verified',
                'kyc_verified_at',
                'kyc_verified_by',
                'kyc_provider',
                'kyc_reference_id',
                'kyc_notes'
            ]);
        });
    }
};
