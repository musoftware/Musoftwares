<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {

        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $results = [];

        // Search Clients
        if (class_exists(\Modules\ERP\Models\Client::class)) {
            $clients = \Modules\ERP\Models\Client::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('company', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($clients as $client) {
                $results[] = [
                    'id' => $client->id,
                    'type' => 'Client',
                    'title' => $client->name . ($client->company ? " ({$client->company})" : ''),
                    'url' => route('erp.invoices.index') // Placeholder route
                ];
            }
        }

        // Search Invoices
        if (class_exists(\Modules\Core\Models\Invoice::class)) {
            $invoices = \Modules\Core\Models\Invoice::where('invoice_number', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($invoices as $invoice) {
                $results[] = [
                    'id' => $invoice->id,
                    'type' => 'Invoice',
                    'title' => "Invoice #{$invoice->invoice_number}",
                    'url' => route('erp.invoices.index')
                ];
            }
        }

        // Search Services
        if (class_exists(\Modules\Core\Models\Service::class)) {
            $services = \Modules\Core\Models\Service::where('title', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($services as $service) {
                $results[] = [
                    'id' => $service->id,
                    'type' => 'Service',
                    'title' => $service->title,
                    'url' => route('marketplace.services.index')
                ];
            }
        }

        // Search Jobs
        if (class_exists(\Modules\Core\Models\Job::class)) {
            $jobs = \Modules\Core\Models\Job::where('title', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($jobs as $job) {
                $results[] = [
                    'id' => $job->id,
                    'type' => 'Job',
                    'title' => $job->title,
                    'url' => route('freelance.jobs.index')
                ];
            }
        }

        return response()->json($results);
    }
}
