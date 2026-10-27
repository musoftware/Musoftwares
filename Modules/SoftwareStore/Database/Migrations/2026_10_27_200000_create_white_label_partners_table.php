<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * White label: a partner sells our programs under their own name, from their own site (iframe) or
 * their own store page, at our price or higher. They keep 30% of our price plus anything they add on top.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('white_label_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 40)->unique();
            $table->string('brand_name');
            $table->string('logo_path')->nullable();
            $table->string('accent_color', 7)->default('#0071e3');
            $table->string('support_whatsapp', 30)->nullable();
            $table->string('support_email')->nullable();
            $table->string('website_url')->nullable();
            // {"<product id>" or "<product id>:<plan key>": price}; missing or lower than ours means our price
            $table->json('prices')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::table('software_orders', function (Blueprint $table) {
            $table->foreignId('white_label_partner_id')->nullable()->after('software_product_id')->constrained()->nullOnDelete();
            $table->decimal('base_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('partner_commission', 12, 2)->nullable()->after('base_amount');
        });

        if (Schema::hasTable('serial_software_licenses')) {
            Schema::table('serial_software_licenses', function (Blueprint $table) {
                $table->foreignId('white_label_partner_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('serial_software_licenses', 'white_label_partner_id')) {
            Schema::table('serial_software_licenses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('white_label_partner_id');
            });
        }

        Schema::table('software_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('white_label_partner_id');
            $table->dropColumn(['base_amount', 'partner_commission']);
        });

        Schema::dropIfExists('white_label_partners');
    }
};
