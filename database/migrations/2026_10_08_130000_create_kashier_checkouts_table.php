<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server-side record of what a Kashier checkout is buying (points, modules) and its price.
 * The webhook fulfils this record; it never trusts the payer-editable metaData for the purchase.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kashier_checkouts')) {
            return;
        }

        Schema::create('kashier_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 50);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('currency_id');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->string('provider_trx_id', 191)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'purpose', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kashier_checkouts');
    }
};
