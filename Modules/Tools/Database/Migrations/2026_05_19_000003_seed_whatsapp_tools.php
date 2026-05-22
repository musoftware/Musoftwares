<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tools') || !Schema::hasTable('tool_pricing_plans')) {
            return;
        }

        // ── WhatsApp Bulk Sender v2 ──────────────────────────────────────────
        $senderId = DB::table('tools')->insertGetId([
            'title'             => 'WhatsApp Bulk Sender',
            'slug'              => 'whatsapp-sender',
            'description'       => 'Advanced WhatsApp messaging with humanization, deliverability tracking, health scoring, and ban prevention. Send bulk campaigns with AI-powered typing simulation, message variation, and automatic block-rate monitoring. Includes proxy support, session persistence, and post-campaign health reports.',
            'short_description' => 'Humanized WhatsApp bulk sending with deliverability tracking & ban prevention.',
            'icon'              => null,
            'category'          => 'whatsapp',
            'supported_os'      => json_encode(['windows', 'mac', 'linux']),
            'current_version'   => '2.0.0',
            'is_active'         => true,
            'is_featured'       => true,
            'is_free'           => false,
            'download_count'    => 0,
            'runner_component'  => 'whatsapp-sender',
            'features'          => json_encode([
                'Humanized typing simulation (40-80 WPM)',
                'Bell-curve delay distribution',
                'Message variation & synonym engine',
                'Typo + correction simulation',
                'Deliverability tracking (sent → read → replied)',
                'Block rate monitoring & auto-stop',
                'Session persistence (QR scan once)',
                'Post-campaign health scoring',
                'Proxy support for number isolation',
                'Arabic & English support',
                'Personalization ({name}, {phone}, {company})',
                'Media attachment support']),
            'requirements'      => json_encode([
                'Node.js runtime (auto-managed)',
                'Chrome/Chromium browser',
                'Active WhatsApp account']),
            'created_at'        => now(),
            'updated_at'        => now()]);

        // Sender pricing plans
        DB::table('tool_pricing_plans')->insert([
            [
                'tool_id'        => $senderId,
                'name'           => 'Starter',
                'price_monthly'  => 29.00,
                'price_yearly'   => 290.00,
                'features'       => json_encode(['1 WhatsApp number', '500 msgs/day', 'Basic humanization', 'Session persistence']),
                'is_popular'     => false,
                'sort_order'     => 0,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $senderId,
                'name'           => 'Growth',
                'price_monthly'  => 79.00,
                'price_yearly'   => 790.00,
                'features'       => json_encode(['5 WhatsApp numbers', '2000 msgs/day', 'Full humanization', 'Deliverability tracking', 'Block rate monitoring']),
                'is_popular'     => true,
                'sort_order'     => 1,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $senderId,
                'name'           => 'Agency',
                'price_monthly'  => 199.00,
                'price_yearly'   => 1990.00,
                'features'       => json_encode(['20 WhatsApp numbers', 'Unlimited msgs/day', 'Full humanization', 'Deliverability tracking', 'Proxy support', 'Priority support']),
                'is_popular'     => false,
                'sort_order'     => 2,
                'created_at'     => now(),
                'updated_at'     => now()]]);

        // ── WhatsApp Number Warmup ───────────────────────────────────────────
        $warmupId = DB::table('tools')->insertGetId([
            'title'             => 'WhatsApp Number Warmup',
            'slug'              => 'wa-warmup',
            'description'       => 'Automatically warm up WhatsApp numbers to build trust scores and prevent bans. 14-day graduated warmup schedule with pool conversations, health monitoring, ban prediction, and trust scoring. Essential for any agency managing multiple WhatsApp numbers.',
            'short_description' => 'Warm up WhatsApp numbers automatically — 14-day schedule with health scoring & ban prevention.',
            'icon'              => null,
            'category'          => 'whatsapp',
            'supported_os'      => json_encode(['windows', 'mac', 'linux']),
            'current_version'   => '1.0.0',
            'is_active'         => true,
            'is_featured'       => true,
            'is_free'           => false,
            'download_count'    => 0,
            'runner_component'  => 'wa-warmup',
            'features'          => json_encode([
                '14-day graduated warmup schedule',
                'Automatic pool conversations',
                'Trust score algorithm (A+ to F grading)',
                'Ban probability prediction',
                'Health monitoring per number',
                'Active hours enforcement',
                'Daily message limit management',
                'Arabic & English conversation pools',
                'Session persistence & restore',
                'Proxy support for number isolation',
                'Post-session health reports',
                'Campaign-readiness detection']),
            'requirements'      => json_encode([
                'Node.js runtime (auto-managed)',
                'Chrome/Chromium browser',
                'Active WhatsApp account',
                '2+ pool numbers for warmup conversations']),
            'created_at'        => now(),
            'updated_at'        => now()]);

        // Warmup pricing plans
        DB::table('tool_pricing_plans')->insert([
            [
                'tool_id'        => $warmupId,
                'name'           => 'Starter',
                'price_monthly'  => 19.00,
                'price_yearly'   => 190.00,
                'features'       => json_encode(['Warm up 1 number', 'Basic health scoring', '14-day schedule', 'English conversations']),
                'is_popular'     => false,
                'sort_order'     => 0,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $warmupId,
                'name'           => 'Growth',
                'price_monthly'  => 49.00,
                'price_yearly'   => 490.00,
                'features'       => json_encode(['Warm up 5 numbers', 'Full health scoring', 'Ban prediction', 'Arabic + English', 'Proxy support']),
                'is_popular'     => true,
                'sort_order'     => 1,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $warmupId,
                'name'           => 'Agency',
                'price_monthly'  => 99.00,
                'price_yearly'   => 990.00,
                'features'       => json_encode(['Warm up 50 numbers', 'Full health scoring', 'Ban prediction', 'All languages', 'Proxy support', 'Priority support']),
                'is_popular'     => false,
                'sort_order'     => 2,
                'created_at'     => now(),
                'updated_at'     => now()]]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('tools') || !Schema::hasTable('tool_pricing_plans')) {
            return;
        }

        $slugs = ['whatsapp-sender', 'wa-warmup'];
        $toolIds = DB::table('tools')->whereIn('slug', $slugs)->pluck('id');

        DB::table('tool_pricing_plans')->whereIn('tool_id', $toolIds)->delete();
        DB::table('tools')->whereIn('slug', $slugs)->delete();
    }
};
