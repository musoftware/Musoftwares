<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbmb_lookup_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('download_token', 64)->unique();
            $table->unsignedInteger('total_ids')->default(0);
            $table->unsignedInteger('found_count')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->unsignedInteger('remaining_balance')->default(0);
            $table->string('result_path')->nullable(); // path to CSV on disk
            $table->timestamp('expires_at');           // 24h from creation
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbmb_lookup_results');
    }
};
