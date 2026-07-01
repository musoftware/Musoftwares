<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('marketplace_packages', 'deleted_at')) {
            return;
        }

        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
