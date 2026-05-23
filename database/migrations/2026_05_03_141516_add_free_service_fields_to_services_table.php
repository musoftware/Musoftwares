<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('services', 'is_free')) {
                $blueprint->boolean('is_free')->default(false)->after('price');
            }
            if (!Schema::hasColumn('services', 'require_share_to_download')) {
                $blueprint->boolean('require_share_to_download')->default(false)->after('is_free');
            }
            if (!Schema::hasColumn('services', 'status')) {
                $blueprint->string('status')->default('draft')->after('price');
            }
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['is_free', 'require_share_to_download']);
        });
    }
};
