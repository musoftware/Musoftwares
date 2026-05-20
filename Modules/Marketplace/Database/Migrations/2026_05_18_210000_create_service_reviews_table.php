<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_reviews')) {
            Schema::create('service_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('marketplace_services')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->tinyInteger('rating')->unsigned()->comment('1-5 stars');
                $table->text('review')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamp('reviewed_at')->useCurrent();
                $table->timestamps();

                // One review per order per reviewer
                $table->unique(['order_id', 'reviewer_id']);
            });
        }

        // Track average rating on services table
        Schema::table('marketplace_services', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_services', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('marketplace_services', 'review_count')) {
                $table->unsignedInteger('review_count')->default(0)->after('avg_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $table->dropColumn(['avg_rating', 'review_count']);
        });
        Schema::dropIfExists('service_reviews');
    }
};
