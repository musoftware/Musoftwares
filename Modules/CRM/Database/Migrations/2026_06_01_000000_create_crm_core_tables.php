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
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');

        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->string('type')->default('open'); // open, won, lost
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable(); // original lead
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->decimal('total_value', 12, 2)->default(0);
            $table->json('custom_data')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('type')->default('embed');
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('type')->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('target_model')->default('lead'); // lead or customer
            $table->timestamps();
        });

        // We already have leads table, let's update it to support the new pipeline architecture
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads', 'pipeline_id')) {
                    $table->unsignedBigInteger('pipeline_id')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('leads', 'pipeline_stage_id')) {
                    $table->unsignedBigInteger('pipeline_stage_id')->nullable()->after('pipeline_id');
                }
                if (Schema::hasColumn('leads', 'campaign_id')) {
                    $table->dropColumn('campaign_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn(['pipeline_id', 'pipeline_stage_id']);
                $table->unsignedBigInteger('campaign_id')->nullable();
            });
        }
    }
};
