<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('title');
            $table->text('auto_reply')->nullable()->after('description');
            
            // Adding translation JSON columns
            $table->json('title_translations')->nullable()->after('title');
            $table->json('tagline_translations')->nullable()->after('tagline');
            $table->json('description_translations')->nullable()->after('description');
            $table->json('auto_reply_translations')->nullable()->after('auto_reply');

            // Old settings
            $table->string('service_link')->nullable();
            $table->boolean('generate_serials')->default(false);
            $table->boolean('allow_random_serial')->default(false);
            $table->integer('validity_days')->nullable();
            $table->string('referral_commission_from')->default('fee');
            $table->decimal('referral_commission_percentage', 5, 2)->nullable();
            $table->boolean('is_free')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_services', function (Blueprint $table) {
            $table->dropColumn([
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
                'is_free'
            ]);
        });
    }
};
