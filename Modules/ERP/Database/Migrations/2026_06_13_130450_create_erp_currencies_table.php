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
        Schema::create('erp_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 10);
            $table->string('symbol', 10);
            $table->string('string_format', 10);
            $table->timestamps();
        });

        // Seed default currencies for ERP
        \Illuminate\Support\Facades\DB::insert('insert into erp_currencies (id, currency, symbol, string_format) values (?, ?, ?, ?)', [1, 'USD', '$', '$%01.2f']);
        \Illuminate\Support\Facades\DB::insert('insert into erp_currencies (id, currency, symbol, string_format) values (?, ?, ?, ?)', [2, 'EGP', 'e£', 'e£%01.2f']);
        \Illuminate\Support\Facades\DB::insert('insert into erp_currencies (id, currency, symbol, string_format) values (?, ?, ?, ?)', [3, 'EUR', '€', '€%01.2f']);
        \Illuminate\Support\Facades\DB::insert('insert into erp_currencies (id, currency, symbol, string_format) values (?, ?, ?, ?)', [4, 'GBP', '£', '£%01.2f']);
        \Illuminate\Support\Facades\DB::insert('insert into erp_currencies (id, currency, symbol, string_format) values (?, ?, ?, ?)', [5, 'AED', 'د.إ', '%01.2f د.إ']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erp_currencies');
    }
};
