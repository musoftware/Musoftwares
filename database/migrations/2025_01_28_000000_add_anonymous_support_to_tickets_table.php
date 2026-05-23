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
        // Only run if the columns don't already exist
        if (!Schema::hasColumn('tickets', 'anonymous_name') || !Schema::hasColumn('tickets', 'anonymous_email')) {
            Schema::table('tickets', function (Blueprint $table) {
                // Add anonymous user fields only if they don't exist
                if (!Schema::hasColumn('tickets', 'anonymous_name')) {
                    $table->string('anonymous_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('tickets', 'anonymous_email')) {
                    $table->string('anonymous_email')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remove anonymous fields if they exist
            if (Schema::hasColumn('tickets', 'anonymous_name')) {
                $table->dropColumn('anonymous_name');
            }
            if (Schema::hasColumn('tickets', 'anonymous_email')) {
                $table->dropColumn('anonymous_email');
            }
        });
    }
};
