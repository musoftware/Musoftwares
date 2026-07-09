<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_emails', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('email');
            $t->timestamp('verified_at')->nullable();
            $t->string('source', 32)->default('admin');
            $t->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique('email', 'user_emails_email_unique');
            $t->index(['user_id', 'email'], 'user_emails_user_email_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_emails');
    }
};
