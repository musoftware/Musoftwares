<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── WhatsApp AI Agent ───────────────────────────────────────────────
        $agentId = DB::table('tools')->insertGetId([
            'title'             => 'WhatsApp AI Sales Agent',
            'slug'              => 'wa-ai-agent',
            'description'       => 'Autonomous Arabic-first AI sales agent. Listens to inbound WhatsApp messages and engages leads using advanced AI (GPT-4o). Adapts to regional dialects (Gulf, Egyptian, Levantine), answers questions based on your business context, and drives users toward your sales goals with human-like typing delays.',
            'short_description' => 'Autonomous AI sales agent that speaks fluent Arabic dialects and qualifies leads.',
            'icon'              => null,
            'category'          => 'whatsapp',
            'supported_os'      => json_encode(['windows', 'mac', 'linux']),
            'current_version'   => '1.0.0',
            'is_active'         => true,
            'is_featured'       => true,
            'is_free'           => false,
            'download_count'    => 0,
            'runner_component'  => 'wa-ai-agent',
            'features'          => json_encode([
                'Autonomous 24/7 conversation',
                'Native Arabic dialect understanding (Gulf, Levantine, Egyptian)',
                'Humanized typing simulation & delays',
                'Context-aware memory (remembers previous messages)',
                'Configurable sales goals & tone',
                'Real-time conversation monitoring',
                'Session persistence (QR scan once)']),
            'requirements'      => json_encode([
                'Node.js runtime (auto-managed)',
                'Chrome/Chromium browser',
                'Active WhatsApp account',
                'OpenAI API Key']),
            'created_at'        => now(),
            'updated_at'        => now()]);

        // Pricing plans
        DB::table('tool_pricing_plans')->insert([
            [
                'tool_id'        => $agentId,
                'name'           => 'Starter',
                'price_monthly'  => 49.00,
                'price_yearly'   => 490.00,
                'features'       => json_encode(['1 AI Agent', 'Standard Context Length', 'Basic humanization']),
                'is_popular'     => false,
                'sort_order'     => 0,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $agentId,
                'name'           => 'Growth',
                'price_monthly'  => 129.00,
                'price_yearly'   => 1290.00,
                'features'       => json_encode(['3 AI Agents', 'Long Context Memory', 'Full humanization', 'Dialect optimization']),
                'is_popular'     => true,
                'sort_order'     => 1,
                'created_at'     => now(),
                'updated_at'     => now()],
            [
                'tool_id'        => $agentId,
                'name'           => 'Agency',
                'price_monthly'  => 299.00,
                'price_yearly'   => 2990.00,
                'features'       => json_encode(['10 AI Agents', 'Unlimited Context', 'Proxy Support', 'Priority Support']),
                'is_popular'     => false,
                'sort_order'     => 2,
                'created_at'     => now(),
                'updated_at'     => now()]]);
    }

    public function down(): void
    {
        $slug = 'wa-ai-agent';
        $toolId = DB::table('tools')->where('slug', $slug)->value('id');

        if ($toolId) {
            DB::table('tool_pricing_plans')->where('tool_id', $toolId)->delete();
            DB::table('tools')->where('id', $toolId)->delete();
        }
    }
};
