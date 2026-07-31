<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Invoice;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'final_total')) {
                $table->double('final_total', 23, 3)->default(0)->after('unpaid');
            }
        });

        // Populate existing invoices' final_total and update unpaid just in case
        Invoice::withTrashed()->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                $invoice->final_total = $invoice->total();
                $invoice->unpaid = $invoice->unpaid_total();
                $invoice->saveQuietly();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'final_total')) {
                $table->dropColumn('final_total');
            }
        });
    }
};
