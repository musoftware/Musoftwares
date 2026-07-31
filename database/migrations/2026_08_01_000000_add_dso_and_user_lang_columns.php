<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'lang')) {
                $table->string('lang')->default('ar')->after('email');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'dso_warning_1_sent_at')) {
                $table->timestamp('dso_warning_1_sent_at')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'dso_warning_2_sent_at')) {
                $table->timestamp('dso_warning_2_sent_at')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'dso_deactivated_sent_at')) {
                $table->timestamp('dso_deactivated_sent_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'lang')) {
                $table->dropColumn('lang');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['dso_warning_1_sent_at', 'dso_warning_2_sent_at', 'dso_deactivated_sent_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
