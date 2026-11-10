<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What the worker read from an Excel, Word or CSV file: its text, sheets and formulas, for Claude. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_attachments', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_attachments', 'extracted_text')) {
                $table->longText('extracted_text')->nullable()->after('path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_attachments', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_attachments', 'extracted_text')) {
                $table->dropColumn('extracted_text');
            }
        });
    }
};
