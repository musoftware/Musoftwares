<?php

namespace Modules\Marketplace\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Marketplace\Models\ServicePlaybook;
use Modules\Marketplace\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ServicePlaybookController extends Controller
{
    public function index(Request $request)
    {
        $query = ServicePlaybook::with(['service:id,title,slug,thumbnail', 'creator:id,name,email'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('service', function ($sq) use ($search) {
                      $sq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        $playbooks = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/ServicePlaybooks/Index', [
            'playbooks' => $playbooks,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $services = Service::with(['packages.currency', 'extras'])
            ->select('id', 'title', 'tagline', 'thumbnail')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/ServicePlaybooks/Create', [
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'service_id' => 'nullable|exists:marketplace_services,id',
            'marketing_message' => 'nullable|string',
            'pricing_info' => 'nullable|string',
            'client_requirements' => 'nullable|string',
            'execution_workflow' => 'nullable|string',
            'thank_you_message' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        ServicePlaybook::create($validated);

        return redirect()->route('admin.marketplace.service-playbooks.index')
            ->with('success', __('admin.service_playbook_created'));
    }

    public function show(ServicePlaybook $service_playbook)
    {
        $service_playbook->load(['service.packages.currency', 'service.extras', 'creator:id,name,email']);

        return Inertia::render('Admin/ServicePlaybooks/Show', [
            'playbook' => $service_playbook,
        ]);
    }

    public function edit(ServicePlaybook $service_playbook)
    {
        $service_playbook->load(['service.packages.currency', 'service.extras']);

        $services = Service::with(['packages.currency', 'extras'])
            ->select('id', 'title', 'tagline', 'thumbnail')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/ServicePlaybooks/Edit', [
            'playbook' => $service_playbook,
            'services' => $services,
        ]);
    }

    public function update(Request $request, ServicePlaybook $service_playbook)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'service_id' => 'nullable|exists:marketplace_services,id',
            'marketing_message' => 'nullable|string',
            'pricing_info' => 'nullable|string',
            'client_requirements' => 'nullable|string',
            'execution_workflow' => 'nullable|string',
            'thank_you_message' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $service_playbook->update($validated);

        return redirect()->route('admin.marketplace.service-playbooks.index')
            ->with('success', __('admin.service_playbook_updated'));
    }

    public function destroy(ServicePlaybook $service_playbook)
    {
        $service_playbook->delete();

        return redirect()->route('admin.marketplace.service-playbooks.index')
            ->with('success', __('admin.service_playbook_deleted'));
    }

    public function getServiceDetails(Service $service)
    {
        $service->load(['packages.currency', 'extras']);

        // Build automatic pricing Markdown template
        $markdown = __('marketplace.playbook_package_details_title', ['title' => $service->title]) . "\n\n";
        
        if ($service->packages && $service->packages->count() > 0) {
            foreach ($service->packages as $pkg) {
                $curr = $pkg->currency->symbol ?? $pkg->currency->code ?? '$';
                $markdown .= __('marketplace.playbook_package_heading', ['name' => $pkg->name]) . "\n";
                $markdown .= __('marketplace.playbook_price_label', ['price' => $pkg->price, 'currency' => $curr]) . "\n";
                if ($pkg->delivery_days) {
                    $markdown .= __('marketplace.playbook_delivery_label', ['days' => $pkg->delivery_days]) . "\n";
                }
                if ($pkg->description) {
                    $markdown .= __('marketplace.playbook_description_label', ['description' => $pkg->description]) . "\n";
                }
                if (!empty($pkg->features) && is_array($pkg->features)) {
                    $markdown .= __('marketplace.playbook_features_label') . "\n";
                    foreach ($pkg->features as $feat) {
                        $markdown .= "  - {$feat}\n";
                    }
                }
                $markdown .= "\n---\n\n";
            }
        } else {
            $markdown .= __('marketplace.playbook_no_packages') . "\n";
        }

        if ($service->extras && $service->extras->count() > 0) {
            $markdown .= __('marketplace.playbook_extras_title') . "\n\n";
            foreach ($service->extras as $extra) {
                $markdown .= __('marketplace.playbook_extra_item', [
                    'title' => $extra->title,
                    'price' => $extra->price,
                    'days' => $extra->delivery_days,
                ]) . "\n";
            }
        }

        return response()->json([
            'service' => $service,
            'pricing_template' => $markdown,
        ]);
    }
}
