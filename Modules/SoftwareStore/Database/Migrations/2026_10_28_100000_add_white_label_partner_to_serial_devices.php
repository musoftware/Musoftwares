<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A program downloaded from a partner's store shows the partner's brand from its very first start,
 * before anything is bought: the device remembers the partner, and each download is logged so a
 * new device can be matched to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_devices', function (Blueprint $table) {
            $table->foreignId('white_label_partner_id')->nullable()->after('serial_software_id')->constrained('white_label_partners')->nullOnDelete();
        });

        Schema::create('white_label_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_partner_id')->constrained('white_label_partners')->cascadeOnDelete();
            $table->foreignId('software_product_id')->constrained('software_products')->cascadeOnDelete();
            $table->string('ip', 45);
            $table->timestamp('created_at')->nullable();
            $table->index(['software_product_id', 'ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_downloads');
        Schema::table('serial_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('white_label_partner_id');
        });
    }
};
