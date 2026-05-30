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
        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->index('status');
            $table->index('client_id');
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->index('status');
            $table->index('job_id');
            $table->index('freelancer_id');
        });

        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->index('status');
            $table->index('job_id');
            $table->index('client_id');
            $table->index('freelancer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('freelance_proposals', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['job_id']);
            $table->dropIndex(['freelancer_id']);
        });

        Schema::table('freelance_contracts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['job_id']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['freelancer_id']);
        });
    }
};
