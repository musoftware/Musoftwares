<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_ledger_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // income, expense, salary
            $table->string('color')->nullable(); // for UI charts
            $table->timestamps();
        });

        Schema::create('platform_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('platform_ledger_categories')->cascadeOnDelete();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Associated with a user (e.g. salary for employee)
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('EGP');
            
            $table->string('type'); // income, expense, salary (denormalized for quick querying)
            
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_interval')->nullable(); // monthly, yearly, weekly
            
            $table->timestamp('transaction_date')->nullable(); // When it actually happened
            $table->timestamp('next_due_date')->nullable(); // When the next recurring payment is due
            
            $table->string('status')->default('completed'); // pending, completed, overdue, cancelled
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_ledger_entries');
        Schema::dropIfExists('platform_ledger_categories');
    }
};
