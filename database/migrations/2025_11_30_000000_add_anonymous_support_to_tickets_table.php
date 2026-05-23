<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('tickets', function (Blueprint $table) {
                // Drop the foreign key constraint first
                $table->dropForeign(['user_id']);
                
                // Make user_id nullable
                $table->bigInteger('user_id')->unsigned()->nullable()->change();
                
                // Add anonymous user fields
                if (!Schema::hasColumn('tickets', 'anonymous_name')) {
                    $table->string('anonymous_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('tickets', 'anonymous_email')) {
                    $table->string('anonymous_email')->nullable()->after('anonymous_name');
                }
                
                // Re-add foreign key constraint but allow null
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // For SQLite, just add the new columns without changing existing ones
            Schema::table('tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('tickets', 'anonymous_name')) {
                    $table->string('anonymous_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('tickets', 'anonymous_email')) {
                    $table->string('anonymous_email')->nullable()->after('anonymous_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down()
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('tickets', function (Blueprint $table) {
                // Drop the foreign key
                $table->dropForeign(['user_id']);
                
                // Remove anonymous fields
                $table->dropColumn(['anonymous_name', 'anonymous_email']);
                
                // Make user_id required again
                $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
                
                // Re-add foreign key constraint
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // For SQLite, just remove the new columns
            Schema::table('tickets', function (Blueprint $table) {
                if (Schema::hasColumn('tickets', 'anonymous_name')) {
                    $table->dropColumn('anonymous_name');
                }
                if (Schema::hasColumn('tickets', 'anonymous_email')) {
                    $table->dropColumn('anonymous_email');
                }
            });
        }
    }
};
