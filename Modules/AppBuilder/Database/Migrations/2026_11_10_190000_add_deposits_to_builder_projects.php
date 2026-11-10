<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A customer may pay part of a quote first (25% or 50%); the files are handed over when the rest is paid. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'quote_paid')) {
                // paid so far toward the current quote; paid_total keeps the sum over all quotes
                $table->decimal('quote_paid', 10, 2)->default(0)->after('paid_total');
            }
            if (! Schema::hasColumn('builder_projects', 'payment_due')) {
                // the amount of the payment link the customer last opened
                $table->decimal('payment_due', 10, 2)->default(0)->after('quote_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['payment_due', 'quote_paid'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
