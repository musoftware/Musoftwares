<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_softwares', function (Blueprint $table) {
            $table->boolean('requires_payment')->default(false)->after('default_status');
            $table->decimal('price', 10, 2)->nullable()->after('requires_payment');
            $table->string('currency', 10)->default('USD')->after('price');
            $table->string('whatsapp_number', 50)->nullable()->after('currency');
            $table->text('payment_instructions')->nullable()->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('serial_softwares', function (Blueprint $table) {
            $table->dropColumn([
                'requires_payment',
                'price',
                'currency',
                'whatsapp_number',
                'payment_instructions',
            ]);
        });
    }
};
