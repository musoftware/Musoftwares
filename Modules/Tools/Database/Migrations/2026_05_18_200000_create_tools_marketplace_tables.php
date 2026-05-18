<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tool Catalog ───────────────────────────────────────────────────────
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('icon')->nullable();           // filename in storage
            $table->string('category');                   // scraper|automation|ocr|ai|data|browser
            $table->json('supported_os')->default('["windows"]'); // windows|mac|linux
            $table->string('current_version')->default('1.0.0');
            $table->unsignedTinyInteger('max_devices')->default(3);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->json('features')->nullable();          // list of feature strings
            $table->json('requirements')->nullable();      // system requirements
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Pricing Plans ───────────────────────────────────────────────────────
        Schema::create('tool_pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('name');                        // Starter|Pro|Agency
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->unsignedTinyInteger('max_devices')->default(1);
            $table->json('features')->nullable();          // plan-specific feature list
            $table->boolean('is_popular')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ─── Tool Versions / Releases ─────────────────────────────────────────
        Schema::create('tool_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('version');                     // semver: 1.0.0
            $table->text('changelog')->nullable();
            $table->string('file_path')->nullable();       // storage path
            $table->string('file_name')->nullable();       // display name
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->string('checksum')->nullable();        // SHA256
            $table->string('min_plan')->nullable();        // plan required to download
            $table->boolean('is_latest')->default(false);
            $table->boolean('is_beta')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['tool_id', 'version']);
        });

        // ─── Tool Screenshots ─────────────────────────────────────────────────
        Schema::create('tool_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ─── User Tool Subscriptions ──────────────────────────────────────────
        Schema::create('tool_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('tool_pricing_plan_id')->constrained('tool_pricing_plans');
            $table->string('billing_cycle')->default('monthly'); // monthly|yearly
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->string('status')->default('active');   // active|cancelled|expired|suspended
            $table->string('payment_method')->nullable();  // wallet|kashier
            $table->string('payment_reference')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tool_id']);
        });

        // ─── License Keys ─────────────────────────────────────────────────────
        Schema::create('tool_licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('license_key')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('tool_subscription_id')->nullable()->constrained('tool_subscriptions')->nullOnDelete();
            $table->unsignedTinyInteger('max_devices')->default(3);
            $table->string('status')->default('active');   // active|suspended|revoked
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tool_id']);
        });

        // ─── Activated Devices ────────────────────────────────────────────────
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

        // ─── Download Audit Log ───────────────────────────────────────────────
        Schema::create('tool_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('tool_version_id')->constrained('tool_versions')->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('downloaded_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_downloads');
        Schema::dropIfExists('activated_devices');
        Schema::dropIfExists('tool_licenses');
        Schema::dropIfExists('tool_subscriptions');
        Schema::dropIfExists('tool_screenshots');
        Schema::dropIfExists('tool_versions');
        Schema::dropIfExists('tool_pricing_plans');
        Schema::dropIfExists('tools');
    }
};
