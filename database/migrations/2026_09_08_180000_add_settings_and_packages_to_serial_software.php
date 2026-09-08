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
        // 1. Add settings & pricing mode fields to serial_softwares
        Schema::table('serial_softwares', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->string('pricing_type', 30)->default('free')->after('default_status'); // free, single, packages
            $table->string('billing_cycle', 30)->default('lifetime')->after('currency'); // lifetime, monthly, annual, custom
            $table->unsignedInteger('billing_days')->nullable()->after('billing_cycle');
        });

        // Backfill pricing_type based on existing requires_payment flag
        \Illuminate\Support\Facades\DB::table('serial_softwares')
            ->where('requires_payment', true)
            ->update(['pricing_type' => 'single']);

        // 2. Create serial_software_packages table
        Schema::create('serial_software_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle', 30)->default('monthly'); // lifetime, monthly, annual, custom
            $table->unsignedInteger('billing_days')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('custom_values')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['serial_software_id', 'is_active']);
            $table->index(['serial_software_id', 'sort_order']);
        });

        // 3. Add package_id to serial_devices, serial_user_devices, and serial_software_licenses
        Schema::table('serial_devices', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('serial_software_id')->constrained('serial_software_packages')->nullOnDelete();
        });

        Schema::table('serial_user_devices', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('user_id')->constrained('serial_software_packages')->nullOnDelete();
        });

        Schema::table('serial_software_licenses', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('serial_software_id')->constrained('serial_software_packages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_software_licenses', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });

        Schema::table('serial_user_devices', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });

        Schema::table('serial_devices', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });

        Schema::dropIfExists('serial_software_packages');

        Schema::table('serial_softwares', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'pricing_type',
                'billing_cycle',
                'billing_days',
            ]);
        });
    }
};
