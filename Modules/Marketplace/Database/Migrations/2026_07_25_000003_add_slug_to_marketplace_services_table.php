<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\Service;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_services')) {
            if (!Schema::hasColumn('marketplace_services', 'slug')) {
                Schema::table('marketplace_services', function (Blueprint $table) {
                    $table->string('slug')->nullable()->after('title')->index();
                });
            }

            // Populate existing records with slugs
            Service::withoutEvents(function () {
                Service::whereNull('slug')->orWhere('slug', '')->chunkById(100, function ($services) {
                    foreach ($services as $service) {
                        $slugStr = Str::slug($service->title);
                        if (empty($slugStr)) {
                            $slugStr = 'service-' . $service->id;
                        }
                        $service->update(['slug' => $slugStr]);
                    }
                });
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_services') && Schema::hasColumn('marketplace_services', 'slug')) {
            Schema::table('marketplace_services', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
