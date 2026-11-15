<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The badge card link used the user id and a long signature: the id showed how many users the site has, and
 * Facebook sometimes changed the long link so the signature broke. Each client now gets a random code instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'badge_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('badge_token', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'badge_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['badge_token']);
            $table->dropColumn('badge_token');
        });
    }
};
