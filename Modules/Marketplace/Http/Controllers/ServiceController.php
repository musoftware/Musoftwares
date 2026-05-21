<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category'])->where('status', 'active');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->latest()->paginate(15);
        $categories = ServiceCategory::all();

        return Inertia::render('Marketplace/Browse', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function show($id)
    {
        $service = Service::with(['seller', 'category', 'packages'])->findOrFail($id);

        if ($service->status !== 'active') {
            $user = auth()->user();
            if (!$user || ($user->id !== $service->seller_id && !$user->hasRole('admin'))) {
                abort(404, 'Service not found or not active.');
            }
        }

        return Inertia::render('Marketplace/Services/Show', [
            'service' => $service,
        ]);
    }

    public function create()
    {
        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']);
        $user = auth()->user();

        return Inertia::render('Marketplace/Services/Create', [
            'categories' => $categories,
            'seller' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar ?? null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                    => 'required|string|max:255',
            'description'              => 'required|string|min:100',
            'category_id'              => 'required|exists:marketplace_service_categories,id',
            'tags'                     => 'nullable|array|max:5',
            'tags.*'                   => 'string|max:40',
            'video_url'                => 'nullable|url|max:255',
            'packages'                 => 'required|array|min:1|max:3',
            'packages.*.name'          => 'required|string|max:80',
            'packages.*.description'   => 'required|string|max:500',
            'packages.*.price'         => 'required|numeric|min:1',
            'packages.*.currency_code' => 'required|string|size:3',
            'packages.*.delivery_days' => 'required|integer|min:1|max:365',
            'packages.*.revisions'     => 'nullable|integer|min:-1',
            'packages.*.features'      => 'nullable|array',
            'packages.*.features.*'    => 'string|max:60',
            'faq'                      => 'nullable|array|max:10',
            'faq.*.question'           => 'required|string|max:200',
            'faq.*.answer'             => 'required|string|max:1000',
            'requirements'             => 'nullable|array|max:10',
            'requirements.*'           => 'string|max:300',
            'gallery'                  => 'nullable|array|max:5',
            'gallery.*'                => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $service = DB::transaction(function () use ($validated, $request) {
            // Handle gallery uploads
            $galleryPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $path = $image->store('services/' . auth()->id(), 'public');
                    $galleryPaths[] = $path;
                }
            }

            $service = Service::create([
                'seller_id'    => auth()->id(),
                'title'        => $validated['title'],
                'description'  => $validated['description'],
                'category_id'  => $validated['category_id'],
                'status'       => 'draft',
                'tags'         => $validated['tags'] ?? [],
                'faq'          => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'gallery'      => $galleryPaths,
                'video_url'    => $validated['video_url'] ?? null,
            ]);

            foreach ($validated['packages'] as $pkg) {
                ServicePackage::create([
                    'service_id'    => $service->id,
                    'name'          => $pkg['name'],
                    'description'   => $pkg['description'],
                    'price'         => $pkg['price'],
                    'currency_code' => $pkg['currency_code'],
                    'delivery_days' => $pkg['delivery_days'],
                    'revisions'     => $pkg['revisions'] ?? 2,
                    'features'      => $pkg['features'] ?? [],
                ]);
            }

            return $service;
        });

        return redirect()->route('marketplace.services.show', $service->id)
            ->with('success', 'Service submitted for review. It will be visible once approved.');
    }

    // Admin Actions
    public function approve($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Service approved.');
    }

    public function reject($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Service rejected.');
    }

    public function feature($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['is_featured' => !$service->is_featured]);

        return redirect()->back()->with('success', 'Service feature status toggled.');
    }
}
