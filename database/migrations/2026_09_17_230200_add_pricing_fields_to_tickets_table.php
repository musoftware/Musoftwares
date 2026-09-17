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
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('priority');
            }
            if (! Schema::hasColumn('tickets', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete()->after('price');
            }
            if (! Schema::hasColumn('tickets', 'pricing_status')) {
                $table->enum('pricing_status', ['pending', 'quoted', 'accepted', 'declined'])->default('pending')->after('currency_id');
            }
            if (! Schema::hasColumn('tickets', 'pricing_notes')) {
                $table->text('pricing_notes')->nullable()->after('pricing_status');
            }
            if (! Schema::hasColumn('tickets', 'quoted_at')) {
                $table->dateTime('quoted_at')->nullable()->after('pricing_notes');
            }
            if (! Schema::hasColumn('tickets', 'quoted_by')) {
                $table->foreignId('quoted_by')->nullable()->constrained('users')->nullOnDelete()->after('quoted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quoted_by');
            $table->dropConstrainedForeignId('currency_id');
            $table->dropColumn(['price', 'pricing_status', 'pricing_notes', 'quoted_at']);
        });
    }
};
