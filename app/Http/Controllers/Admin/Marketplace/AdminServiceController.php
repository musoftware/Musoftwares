<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Service;
use App\Services\MarketplaceService;
use App\Http\Resources\Marketplace\ServiceResource;
use App\Http\Requests\Admin\Marketplace\UpdateServiceStatusRequest;
use App\Http\Requests\Admin\Marketplace\BulkUpdateServiceStatusRequest;
use Inertia\Inertia;

class AdminServiceController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {}

    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('seller', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->latest()
                          ->paginate(15)
                          ->withQueryString()
                          ->through(fn($s) => clone (new ServiceResource($s))->resolve());

        return Inertia::render('Admin/Marketplace/Services/Index', [
            'services' => $services,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function updateStatus(UpdateServiceStatusRequest $request, Service $service)
    {
        $this->marketplaceService->updateServiceStatus($service, $request->validated());

        return redirect()->back()->with('success', 'Service status updated successfully.');
    }

    public function bulkAction(BulkUpdateServiceStatusRequest $request)
    {
        $validated = $request->validated();
        
        $count = $this->marketplaceService->bulkUpdateStatus($validated['ids'], $validated['action']);

        return redirect()->back()->with('success', "{$count} services updated successfully.");
    }
}
