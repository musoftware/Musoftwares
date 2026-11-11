<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Big design pictures are made smaller in the browser for the chat (a preview); after payment the customer uploads
 * the originals for the build, which waits a while for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_attachments', function (Blueprint $table) {
            $table->string('quality', 16)->nullable()->after('size');
        });
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->timestamp('originals_due_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('builder_attachments', function (Blueprint $table) {
            $table->dropColumn('quality');
        });
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn('originals_due_at');
        });
    }
};
