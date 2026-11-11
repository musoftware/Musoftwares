<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The discount offered once to a customer who was leaving after seeing the price. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('leave_discount_percent')->default(0)->after('amount');
            $table->decimal('leave_discount_usd', 10, 2)->default(0)->after('leave_discount_percent');
            $table->timestamp('leave_discount_at')->nullable()->after('leave_discount_usd');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn(['leave_discount_percent', 'leave_discount_usd', 'leave_discount_at']);
        });
    }
};
