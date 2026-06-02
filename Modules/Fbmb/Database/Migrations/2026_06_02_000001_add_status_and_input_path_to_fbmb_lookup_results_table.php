<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fbmb_lookup_results', function (Blueprint $table) {
            $table->string('status', 32)->default('completed')->after('remaining_balance');
            $table->string('input_path')->nullable()->after('status');
            $table->text('error_message')->nullable()->after('input_path');
        });
    }

    public function down(): void
    {
        Schema::table('fbmb_lookup_results', function (Blueprint $table) {
            $table->dropColumn(['status', 'input_path', 'error_message']);
        });
    }
};
