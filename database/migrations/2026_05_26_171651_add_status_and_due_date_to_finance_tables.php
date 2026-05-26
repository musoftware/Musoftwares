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
        Schema::table('cost_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cost_transactions', 'status')) {
                $table->string('status', 30)->default('completed');
            }
            if (!Schema::hasColumn('cost_transactions', 'due_date')) {
                $table->date('due_date')->nullable();
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status', 30)->default('completed');
            }
            if (!Schema::hasColumn('transactions', 'due_date')) {
                $table->date('due_date')->nullable();
            }
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cost_transactions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('cost_transactions', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('transactions', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};
