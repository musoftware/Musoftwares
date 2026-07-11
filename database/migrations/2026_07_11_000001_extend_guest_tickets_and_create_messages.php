<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_tickets', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('mobile');
            $table->timestamp('last_message_at')->nullable()->after('status');
            $table->string('last_message_message_id')->nullable()->after('last_message_at');
            $table->index('last_message_at');
            $table->index('status');
        });

        Schema::create('guest_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_ticket_id')->constrained('guest_tickets')->cascadeOnDelete();
            $table->string('direction', 16);
            $table->string('from_email')->nullable();
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable()->index();
            $table->text('references')->nullable();
            $table->json('headers_json')->nullable();
            $table->json('attachments_json')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['guest_ticket_id', 'direction']);
            $table->index(['guest_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_ticket_messages');

        Schema::table('guest_tickets', function (Blueprint $table) {
            $table->dropIndex(['last_message_at']);
            $table->dropIndex(['status']);
            $table->dropColumn(['subject', 'last_message_at', 'last_message_message_id']);
        });
    }
};
