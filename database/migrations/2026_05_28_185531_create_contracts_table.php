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
        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('project_proposal_id')->nullable()->constrained()->onDelete('set null');
                $table->string('project_name');
                $table->longText('description')->nullable();
                $table->decimal('total_amount', 12, 2);
                $table->integer('currency_id')->default(1);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['draft', 'sent', 'signed', 'active', 'completed'])->default('draft');
                $table->json('content')->nullable(); // For detailed terms
                $table->text('payment_terms')->nullable();
                $table->boolean('deposit_paid')->default(false);
                $table->decimal('deposit_amount', 12, 2)->default(0);
                $table->longText('client_signature')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('contracts', function (Blueprint $table) {
                if (! Schema::hasColumn('contracts', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->after('id');
                }
                if (! Schema::hasColumn('contracts', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null')->after('user_id');
                }
                if (! Schema::hasColumn('contracts', 'project_proposal_id')) {
                    $table->foreignId('project_proposal_id')->nullable()->constrained()->onDelete('set null')->after('project_id');
                }
                if (! Schema::hasColumn('contracts', 'project_name')) {
                    $table->string('project_name')->after('project_proposal_id');
                }
                if (! Schema::hasColumn('contracts', 'description')) {
                    $table->longText('description')->nullable()->after('project_name');
                }
                if (! Schema::hasColumn('contracts', 'total_amount')) {
                    $table->decimal('total_amount', 12, 2)->after('description');
                }
                if (! Schema::hasColumn('contracts', 'currency_id')) {
                    $table->integer('currency_id')->default(1)->after('total_amount');
                }
                if (! Schema::hasColumn('contracts', 'start_date')) {
                    $table->date('start_date')->nullable()->after('currency_id');
                }
                if (! Schema::hasColumn('contracts', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (! Schema::hasColumn('contracts', 'status')) {
                    $table->enum('status', ['draft', 'sent', 'signed', 'active', 'completed'])->default('draft')->after('end_date');
                }
                if (! Schema::hasColumn('contracts', 'content')) {
                    $table->json('content')->nullable()->after('status');
                }
                if (! Schema::hasColumn('contracts', 'payment_terms')) {
                    $table->text('payment_terms')->nullable()->after('content');
                }
                if (! Schema::hasColumn('contracts', 'deposit_paid')) {
                    $table->boolean('deposit_paid')->default(false)->after('payment_terms');
                }
                if (! Schema::hasColumn('contracts', 'deposit_amount')) {
                    $table->decimal('deposit_amount', 12, 2)->default(0)->after('deposit_paid');
                }
                if (! Schema::hasColumn('contracts', 'client_signature')) {
                    $table->longText('client_signature')->nullable()->after('deposit_amount');
                }
                if (! Schema::hasColumn('contracts', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable()->after('client_signature');
                }
                if (! Schema::hasColumn('contracts', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
