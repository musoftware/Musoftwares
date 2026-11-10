<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A live trial: the delivered program in a real browser on a small Linux desktop, opened from a link. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'demo_status')) {
                // requested -> live, or failed; the worker picks up requested trials
                $table->string('demo_status', 12)->nullable()->after('version');
                $table->string('demo_url', 500)->nullable()->after('demo_status');
                // encrypted by the model's cast
                $table->text('demo_password')->nullable()->after('demo_url');
                $table->timestamp('demo_requested_at')->nullable()->after('demo_password');
                $table->timestamp('demo_expires_at')->nullable()->after('demo_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            foreach (['demo_expires_at', 'demo_requested_at', 'demo_password', 'demo_url', 'demo_status'] as $column) {
                if (Schema::hasColumn('builder_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
