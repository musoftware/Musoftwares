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
        if (!Schema::hasTable('booking_branches')) {
            Schema::create('booking_branches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_main_branch')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('booking_branches', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_branches', 'phone')) {
                    $table->string('phone')->nullable()->after('address');
                }
                if (!Schema::hasColumn('booking_branches', 'is_main_branch')) {
                    $table->boolean('is_main_branch')->default(false)->after('phone');
                }
                if (!Schema::hasColumn('booking_branches', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_branches');
    }
};
