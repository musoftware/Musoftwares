<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('software_products', 'serial_software_id')) {
            Schema::table('software_products', function (Blueprint $table) {
                $table->unsignedBigInteger('serial_software_id')->nullable()->after('slug')->index();
            });
        }

        if (! Schema::hasColumn('software_orders', 'device_id')) {
            Schema::table('software_orders', function (Blueprint $table) {
                $table->string('device_id', 128)->nullable()->after('client_whatsapp');
                $table->timestamp('activated_at')->nullable()->after('paid_at');
                $table->timestamp('license_expires_at')->nullable()->after('activated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('software_orders', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'activated_at', 'license_expires_at']);
        });
        Schema::table('software_products', function (Blueprint $table) {
            $table->dropColumn('serial_software_id');
        });
    }
};
