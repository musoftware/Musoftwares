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
        $resolvedCategory = null;

        if (!empty($categoryParam)) {
            $paramStr = trim((string) $categoryParam);

            if (is_numeric($paramStr)) {
                $resolvedCategory = ServiceCategory::find((int) $paramStr);
            }

            if (!$resolvedCategory) {
                $resolvedCategory = ServiceCategory::where('slug', $paramStr)->first();
            }

            if (!$resolvedCategory) {
                $aliases = [
                    'web' => 'web-development',
                    'dev' => 'web-development',
                    'development' => 'web-development',
                    'design' => 'graphic-design',
                    'graphics' => 'graphic-design',
                    'marketing' => 'digital-marketing',
                    'writing' => 'writing-translation',
                    'translation' => 'writing-translation',
                    'video' => 'video-animation',
                    'animation' => 'video-animation',
                    'music' => 'music-audio',
                    'audio' => 'music-audio',
                    'tech' => 'programming-tech',
                    'programming' => 'programming-tech',
                    'software' => 'programming-tech',
                    'biz' => 'business',
                ];

                $aliasSlug = $aliases[strtolower($paramStr)] ?? null;
                if ($aliasSlug) {
                    $resolvedCategory = ServiceCategory::where('slug', $aliasSlug)->first();
                }
            }

            if (!$resolvedCategory) {
                $resolvedCategory = ServiceCategory::where('slug', 'like', "%{$paramStr}%")
                    ->orWhere('name', 'like', "%{$paramStr}%")
                    ->first();
            }

            if ($resolvedCategory) {
                $query->where('category_id', $resolvedCategory->id);
            } else {
                $query->whereHas('category', function ($q) use ($paramStr) {
                    $q->where('slug', 'like', "%{$paramStr}%")
                      ->orWhere('name', 'like', "%{$paramStr}%");
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
                'category' => $resolvedCategory ? $resolvedCategory->slug : ($categoryParam ?? ''),
                'category_id' => $resolvedCategory ? $resolvedCategory->id : ($categoryParam ?? ''),
                'category_name' => $resolvedCategory ? $resolvedCategory->name : '',
            ],
        ])->withViewData([
            'meta' => [
                'title'       => 'Software Development & IT Services Marketplace | MuSoftwares',
                'description' => 'Browse top software development, IT services, custom scripts, and digital solutions on MuSoftwares Marketplace.',
                'image'       => url('/images/og-default.jpg'),
                'url'         => route('marketplace.services.index'),
                'type'        => 'website',
            ]
        ]);
    }

    public function show($id, Request $request, $slug = null)
    {
        $targetId = $id;
        $extractedSlug = $slug;
        
        // Handle URLs formatted as /services/98-some-title-slug
        if (!is_numeric($id)) {
            if (preg_match('/^(\d+)-(.*)$/', (string)$id, $matches)) {
                $targetId = $matches[1];
                $extractedSlug = $matches[2];
            } elseif (preg_match('/^(\d+)$/', (string)$id, $matches)) {
                $targetId = $matches[1];
            }
        }

        $query = Service::with(['seller', 'category', 'packages.currency', 'reviews.reviewer', 'extras']);

        $service = null;
        if (is_numeric($targetId)) {
            $service = (clone $query)->find($targetId);
        }

        if (!$service) {
            $searchSlug = $extractedSlug ?: $id;
            $service = (clone $query)->where('slug', $searchSlug)->first();
        }

        if (!$service) {
            // Search by title slug fallback
            $service = $query->get()->first(function ($s) use ($id, $slug, $extractedSlug) {
                $sSlug = \Illuminate\Support\Str::slug($s->title);
                return $sSlug === $id || $sSlug === $slug || $sSlug === $extractedSlug;
            });
        }

        if (!$service) {
            return Inertia::render('Marketplace/Services/ExclusiveService', [
                'serviceSlug' => str_replace('-', ' ', $id),
            ]);
        }

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if ($service->status !== 'active') {
            if ($isAdmin) {
                $service->status = 'active';
                $service->approved_at = $service->approved_at ?: now();
                $service->approved_by = $service->approved_by ?: $user->id;
                $service->save();
            } elseif (!$user || $user->id !== $service->seller_id) {
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
        $shareUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);

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

            $thumbnailPath = null;
            if (!empty($galleryPaths[0])) {
                $firstImagePath = $galleryPaths[0];
                $fullPath = public_path('uploads/' . ltrim($firstImagePath, '/'));
                $thumbRelative = 'services/' . auth()->id() . '/thumb_' . basename($firstImagePath);
                $thumbFullPath = public_path('uploads/' . $thumbRelative);

                if (\App\Helpers\ImageHelper::createThumbnail($fullPath, $thumbFullPath, 600, 400, 80)) {
                    $thumbnailPath = $thumbRelative;
                } else {
                    $thumbnailPath = $firstImagePath;
                }
            }

            $user = auth()->user();
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin));

            $service = Service::create([
                'seller_id'    => auth()->id(),
                'title'        => $validated['title'] ?? null,
                'slug'         => !empty($validated['title']) ? Str::slug($validated['title']) : null,
                'thumbnail'    => $thumbnailPath,
                'title_translations' => $validated['title_translations'] ?? null,
                'tagline'      => $validated['tagline'] ?? null,
                'tagline_translations' => $validated['tagline_translations'] ?? null,
                'description'  => $validated['description'] ?? null,
                'description_translations' => $validated['description_translations'] ?? null,
                'auto_reply'   => $validated['auto_reply'] ?? null,
                'auto_reply_translations' => $validated['auto_reply_translations'] ?? null,
                'category_id'  => $validated['category_id'],
                'status'       => $isAdmin ? 'active' : 'draft',
                'approved_at'  => $isAdmin ? now() : null,
                'approved_by'  => $isAdmin ? $user->id : null,
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

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin));
        $msg = $isAdmin
            ? __('general.service_created')
            : __('general.service_submitted_for_review_it_will_be_visible_once_approved');

        return redirect()->route('marketplace.services.show', $service->id)
            ->with('success', $msg);
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

            $thumbnailPath = $service->thumbnail;
            if (!empty($galleryPaths[0])) {
                $firstImagePath = $galleryPaths[0];
                $fullPath = public_path('uploads/' . ltrim($firstImagePath, '/'));
                $thumbRelative = 'services/' . auth()->id() . '/thumb_' . basename($firstImagePath);
                $thumbFullPath = public_path('uploads/' . $thumbRelative);

                if (\App\Helpers\ImageHelper::createThumbnail($fullPath, $thumbFullPath, 600, 400, 80)) {
                    $thumbnailPath = $thumbRelative;
                } else {
                    $thumbnailPath = $firstImagePath;
                }
            }

            $user = auth()->user();
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin));

            $service->update([
                'title'        => $validated['title'] ?? null,
                'slug'         => !empty($validated['title']) ? Str::slug($validated['title']) : $service->slug,
                'thumbnail'    => $thumbnailPath,
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
                'status'       => $isAdmin ? 'active' : 'draft',
                'approved_at'  => $isAdmin ? ($service->approved_at ?? now()) : $service->approved_at,
                'approved_by'  => $isAdmin ? ($service->approved_by ?? $user->id) : $service->approved_by,
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

    public function createAi()
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (!$isAdmin) {
            abort(403, 'Unauthorized access.');
        }

        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Marketplace/Services/CreateAi', [
            'categories' => $categories,
        ]);
    }

    public function storeAi(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (!$isAdmin) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'provider' => 'required|string|in:chatgpt,gemini',
        ]);

        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug'])->toArray();

        $aiService = app(\App\Services\AI\MarketplaceAiService::class);
        $aiData = $aiService->generateServiceData($validated['title'], $validated['provider'], $categories, $user->id);

        $service = DB::transaction(function () use ($aiData, $user) {
            $service = Service::create([
                'seller_id'    => $user->id,
                'title'        => $aiData['title'],
                'slug'         => Str::slug($aiData['title']),
                'thumbnail'    => $aiData['thumbnail'] ?? null,
                'tagline'      => $aiData['tagline'] ?? null,
                'description'  => $aiData['description'] ?? null,
                'category_id'  => $aiData['category_id'],
                'status'       => 'active',
                'approved_at'  => now(),
                'approved_by'  => $user->id,
                'tags'         => $aiData['tags'] ?? [],
                'faq'          => $aiData['faq'] ?? [],
                'requirements' => $aiData['requirements'] ?? [],
                'gallery'      => $aiData['gallery'] ?? [],
                'is_free'      => false,
            ]);

            if (!empty($aiData['packages']) && is_array($aiData['packages'])) {
                foreach ($aiData['packages'] as $pkg) {
                    ServicePackage::create([
                        'service_id'    => $service->id,
                        'name'          => $pkg['name'] ?? 'Standard',
                        'description'   => $pkg['description'] ?? '',
                        'price'         => $pkg['price'] ?? 25,
                        'currency_id'   => $pkg['currency_id'] ?? 1,
                        'delivery_days' => $pkg['delivery_days'] ?? 3,
                        'revisions'     => $pkg['revisions'] ?? 2,
                        'features'      => $pkg['features'] ?? [],
                    ]);
                }
            } else {
                ServicePackage::create([
                    'service_id'    => $service->id,
                    'name'          => 'Standard',
                    'description'   => $service->description ?: 'Standard package deliverable.',
                    'price'         => 25,
                    'currency_id'   => 1,
                    'delivery_days' => 3,
                    'revisions'     => 2,
                    'features'      => [],
                ]);
            }

            return $service;
        });

        return redirect()->route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug])
            ->with('success', __('general.service_created_successfully_by_ai') ?? 'Service generated successfully by AI!');
    }
}

