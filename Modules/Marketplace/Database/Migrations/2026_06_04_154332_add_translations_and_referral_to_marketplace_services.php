<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_services', 'tagline')) {
                $table->string('tagline')->nullable()->after('title');
            }
            if (! Schema::hasColumn('marketplace_services', 'auto_reply')) {
                $table->text('auto_reply')->nullable()->after('description');
            }

            if (! Schema::hasColumn('marketplace_services', 'title_translations')) {
                $table->json('title_translations')->nullable()->after('title');
            }
            if (! Schema::hasColumn('marketplace_services', 'tagline_translations')) {
                $table->json('tagline_translations')->nullable()->after('tagline');
            }
            if (! Schema::hasColumn('marketplace_services', 'description_translations')) {
                $table->json('description_translations')->nullable()->after('description');
            }
            if (! Schema::hasColumn('marketplace_services', 'auto_reply_translations')) {
                $table->json('auto_reply_translations')->nullable()->after('auto_reply');
            }

            if (! Schema::hasColumn('marketplace_services', 'service_link')) {
                $table->string('service_link')->nullable();
            }
            if (! Schema::hasColumn('marketplace_services', 'generate_serials')) {
                $table->boolean('generate_serials')->default(false);
            }
            if (! Schema::hasColumn('marketplace_services', 'allow_random_serial')) {
                $table->boolean('allow_random_serial')->default(false);
            }
            if (! Schema::hasColumn('marketplace_services', 'validity_days')) {
                $table->integer('validity_days')->nullable();
            }
            if (! Schema::hasColumn('marketplace_services', 'referral_commission_from')) {
                $table->string('referral_commission_from')->default('fee');
            }
            if (! Schema::hasColumn('marketplace_services', 'referral_commission_percentage')) {
                $table->decimal('referral_commission_percentage', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('marketplace_services', 'is_free')) {
                $table->boolean('is_free')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $columns = [
                'tagline',
                'auto_reply',
                'title_translations',
                'tagline_translations',
                'description_translations',
                'auto_reply_translations',
                'service_link',
                'generate_serials',
                'allow_random_serial',
                'validity_days',
                'referral_commission_from',
                'referral_commission_percentage',
                'is_free',
            ];

            $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn('marketplace_services', $c)));

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};