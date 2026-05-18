<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_storage_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name'); // e.g., 'AWS S3', 'Cloudflare R2'
            $table->string('driver')->default('s3'); // Always s3-compatible for this architecture
            $table->string('key');
            $table->string('secret');
            $table->string('region')->nullable();
            $table->string('bucket');
            $table->string('endpoint')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_storage_providers');
    }
};
