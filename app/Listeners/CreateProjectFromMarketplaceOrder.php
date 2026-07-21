<?php

namespace App\Listeners;

use App\Events\MarketplaceOrderPlaced;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class CreateProjectFromMarketplaceOrder
{
    /**
     * Handle the event.
     *
     * @param  MarketplaceOrderPlaced  $event
     * @return void
     */
    public function handle(MarketplaceOrderPlaced $event): void
    {
        $order = $event->order;

        if (!$order) {
            return;
        }

        try {
            // Load relations if not already loaded
            $order->loadMissing(['buyer', 'package.service']);

            $package = $order->package;
            $service = $package ? $package->service : null;
            
            $serviceTitle = $service ? $service->title : 'Custom Development';
            $packageName = $package ? $package->name : 'Standard';
            $deliveryDays = $package ? ($package->delivery_days ?? 7) : 7;

            // Create client project
            $project = Project::create([
                'user_id' => $order->buyer_id,
                'project_name' => "Order #{$order->id}: {$serviceTitle} ({$packageName})",
                'budget' => $order->amount,
                'status' => 'open',
                'date_start' => now(),
                'date_end' => now()->addDays($deliveryDays),
                'archived' => 0,
                'percentage' => 0,
            ]);

            // Add standard tasks
            $tasks = [
                [
                    'task_name' => 'Kickoff & Requirement Alignment',
                    'task_description' => 'Review the order details, assets, and initial requirements provided by the client.',
                    'priority' => 'high',
                    'due_date' => now()->addDays(1),
                ],
                [
                    'task_name' => 'Design & UI/UX Review',
                    'task_description' => 'Prepare and review user interface mockups or system workflows.',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(max(2, $deliveryDays - 4)),
                ],
                [
                    'task_name' => 'Development & Staging Setup',
                    'task_description' => 'Implementation of custom features and deployment to staging server.',
                    'priority' => 'high',
                    'due_date' => now()->addDays(max(3, $deliveryDays - 1)),
                ],
                [
                    'task_name' => 'Final QA & Delivery Verification',
                    'task_description' => 'Perform final testing and deliver completed work for client review.',
                    'priority' => 'high',
                    'due_date' => now()->addDays($deliveryDays),
                ],
            ];

            foreach ($tasks as $taskData) {
                $project->tasks()->create([
                    'user_id' => $order->buyer_id,
                    'task_name' => $taskData['task_name'],
                    'task_description' => $taskData['task_description'],
                    'priority' => $taskData['priority'],
                    'due_date' => $taskData['due_date'],
                    'archived' => 0,
                ]);
            }

            Log::info("Created Project ID {$project->id} and default tasks from Marketplace Order ID {$order->id}");

        } catch (\Throwable $e) {
            Log::error("Failed to create project from Marketplace Order ID {$order->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            if (app()->environment('testing')) {
                throw $e;
            }
        }
    }
}
