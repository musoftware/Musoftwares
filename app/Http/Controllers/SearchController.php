<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\Invoice;
use Modules\Marketplace\Models\Service;

class SearchController extends Controller
{
    public function index(Request $request)
    {

        $query = $request->input('q');

        if (! $query) {
            return response()->json([]);
        }

        $results = [];

        // Search Clients
        if (class_exists(Client::class)) {
            $clients = Client::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('company', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($clients as $client) {
                $results[] = [
                    'id' => $client->id,
                    'type' => 'Client',
                    'title' => $client->name.($client->company ? " ({$client->company})" : ''),
                    'url' => route('erp.invoices.index'), // Placeholder route
                ];
            }
        }

        // Search Invoices
        if (class_exists(Invoice::class)) {
            $invoices = Invoice::where('invoice_number', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($invoices as $invoice) {
                $results[] = [
                    'id' => $invoice->id,
                    'type' => 'Invoice',
                    'title' => "Invoice #{$invoice->invoice_number}",
                    'url' => route('erp.invoices.index'),
                ];
            }
        }

        // Search Services
        if (class_exists(Service::class)) {
            $services = Service::where('title', 'like', "%{$query}%")
                ->take(5)
                ->get();

            foreach ($services as $service) {
                $results[] = [
                    'id' => $service->id,
                    'type' => 'Service',
                    'title' => $service->title,
                    'url' => route('marketplace.services.index'),
                ];
            }
        }

        return response()->json($results);
    }
}
