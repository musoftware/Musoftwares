<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One program store. The older "store tools" list is replaced by the software store (/services);
 * any published tool the store does not sell yet is copied there HIDDEN, so the admin reviews its
 * page before it shows. Nothing is deleted: store_tools stays as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_tools') || ! Schema::hasTable('software_products')) {
            return;
        }

        $order = (int) DB::table('software_products')->max('sort_order');

        foreach (DB::table('store_tools')->where('is_published', true)->orderBy('sort_order')->get() as $tool) {
            $covered = $tool->serial_software_id
                ? DB::table('software_products')->where('serial_software_id', $tool->serial_software_id)->exists()
                : DB::table('software_products')->where('name_en', $tool->name)->exists();
            if ($covered) {
                continue;
            }

            DB::table('software_products')->insert([
                'slug' => $this->freeSlug($tool->slug ?: $tool->name),
                'serial_software_id' => $tool->serial_software_id,
                'name_en' => $tool->name,
                'name_ar' => $tool->name,
                'tagline_en' => $tool->tagline,
                'tagline_ar' => $tool->tagline,
                'description_en' => $tool->description,
                'description_ar' => $tool->description,
                'price' => (float) $tool->price,
                'currency' => strtoupper($tool->currency ?: 'USD'),
                'is_active' => false,
                'sort_order' => ++$order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // copies are hidden products; the admin may have edited them since, so they are left in place
    }

    /** A slug not taken yet; "-system" endings are business systems in the store, so tools avoid them. */
    private function freeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'program';
        if (str_ends_with($base, '-system')) {
            $base .= '-app';
        }
        $slug = $base;
        for ($i = 2; DB::table('software_products')->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
};
