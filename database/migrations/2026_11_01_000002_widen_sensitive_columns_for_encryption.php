<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_payment_methods')) {
            Schema::table('user_payment_methods', function (Blueprint $table): void {
                if (Schema::hasColumn('user_payment_methods', 'id_number')) {
                    $table->text('id_number')->nullable()->change();
                }
                if (Schema::hasColumn('user_payment_methods', 'bank_number')) {
                    $table->text('bank_number')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('payout_methods') && Schema::hasColumn('payout_methods', 'details')) {
            Schema::table('payout_methods', function (Blueprint $table): void {
                $table->longText('details')->nullable()->change();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                foreach (['openai_api_key', 'gemini_api', 'autosms_verification_secret', 'kyc_notes'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->text($column)->nullable()->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Column widening is kept intact to avoid truncating AES-256 encrypted payloads.
    }
};

