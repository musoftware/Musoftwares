<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A menu made while signed in belongs to that account, so it can be edited without keeping a link. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('free_qr_menus', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('code')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('free_qr_menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
