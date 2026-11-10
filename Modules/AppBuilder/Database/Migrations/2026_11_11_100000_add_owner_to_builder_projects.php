<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The signed-in customer a project belongs to, so it shows in their account and the link is never lost. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('uuid')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
