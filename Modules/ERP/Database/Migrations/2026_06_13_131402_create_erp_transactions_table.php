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
        Schema::create('erp_transactions', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('erp_users')->onDelete('cascade');

            $table->double('amount', 33, 10);

            $table->text('reason')->nullable();
            $table->enum('type', ['received', 'refunded', 'sent', 'used', 'earned']);

            $table->bigInteger('project_id')->index()->nullable();

            $table->bigInteger('currency_id')->unsigned()->index()->default(1);
            $table->foreign('currency_id')->references('id')->on('erp_currencies');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_transactions');
    }
};
