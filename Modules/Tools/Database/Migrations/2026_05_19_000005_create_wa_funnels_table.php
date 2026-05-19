<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Create Tables for Funnel State Management ────────────────────
        Schema::create('wa_funnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft'); // draft, active, paused
            $table->json('nodes'); // ReactFlow nodes
            $table->json('edges'); // ReactFlow edges
            $table->timestamps();
        });

        Schema::create('wa_funnel_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_funnel_id')->constrained('wa_funnels')->cascadeOnDelete();
            $table->string('phone_number');
            $table->string('current_node_id');
            $table->string('status')->default('waiting'); // waiting, processing, completed, failed
            $table->json('variables')->nullable(); // stored contextual variables (e.g., name, custom fields)
            $table->timestamp('next_execution_at')->nullable(); // For time delays
            $table->timestamps();
            
            $table->index(['wa_funnel_id', 'status', 'next_execution_at']);
        });

        // ── 2. Register wa-funnel-engine in the Marketplace ─────────────────
        $engineId = DB::table('tools')->insertGetId([
            'title'             => 'WhatsApp Funnel Engine',
            'slug'              => 'wa-funnel-engine',
            'description'       => 'Enterprise-grade visual funnel builder for WhatsApp. Create advanced automated workflows with drag-and-drop nodes. Support for time delays, conditional logic (A/B testing), and auto-replies. Includes a robust state machine to manage thousands of concurrent conversations.',
            'short_description' => 'Drag-and-drop visual funnel builder with time delays and conditional automation.',
            'icon'              => null,
            'category'          => 'whatsapp',
            'supported_os'      => json_encode(['windows', 'mac', 'linux']),
            'current_version'   => '1.0.0',
            'max_devices'       => 3,
            'is_active'         => true,
            'is_featured'       => true,
            'is_free'           => false,
            'download_count'    => 0,
            'runner_component'  => 'wa-funnel-engine',
            'features'          => json_encode([
                'Visual drag-and-drop workflow builder',
                'Time delay nodes (Wait X hours/days)',
                'Conditional routing (If A then B)',
                'A/B testing optimizer',
                'Persistent state machine',
                'Integration with AI Agent nodes',
                'Humanized typing simulation',
            ]),
            'requirements'      => json_encode([
                'Node.js runtime (auto-managed)',
                'Chrome/Chromium browser',
                'Active WhatsApp account',
            ]),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Pricing plans
        DB::table('tool_pricing_plans')->insert([
            [
                'tool_id'        => $engineId,
                'name'           => 'Growth',
                'price_monthly'  => 99.00,
                'price_yearly'   => 990.00,
                'max_devices'    => 1,
                'features'       => json_encode(['3 Active Funnels', 'Basic Nodes', '1,000 active contacts']),
                'is_popular'     => false,
                'sort_order'     => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'tool_id'        => $engineId,
                'name'           => 'Agency',
                'price_monthly'  => 249.00,
                'price_yearly'   => 2490.00,
                'max_devices'    => 3,
                'features'       => json_encode(['Unlimited Funnels', 'Advanced A/B Testing', '10,000 active contacts', 'API Webhooks']),
                'is_popular'     => true,
                'sort_order'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'tool_id'        => $engineId,
                'name'           => 'Enterprise',
                'price_monthly'  => 499.00,
                'price_yearly'   => 4990.00,
                'max_devices'    => 10,
                'features'       => json_encode(['Unlimited Everything', 'Custom Nodes', 'Dedicated IP Proxy', 'Priority Support']),
                'is_popular'     => false,
                'sort_order'     => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }

    public function down(): void
    {
        $slug = 'wa-funnel-engine';
        $toolId = DB::table('tools')->where('slug', $slug)->value('id');

        if ($toolId) {
            DB::table('tool_pricing_plans')->where('tool_id', $toolId)->delete();
            DB::table('tools')->where('id', $toolId)->delete();
        }

        Schema::dropIfExists('wa_funnel_states');
        Schema::dropIfExists('wa_funnels');
    }
};
