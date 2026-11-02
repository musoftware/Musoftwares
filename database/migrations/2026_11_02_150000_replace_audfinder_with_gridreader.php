<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateSerialSoftware();
        $this->migrateSoftwareProduct();
        $this->seedInitialRelease();
    }

    public function down(): void
    {
        // Permanent replacement: AudFinder is removed in favor of GridReader
    }

    private function migrateSerialSoftware(): void
    {
        if (! Schema::hasTable('serial_softwares')) {
            return;
        }

        $existingAudFinder = DB::table('serial_softwares')->where('name', 'AudFinder')->first();
        $existingGridReader = DB::table('serial_softwares')->where('name', 'GridReader')->first();

        if ($existingAudFinder && ! $existingGridReader) {
            DB::table('serial_softwares')->where('id', $existingAudFinder->id)->update([
                'name' => 'GridReader',
                'updated_at' => now(),
            ]);
            return;
        }

        if ($existingAudFinder && $existingGridReader) {
            $this->repointSerialRelationships($existingAudFinder->id, $existingGridReader->id);
            DB::table('serial_softwares')->where('id', $existingAudFinder->id)->delete();
            return;
        }
    }

    private function repointSerialRelationships(int $oldId, int $newId): void
    {
        if (Schema::hasTable('serial_devices')) {
            DB::table('serial_devices')->where('serial_software_id', $oldId)->update(['serial_software_id' => $newId]);
        }
        if (Schema::hasTable('serial_software_licenses')) {
            DB::table('serial_software_licenses')->where('serial_software_id', $oldId)->update(['serial_software_id' => $newId]);
        }
        if (Schema::hasTable('software_products')) {
            DB::table('software_products')->where('serial_software_id', $oldId)->update(['serial_software_id' => $newId]);
        }
    }

    private function migrateSoftwareProduct(): void
    {
        if (! Schema::hasTable('software_products')) {
            return;
        }

        $gridSoftwareId = Schema::hasTable('serial_softwares')
            ? DB::table('serial_softwares')->where('name', 'GridReader')->value('id')
            : null;

        $audProduct = DB::table('software_products')->where('slug', 'audfinder')->first();
        $gridProduct = DB::table('software_products')->where('slug', 'gridreader')->first();

        if ($audProduct && ! $gridProduct) {
            DB::table('software_products')->where('id', $audProduct->id)->update([
                'slug' => 'gridreader',
                'name_en' => 'GridReader',
                'name_ar' => 'جريد ريدر',
                'image_path' => 'images/software/gridreader.png',
                'tagline_en' => 'Lightning-fast viewer and query engine for CSV, Excel, JSON, SQLite, PostgreSQL and MySQL.',
                'tagline_ar' => 'مستعرض ومحرك استعلام فائق السرعة لملفات CSV و Excel و JSON وقواعد بيانات SQLite و PostgreSQL و MySQL.',
                'description_en' => 'Open massive tabular datasets in milliseconds. Instant virtual scrolling, multi-column search, SQLite index acceleration, list matching, and direct SQL querying with zero lag.',
                'description_ar' => 'افتح أضخم ملفات البيانات والجداول في أجزاء من الثانية. تصفح افتراضي فائق السرعة، بحث متعدد الأعمدة، تسريع بالفهارس، مطابقة قوائم، واستعلام SQL مباشر بدون أي تهنيج.',
                'serial_software_id' => $gridSoftwareId ?? $audProduct->serial_software_id,
                'updated_at' => now(),
            ]);
            return;
        }

        if ($audProduct && $gridProduct) {
            if (Schema::hasTable('software_orders')) {
                DB::table('software_orders')->where('software_product_id', $audProduct->id)->update(['software_product_id' => $gridProduct->id]);
            }
            if (Schema::hasTable('software_releases')) {
                DB::table('software_releases')->where('software_product_id', $audProduct->id)->update(['software_product_id' => $gridProduct->id]);
            }
            DB::table('software_products')->where('id', $audProduct->id)->delete();
        }
    }

    private function seedInitialRelease(): void
    {
        if (! Schema::hasTable('software_products') || ! Schema::hasTable('software_releases')) {
            return;
        }

        $gridProdId = DB::table('software_products')->where('slug', 'gridreader')->value('id');
        if (! $gridProdId || DB::table('software_releases')->where('software_product_id', $gridProdId)->where('version', '1.0.0')->exists()) {
            return;
        }

        $installerPath = storage_path('app/private/software-releases/gridreader/gridreader-setup-1.0.0.exe');
        if (! file_exists($installerPath)) {
            return;
        }

        $sha512 = base64_encode(hash_file('sha512', $installerPath, true));
        $size = filesize($installerPath);
        $blockmapRelative = 'software-releases/gridreader/gridreader-setup-1.0.0.exe.blockmap';
        $blockmapFull = storage_path('app/private/'.$blockmapRelative);

        DB::table('software_releases')->insert([
            'software_product_id' => $gridProdId,
            'version' => '1.0.0',
            'file_path' => 'software-releases/gridreader/gridreader-setup-1.0.0.exe',
            'file_name' => 'gridreader-setup-1.0.0.exe',
            'sha512' => $sha512,
            'size' => $size,
            'blockmap_path' => file_exists($blockmapFull) ? $blockmapRelative : null,
            'is_published' => true,
            'released_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
