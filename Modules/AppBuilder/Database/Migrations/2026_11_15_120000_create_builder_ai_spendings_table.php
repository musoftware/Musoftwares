<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every dollar Claude cost us, so the part past the free credit becomes a business cost once a day.
 * What was spent before this table is kept as one row, so it counts against the free credit too.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('builder_ai_spendings')) {
            Schema::create('builder_ai_spendings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->string('source', 30);
                $table->decimal('usd', 12, 6);
                $table->timestamp('booked_at')->nullable()->index();
                $table->unsignedBigInteger('cost_transaction_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $before = $this->spentBefore();
        if ($before > 0 && DB::table('builder_ai_spendings')->where('source', 'before')->doesntExist()) {
            DB::table('builder_ai_spendings')->insert(['source' => 'before', 'usd' => $before, 'booked_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_ai_spendings');
    }

    private function spentBefore(): float
    {
        if (! Schema::hasTable('builder_projects')) {
            return 0.0;
        }

        $prices = config('builder.chat_prices');
        $row = DB::table('builder_projects')
            ->selectRaw('COALESCE(SUM(build_cost_usd), 0) as builds, COALESCE(SUM(chat_input_tokens), 0) as input, COALESCE(SUM(chat_output_tokens), 0) as output')
            ->first();

        return round((float) $row->builds + ((float) $row->input * $prices['input_usd_per_mtok'] + (float) $row->output * $prices['output_usd_per_mtok']) / 1_000_000, 6);
    }
};
