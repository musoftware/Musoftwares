<?php

use Illuminate\Database\Migrations\Migration;
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
        Invoice::withTrashed()->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                // Force recalculating the actual total bypassing the stale cache
                $invoice->final_total = $invoice->total(true);
                $invoice->unpaid = $invoice->unpaid_total(true);
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
        // No action needed for down migration since final_total is already present
    }
};
