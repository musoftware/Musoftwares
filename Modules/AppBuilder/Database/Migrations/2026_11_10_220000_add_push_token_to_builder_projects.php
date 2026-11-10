<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The customer's browser, when they allow notifications: a question, a delivery or a live trial reaches them with the page closed. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'push_token')) {
                $table->string('push_token', 500)->nullable()->after('client_whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'push_token')) {
                $table->dropColumn('push_token');
            }
        });
    }
};
