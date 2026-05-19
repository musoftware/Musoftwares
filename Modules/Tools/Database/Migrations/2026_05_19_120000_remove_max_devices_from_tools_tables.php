<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activated_devices');

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->dropColumn('max_devices');
        });

        Schema::table('tool_pricing_plans', function (Blueprint $table) {
            $table->dropColumn('max_devices');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn('max_devices');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_devices')->default(3);
        });

        Schema::table('tool_pricing_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_devices')->default(1);
        });

        Schema::table('tool_licenses', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_devices')->default(3);
        });

        Schema::create('activated_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_license_id')->constrained('tool_licenses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hardware_fingerprint');        // SHA256 of CPU+MAC
            $table->string('device_name')->nullable();
            $table->string('os')->nullable();              // windows|mac|linux
            $table->string('app_version')->nullable();     // version of the desktop app
            $table->string('status')->default('active');   // active|revoked|banned
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['tool_license_id', 'hardware_fingerprint']);
            $table->index(['hardware_fingerprint']);
        });
    }
};
