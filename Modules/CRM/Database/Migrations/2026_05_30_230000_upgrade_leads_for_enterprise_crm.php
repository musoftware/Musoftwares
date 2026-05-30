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
        Schema::table('leads', function (Blueprint $table) {
            // Drop old generic status if exists (assuming it was string or enum)
            // But to be safe, we will just add the new enterprise fields
            
            // Enterprise Pipeline Status
            $table->string('pipeline_stage')->default('NEW')->after('status')->comment('NEW, NO_ANSWER, FOLLOW_UP, INTERESTED, MEETING_SCHEDULED, NEGOTIATION, WON, LOST');
            
            // SLA and Workforce Fields
            $table->timestamp('last_contacted_at')->nullable()->after('pipeline_stage');
            $table->timestamp('sla_breach_at')->nullable()->after('last_contacted_at');
            $table->integer('call_attempts')->default(0)->after('sla_breach_at');
            
            // Reassignment logic
            $table->timestamp('reassigned_at')->nullable()->after('call_attempts');
            $table->boolean('is_stale')->default(false)->after('reassigned_at');
            
            // Source attribution for Lead Collectors
            $table->string('source')->nullable()->after('is_stale');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_stage',
                'last_contacted_at',
                'sla_breach_at',
                'call_attempts',
                'reassigned_at',
                'is_stale',
                'source',
            ]);
        });
    }
};
