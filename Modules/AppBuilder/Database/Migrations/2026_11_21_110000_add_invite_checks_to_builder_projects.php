<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The browser a chat came from and when its invite link was opened, so a free first build goes only to a real invitee. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('builder_projects', 'device_id')) {
                $table->string('device_id', 64)->nullable()->index();
            }
            if (! Schema::hasColumn('builder_projects', 'invited_at')) {
                $table->timestamp('invited_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropIndex(['device_id']);
            $table->dropColumn(['device_id', 'invited_at']);
        });
    }
};
