<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Every fix is free for two months after the first delivery (or the import): the project's warranty. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('builder_projects', 'warranty_until')) {
            return;
        }

        Schema::table('builder_projects', function (Blueprint $table): void {
            $table->timestamp('warranty_until')->nullable()->after('built_at');
        });
        // projects delivered before the warranty existed: two months from their import or last delivery
        DB::table('builder_projects')->where('version', '>', 0)->orderBy('id')->each(function (object $project): void {
            $from = $project->imported_at ?? $project->built_at ?? $project->updated_at;
            DB::table('builder_projects')->where('id', $project->id)->update(['warranty_until' => \Illuminate\Support\Carbon::parse($from)->addDays(60)]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('builder_projects', 'warranty_until')) {
            Schema::table('builder_projects', fn (Blueprint $table) => $table->dropColumn('warranty_until'));
        }
    }
};
