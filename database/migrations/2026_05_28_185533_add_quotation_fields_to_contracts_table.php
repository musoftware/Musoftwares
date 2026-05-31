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
            $table->text('project_description')->nullable()->after('project_name');
            $table->string('reference')->nullable()->after('id');
            $table->string('prepared_by')->nullable()->after('reference');
            $table->date('valid_until')->nullable()->after('prepared_by');
            $table->string('duration')->nullable()->after('valid_until');
            $table->boolean('includes_hosting')->default(false)->after('duration');
            $table->string('hosting_duration')->nullable()->after('includes_hosting');
            $table->boolean('includes_support')->default(true)->after('hosting_duration');
            $table->string('support_duration')->nullable()->after('includes_support');
            $table->text('notes')->nullable()->after('payment_terms');
            $table->text('terms')->nullable()->after('notes');
            $table->json('features')->nullable()->after('terms');
            $table->json('items')->nullable()->after('features');
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
