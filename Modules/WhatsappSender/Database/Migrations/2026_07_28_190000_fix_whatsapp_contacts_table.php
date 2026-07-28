<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the whatsapp_contacts table exists and does NOT have the whatsapp_contact_group_id column
        if (Schema::hasTable('whatsapp_contacts') && !Schema::hasColumn('whatsapp_contacts', 'whatsapp_contact_group_id')) {
            // Drop it since it is legacy/orphaned without group relations
            Schema::dropIfExists('whatsapp_contacts');
        }

        // Recreate it properly if it does not exist
        if (!Schema::hasTable('whatsapp_contacts')) {
            Schema::create('whatsapp_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_contact_group_id')->constrained('whatsapp_contact_groups')->onDelete('cascade');
                $table->string('name')->nullable();
                $table->string('phone');
                $table->json('custom_fields')->nullable();
                $table->timestamps();

                $table->index(['whatsapp_contact_group_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
