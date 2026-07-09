<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->string('category', 80)->nullable()->after('reason')->index();
            $table->string('payment_method', 40)->nullable()->after('category');
            $table->decimal('tax_amount', 23, 3)->default(0)->after('payment_method');
            $table->decimal('tax_rate', 6, 3)->default(0)->after('tax_amount');
            $table->boolean('is_billable')->default(false)->after('tax_rate')->index();
            $table->unsignedBigInteger('created_by')->nullable()->after('is_billable')->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->string('attachment_path')->nullable()->after('created_by');
            $table->text('notes')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_billable']);
            $table->dropIndex(['created_by']);
            $table->dropColumn([
                'category',
                'payment_method',
                'tax_amount',
                'tax_rate',
                'is_billable',
                'created_by',
                'attachment_path',
                'notes',
            ]);
        });
    }
};
