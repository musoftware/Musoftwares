<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('storage_provider_id')->nullable();
            
            $table->string('name');
            $table->string('path'); // The key in the storage bucket
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0); // Size in bytes
            $table->string('folder')->nullable(); // Pseudo-folder path or category
            
            $table->unsignedBigInteger('uploaded_by')->nullable(); // user_id
            
            $table->json('permissions')->nullable(); // To store share links, access control, etc.
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('storage_provider_id')->references('id')->on('tenant_storage_providers')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_files');
    }
};
