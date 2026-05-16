<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Jobs
        Schema::create('freelance_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('budget', 20, 8);
            $table->string('currency_code', 3);
            $table->string('status'); // open, in_progress, completed, cancelled
            $table->softDeletes();
            $table->timestamps();
        });

        // Proposals
        Schema::create('freelance_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('freelance_jobs')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->decimal('bid_amount', 20, 8);
            $table->string('currency_code', 3);
            $table->string('status'); // pending, accepted, rejected
            $table->timestamps();
        });

        // Contracts
        Schema::create('freelance_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('freelance_jobs')->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained('freelance_proposals')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 20, 8);
            $table->string('currency_code', 3);
            $table->string('status'); // active, completed, disputed
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Point Transactions (Immutable)
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->string('type'); // earned, spent
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('freelance_contracts');
        Schema::dropIfExists('freelance_proposals');
        Schema::dropIfExists('freelance_jobs');
    }
};
