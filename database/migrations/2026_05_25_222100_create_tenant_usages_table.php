<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_usages')) {
            Schema::create('tenant_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                
                $table->string('usage_key'); // e.g. monthly_whatsapp_messages
                $table->integer('used_amount')->default(0);
                $table->integer('limit_amount')->nullable(); // null means unlimited
                
                // daily, monthly, yearly, never
                $table->string('reset_frequency')->default('monthly');
                $table->timestamp('last_reset_at')->useCurrent();
                
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'usage_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_usages');
    }
};
