<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The feasibility study is bought on its own (a small fixed price); what was paid for it comes off the project's first price. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'study_paid')) {
                $table->decimal('study_paid', 10, 2)->default(0)->after('study_fee');
                $table->string('study_payment_reference', 120)->nullable()->after('study_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['study_payment_reference', 'study_paid'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
