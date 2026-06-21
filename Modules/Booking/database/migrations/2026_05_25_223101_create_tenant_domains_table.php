<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_domains')) {
            Schema::create('tenant_domains', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('domain')->unique(); // e.g., 'book.myclinic.com'
                $table->boolean('is_verified')->default(false);
                $table->enum('ssl_status', ['pending', 'active', 'failed'])->default('pending');
                $table->string('verification_token')->nullable(); // For DNS TXT record validation
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
