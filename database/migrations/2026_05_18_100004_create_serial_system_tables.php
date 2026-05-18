<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serial License Management System — copied from old project.
 *
 * How it works:
 * 1. Client software calls POST /api/serial/device on startup
 * 2. Device is auto-registered under its software name
 * 3. Admin assigns device_id to a platform user (SerialUserDevice)
 * 4. Observer syncs status: SerialUserDevice → SerialDevice
 * 5. API returns 'active' or 'inactive' — software acts accordingly
 *
 * Tables:
 *   serial_softwares  — software/program names (auto-created on first check-in)
 *   serial_devices    — every machine that has ever checked in
 *   serial_user_devices — admin assignment: which user owns which device
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Serial Softwares ─────────────────────────────────────────
        // Each distinct program_name becomes a software row.
        // default_status is applied to new devices when they first check in.
        Schema::create('serial_softwares', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                    // program name sent by software
            $table->string('default_status')->default('active'); // active | inactive
            $table->timestamps();
        });

        // ── Serial Devices ────────────────────────────────────────────
        // Auto-created/updated every time a device calls the check-in API.
        // Unique: (serial_software_id, device_id) — same machine, same program = one record.
        Schema::create('serial_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_software_id')->constrained('serial_softwares')->cascadeOnDelete();
            $table->string('device_id');                        // hardware/OS unique ID sent by software
            $table->string('status')->nullable();               // active | inactive (synced from SerialUserDevice)

            // Environment fingerprint — sent by software on check-in
            $table->string('user_name')->nullable();
            $table->string('user_domain')->nullable();
            $table->string('machine_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('framework_version')->nullable();
            $table->boolean('is_64bit_os')->nullable();
            $table->boolean('is_64bit_process')->nullable();
            $table->text('current_directory')->nullable();
            $table->string('current_culture')->nullable();
            $table->string('current_ui_culture')->nullable();

            $table->timestamp('last_check_date')->nullable();   // when the device last called in

            $table->timestamps();

            $table->unique(['serial_software_id', 'device_id']);
            $table->index('status');
            $table->index('last_check_date');
        });

        // ── Serial User Devices ───────────────────────────────────────
        // Admin assigns a device_id to a platform user.
        // One device → one user (unique device_id).
        // Observer syncs status changes back to SerialDevice.
        Schema::create('serial_user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id');                        // must match serial_devices.device_id
            $table->string('status')->default('active');        // active | inactive
            $table->text('notes')->nullable();                  // admin notes about this assignment

            $table->timestamps();

            $table->unique('device_id');                        // one device → one user
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_user_devices');
        Schema::dropIfExists('serial_devices');
        Schema::dropIfExists('serial_softwares');
    }
};
