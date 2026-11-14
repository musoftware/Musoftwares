<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Every question a visitor asked the site's AI assistant, with its answer, for the owner to read. */
    public function up(): void
    {
        if (Schema::hasTable('assistant_exchanges')) {
            return;
        }
        Schema::create('assistant_exchanges', function (Blueprint $table) {
            $table->id();
            // who asked: "device:<id>" on the site, "telegram:<chat id>" in the bot
            $table->string('visitor', 80)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('page', 255)->nullable();
            $table->text('question');
            $table->text('answer');
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_exchanges');
    }
};
