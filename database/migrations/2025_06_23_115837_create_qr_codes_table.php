<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['url', 'text', 'email', 'phone', 'sms', 'wifi', 'vcard']);
            $table->text('content'); // Original content
            $table->text('qr_data'); // Formatted QR data
            $table->string('filename')->nullable(); // Generated QR image filename
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('scans')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->json('settings')->nullable(); // Size, color, logo, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
