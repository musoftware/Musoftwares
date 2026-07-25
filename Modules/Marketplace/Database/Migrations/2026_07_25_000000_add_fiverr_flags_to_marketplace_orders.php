<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_orders', 'is_late')) {
                $table->boolean('is_late')->default(false);
            }
            if (!Schema::hasColumn('marketplace_orders', 'revision_count')) {
                $table->integer('revision_count')->default(0);
            }
            if (!Schema::hasColumn('marketplace_orders', 'extension_count')) {
                $table->integer('extension_count')->default(0);
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_count')) {
                $table->integer('delivery_count')->default(0);
            }
            if (!Schema::hasColumn('marketplace_orders', 'auto_completed')) {
                $table->boolean('auto_completed')->default(false);
            }
            if (!Schema::hasColumn('marketplace_orders', 'requirements_completed')) {
                $table->boolean('requirements_completed')->default(false);
            }
            if (!Schema::hasColumn('marketplace_orders', 'has_dispute')) {
                $table->boolean('has_dispute')->default(false);
            }
            if (!Schema::hasColumn('marketplace_orders', 'cancel_requested_by')) {
                $table->string('cancel_requested_by')->nullable();
            }
            if (!Schema::hasColumn('marketplace_orders', 'due_date')) {
                $table->timestamp('due_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_late',
                'revision_count',
                'extension_count',
                'delivery_count',
                'auto_completed',
                'requirements_completed',
                'has_dispute',
                'cancel_requested_by',
                'due_date',
            ]);
        });
    }
};
