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
        // 1. Add reseller_price to serial_softwares
        if (Schema::hasTable('serial_softwares') && ! Schema::hasColumn('serial_softwares', 'reseller_price')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                $table->decimal('reseller_price', 10, 2)->nullable()->after('price');
            });
        }

        // 2. Add reseller_price to serial_software_packages
        if (Schema::hasTable('serial_software_packages') && ! Schema::hasColumn('serial_software_packages', 'reseller_price')) {
            Schema::table('serial_software_packages', function (Blueprint $table) {
                $table->decimal('reseller_price', 10, 2)->nullable()->after('price');
            });
        }

        // 3. Create immutable serial_device_trial_logs table
        if (! Schema::hasTable('serial_device_trial_logs')) {
            Schema::create('serial_device_trial_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
                $table->string('device_id');
                $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('granted_at');
                $table->timestamps();

                // Unique constraint ensures a device_id can NEVER have more than 1 free trial for a specific software
                $table->unique(['serial_software_id', 'device_id'], 'software_device_trial_unique');
                $table->index(['device_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_device_trial_logs');

        if (Schema::hasTable('serial_software_packages') && Schema::hasColumn('serial_software_packages', 'reseller_price')) {
            Schema::table('serial_software_packages', function (Blueprint $table) {
                $table->dropColumn('reseller_price');
            });
        }

        if (Schema::hasTable('serial_softwares') && Schema::hasColumn('serial_softwares', 'reseller_price')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                $table->dropColumn('reseller_price');
            });
        }
    }
};
