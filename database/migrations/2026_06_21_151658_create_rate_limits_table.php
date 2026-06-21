<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable()->comment('E.g. api, web, tenant, auth');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('max_requests');
            $table->integer('decay_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module', 'tenant_id']);
            $table->index(['module', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
    }
};
