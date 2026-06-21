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
        Schema::table('erp_tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('erp_tenants', 'subdomain')) {
                $table->string('subdomain')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('erp_tenants', 'logo')) {
                $table->string('logo')->nullable()->after('subdomain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_tenants', function (Blueprint $table) {
            $table->dropColumn(['subdomain', 'logo']);
        });
    }
};
