<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Skills
        if (!Schema::hasTable('freelance_skills')) {
            Schema::create('freelance_skills', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // User Skills
        if (!Schema::hasTable('freelance_user_skills')) {
            Schema::create('freelance_user_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained('freelance_skills')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // Point Packages
        if (!Schema::hasTable('point_packages')) {
            Schema::create('point_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('points');
                $table->decimal('price', 20, 8);
                $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
                $table->timestamps();
            });
        }

        // Jobs
        if (!Schema::hasTable('freelance_jobs')) {
            Schema::create('freelance_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('description');
                $table->decimal('budget', 20, 8)->default(0);
                $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
                $table->integer('min_proposal_points')->default(0);
                $table->string('type'); // fixed, hourly
                $table->string('duration')->nullable();
                $table->string('status'); // open, in_progress, completed, cancelled
                $table->softDeletes();
                $table->timestamps();
            });
        }


        // Job Skills
        if (!Schema::hasTable('freelance_job_skills')) {
            Schema::create('freelance_job_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('freelance_jobs')->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained('freelance_skills')->cascadeOnDelete();
                $table->boolean('is_required')->default(true);
                $table->timestamps();
            });
        }

        // Proposals
        if (!Schema::hasTable('freelance_proposals')) {
            Schema::create('freelance_proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('freelance_jobs')->cascadeOnDelete();
                $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
                $table->text('cover_letter');
                $table->decimal('bid_amount', 20, 8)->default(0);
                $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
                $table->integer('proposed_budget_points')->default(0);
                $table->integer('points_spent')->default(0);
                $table->string('status'); // pending, accepted, rejected
                $table->timestamps();
            });
        }

        // Contracts
        if (!Schema::hasTable('freelance_contracts')) {
            Schema::create('freelance_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('freelance_jobs')->cascadeOnDelete();
                $table->foreignId('proposal_id')->constrained('freelance_proposals')->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 20, 8)->default(0);
                $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
                $table->integer('contract_points')->default(0);
                $table->string('status'); // active, completed, disputed
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // Point Transactions (Immutable)
        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->integer('points');
                $table->string('type'); // earned, spent, credit, debit
                $table->string('description')->nullable();
                $table->string('reference_type')->nullable();
                $table->string('reference_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('freelance_contracts');
        Schema::dropIfExists('freelance_proposals');
        Schema::dropIfExists('freelance_job_skills');
        Schema::dropIfExists('freelance_jobs');
        Schema::dropIfExists('point_packages');
        Schema::dropIfExists('freelance_user_skills');
        Schema::dropIfExists('freelance_skills');
    }
};
