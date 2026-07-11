<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'project_description')) {
                $table->text('project_description')->nullable()->after('project_name');
            }
            if (! Schema::hasColumn('contracts', 'reference')) {
                $table->string('reference')->nullable()->after('id');
            }
            if (! Schema::hasColumn('contracts', 'prepared_by')) {
                $table->string('prepared_by')->nullable()->after('reference');
            }
            if (! Schema::hasColumn('contracts', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('prepared_by');
            }
            if (! Schema::hasColumn('contracts', 'duration')) {
                $table->string('duration')->nullable()->after('valid_until');
            }
            if (! Schema::hasColumn('contracts', 'includes_hosting')) {
                $table->boolean('includes_hosting')->default(false)->after('duration');
            }
            if (! Schema::hasColumn('contracts', 'hosting_duration')) {
                $table->string('hosting_duration')->nullable()->after('includes_hosting');
            }
            if (! Schema::hasColumn('contracts', 'includes_support')) {
                $table->boolean('includes_support')->default(true)->after('hosting_duration');
            }
            if (! Schema::hasColumn('contracts', 'support_duration')) {
                $table->string('support_duration')->nullable()->after('includes_support');
            }
            if (! Schema::hasColumn('contracts', 'notes')) {
                $table->text('notes')->nullable()->after('payment_terms');
            }
            if (! Schema::hasColumn('contracts', 'terms')) {
                $table->text('terms')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('contracts', 'features')) {
                $table->json('features')->nullable()->after('terms');
            }
            if (! Schema::hasColumn('contracts', 'items')) {
                $table->json('items')->nullable()->after('features');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'project_description',
                'reference',
                'prepared_by',
                'valid_until',
                'duration',
                'includes_hosting',
                'hosting_duration',
                'includes_support',
                'support_duration',
                'notes',
                'terms',
                'features',
                'items',
            ]);
        });
    }
};
