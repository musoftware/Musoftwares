<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('expires_at');
            $table->string('paid_method')->nullable()->after('cancelled_at');
            $table->string('paid_transaction_id', 191)->nullable()->after('paid_method');

            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['expires_at']);

            $table->dropColumn([
                'description',
                'expires_at',
                'cancelled_at',
                'paid_method',
                'paid_transaction_id',
            ]);
        });
    }
};