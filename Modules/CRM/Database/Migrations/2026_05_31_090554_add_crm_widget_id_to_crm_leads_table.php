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
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('crm_widget_id')->nullable()->after('workspace_id');
            $table->foreign('crm_widget_id')->references('id')->on('crm_widgets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['crm_widget_id']);
            $table->dropColumn('crm_widget_id');
        });
    }
};
