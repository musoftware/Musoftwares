<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_delivery_files')) {
            Schema::create('order_delivery_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('service_order_id')->nullable();
                $table->string('file_path');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('order_delivery_files', function (Blueprint $table) {
                if (!Schema::hasColumn('order_delivery_files', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('order_delivery_files', 'service_order_id')) {
                    $table->unsignedBigInteger('service_order_id')->nullable()->after('order_id');
                }
                if (!Schema::hasColumn('order_delivery_files', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            // Synchronize foreign key column data for backward compatibility
            try {
                DB::statement("UPDATE order_delivery_files SET order_id = service_order_id WHERE order_id IS NULL AND service_order_id IS NOT NULL");
                DB::statement("UPDATE order_delivery_files SET service_order_id = order_id WHERE service_order_id IS NULL AND order_id IS NOT NULL");
            } catch (\Throwable $e) {
                // Ignore if table is empty or query not supported
            }
        }
    }

    public function down(): void
    {
        // No destructive rollback needed
    }
};
