<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per delivered build, so the owner can see whether the system works without him: delivered without a
 * person stepping in, accepted without a fix, and what it really cost against what was paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('builder_deliveries')) {
            Schema::create('builder_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->boolean('hands_off');
                $table->decimal('cost_usd', 10, 4)->default(0);
                $table->decimal('price_usd', 10, 2)->default(0);
                $table->timestamp('delivered_at');
                $table->timestamps();
                $table->softDeletes();
                $table->index('delivered_at');
            });
        }

        Schema::table('builder_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_projects', 'owner_touched')) {
                $table->boolean('owner_touched')->default(false)->after('auto_retries');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (Schema::hasColumn('builder_projects', 'owner_touched')) {
                $table->dropColumn('owner_touched');
            }
        });
        Schema::dropIfExists('builder_deliveries');
    }
};
