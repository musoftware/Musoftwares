<?php

namespace App\Listeners;

use App\Events\MarketplaceOrderPlaced;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderKickoffListener
{
    public function handle(MarketplaceOrderPlaced $event): void
    {
        $order = $event->order;
        if (!$order) {
            return;
        }

        try {
            $order->loadMissing(['package.service']);

            $snapshot = is_array($order->snapshot) ? $order->snapshot : json_decode($order->snapshot ?? '[]', true);
            $serviceTitle = $snapshot['service_title'] ?? $order->package?->service?->title ?? 'Service';
            $packageName = $snapshot['package_name'] ?? $order->package?->name ?? 'Package';

            $projectName = "Order #{$order->id}: {$serviceTitle} ({$packageName})";

            // Prevent duplicate project creation if listener runs multiple times
            $project = Project::firstOrCreate(
                [
                    'user_id' => $order->buyer_id,
                    'project_name' => $projectName,
                ],
                [
                    'budget' => $order->amount,
                    'status' => 'open',
                    'archived' => 0,
                    'ai_enabled' => false,
                ]
            );

            // Seed initial project kickoff tasks
            $initialTasks = [
                ['task_name' => 'Kickoff & Requirement Alignment', 'priority' => 'high'],
                ['task_name' => 'Design & UI/UX Review', 'priority' => 'medium'],
                ['task_name' => 'Development & Staging Setup', 'priority' => 'high'],
                ['task_name' => 'Final QA & Delivery Verification', 'priority' => 'high'],
            ];

            foreach ($initialTasks as $taskData) {
                Task::firstOrCreate([
                    'project_id' => $project->id,
                    'task_name' => $taskData['task_name'],
                ], [
                    'user_id' => $order->buyer_id,
                    'priority' => $taskData['priority'],
                    'status' => 'pending',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to auto-kickoff project for Marketplace Order #{$order->id}: " . $e->getMessage());
        }
    }
}
