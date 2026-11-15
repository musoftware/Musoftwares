<?php

use App\Models\UserReferral;
use App\Services\ReferralSlugs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invite links get a readable name made from the first name (/r/ahmed) instead of only the long code, and
 * every name a link had before is kept here so links already shared keep working. The long codes stay too.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_referral_slugs')) {
            Schema::create('user_referral_slugs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_referral_id')->constrained('user_referrals')->cascadeOnDelete();
                $table->string('slug', 100)->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $slugs = app(ReferralSlugs::class);
        UserReferral::with('user')->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))->chunkById(200, function ($referrals) use ($slugs): void {
            foreach ($referrals as $referral) {
                $slugs->ensure($referral);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_referral_slugs');
    }
};
