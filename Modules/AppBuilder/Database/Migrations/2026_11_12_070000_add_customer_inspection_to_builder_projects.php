<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer can bring their own project (a Google Drive link) and pay a small fixed fee to have it inspected in a
 * locked box; the maintenance price shows after the inspection. Their project was not built by us, so it has no warranty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('builder_projects', 'customer_project')) {
            return;
        }

        Schema::table('builder_projects', function (Blueprint $table): void {
            $table->boolean('customer_project')->default(false)->after('import_url');
            $table->string('inspection_status', 20)->nullable()->after('customer_project');
            $table->text('inspection_need')->nullable()->after('inspection_status');
            $table->decimal('inspection_paid', 10, 2)->default(0)->after('inspection_need');
            $table->string('inspection_reference', 120)->nullable()->after('inspection_paid');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['inspection_reference', 'inspection_paid', 'inspection_need', 'inspection_status', 'customer_project'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
