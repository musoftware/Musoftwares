<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Helpers\FinanceHelper;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\CurrenciesExchange;
use App\Models\Favorite;
use App\Services\AI\MarketplaceAiService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Marketplace\Http\Requests\StoreServiceRequest;
use Modules\Marketplace\Http\Requests\UpdateServiceRequest;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceExtra;
use Modules\Marketplace\Models\ServicePackage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages.currency'])->where('status', 'active');

        $categoryParam = $request->input('category') ?? $request->input('category_id') ?? $request->input('category_slug');
        $resolvedCategory = null;

        if (! empty($categoryParam)) {
            $paramStr = trim((string) $categoryParam);

            if (is_numeric($paramStr)) {
                $resolvedCategory = ServiceCategory::find((int) $paramStr);
            }

            if (! $resolvedCategory) {
                $resolvedCategory = ServiceCategory::where('slug', $paramStr)->first();
            }

            if (! $resolvedCategory) {
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

            if (! $resolvedCategory) {
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
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->latest()->paginate(15);
        $categories = \Illuminate\Support\Facades\Cache::remember('mk_categories_list', 3600, function () {
            return ServiceCategory::orderBy('name')->get();
        });

        $currentLocale = app()->getLocale();
        $viewerCurrency = FinanceHelper::instance()->getViewerCurrency($request);
        $userFavoriteIds = auth()->check()
            ? Favorite::where('user_id', auth()->id())->where('favoritable_type', Service::class)->pluck('favoritable_id')->toArray()
            : [];

        $services->getCollection()->transform(function ($service) use ($viewerCurrency, $userFavoriteIds, $currentLocale) {
            $service->is_favorited = in_array($service->id, $userFavoriteIds);
            $service = $this->localizeService($service, $currentLocale);

            // Strip massive unused text/JSON blobs on listing page to dramatically speed up Inertia rendering
            $service->makeHidden(['description', 'description_translations', 'auto_reply', 'auto_reply_translations', 'faq', 'requirements']);

            $service->packages->transform(function ($package) use ($viewerCurrency) {
                if ($package->currency_id && $package->currency_id != $viewerCurrency->id) {
                    $package->price = CurrenciesExchange::RateToday(
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

        $canonicalUrl = route('marketplace.services.index');
        $isAr = $currentLocale === 'ar';

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
                'title' => __('marketplace.meta_title'),
                'description' => __('marketplace.meta_description'),
                'image' => url('/images/og-default.jpg'),
                'url' => $canonicalUrl,
                'canonical_url' => $canonicalUrl,
                'en_url' => $canonicalUrl.'?lang=en',
                'ar_url' => $canonicalUrl.'?lang=ar',
                'type' => 'website',
            ],
        ]);
    }

    public function show($id, Request $request, $slug = null)
    {
        $targetId = $id;
        $extractedSlug = $slug;

        // Handle URLs formatted as /services/98-some-title-slug
        if (! is_numeric($id)) {
            if (preg_match('/^(\d+)-(.*)$/', (string) $id, $matches)) {
                $targetId = $matches[1];
                $extractedSlug = $matches[2];
            } elseif (preg_match('/^(\d+)$/', (string) $id, $matches)) {
                $targetId = $matches[1];
            }
        }

        $query = Service::with(['seller', 'category', 'packages.currency', 'reviews.reviewer', 'extras']);

        $service = null;
        if (is_numeric($targetId)) {
            $service = (clone $query)->find($targetId);
        }

        if (! $service) {
            $searchSlug = $extractedSlug ?: $id;
            $service = (clone $query)->where('slug', $searchSlug)->first();
        }

        if (! $service) {
            $searchSlug = $extractedSlug ?: $slug ?: $id;
            if ($searchSlug) {
                $service = (clone $query)->where('slug', 'like', "%{$searchSlug}%")->first();
            }
        }

        if (! $service) {
            return Inertia::render('Marketplace/Services/ExclusiveService', [
                'serviceSlug' => str_replace('-', ' ', $id),
            ]);
        }

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if ($service->status !== 'active') {
            if ($isAdmin) {
                $service->status = 'active';
                $service->approved_at = $service->approved_at ?: now();
                $service->approved_by = $service->approved_by ?: $user->id;
                $service->save();
            } elseif (! $user || $user->id !== $service->seller_id) {
                return Inertia::render('Marketplace/Services/ExclusiveService', [
                    'serviceSlug' => $service->title ?? str_replace('-', ' ', $id),
                ]);
            }
        }

        $viewerCurrency = FinanceHelper::instance()->getViewerCurrency($request);

        $service->is_favorited = auth()->check()
            ? Favorite::where('user_id', auth()->id())->where('favoritable_type', Service::class)->where('favoritable_id', $service->id)->exists()
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
                $package->price = CurrenciesExchange::RateToday(
                    $package->price,
                    $package->currency_id,
                    $viewerCurrency->id
                );
                if ($package->old_price) {
                    $package->old_price = CurrenciesExchange::RateToday(
                        $package->old_price,
                        $package->currency_id,
                        $viewerCurrency->id
                    );
                }
                $package->currency_id = $viewerCurrency->id;
                $package->setRelation('currency', $viewerCurrency);
            }

            return $package;
        });

        $currentLocale = app()->getLocale();
        $service = $this->localizeService($service, $currentLocale);

        $shareTitle = $service->title.' | Musoftware Marketplace';
        $shareDesc = Str::limit(strip_tags($service->tagline ?: $service->description ?: $service->title), 160);
        $shareImage = $service->cover_image;
        $canonicalUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Product',
                    '@id' => $canonicalUrl.'#product',
                    'name' => $service->title,
                    'image' => array_filter([$shareImage]),
                    'description' => $shareDesc,
                    'category' => $service->category->name ?? 'Software Services',
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => 'Musoftware Marketplace',
                    ],
                    'offers' => [
                        '@type' => 'AggregateOffer',
                        'priceCurrency' => $viewerCurrency->code ?? $viewerCurrency->currency ?? 'USD',
                        'lowPrice' => (float) ($service->packages->min('price') ?? 0),
                        'highPrice' => (float) ($service->packages->max('price') ?? 0),
                        'offerCount' => $service->packages->count() ?: 1,
                        'availability' => 'https://schema.org/InStock',
                    ],
                    'seller' => [
                        '@type' => 'Organization',
                        'name' => $service->seller->name ?? 'Verified Seller',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonicalUrl.'#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Marketplace',
                            'item' => route('marketplace.services.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $service->title,
                            'item' => $canonicalUrl,
                        ],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => $canonicalUrl.'#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'How does the escrow payment system work?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Funds are securely held in escrow by Musoftware Marketplace and are only released to the seller after you review and approve the final deliverable.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Can I request revisions for this service?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Yes, each service package includes defined revision rounds. You can request revisions directly through the order dashboard.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What happens if the seller does not deliver on time?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'If a seller misses the agreed delivery deadline without mutual agreement, you can request an instant full refund or cancellation.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return Inertia::render('Marketplace/Services/Show', [
            'service' => $service,
        ])->withViewData([
            'meta' => [
                'title' => $shareTitle,
                'description' => $shareDesc,
                'image' => $shareImage,
                'url' => $canonicalUrl,
                'canonical_url' => $canonicalUrl,
                'en_url' => $canonicalUrl.'?lang=en',
                'ar_url' => $canonicalUrl.'?lang=ar',
                'schema_json' => $schemaJson,
            ],
        ]);
    }

    protected function localizeService(Service $service, string $targetLocale): Service
    {
        if (! in_array($targetLocale, ['en', 'ar'])) {
            return $service;
        }

        $titleTrans = $service->title_translations ?? [];
        if (! empty($titleTrans[$targetLocale])) {
            $service->title = $titleTrans[$targetLocale];
        }

        $taglineTrans = $service->tagline_translations ?? [];
        if (! empty($taglineTrans[$targetLocale])) {
            $service->tagline = $taglineTrans[$targetLocale];
        }

        $descTrans = $service->description_translations ?? [];
        if (! empty($descTrans[$targetLocale])) {
            $service->description = $descTrans[$targetLocale];
        }

        return $service;
    }

    public function create()
    {
        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']);
        $user = auth()->user();

        return Inertia::render('Marketplace/Services/Create', [
            'categories' => $categories,
            'seller' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? null,
            ],
        ]);
    }

    public function store(StoreServiceRequest $request)
    {
        $validated = $request->validated();

        $service = DB::transaction(function () use ($validated, $request) {
            // Handle gallery uploads using standard Laravel Storage disk with custom ordering support
            $keptPaths = array_values($validated['kept_gallery'] ?? $request->input('kept_gallery', []));
            $uploadedPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $uploadedPaths[] = $image->store('services/'.auth()->id(), 'public_uploads');
                }
            }

            $galleryOrder = $validated['gallery_order'] ?? $request->input('gallery_order', []);
            $galleryPaths = [];
            if (! empty($galleryOrder) && is_array($galleryOrder)) {
                $usedKept = [];
                $usedUploaded = [];
                foreach ($galleryOrder as $token) {
                    if (str_starts_with($token, 'kept:')) {
                        $val = substr($token, 5);
                        if (is_numeric($val)) {
                            $idx = (int) $val;
                            if (isset($keptPaths[$idx])) {
                                $galleryPaths[] = $keptPaths[$idx];
                                $usedKept[$idx] = true;
                            }
                        } else {
                            $idx = array_search($val, $keptPaths);
                            if ($idx !== false) {
                                $galleryPaths[] = $keptPaths[$idx];
                                $usedKept[$idx] = true;
                            }
                        }
                    } elseif (str_starts_with($token, 'new:') || str_starts_with($token, 'gallery:')) {
                        $idx = (int) str_replace(['new:', 'gallery:'], '', $token);
                        if (isset($uploadedPaths[$idx])) {
                            $galleryPaths[] = $uploadedPaths[$idx];
                            $usedUploaded[$idx] = true;
                        }
                    }
                }
                foreach ($keptPaths as $idx => $path) {
                    if (! isset($usedKept[$idx]) && ! in_array($path, $galleryPaths, true)) {
                        $galleryPaths[] = $path;
                    }
                }
                foreach ($uploadedPaths as $idx => $path) {
                    if (! isset($usedUploaded[$idx]) && ! in_array($path, $galleryPaths, true)) {
                        $galleryPaths[] = $path;
                    }
                }
            } else {
                $galleryPaths = array_merge($keptPaths, $uploadedPaths);
            }

            $thumbnailPath = null;
            if (! empty($galleryPaths[0])) {
                $firstImagePath = $galleryPaths[0];
                $fullPath = public_path('uploads/'.ltrim($firstImagePath, '/'));
                $thumbRelative = 'services/'.auth()->id().'/thumb_'.basename($firstImagePath);
                $thumbFullPath = public_path('uploads/'.$thumbRelative);

                if (file_exists(public_path('uploads/'.$thumbRelative))) {
                    $thumbnailPath = $thumbRelative;
                } elseif (ImageHelper::createThumbnail($fullPath, $thumbFullPath, 600, 400, 80)) {
                    $thumbnailPath = $thumbRelative;
                } else {
                    $thumbnailPath = $firstImagePath;
                }
            }

            $user = auth()->user();

            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin));

            $service = Service::create([
                'seller_id' => auth()->id(),
                'title' => $validated['title'] ?? null,
                'slug' => ! empty($validated['title']) ? Str::slug($validated['title']) : null,
                'thumbnail' => $thumbnailPath,
                'title_translations' => $validated['title_translations'] ?? null,
                'tagline' => $validated['tagline'] ?? null,
                'tagline_translations' => $validated['tagline_translations'] ?? null,
                'description' => $validated['description'] ?? null,
                'description_translations' => $validated['description_translations'] ?? null,
                'auto_reply' => $validated['auto_reply'] ?? null,
                'auto_reply_translations' => $validated['auto_reply_translations'] ?? null,
                'category_id' => $validated['category_id'],
                'status' => $isAdmin ? 'active' : 'draft',
                'approved_at' => $isAdmin ? now() : null,
                'approved_by' => $isAdmin ? $user->id : null,
                'tags' => $validated['tags'] ?? [],
                'faq' => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'gallery' => $galleryPaths,
                'video_url' => $validated['video_url'] ?? null,
                'service_link' => $validated['service_link'] ?? null,
                'is_free' => $validated['is_free'] ?? false,
                'generate_serials' => $validated['generate_serials'] ?? false,
                'allow_random_serial' => $validated['allow_random_serial'] ?? false,
                'validity_days' => $validated['validity_days'] ?? null,
                'referral_commission_from' => $validated['referral_commission_from'] ?? 'fee',
                'referral_commission_percentage' => $validated['referral_commission_percentage'] ?? null,
            ]);

            if (isset($validated['extras'])) {
                foreach ($validated['extras'] as $extra) {
                    ServiceExtra::create([
                        'service_id' => $service->id,
                        'title' => $extra['title'],
                        'price' => $extra['price'],
                        'duration_days' => $extra['duration_days'] ?? 0,
                    ]);
                }
            }

            foreach ($validated['packages'] as $pkg) {
                ServicePackage::create([
                    'service_id' => $service->id,
                    'name' => $pkg['name'],
                    'description' => $pkg['description'],
                    'price' => $pkg['price'],
                    'old_price' => !empty($pkg['old_price']) ? $pkg['old_price'] : null,
                    'currency_id' => $pkg['currency_id'],
                    'delivery_days' => $pkg['delivery_days'],
                    'revisions' => $pkg['revisions'] ?? 2,
                    'features' => $pkg['features'] ?? [],
                ]);
            }

            return $service;
        });

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin));
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
            $keptPaths = array_values($validated['kept_gallery'] ?? []);
            $uploadedPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $uploadedPaths[] = $image->store('services/'.auth()->id(), 'public_uploads');
                }
            }

            $galleryOrder = $validated['gallery_order'] ?? $request->input('gallery_order', []);
            $galleryPaths = [];
            if (! empty($galleryOrder) && is_array($galleryOrder)) {
                $usedKept = [];
                $usedUploaded = [];
                foreach ($galleryOrder as $token) {
                    if (str_starts_with($token, 'kept:')) {
                        $val = substr($token, 5);
                        if (is_numeric($val)) {
                            $idx = (int) $val;
                            if (isset($keptPaths[$idx])) {
                                $galleryPaths[] = $keptPaths[$idx];
                                $usedKept[$idx] = true;
                            }
                        } else {
                            $idx = array_search($val, $keptPaths);
                            if ($idx !== false) {
                                $galleryPaths[] = $keptPaths[$idx];
                                $usedKept[$idx] = true;
                            }
                        }
                    } elseif (str_starts_with($token, 'new:') || str_starts_with($token, 'gallery:')) {
                        $idx = (int) str_replace(['new:', 'gallery:'], '', $token);
                        if (isset($uploadedPaths[$idx])) {
                            $galleryPaths[] = $uploadedPaths[$idx];
                            $usedUploaded[$idx] = true;
                        }
                    }
                }
                foreach ($keptPaths as $idx => $path) {
                    if (! isset($usedKept[$idx]) && ! in_array($path, $galleryPaths, true)) {
                        $galleryPaths[] = $path;
                    }
                }
                foreach ($uploadedPaths as $idx => $path) {
                    if (! isset($usedUploaded[$idx]) && ! in_array($path, $galleryPaths, true)) {
                        $galleryPaths[] = $path;
                    }
                }
            } else {
                $galleryPaths = array_merge($keptPaths, $uploadedPaths);
            }

            $thumbnailPath = $service->thumbnail;
            if (! empty($galleryPaths[0])) {
                $firstImagePath = $galleryPaths[0];
                $fullPath = public_path('uploads/'.ltrim($firstImagePath, '/'));
                $thumbRelative = 'services/'.auth()->id().'/thumb_'.basename($firstImagePath);
                $thumbFullPath = public_path('uploads/'.$thumbRelative);

                if (ImageHelper::createThumbnail($fullPath, $thumbFullPath, 600, 400, 80)) {
                    $thumbnailPath = $thumbRelative;
                } else {
                    $thumbnailPath = $firstImagePath;
                }
            }

            $user = auth()->user();
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin));

            $service->update([
                'title' => $validated['title'] ?? null,
                'slug' => ! empty($validated['title']) ? Str::slug($validated['title']) : $service->slug,
                'thumbnail' => $thumbnailPath,
                'title_translations' => $validated['title_translations'] ?? null,
                'tagline' => $validated['tagline'] ?? null,
                'tagline_translations' => $validated['tagline_translations'] ?? null,
                'description' => $validated['description'] ?? null,
                'description_translations' => $validated['description_translations'] ?? null,
                'auto_reply' => $validated['auto_reply'] ?? null,
                'auto_reply_translations' => $validated['auto_reply_translations'] ?? null,
                'category_id' => $validated['category_id'],
                'tags' => $validated['tags'] ?? [],
                'faq' => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'gallery' => $galleryPaths,
                'video_url' => $validated['video_url'] ?? null,
                'service_link' => $validated['service_link'] ?? null,
                'is_free' => $validated['is_free'] ?? false,
                'generate_serials' => $validated['generate_serials'] ?? false,
                'allow_random_serial' => $validated['allow_random_serial'] ?? false,
                'validity_days' => $validated['validity_days'] ?? null,
                'referral_commission_from' => $validated['referral_commission_from'] ?? 'fee',
                'referral_commission_percentage' => $validated['referral_commission_percentage'] ?? null,
                'status' => $isAdmin ? 'active' : 'draft',
                'approved_at' => $isAdmin ? ($service->approved_at ?? now()) : $service->approved_at,
                'approved_by' => $isAdmin ? ($service->approved_by ?? $user->id) : $service->approved_by,
            ]);

            $submittedExtraIds = collect($validated['extras'] ?? [])->pluck('id')->filter()->toArray();
            $service->extras()->whereNotIn('id', $submittedExtraIds)->delete();

            if (isset($validated['extras'])) {
                foreach ($validated['extras'] as $extra) {
                    if (! empty($extra['id'])) {
                        $service->extras()->where('id', $extra['id'])->update([
                            'title' => $extra['title'],
                            'price' => $extra['price'],
                            'duration_days' => $extra['duration_days'] ?? 0,
                        ]);
                    } else {
                        ServiceExtra::create([
                            'service_id' => $service->id,
                            'title' => $extra['title'],
                            'price' => $extra['price'],
                            'duration_days' => $extra['duration_days'] ?? 0,
                        ]);
                    }
                }
            }

            $submittedPackageIds = collect($validated['packages'])->pluck('id')->filter()->toArray();

            // Soft-delete removed packages
            $service->packages()->whereNotIn('id', $submittedPackageIds)->delete();

            foreach ($validated['packages'] as $pkg) {
                if (! empty($pkg['id'])) {
                    // Update existing
                    $service->packages()->where('id', $pkg['id'])->update([
                        'name' => $pkg['name'],
                        'description' => $pkg['description'],
                        'price' => $pkg['price'],
                        'old_price' => !empty($pkg['old_price']) ? $pkg['old_price'] : null,
                        'currency_id' => $pkg['currency_id'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions' => $pkg['revisions'] ?? 2,
                        'features' => $pkg['features'] ?? [],
                    ]);
                } else {
                    // Create new
                    ServicePackage::create([
                        'service_id' => $service->id,
                        'name' => $pkg['name'],
                        'description' => $pkg['description'],
                        'price' => $pkg['price'],
                        'old_price' => !empty($pkg['old_price']) ? $pkg['old_price'] : null,
                        'currency_id' => $pkg['currency_id'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions' => $pkg['revisions'] ?? 2,
                        'features' => $pkg['features'] ?? [],
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
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (! $isAdmin) {
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
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (! $isAdmin) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'provider' => 'required|string|in:chatgpt,gemini',
        ]);

        try {
            $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug'])->toArray();

            $aiService = app(MarketplaceAiService::class);
            $aiData = $aiService->generateServiceData($validated['title'], $validated['provider'], $categories, $user->id);

            $service = DB::transaction(function () use ($aiData, $user) {
                $service = Service::create([
                    'seller_id' => $user->id,
                    'title' => $aiData['title'],
                    'slug' => Str::slug($aiData['title']),
                    'thumbnail' => $aiData['thumbnail'] ?? null,
                    'tagline' => $aiData['tagline'] ?? null,
                    'description' => $aiData['description'] ?? null,
                    'category_id' => $aiData['category_id'],
                    'status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                    'tags' => $aiData['tags'] ?? [],
                    'faq' => $aiData['faq'] ?? [],
                    'requirements' => $aiData['requirements'] ?? [],
                    'gallery' => $aiData['gallery'] ?? [],
                    'is_free' => false,
                ]);

                if (! empty($aiData['packages']) && is_array($aiData['packages'])) {
                    foreach ($aiData['packages'] as $pkg) {
                        ServicePackage::create([
                            'service_id' => $service->id,
                            'name' => $pkg['name'] ?? 'Standard',
                            'description' => $pkg['description'] ?? '',
                            'price' => $pkg['price'] ?? 25,
                            'currency_id' => $pkg['currency_id'] ?? 1,
                            'delivery_days' => $pkg['delivery_days'] ?? 3,
                            'revisions' => $pkg['revisions'] ?? 2,
                            'features' => $pkg['features'] ?? [],
                        ]);
                    }
                } else {
                    ServicePackage::create([
                        'service_id' => $service->id,
                        'name' => 'Standard',
                        'description' => $service->description ?: 'Standard package deliverable.',
                        'price' => 25,
                        'currency_id' => 1,
                        'delivery_days' => 3,
                        'revisions' => 2,
                        'features' => [],
                    ]);
                }

                return $service;
            });

            return redirect()->route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug])
                ->with('success', __('general.service_created_successfully_by_ai') ?? 'Service generated successfully by AI!');
        } catch (\Throwable $e) {
            Log::error('Marketplace AI generation error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'title' => $validated['title'] ?? null,
                'provider' => $validated['provider'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);

        $service->delete();

        $referer = request()->headers->get('referer');
        if ($referer && (str_contains($referer, '/marketplace/services/'.$service->id) || str_contains($referer, '/marketplace/services/'.$service->slug))) {
            return redirect()->route('marketplace.services.index')
                ->with('success', __('general.service_deleted_successfully'));
        }

        return redirect()->back()
            ->with('success', __('general.service_deleted_successfully'));
    }

    public function getAiImagePrompt(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (! $isAdmin) {
            return response()->json(['error' => 'Unauthorized access. Only admins can view AI image prompts.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prompt' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        $prompt = ! empty($validated['prompt']) ? $validated['prompt'] : $validated['title'];
        $description = $validated['description'] ?? null;

        $aiService = app(MarketplaceAiService::class);
        $refinedPrompt = $aiService->getRefinedImagePrompt($prompt, $description);

        return response()->json([
            'success' => true,
            'prompt' => $refinedPrompt,
        ]);
    }

    public function generateAiImage(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || ! empty($user->is_admin) || ($user->role ?? null) === 'admin');

        if (! $isAdmin) {
            return response()->json(['error' => 'Unauthorized access. Only admins can generate AI images.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prompt' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        $prompt = ! empty($validated['prompt']) ? $validated['prompt'] : $validated['title'];
        $description = $validated['description'] ?? null;

        $aiService = app(MarketplaceAiService::class);
        $refinedPrompt = $aiService->getRefinedImagePrompt($prompt, $description);
        $result = $aiService->generateCoverImage($refinedPrompt, $user->id);

        if (! empty($result['gallery'][0])) {
            $imagePath = $result['gallery'][0];

            return response()->json([
                'success' => true,
                'prompt' => $refinedPrompt,
                'path' => $imagePath,
                'url' => asset('uploads/'.ltrim($imagePath, '/')),
            ]);
        }

        return response()->json([
            'error' => 'Failed to generate AI image. Please verify API settings or try again.',
            'prompt' => $refinedPrompt,
        ], 500);
    }

    /**
     * Display services for a specific Technology / Tag.
     */
    public function showTechnology(string $tag, Request $request)
    {
        $tagClean = trim(urldecode($tag));
        $query = Service::with(['seller', 'category', 'packages.currency'])
            ->where('status', 'active')
            ->whereJsonContains('tags', $tagClean);

        $services = $query->latest()->paginate(15);
        $categories = \Illuminate\Support\Facades\Cache::remember('mk_categories_list', 3600, function () {
            return ServiceCategory::orderBy('name')->get();
        });

        $services->getCollection()->transform(function ($service) {
            $service->makeHidden(['description', 'description_translations', 'auto_reply', 'auto_reply_translations', 'faq', 'requirements']);
            return $service;
        });

        $title = "Services built with " . ucfirst($tagClean);
        $canonicalUrl = route('marketplace.technologies.show', ['tag' => $tagClean]);

        $schemaJson = \Modules\Marketplace\Helpers\MarketplaceSchemaHelper::forBreadcrumbs([
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Marketplace', 'url' => route('marketplace.services.index')],
            ['name' => "Technology: {$tagClean}", 'url' => $canonicalUrl],
        ]);

        return Inertia::render('Marketplace/Browse', [
            'services' => $services,
            'categories' => $categories,
            'schemaJson' => $schemaJson,
            'filters' => [
                'tag' => $tagClean,
                'technology' => $tagClean,
            ],
        ])->withViewData([
            'meta' => [
                'title' => "{$title} | MuSoftwares Marketplace",
                'description' => "Find best freelancers and services specialized in {$tagClean} on MuSoftwares Marketplace.",
                'url' => $canonicalUrl,
                'schemaJson' => $schemaJson,
            ],
        ]);
    }

    /**
     * Display services for a specific Integration.
     */
    public function showIntegration(string $tag, Request $request)
    {
        $tagClean = trim(urldecode($tag));
        $query = Service::with(['seller', 'category', 'packages.currency'])
            ->where('status', 'active')
            ->where(function ($q) use ($tagClean) {
                $q->whereJsonContains('tags', $tagClean)
                  ->orWhere('title', 'like', "%{$tagClean}%")
                  ->orWhere('description', 'like', "%{$tagClean}%");
            });

        $services = $query->latest()->paginate(15);
        $categories = \Illuminate\Support\Facades\Cache::remember('mk_categories_list', 3600, function () {
            return ServiceCategory::orderBy('name')->get();
        });

        $services->getCollection()->transform(function ($service) {
            $service->makeHidden(['description', 'description_translations', 'auto_reply', 'auto_reply_translations', 'faq', 'requirements']);
            return $service;
        });

        $title = "{$tagClean} Integrations & Solutions";
        $canonicalUrl = route('marketplace.integrations.show', ['tag' => $tagClean]);

        $schemaJson = \Modules\Marketplace\Helpers\MarketplaceSchemaHelper::forBreadcrumbs([
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Marketplace', 'url' => route('marketplace.services.index')],
            ['name' => "Integration: {$tagClean}", 'url' => $canonicalUrl],
        ]);

        return Inertia::render('Marketplace/Browse', [
            'services' => $services,
            'categories' => $categories,
            'schemaJson' => $schemaJson,
            'filters' => [
                'tag' => $tagClean,
                'integration' => $tagClean,
            ],
        ])->withViewData([
            'meta' => [
                'title' => "{$title} | MuSoftwares Marketplace",
                'description' => "Explore {$tagClean} integrations, tools, and automated services on MuSoftwares Marketplace.",
                'url' => $canonicalUrl,
                'schemaJson' => $schemaJson,
            ],
        ]);
    }
}

