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
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages.currency'])->where('status', 'active');

        $categoryParam = $request->input('category') ?? $request->input('category_id') ?? $request->input('category_slug');

        if (!empty($categoryParam)) {
            if (is_numeric($categoryParam)) {
                $query->where('category_id', $categoryParam);
            } else {
                $query->whereHas('category', function ($q) use ($categoryParam) {
                    $q->where('slug', $categoryParam)
                      ->orWhere('name', 'like', "%{$categoryParam}%");
                });
            }
        }

        $search = $request->input('search') ?? $request->input('q');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->latest()->paginate(15);
        $categories = ServiceCategory::orderBy('name')->get();

        $viewerCurrency = \App\Helpers\FinanceHelper::instance()->getViewerCurrency($request);
        $userFavoriteIds = auth()->check()
            ? \App\Models\Favorite::where('user_id', auth()->id())->where('favoritable_type', Service::class)->pluck('favoritable_id')->toArray()
            : [];

        $services->getCollection()->transform(function ($service) use ($viewerCurrency, $userFavoriteIds) {
            $service->is_favorited = in_array($service->id, $userFavoriteIds);
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
            'filters' => [
                'search' => $search ?? '',
                'category' => $categoryParam ?? '',
                'category_id' => $categoryParam ?? '',
            ],
        ]);
    }

    public function show($id, Request $request)
    {
        $service = Service::with(['seller', 'category', 'packages.currency', 'reviews.reviewer', 'extras'])->find($id);

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

        $service->is_favorited = auth()->check()
            ? \App\Models\Favorite::where('user_id', auth()->id())->where('favoritable_type', Service::class)->where('favoritable_id', $service->id)->exists()
            : false;

        if ($service->packages->isEmpty()) {
            ServicePackage::create([
                'service_id' => $service->id,
                'name' => 'Standard',
                'description' => $service->description ?: 'Standard package deliverable.',
                'price' => $service->is_free ? 0 : 5,
                'currency_id' => 1,
                'delivery_days' => 3,
                'revisions' => 2,
            ]);
            $service->load('packages.currency');
        }

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

        $shareTitle = $service->title . ' | Musoftware Marketplace';
        $shareDesc = \Illuminate\Support\Str::limit(strip_tags($service->tagline ?: $service->description ?: $service->title), 160);
        $shareImage = $service->cover_image;
        $shareUrl = route('marketplace.services.show', $service->id);

        return Inertia::render('Marketplace/Services/Show', [
            'service' => $service,
        ])->withViewData([
            'meta' => [
                'title'       => $shareTitle,
                'description' => $shareDesc,
                'image'       => $shareImage,
                'url'         => $shareUrl,
                'type'        => 'product',
            ]
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
            // Handle gallery uploads using standard Laravel Storage disk (public_uploads disk points directly to public/uploads)
            $galleryPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $galleryPaths[] = $image->store('services/' . auth()->id(), 'public_uploads');
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
                    $galleryPaths[] = $image->store('services/' . auth()->id(), 'public_uploads');
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

    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()->back()
            ->with('success', __('general.service_deleted_successfully'));
    }

}

