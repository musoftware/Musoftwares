<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();

            $table->string('vendor', 255);
            $table->bigInteger('vendor_product_id');

            $table->string('product_name', 1024);
            $table->double('product_price', 9, 2);
            $table->double('commission', 9, 2);

            $table->bigInteger('min_qty')->default(1);
            $table->longText('product_short_description')->nullable();
            $table->longText('product_description')->nullable();

            $table->enum('type', ['simple', 'variant']);

            $table->unique(['vendor', 'vendor_product_id']);

            $table->timestamps();
            $table->softDeletes();
        });
        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE shop_products ADD FULLTEXT search(product_name)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_products');
    }
};
