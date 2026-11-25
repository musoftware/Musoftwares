<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A timed task is priced as a service, not as minutes: the time a normal freelancer needs (market time),
 * a bonus for starting soon after the client asked, and a bonus for the client's past ratings.
 * The client rates each task from an emailed link with a random code. The gap between the service price
 * and the real sessions is kept as one timer line marked as an adjustment, so every total stays correct.
 */
return new class extends Migration
{
    private const ITEM_COLUMNS = ['requested_at', 'market_minutes', 'client_rating', 'client_rating_note', 'rated_at', 'rating_token', 'rating_requested_at'];

    public function up(): void
    {
        if (! Schema::hasColumn('invoice_items', 'market_minutes')) {
            Schema::table('invoice_items', function (Blueprint $table): void {
                $table->dateTime('requested_at')->nullable();
                $table->unsignedInteger('market_minutes')->nullable();
                $table->unsignedTinyInteger('client_rating')->nullable();
                $table->string('client_rating_note', 1000)->nullable();
                $table->dateTime('rated_at')->nullable();
                $table->string('rating_token', 32)->nullable()->unique();
                $table->dateTime('rating_requested_at')->nullable();
            });
        }

        if (! Schema::hasColumn('invoice_item_timers', 'is_service_adjustment')) {
            Schema::table('invoice_item_timers', function (Blueprint $table): void {
                $table->boolean('is_service_adjustment')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_item_timers', 'is_service_adjustment')) {
            Schema::table('invoice_item_timers', function (Blueprint $table): void {
                $table->dropColumn('is_service_adjustment');
            });
        }

        if (Schema::hasColumn('invoice_items', 'market_minutes')) {
            Schema::table('invoice_items', function (Blueprint $table): void {
                $table->dropUnique(['rating_token']);
                $table->dropColumn(self::ITEM_COLUMNS);
            });
        }
    }
};
