<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new, contacted, converted, dead
            $table->string('locale')->nullable();
            $table->string('phone')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('platform_lead_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('platform_lead_set_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('platform_leads')->cascadeOnDelete();
            $table->foreignId('lead_set_id')->constrained('platform_lead_sets')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_lead_set_memberships');
        Schema::dropIfExists('platform_lead_sets');
        Schema::dropIfExists('platform_leads');
    }
};
