<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('module'); // booking, crm, etc.
            $table->string('feature_key'); // whatsapp_reminders, online_booking, custom_domain
            $table->unsignedBigInteger('plan_id')->nullable(); // which plan granted this feature
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            // A tenant can only have one active entry per feature key
            $table->unique(['tenant_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};
