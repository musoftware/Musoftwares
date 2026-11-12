<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each price the customer pays is an invoice in their account; a price paid in parts keeps adding to the same one. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('builder_projects', 'quote_invoice_id')) {
            return;
        }

        Schema::table('builder_projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('quote_invoice_id')->nullable()->after('quote_paid');
            $table->unsignedInteger('quote_invoice_version')->nullable()->after('quote_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['quote_invoice_version', 'quote_invoice_id'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
