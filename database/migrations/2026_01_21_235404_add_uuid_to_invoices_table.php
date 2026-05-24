<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Populate existing records
        $invoices = \Illuminate\Support\Facades\DB::table('invoices')->whereNull('uuid')->get();
        foreach ($invoices as $invoice) {
            \Illuminate\Support\Facades\DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
