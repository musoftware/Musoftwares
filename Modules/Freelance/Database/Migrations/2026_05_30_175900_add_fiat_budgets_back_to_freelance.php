<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->decimal('budget', 20, 8)->default(0)->after('description');
            $table->foreignId('currency_id')->default(2)->after('budget')->constrained('currencies')->onDelete('restrict');
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->decimal('bid_amount', 20, 8)->default(0)->after('cover_letter');
            $table->foreignId('currency_id')->default(2)->after('bid_amount')->constrained('currencies')->onDelete('restrict');
        });

        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->decimal('amount', 20, 8)->default(0)->after('freelancer_id');
            $table->foreignId('currency_id')->default(2)->after('amount')->constrained('currencies')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'amount']);
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'bid_amount']);
        });

        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'budget']);
        });
    }
};
