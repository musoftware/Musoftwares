<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'enable_notifications')) {
                $table->boolean('enable_notifications')
                    ->default(true)
                    ->after('whatsapp_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'enable_notifications')) {
                $table->dropColumn('enable_notifications');
            }
        });
    }
};
