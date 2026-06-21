<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_cost_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->enum('line_type', ['direct', 'user_credit']);
            $table->double('amount', 23, 3)->default(0);
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('credit_user_id')->nullable();
            $table->foreign('credit_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('cost_transaction_id')->nullable();
            $table->unsignedBigInteger('earned_transaction_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('invoices')) {
            $rows = DB::table('invoices')
                ->where('cost', '>', 0)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->get(['id', 'cost', 'cost_payable_user_id']);

            foreach ($rows as $inv) {
                DB::table('invoice_cost_lines')->insert([
                    'invoice_id' => $inv->id,
                    'line_type' => $inv->cost_payable_user_id ? 'user_credit' : 'direct',
                    'amount' => $inv->cost,
                    'description' => null,
                    'credit_user_id' => $inv->cost_payable_user_id,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_cost_lines');
    }
};
