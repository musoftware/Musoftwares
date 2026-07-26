<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\WhatsappSender\Models\WhatsappBusiness;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('whatsapp_businesses', 'webhook_verify_token')) {
            Schema::table('whatsapp_businesses', function (Blueprint $table) {
                $table->string('webhook_verify_token', 255)->nullable()->unique()->after('status');
            });
        }

        // Backfill existing WhatsappBusiness records with unique webhook verify tokens
        foreach (WhatsappBusiness::all() as $business) {
            if (empty($business->webhook_verify_token)) {
                $business->update([
                    'webhook_verify_token' => 'biz_wt_' . Str::random(24),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_businesses', 'webhook_verify_token')) {
            Schema::table('whatsapp_businesses', function (Blueprint $table) {
                $table->dropColumn('webhook_verify_token');
            });
        }
    }
};
