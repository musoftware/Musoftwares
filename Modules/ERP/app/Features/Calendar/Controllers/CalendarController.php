<?php

namespace Modules\ERP\app\Features\Calendar\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Closure;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\Invoice;
use Inertia\Inertia;

class CalendarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, Closure $next) {
                $user = auth('erp_team')->user();
                if (Auth::guard('erp_team')->check()) {
                    $user = Auth::guard('erp_team')->user()->tenant->user;
                }
                if (!$user || !$user->hasModuleSubscription('erp-calendar')) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json(['message' => 'Calendar addon required.'], 403);
                    }
                    return Inertia::render('ERP/UpgradePreview', ['module' => 'erp-calendar']);
                }
                return $next($request);
            }),
        ];
    }

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        // Let's gather events from Tasks and Invoices
        $events = collect();
        $user = $request->user();

        if ($user->hasModuleSubscription('erp-tasks')) {
            $tasks = ERPTask::where('tenant_id', $tenant->id)
                ->where('archived', false)
                ->whereNotNull('due_date')
                ->get();
            
            foreach ($tasks as $task) {
                $events->push([
                    'id' => 'task_' . $task->id,
                    'title' => 'Task: ' . $task->task_name,
                    'start' => $task->due_date->format('Y-m-d'),
                    'end' => $task->due_date->format('Y-m-d'),
                    'url' => route('erp.tasks.show', $task->id),
                    'backgroundColor' => '#3b82f6', // blue-500
                    'borderColor' => '#3b82f6',
                ]);
            }
        }

        // Fetch invoice due dates
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent', 'partial'])
            ->whereNotNull('due_date')
            ->get();
        
        foreach ($invoices as $invoice) {
            $events->push([
                'id' => 'invoice_' . $invoice->id,
                'title' => 'Invoice Due: ' . $invoice->invoice_number,
                'start' => $invoice->due_date->format('Y-m-d'),
                'end' => $invoice->due_date->format('Y-m-d'),
                'url' => route('erp.invoices.show', $invoice->id),
                'backgroundColor' => '#f59e0b', // amber-500
                'borderColor' => '#f59e0b',
            ]);
        }

        // Fetch project deadlines if they have projects addon
        if ($user->hasModuleSubscription('erp-projects')) {
            $projects = Project::where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['Completed', 'Cancelled'])
                ->whereNotNull('due_date')
                ->get();
            
            foreach ($projects as $project) {
                $events->push([
                    'id' => 'project_' . $project->id,
                    'title' => 'Project Due: ' . $project->name,
                    'start' => $project->due_date->format('Y-m-d'),
                    'end' => $project->due_date->format('Y-m-d'),
                    'url' => route('erp.projects.show', $project->id),
                    'backgroundColor' => '#10b981', // emerald-500
                    'borderColor' => '#10b981',
                ]);
            }
        }

        return Inertia::render('ERP/Calendar/Index', [
            'events' => $events->values()->toArray(),
        ]);
    }
}
