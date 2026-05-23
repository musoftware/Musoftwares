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
        Schema::create('premium_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->default('fas fa-crown');
            $table->string('category')->default('financial');
            $table->enum('access_level', ['basic', 'premium', 'enterprise'])->default('premium');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->json('api_endpoints')->nullable();
            $table->timestamps();
        });

        Schema::create('membership_premium_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('membership_id');
            $table->unsignedBigInteger('premium_tool_id');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->foreign('membership_id')->references('id')->on('memberships')->onDelete('cascade');
            $table->foreign('premium_tool_id')->references('id')->on('premium_tools')->onDelete('cascade');
            $table->unique(['membership_id', 'premium_tool_id']);
        });

        Schema::create('premium_tool_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('premium_tool_id');
            $table->timestamp('used_at');
            $table->json('usage_data')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('premium_tool_id')->references('id')->on('premium_tools')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('premium_tool_usage');
        Schema::dropIfExists('membership_premium_tools');
        Schema::dropIfExists('premium_tools');
    }
};
