<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add sort_order to plans for admin reordering.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('id');
        });

        // Set initial order by id so existing plans keep their current order
        $plans = DB::table('plans')->orderBy('id')->get();
        foreach ($plans as $i => $plan) {
            DB::table('plans')->where('id', $plan->id)->update(['sort_order' => $i]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
