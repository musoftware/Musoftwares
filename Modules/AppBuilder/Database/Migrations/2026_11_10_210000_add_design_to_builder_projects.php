<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A professional design by the Musoftware designer, priced apart from the programming. A paid project with a
 * design waits in "designing" until the team adds the Figma link; then the build starts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'design_fee')) {
                $table->decimal('design_fee', 10, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('builder_projects', 'design_url')) {
                $table->string('design_url', 500)->nullable()->after('design_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['design_url', 'design_fee'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
