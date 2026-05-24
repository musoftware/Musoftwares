<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('title');
            }
        });

        // Update all services to have a slug using raw DB (avoid model dependency)
        $services = \Illuminate\Support\Facades\DB::table('services')->whereNull('slug')->orWhere('slug', '')->get();
        foreach ($services as $service) {
            $slug = \Illuminate\Support\Str::slug($service->title ?? 'service-' . $service->id);
            \Illuminate\Support\Facades\DB::table('services')->where('id', $service->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
