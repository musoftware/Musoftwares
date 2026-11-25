<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When each admin last opened each builder chat, so the chat list can mark the ones with new customer messages.
 * Chats from before this table are not marked (builder_new_since), or every old chat would look new on day one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_admin_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->timestamp('seen_at');
            $table->timestamps();
            $table->unique(['user_id', 'project_id']);
        });

        DB::table('admin_settings')->updateOrInsert(['setting_key' => 'builder_new_since'], ['setting_value' => now()->toDateTimeString(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_admin_views');
        DB::table('admin_settings')->where('setting_key', 'builder_new_since')->delete();
    }
};
