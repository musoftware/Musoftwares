<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_accounts')) {
            Schema::create('whatsapp_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('name')->nullable();
                $table->string('phone_number_id');
                $table->string('waba_id')->nullable();
                $table->text('access_token');
                $table->string('status')->default('active'); // active, disabled, disconnected
                $table->string('facebook_user_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_logs')) {
            Schema::create('whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->onDelete('set null');
                $table->string('recipient_phone');
                $table->string('message_type')->default('text'); // text, template
                $table->text('message_body')->nullable();
                $table->string('status')->default('pending'); // sent, failed, pending
                $table->string('meta_message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
