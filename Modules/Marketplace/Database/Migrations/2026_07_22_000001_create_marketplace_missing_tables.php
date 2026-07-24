<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('free_downloads')) {
            Schema::create('free_downloads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->nullable()->constrained('marketplace_services')->cascadeOnDelete();
                $table->string('email');
                $table->string('name')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('download_token')->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('favoritable_type');
                $table->unsignedBigInteger('favoritable_id');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
            });
        }

        if (!Schema::hasTable('marketplace_serial_user_devices')) {
            Schema::create('marketplace_serial_user_devices', function (Blueprint $table) {
                $table->id();
                $table->string('serial_key');
                $table->string('hwid');
                $table->string('mac_address');
                $table->string('device_name')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_landing_page_ab_metrics')) {
            Schema::create('service_landing_page_ab_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('landing_page_id')->nullable()->constrained('service_landing_pages')->cascadeOnDelete();
                $table->unsignedBigInteger('variant_id')->nullable();
                $table->string('event_type');
                $table->string('visitor_ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->integer('scroll_depth')->nullable();
                $table->integer('time_on_page')->nullable();
                $table->decimal('conversion_value', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_delivery_files')) {
            Schema::create('order_delivery_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('marketplace_orders')->cascadeOnDelete();
                $table->string('file_path');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('message_activities')) {
            Schema::table('message_activities', function (Blueprint $table) {
                if (!Schema::hasColumn('message_activities', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('message_activities', 'activity_type')) {
                    $table->string('activity_type')->nullable()->after('user_id');
                }
            });
        }

        if (Schema::hasTable('user_referrals')) {
            Schema::table('user_referrals', function (Blueprint $table) {
                if (!Schema::hasColumn('user_referrals', 'total_earnings')) {
                    $table->decimal('total_earnings', 10, 2)->default(0)->after('user_id');
                }
                if (!Schema::hasColumn('user_referrals', 'referred_user_id')) {
                    $table->foreignId('referred_user_id')->nullable()->after('total_earnings');
                }
            });
        }

        if (Schema::hasTable('user_referral_request_withdraws')) {
            Schema::table('user_referral_request_withdraws', function (Blueprint $table) {
                if (!Schema::hasColumn('user_referral_request_withdraws', 'payment_method')) {
                    $table->string('payment_method')->nullable();
                }
                if (!Schema::hasColumn('user_referral_request_withdraws', 'payment_info')) {
                    $table->text('payment_info')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('free_downloads');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('marketplace_serial_user_devices');
        Schema::dropIfExists('service_landing_page_ab_metrics');
        Schema::dropIfExists('order_delivery_files');
    }
};
