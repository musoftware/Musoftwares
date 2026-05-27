<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('erp_tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('status');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('erp_tenants', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
