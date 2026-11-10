<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A customer who came from a referral link pays a little more, and that part goes to the referrer. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('builder_projects', 'referrer_user_id')) {
                $table->foreignId('referrer_user_id')->nullable()->after('client_whatsapp')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('builder_projects', 'referral_commission')) {
                $table->decimal('referral_commission', 10, 2)->default(0)->after('referrer_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('builder_projects', 'referrer_user_id')) {
                $table->dropConstrainedForeignId('referrer_user_id');
            }
            if (Schema::hasColumn('builder_projects', 'referral_commission')) {
                $table->dropColumn('referral_commission');
            }
        });
    }
};
