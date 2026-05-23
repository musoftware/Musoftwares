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
            // Tags for services (JSON array)
            $table->json('tags')->nullable()->after('description');
            
            // Pricing packages (JSON array of Basic/Standard/Premium tiers)
            $table->json('packages')->nullable()->after('price');
            
            // Deliverables per tier (JSON)
            $table->json('deliverables')->nullable()->after('packages');
            
            // Views counter
            $table->integer('views')->default(0)->after('status');
        });

        // Update status enum - skip on SQLite (no enum support needed)
        if (config('database.default') !== 'sqlite') {
            Schema::table('services', function (Blueprint $table) {
                $table->enum('status', ['pending', 'reviewing', 'approved', 'declined', 'active', 'delivered', 'completed', 'cancelled', 'disputed', 'draft'])->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['tags', 'packages', 'deliverables', 'views']);
            
            // Revert status enum to original values
            $table->enum('status', ['pending', 'reviewing', 'approved', 'declined'])->default('pending')->change();
        });
    }
};
