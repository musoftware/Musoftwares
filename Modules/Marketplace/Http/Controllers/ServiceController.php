<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Http\Requests\StoreServiceRequest;
use Modules\Marketplace\Http\Requests\UpdateServiceRequest;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages.currency'])->where('status', 'active');

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

        $viewerCurrency = \App\Helpers\FinanceHelper::instance()->getViewerCurrency($request);

        $services->getCollection()->transform(function ($service) use ($viewerCurrency) {
            $service->packages->transform(function ($package) use ($viewerCurrency) {
                if ($package->currency_id && $package->currency_id != $viewerCurrency->id) {
                    $package->price = \App\Models\CurrenciesExchange::RateToday(
                        $package->price,
                        $package->currency_id,
                        $viewerCurrency->id
                    );
                    $package->currency_id = $viewerCurrency->id;
                    $package->setRelation('currency', $viewerCurrency);
                }
                return $package;
            });
            return $service;
        });

        return Inertia::render('Marketplace/Browse', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function show($id, Request $request)
    {
        $service = Service::with(['seller', 'category', 'packages.currency'])->find($id);

        if (!$service) {
            return Inertia::render('Marketplace/Services/ExclusiveService', [
                'serviceSlug' => str_replace('-', ' ', $id),
            ]);
        }

        if ($service->status !== 'active') {
            $user = auth()->user();
            if (!$user || ($user->id !== $service->seller_id && !$user->hasRole('admin'))) {
                return Inertia::render('Marketplace/Services/ExclusiveService', [
                    'serviceSlug' => $service->title ?? str_replace('-', ' ', $id),
                ]);
            }
        }

        $viewerCurrency = \App\Helpers\FinanceHelper::instance()->getViewerCurrency($request);

        $service->packages->transform(function ($package) use ($viewerCurrency) {
            if ($package->currency_id && $package->currency_id != $viewerCurrency->id) {
                $package->price = \App\Models\CurrenciesExchange::RateToday(
                    $package->price,
                    $package->currency_id,
                    $viewerCurrency->id
                );
                $package->currency_id = $viewerCurrency->id;
                $package->setRelation('currency', $viewerCurrency);
            }
            return $package;
        });

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

    public function store(StoreServiceRequest $request)
    {
        $validated = $request->validated();

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
                'title'        => $validated['title'] ?? null,
                'title_translations' => $validated['title_translations'] ?? null,
                'tagline'      => $validated['tagline'] ?? null,
                'tagline_translations' => $validated['tagline_translations'] ?? null,
                'description'  => $validated['description'] ?? null,
                'description_translations' => $validated['description_translations'] ?? null,
                'auto_reply'   => $validated['auto_reply'] ?? null,
                'auto_reply_translations' => $validated['auto_reply_translations'] ?? null,
                'category_id'  => $validated['category_id'],
                'status'       => 'draft',
                'tags'         => $validated['tags'] ?? [],
                'faq'          => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'gallery'      => $galleryPaths,
                'video_url'    => $validated['video_url'] ?? null,
                'service_link' => $validated['service_link'] ?? null,
                'is_free'      => $validated['is_free'] ?? false,
                'generate_serials' => $validated['generate_serials'] ?? false,
                'allow_random_serial' => $validated['allow_random_serial'] ?? false,
                'validity_days' => $validated['validity_days'] ?? null,
                'referral_commission_from' => $validated['referral_commission_from'] ?? 'fee',
                'referral_commission_percentage' => $validated['referral_commission_percentage'] ?? null,
            ]);

            if (isset($validated['extras'])) {
                foreach ($validated['extras'] as $extra) {
                    \Modules\Marketplace\Models\ServiceExtra::create([
                        'service_id'    => $service->id,
                        'title'         => $extra['title'],
                        'price'         => $extra['price'],
                        'duration_days' => $extra['duration_days'] ?? 0,
                    ]);
                }
            }

            foreach ($validated['packages'] as $pkg) {
                ServicePackage::create([
                    'service_id'    => $service->id,
                    'name'          => $pkg['name'],
                    'description'   => $pkg['description'],
                    'price'         => $pkg['price'],
                    'currency_id'   => $pkg['currency_id'],
                    'delivery_days' => $pkg['delivery_days'],
                    'revisions'     => $pkg['revisions'] ?? 2,
                    'features'      => $pkg['features'] ?? [],
                ]);
            }

            return $service;
        });

        return redirect()->route('marketplace.services.show', $service->id)
            ->with('success', __('general.service_submitted_for_review_it_will_be_visible_once_approved'));
    }

    public function edit(Service $service)
    {
        if (auth()->id() !== $service->seller_id) {
            abort(403);
        }

        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']);
        $service->load(['packages', 'extras']);

        return Inertia::render('Marketplace/Services/Edit', [
            'categories' => $categories,
            'service' => $service,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $this->authorize('update', $service);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $service) {
            $galleryPaths = $validated['kept_gallery'] ?? [];

            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $path = $image->store('services/' . auth()->id(), 'public');
                    $galleryPaths[] = $path;
                }
            }

            $service->update([
                'title'        => $validated['title'] ?? null,
                'title_translations' => $validated['title_translations'] ?? null,
                'tagline'      => $validated['tagline'] ?? null,
                'tagline_translations' => $validated['tagline_translations'] ?? null,
                'description'  => $validated['description'] ?? null,
                'description_translations' => $validated['description_translations'] ?? null,
                'auto_reply'   => $validated['auto_reply'] ?? null,
                'auto_reply_translations' => $validated['auto_reply_translations'] ?? null,
                'category_id'  => $validated['category_id'],
                'tags'         => $validated['tags'] ?? [],
                'faq'          => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'gallery'      => $galleryPaths,
                'video_url'    => $validated['video_url'] ?? null,
                'service_link' => $validated['service_link'] ?? null,
                'is_free'      => $validated['is_free'] ?? false,
                'generate_serials' => $validated['generate_serials'] ?? false,
                'allow_random_serial' => $validated['allow_random_serial'] ?? false,
                'validity_days' => $validated['validity_days'] ?? null,
                'referral_commission_from' => $validated['referral_commission_from'] ?? 'fee',
                'referral_commission_percentage' => $validated['referral_commission_percentage'] ?? null,
                'status'       => 'draft', // Requires re-approval
            ]);

            $submittedExtraIds = collect($validated['extras'] ?? [])->pluck('id')->filter()->toArray();
            $service->extras()->whereNotIn('id', $submittedExtraIds)->delete();

            if (isset($validated['extras'])) {
                foreach ($validated['extras'] as $extra) {
                    if (!empty($extra['id'])) {
                        $service->extras()->where('id', $extra['id'])->update([
                            'title'         => $extra['title'],
                            'price'         => $extra['price'],
                            'duration_days' => $extra['duration_days'] ?? 0,
                        ]);
                    } else {
                        \Modules\Marketplace\Models\ServiceExtra::create([
                            'service_id'    => $service->id,
                            'title'         => $extra['title'],
                            'price'         => $extra['price'],
                            'duration_days' => $extra['duration_days'] ?? 0,
                        ]);
                    }
                }
            }

            $submittedPackageIds = collect($validated['packages'])->pluck('id')->filter()->toArray();

            // Soft-delete removed packages
            $service->packages()->whereNotIn('id', $submittedPackageIds)->delete();

            foreach ($validated['packages'] as $pkg) {
                if (!empty($pkg['id'])) {
                    // Update existing
                    $service->packages()->where('id', $pkg['id'])->update([
                        'name'          => $pkg['name'],
                        'description'   => $pkg['description'],
                        'price'         => $pkg['price'],
                        'currency_id'   => $pkg['currency_id'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions'     => $pkg['revisions'] ?? 2,
                        'features'      => $pkg['features'] ?? [],
                    ]);
                } else {
                    // Create new
                    ServicePackage::create([
                        'service_id'    => $service->id,
                        'name'          => $pkg['name'],
                        'description'   => $pkg['description'],
                        'price'         => $pkg['price'],
                        'currency_id'   => $pkg['currency_id'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions'     => $pkg['revisions'] ?? 2,
                        'features'      => $pkg['features'] ?? [],
                    ]);
                }
            }
        });

        return redirect()->route('marketplace.services.show', $service->id)
            ->with('success', __('general.service_updated_successfully_and_submitted_for_re_approval'));
    }

}
