@extends('layouts.app')
{{-- ============ PAGE METADATA ============ --}}
@section('title', $service->title . ' | ' . config('app.name', 'Laravel'))
@section('description', $service->description_str())
@section('additional_head_code')
    {{-- SEO: Canonical and Language Alternates --}}
    <link rel="canonical" href="{{ route('services.show.localized', ['locale' => app()->getLocale(), 'service' => $service->slug]) }}">
    @foreach(['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'pt-BR', 'ru', 'ja', 'zh', 'el'] as $locale)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ route('services.show.localized', ['locale' => $locale, 'service' => $service->slug]) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ route('services.show', $service) }}">

    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}" />
    <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'ar' ? 'en_US' : 'ar_AR' }}" />

    {{-- Facebook Open Graph --}}
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'ar' ? 'en_US' : 'ar_AR' }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $service->title }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($service->description_str()), 200) }}">
    <meta property="og:image" content="{{ asset($service->image) }}">
    <meta property="og:url" content="{{ route('services.show', $service) }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="product:price:amount" content="{{ $service->price }}">
    <meta property="product:price:currency" content="{{ $service->currency }}">
    {{-- Google Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": {!! json_encode($service->title) !!},
        "description": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($service->description_str()), 160)) !!},
        "image": "{{ asset($service->image) }}",
        "sku": "{{ $service->id }}",
        "brand": {
            "@type": "Brand",
            "name": "{{ config('app.name') }}"
        },
        "offers": {
            "@type": "Offer",
            "url": "{{ route('services.show', $service) }}",
            "priceCurrency": "{{ $service->current_currency_str() }}",
            "price": "{{ $service->current_price() }}",
            "availability": "https://schema.org/InStock",
            "seller": {
                "@type": "Organization",
                "name": "{{ config('app.name') }}"
            }
        }@if($service->reviews_count > 0),
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "{{ $service->average_rating }}",
            "reviewCount": "{{ $service->reviews_count }}",
            "bestRating": "5",
            "worstRating": "1"
        }@endif
    }
    </script>
    {{-- External Scripts --}}
    <script async type="text/javascript" src="https://platform-api.sharethis.com/js/sharethis.js#property=673bb5bd9091730012acf0d0&product=inline-share-buttons&source=platform"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
@endsection
{{-- ============ VIEW VARIABLES & LOGIC ============ --}}
@php
    // User authentication and permissions
    $isLoggedIn = Auth::check();
    $user = Auth::user();
    $isAdmin = $isLoggedIn && $user?->hasRole('admin');
    $isOwnService = $isLoggedIn && $service->user_id === $user->id && !$isAdmin;
    // Service statistics
    $avgRating = round($service->average_rating, 1);
    $reviewCount = $service->reviews_count;
    $displayRating = $reviewCount > 0 ? $avgRating : 5.0;
    // Category breadcrumb structure
    $categoryPath = [];
    if ($service->category) {
        $categoryPath = [
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => $service->category->name, 'slug' => $service->category->slug]
        ];
    }
    // Portfolio items setup
    $portfolioItems = $service->portfolio_items ?? collect();

    // Referral system variables
    $userReferral = $isLoggedIn ? \App\Models\UserReferral::where('user_id', $user->id)->first() : null;
    $serviceRefPath = 'services/' . $service->slug;
    $displayCurrencyId = $isLoggedIn ? $user->currency : $service->getGuestCurrencyId();
    $estimatedCommissionStr = \App\Helpers\FinanceHelper::instance()->format_money(
        $service->getEstimatedReferralCommissionPerSale($displayCurrencyId), 
        $displayCurrencyId
    );
    // Service tags
    $tags = [
        'Software Development', 'Web Application', 'Custom Solutions', 'API Integration',
        'Database Design', 'Cloud Services', 'Mobile Development', 'E-commerce Solutions',
        'UI/UX Design', 'Performance Optimization'
    ];
@endphp
@section('content')
    <div class="dashboard-container at-mobile-scroll-fix service-page-content services-show-page">
        {{-- ============ BREADCRUMB NAVIGATION ============ --}}
        <div class="service-breadcrumb">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                        {{-- Home --}}
                        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a href="{{ route('landing.home') }}" itemprop="item" itemscope itemtype="https://schema.org/Thing">
                                <span itemprop="name">{{ __('Home') }}</span>
                                <meta itemprop="url" content="{{ route('landing.home') }}">
                            </a>
                            <meta itemprop="position" content="1">
                        </li>
                        {{-- Services --}}
                        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a href="{{ route('services.mindex') }}" itemprop="item" itemscope itemtype="https://schema.org/Thing">
                                <span itemprop="name">{{ __('Services') }}</span>
                                <meta itemprop="url" content="{{ route('services.mindex') }}">
                            </a>
                            <meta itemprop="position" content="2">
                        </li>
                        {{-- Category Breadcrumbs --}}
                        @foreach($categoryPath as $index => $category)
                            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                @if($loop->last)
                                    <span itemprop="name">{{ $category['name'] }}</span>
                                    <meta itemprop="url" content="{{ route('services.index') }}?category={{ $category['slug'] }}">
                                @else
                                    <a href="{{ route('services.index') }}?category={{ $category['slug'] }}" itemprop="item" itemscope itemtype="https://schema.org/Thing">
                                        <span itemprop="name">{{ $category['name'] }}</span>
                                        <meta itemprop="url" content="{{ route('services.index') }}?category={{ $category['slug'] }}">
                                    </a>
                                @endif
                                <meta itemprop="position" content="{{ $index + 3 }}">
                            </li>
                        @endforeach
                        {{-- Current Service --}}
                        <li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <span itemprop="name">{{ Str::limit($service->title, 50) }}</span>
                            <meta itemprop="url" content="{{ route('services.show', $service) }}">
                            <meta itemprop="position" content="{{ count($categoryPath) + 4 }}">
                        </li>
                    </ol>
                </nav>
                {{-- Breadcrumb Actions --}}
                <div class="breadcrumb-actions">
                    <button class="breadcrumb-action" onclick="navigator.share && navigator.share({title: '{{ $service->title }}', url: window.location.href})">
                        <i class="ti ti-share"></i>
                        {{ __('Share') }}
                    </button>
                    <button class="breadcrumb-action" onclick="window.print()">
                        <i class="ti ti-printer"></i>
                        {{ __('Print') }}
                    </button>
                </div>
            </div>
        </div>
        {{-- ============ ADMIN TOOLBAR ============ --}}
        @if($isAdmin)
            <div class="admin-toolbar bg-light border-bottom py-2">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            {{-- Service Status Badge --}}
                            <span class="badge {{ in_array($service->status, ['approved', 'active']) ? 'bg-success' : ($service->status === 'paused' ? 'bg-warning' : ($service->status === 'suspended' ? 'bg-danger' : 'bg-secondary')) }}">
                                {{ ucfirst($service->status) }}
                            </span>
                            <span class="text-muted small">Service ID: #{{ $service->id }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            @if($service->status !== 'paused' && $service->status !== 'suspended')
                            <form action="{{ route('admin.services.suspend', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to suspend this service?') }}')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="ti ti-player-pause me-1"></i>{{ __('Suspend Service') }}
                                </button>
                            </form>
                            @endif
                            @if(!in_array($service->status, ['active', 'approved']))
                            <form action="{{ route('admin.services.approve', $service) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-check me-1"></i>{{ __('Approve Service') }}
                                </button>
                            </form>
                            @endif
                            <form action="{{ route('services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this service? This action cannot be undone.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash me-1"></i>{{ __('Delete Service') }}
                                </button>
                            </form>
                            <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-settings me-1"></i>{{ __('View in Admin Panel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- ============ MAIN CONTENT ROW ============ --}}
        <div class="row">
            {{-- Left Column: Service Details --}}
            <div class="col-lg-8">
                {{-- ============ SERVICE HEADER ============ --}}
                <div class="service-header">
                    <h1 class="service-title">{{ $service->title }}</h1>
                    <div class="seller-info">
                        <a href="{{ route('user.link', $service->user->slug) }}" class="text-decoration-none">
                            <img alt="{{ $service->user->name }}" src="{{ $service->user->profile_photo_url }}" class="seller-avatar">
                        </a>
                        <div class="seller-details">
                            <a href="{{ route('user.link', $service->user->slug) }}" class="text-decoration-none text-dark">
                                <h6>{{ $service->user->name }}</h6>
                            </a>
                            <span class="seller-level">{{ __('Professional Seller') }}</span>
                        </div>
                    </div>
                    <div class="service-stats">
                        <div class="stat-item" role="img" aria-label="Rated {{ number_format($service->average_rating, 1) }} out of 5 stars">
                            <i class="ti ti-star-filled"></i>
                            <span>
                                @if($reviewCount > 0)
                                    {{ $avgRating }} ({{ $reviewCount }} {{ __('reviews') }})
                                @else
                                    {{ __('No reviews yet') }}
                                @endif
                            </span>
                        </div>
                        <div class="stat-item">
                            <i class="ti ti-shopping-cart"></i>
                            <span>{{ $service->orders_in_queue }} {{ __('Orders in queue') }}</span>
                        </div>
                    </div>
                </div>
                {{-- ============ PORTFOLIO GALLERY ============ --}}
                @if($portfolioItems->count() > 0)
                    <div class="portfolio-gallery">
                        <div class="gallery-header d-flex justify-content-between align-items-center mb-3">
                            <h4 class="gallery-title">{{ __('Portfolio & Samples') }}</h4>
                            @if($portfolioItems->count() > 1)
                                <div class="gallery-controls">
                                    <button class="gallery-btn gallery-btn-prev" id="galleryPrev" aria-label="{{ __('general.previous_image') }}">
                                        <i class="ti ti-chevron-left"></i>
                                    </button>
                                    <button class="gallery-btn gallery-btn-next" id="galleryNext" aria-label="{{ __('general.next_image') }}">
                                        <i class="ti ti-chevron-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        {{-- Main Gallery Display --}}
                        <div class="gallery-main-container skeleton-shimmer">
                            <div class="gallery-main" id="portfolioGalleryMain">
                                @foreach($portfolioItems as $index => $item)
                                    <div class="gallery-item {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                                        @if($item->type === 'video')
                                            <div class="video-container">
                                                <iframe src="{{ $item->image_path }}" title="{{ $item->title ?? $service->title . ' ' . __('Video Preview') }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                                <div class="video-overlay">
                                                    <i class="ti ti-player-play"></i>
                                                </div>
                                            </div>
                                        @else
                                            <img alt="{{ $item->title ?? $service->title }} - {{ $index + 1 }}" src="{{ asset($item->image_path) }}" loading="lazy">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @if($portfolioItems->count() > 1)
                            {{-- Gallery Thumbnails --}}
                            <div class="gallery-thumbnails-container">
                                <div class="gallery-thumbnails" id="portfolioThumbnails">
                                    @foreach($portfolioItems as $index => $item)
                                        <div class="gallery-thumb {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}" data-image="{{ $item->type === 'image' ? asset($item->image_path) : $item->image_path }}">
                                            @if($item->type === 'video')
                                                @php
                                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $item->image_path, $match);
                                                    $youtubeId = $match[1] ?? 'dQw4w9WgXcQ';
                                                @endphp
                                                <div class="video-thumb">
                                                    <img alt="{{ $item->title }}" src="https://img.youtube.com/vi/{{ $youtubeId }}/hqdefault.jpg" loading="lazy">
                                                    <div class="video-play-icon">
                                                        <i class="ti ti-player-play"></i>
                                                    </div>
                                                </div>
                                            @else
                                                <img alt="{{ $item->title ?? $service->title }} - {{ $index + 1 }}" src="{{ asset($item->image_path) }}" loading="lazy">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Gallery View Toggle --}}
                            <div class="gallery-view-toggle">
                                <button class="view-btn active" data-view="grid" id="gridViewBtn">
                                    <i class="ti ti-layout-grid"></i>
                                    {{ __('Grid') }}
                                </button>
                                <button class="view-btn" data-view="carousel" id="carouselViewBtn">
                                    <i class="ti ti-carousel"></i>
                                    {{ __('Carousel') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
                {{-- ============ SERVICE DESCRIPTION ============ --}}
                <div class="service-description">
                    <h3 class="section-title">{{ __('About This Gig') }}</h3>
                    <div class="auto-align service-description-body">
                        {{-- XSS: verify this is safe - user content with HTML --}}
                        {!! nl2br(e($service->description_str())) !!}
                    </div>
                </div>
                {{-- ============ DELIVERABLES ============ --}}
                @php
                    $dbPackages = $service->packages()->get()->sortBy(function($pkg) { return array_search($pkg->name, ['basic', 'standard', 'premium']); })->values();
                    $packagesWithFeatures = $dbPackages->filter(fn ($p) => $p->hasFeatures());
                @endphp
                @if($packagesWithFeatures->isNotEmpty())
                <div class="deliverables-section">
                    <h3 class="section-title">{{ __('What\'s Included') }}</h3>
                    <div class="deliverables-content">
                        <div class="deliverables-grid">
                            @foreach($dbPackages as $pkg)
                                <div class="deliverables-column">
                                    <h5 class="deliverables-subtitle">{{ $pkg->title ?: __(ucfirst($pkg->name) . ' Package') }}</h5>
                                    @if($pkg->hasFeatures())
                                        <ul class="deliverables-list">
                                            @foreach($pkg->features as $feature)
                                                <li class="deliverable-item included">
                                                    <i class="ti ti-check"></i>
                                                    <span>{{ $feature }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted small">{{ $pkg->description ?: __('Contact seller for details.') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="deliverables-legend mt-4">
                            <div class="legend-item">
                                <i class="ti ti-check text-success"></i>
                                <span>{{ __('Included') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                {{-- ============ SERVICE TAGS ============ --}}
                <div class="service-tags-section">
                    <h3 class="section-title">{{ __('Service Tags') }}</h3>
                    <div class="tags-container">
                        @foreach($tags as $tag)
                            <a href="{{ route('services.index') }}?q={{ urlencode($tag) }}" class="service-tag-pill">
                                <i class="ti ti-tag"></i>
                                <span>{{ $tag }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="tags-info mt-3">
                        <p class="text-muted small mb-0">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ __('Click on any tag to discover similar services in this category') }}
                        </p>
                    </div>
                </div>
                {{-- ============ SERVICE EXTRAS ============ --}}
                @if($service->extras->count() > 0)
                    <div class="service-extras mt-5">
                        <h3 class="section-title">{{ __('Available Extras') }}</h3>
                        <div class="card border-0 shadow-sm rounded-3">
                            <ul class="list-group list-group-flush rounded-3">
                                @foreach($service->extras as $extra)
                                    <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-success">
                                                <i class="ti ti-circle-check fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $extra->title }}</h6>
                                                @if($extra->duration_days > 0)
                                                    <small class="text-muted">
                                                        <i class="ti ti-clock me-1"></i>
                                                        +{{ $service->formatDuration($extra->duration_days) }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="fw-bold">+{{ $extra->current_price_str() }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                {{-- ============ FAQs ============ --}}
                @if($service->faqs->isNotEmpty())
                    <div class="service-faqs mt-5">
                        <h3 class="section-title">{{ __('services.form.faqs_title') }}</h3>
                        <div class="accordion" id="accordionFAQs">
                            @foreach($service->faqs as $index => $faq)
                                <div class="accordion-item border-0 shadow-sm mb-2 rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="heading{{ $index }}">
                                        <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }} bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionFAQs">
                                        <div class="accordion-body text-muted bg-light">
                                            {{-- XSS: verify this is safe - user content with HTML --}}
                                            {!! nl2br(e($faq->answer)) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                {{-- ============ ABOUT SELLER ============ --}}
                <div class="about-seller">
                    <h3 class="section-title">{{ __('About The Seller') }}</h3>
                    <div class="seller-card">
                        <a href="{{ route('user.link', $service->user->slug) }}">
                            <img alt="{{ $service->user->name }}" src="{{ $service->user->profile_photo_url }}" class="seller-card-avatar">
                        </a>
                        <div class="seller-card-info">
                            <a href="{{ route('user.link', $service->user->slug) }}" class="text-decoration-none text-dark">
                                <h5>{{ $service->user->name }}</h5>
                            </a>
                            <p>{{ $service->user->job ?? __('Professional Service Provider') }}</p>
                            <a href="{{ route('user.link', $service->user->slug) }}" class="at-btn at-btn-ghost at-btn-sm mt-2">
                                {{ __('View Profile') }}
                            </a>
                        </div>
                    </div>
                    <div class="seller-stats-grid">
                        <div class="seller-stat">
                            <div class="seller-stat-label">{{ __('From') }}</div>
                            <div class="seller-stat-value">{{ $service->user->country ?? __('common.countries.egypt') }}</div>
                        </div>
                        <div class="seller-stat">
                            <div class="seller-stat-label">{{ __('user.profile.about.member_since') }}</div>
                            <div class="seller-stat-value">{{ $service->user->created_at->format('M Y') }}</div>
                        </div>
                        <div class="seller-stat">
                            <div class="seller-stat-label">{{ __('Total orders') }}</div>
                            <div class="seller-stat-value">{{ $service->total_orders }}</div>
                        </div>
                        <div class="seller-stat">
                            <div class="seller-stat-label">{{ __('Last delivery') }}</div>
                            <div class="seller-stat-value">
                                @if($service->latest_delivery)
                                    {{ $service->latest_delivery->diffForHumans() }}
                                @else
                                    {{ __('No deliveries yet') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Right Column: Order Box --}}
            <div class="col-lg-4">
                {{-- ============ ORDER BOX ============ --}}
                <div class="order-box-wrapper sticky-top" style="top: 100px;">
                    <div class="order-box at-card p-0 overflow-hidden">
                        {{-- Package Tabs --}}
                        <div class="package-tabs d-flex">
                            @foreach($dbPackages as $tabIndex => $tabPkg)
                            <button class="package-tab flex-fill {{ $tabIndex === 0 ? 'active' : '' }}" data-tab="{{ $tabPkg->name }}" data-price="{{ $tabPkg->price }}" role="tab" aria-selected="{{ $tabIndex === 0 ? 'true' : 'false' }}" aria-controls="panel-{{ $tabIndex }}">
                                {{ $tabPkg->title ?: __(ucfirst($tabPkg->name)) }}
                            </button>
                            @endforeach
                        </div>
                        <div class="package-content p-4 pt-5">
                            @foreach($dbPackages as $panelIndex => $pkg)
                            <div class="tab-pane {{ $panelIndex === 0 ? 'active' : '' }}" id="panel-{{ $panelIndex }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="package-title mb-0">{{ $pkg->title ?: __(ucfirst($pkg->name)) }}</h4>
                                    <div class="package-price h3 fw-bold mb-0">
                                        @if($service->isFree())
                                            <span class="text-success">{{ __('services.free.price_free') }}</span>
                                        @else
                                            {{ \App\Helpers\FinanceHelper::instance()->format_money($pkg->price, $service->currency) }}
                                        @endif
                                    </div>
                                </div>
                                @if($pkg->description)
                                <p class="package-description text-muted small mb-4">
                                    {{ $pkg->description }}
                                </p>
                                @elseif($pkg->name === 'standard' && $service->tagline)
                                <p class="package-description text-muted small mb-4">
                                    {{ $service->tagline }}
                                </p>
                                @endif
                                <div class="package-specs d-flex gap-3 mb-4">
                                    <div class="spec-item d-flex align-items-center gap-1 small fw-bold">
                                        <i class="ti ti-clock text-primary"></i>
                                        <span>{{ $pkg->formatted_delivery }}</span>
                                    </div>
                                    <div class="spec-item d-flex align-items-center gap-1 small fw-bold">
                                        <i class="ti ti-refresh text-primary"></i>
                                        <span>{{ $pkg->formatted_revisions }} {{ $pkg->revisions === 1 ? __('Revision') : __('Revisions') }}</span>
                                    </div>
                                </div>
                                @if($pkg->hasFeatures())
                                <ul class="package-features list-unstyled mb-4">
                                    @foreach($pkg->features as $feature)
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <i class="ti ti-check text-success mt-1"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        <div class="px-4 pb-4">
                        {{-- ============ REFERRAL CARD ============ --}}
                        @if(!$isOwnService)
                            <div class="referral-service-card card border-0 shadow-sm rounded-3 mt-3 mb-3 p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                                <h6 class="mb-2 fw-bold d-flex align-items-center">
                                    <i class="ti ti-affiliate me-2 text-primary"></i>
                                    {{ __('services.referral_card.title') }}
                                </h6>
                                @if($isLoggedIn && $userReferral)
                                    @php
                                        $refSegment = $userReferral->slug ?? $userReferral->key;
                                        $refLinkToService = url('r/' . $refSegment) . '?to=' . urlencode($serviceRefPath);
                                    @endphp
                                    <p class="small text-muted mb-2">{{ __('services.referral_card.share_and_earn') }}</p>
                                    <p class="small mb-2">
                                        <strong>{{ __($service->getReferralCardEarnTranslationKey(), $service->getReferralCardEarnTranslationParams()) }}</strong>
                                    </p>
                                    <p class="small text-success mb-2"><i class="ti ti-cash me-1"></i>{{ __('services.referral_card.on_sale_referrer_gets', ['price' => $service->current_price_str(), 'amount' => $estimatedCommissionStr]) }}</p>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control bg-white border" value="{{ $refLinkToService }}" readonly id="serviceRefLink" aria-label="{{ __('services.referral_card.referral_link') }}">
                                        <button type="button" class="at-btn at-btn-ghost" id="serviceRefCopyBtn" data-copied="{{ __('services.referral_card.copied') }}" aria-label="Copy">
                                            <i class="ti ti-copy"></i> <span class="btn-copy-text">{{ __('services.referral_card.copy') }}</span>
                                        </button>
                                    </div>
                                    <a href="{{ route('client.referral.index') }}" class="small text-primary mt-1 d-inline-block">{{ __('services.referral_card.manage_referral_earnings') }}</a>
                                @elseif($isLoggedIn)
                                    <p class="small text-muted mb-2">{{ __('services.referral_card.get_link_earn') }}</p>
                                    <p class="small mb-2">{{ __($service->getReferralCardEarnTranslationKey(), $service->getReferralCardEarnTranslationParams()) }}</p>
                                    <p class="small text-success mb-2"><i class="ti ti-cash me-1"></i>{{ __('services.referral_card.on_sale_referrer_gets', ['price' => $service->current_price_str(), 'amount' => $estimatedCommissionStr]) }}</p>
                                    <a href="{{ route('client.referral.index') }}" class="at-btn at-btn-ghost at-btn-sm">{{ __('services.referral_card.get_my_referral_link') }}</a>
                                @else
                                    <p class="small text-muted mb-1">{{ __($service->getReferralCardEarnTranslationKey(), $service->getReferralCardEarnTranslationParams()) }}</p>
                                    <p class="small text-success mb-2"><i class="ti ti-cash me-1"></i>{{ __('services.referral_card.on_sale_referrer_gets', ['price' => $service->current_price_str(), 'amount' => $estimatedCommissionStr]) }}</p>
                                    <a href="{{ route('register', ['redirect' => $serviceRefPath]) }}" class="btn btn-sm btn-outline-primary">{{ __('services.referral_card.sign_up_to_get_link') }}</a>
                                @endif
                                <p class="small text-muted mt-2 mb-0 border-top pt-2">
                                    <i class="ti ti-info-circle me-1"></i>
                                    {{ __($service->getReferralCardFooterNoteKey()) }}
                                    @if($isLoggedIn && $userReferral)
                                        {{ __('services.referral_card.marketing_fee_option') }}
                                    @endif
                                </p>
                            </div>
                        @endif
                        {{-- ============ ALERTS ============ --}}
                        @if($isOwnService)
                            <div class="alert alert-warning mb-3">
                                <i class="ti ti-alert-triangle me-2"></i>
                                {{ __('You cannot order your own service') }}
                            </div>
                            <a href="{{ route('services.edit', $service) }}" class="at-btn at-btn-ghost w-100 mb-3">
                                <i class="ti ti-edit me-2"></i>{{ __('Edit service') }}
                            </a>
                            <p class="small text-muted mb-0">{{ __('services.referral_card.owner_commission_source') }}</p>
                        @endif
                        {{-- ============ ORDER FORM ============ --}}
                        @if(!$isOwnService)
                            <form id="orderForm" action="{{ route('services.make-order', $service) }}" method="POST">
                                @csrf
                                @if(!$isLoggedIn && session('referral'))
                                    <input type="hidden" name="referral" value="{{ session('referral') }}">
                                @endif
                                @if($service->extras->count() > 0)
                                    <div class="extras-selection mb-3 py-3 border-top border-bottom">
                                        <h6 class="mb-3 fw-bold">{{ __('Add Extras') }}</h6>
                                        @foreach($service->extras as $extra)
                                            <div class="form-check d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <input class="form-check-input border-secondary extra-checkbox" type="checkbox" name="extras[]" value="{{ $extra->id }}" id="order_extra_{{ $extra->id }}" data-price="{{ $extra->current_price() }}">
                                                    <label class="form-check-label ms-1" for="order_extra_{{ $extra->id }}" style="cursor: pointer;">
                                                        {{ $extra->title }}
                                                    </label>
                                                </div>
                                                <span class="text-dark fw-bold">+{{ $extra->current_price_str() }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                {{-- Guest User Fields --}}
                                @if(!$isLoggedIn)
                                    <x-guest-register-fields />
                                @endif
                                <input type="hidden" name="qty" value="1">
                                <input type="hidden" name="package" id="selectedPackage" value="{{ $service->hasPackage('basic') ? 'basic' : ($service->hasPackage('standard') ? 'standard' : 'premium') }}">
                                <input type="hidden" name="use_balance" id="useBalanceInput" value="0">
                                {{-- Requirements Field --}}
                                <div class="requirements-section mb-3 py-3 border-top">
                                    <label for="requirements" class="form-label fw-bold">
                                        {{ __('Describe your requirements') }} <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="requirements" name="requirements" rows="4" placeholder="{{ __('Please describe what you need, deadlines, and any specific requirements...') }}" required minlength="20"></textarea>
                                    <small class="text-muted">{{ __('Minimum 20 characters') }}</small>
                                </div>
                                @if($isLoggedIn)
                                    {{-- Payment Method Selection --}}
                                    <div class="payment-method-section mb-3 py-3 border-top">
                                        <h6 class="mb-3 fw-bold">{{ __('payment.payment_method') }}</h6>
                                        @php
                                            $userBalance = $user->available_balance();
                                            $servicePrice = $service->current_price();
                                            $hasSufficientBalance = $userBalance >= $servicePrice;
                                        @endphp
                                        <div class="mb-2">
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input payment-method-radio" name="payment_method" value="balance" {{ $hasSufficientBalance ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    {{ __('Use Balance') }} ({{ \App\Helpers\FinanceHelper::instance()->format_money($userBalance, $user->currency) }})
                                                </span>
                                            </label>
                                        </div>
                                        @if(!$hasSufficientBalance)
                                            <div class="alert alert-warning small py-2">
                                                <i class="ti ti-alert-triangle me-1"></i>
                                                {{ __('Insufficient balance for this order') }}
                                            </div>
                                        @endif
                                        <div class="mb-2">
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input payment-method-radio" name="payment_method" value="card" {{ !$hasSufficientBalance ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    {{ __('Credit/Debit Card') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                                {{-- Submit Button --}}
                                <button type="submit" class="at-btn at-btn-primary w-100" id="submitOrderBtn">
                                    <i class="ti ti-shopping-cart me-2"></i>
                                    @if($service->isFree())
                                        {{ __('services.free.get_for_free') }}
                                    @else
                                        {{ __('Continue') }}
                                    @endif
                                </button>
                            </form>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- ============ REVIEWS SECTION ============ --}}
        <div class="reviews-section mt-5">
            <h3 class="section-title">{{ __('Customer Reviews') }}</h3>
            @if($service->reviews->isNotEmpty())
                <div class="reviews-summary mb-4">
                    <div class="review-stats d-flex align-items-center gap-4">
                        <div class="rating-display">
                            <div class="rating-number h2 mb-0">{{ $displayRating }}</div>
                            <div class="rating-stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="ti ti-star {{ $i <= round($displayRating) ? 'filled' : '' }}"></i>
                                @endfor
                            </div>
                            <div class="rating-count text-muted">{{ $reviewCount }} {{ __('reviews') }}</div>
                        </div>
                        <div class="rating-bars flex-grow-1">
                            @for($i = 5; $i >= 1; $i--)
                                <div class="rating-bar d-flex align-items-center gap-2">
                                    <span class="rating-label">{{ $i }}</span>
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: {{ $service->getRatingPercentage($i) }}%"></div>
                                    </div>
                                    <span class="rating-percentage">{{ $service->getRatingPercentage($i) }}%</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="reviews-list">
                    @foreach($service->reviews->take(10) as $review)
                        <div class="review-item card border-0 shadow-sm mb-3 rounded-3">
                            <div class="card-body p-4">
                                <div class="review-header d-flex justify-content-between align-items-start mb-3">
                                    <div class="reviewer-info d-flex align-items-center gap-3">
                                        <img alt="{{ $review->user->name }}" src="{{ $review->user->profile_photo_url }}" class="reviewer-avatar">
                                        <div>
                                            <h6 class="reviewer-name mb-0">{{ $review->user->name }}</h6>
                                            <div class="review-rating">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="ti ti-star {{ $i <= $review->rating ? 'filled' : '' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-meta">
                                        <div class="review-date text-muted small">{{ $review->created_at->format('M j, Y') }}</div>
                                        @if($review->package)
                                            <div class="review-package badge bg-light text-dark small">{{ ucfirst($review->package) }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="review-content">
                                    <p class="review-text mb-3">{{ $review->comment }}</p>
                                    @if($review->reply)
                                        <div class="review-reply bg-light p-3 rounded-3">
                                            <div class="reply-header d-flex align-items-center gap-2 mb-2">
                                                <img alt="{{ $service->user->name }}" src="{{ $service->user->profile_photo_url }}" class="reply-avatar">
                                                <div>
                                                    <h6 class="reply-name mb-0">{{ $service->user->name }}</h6>
                                                    <span class="reply-badge badge bg-primary small">{{ __('Seller Reply') }}</span>
                                                </div>
                                            </div>
                                            <p class="reply-text mb-0">{{ $review->reply }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($service->reviews->count() > 10)
                    <div class="text-center mt-4">
                        <a href="{{ route('services.show', $service) }}#reviews" class="at-btn at-btn-ghost">
                            {{ __('View all reviews') }} ({{ $service->reviews->count() }})
                        </a>
                    </div>
                @endif
            @else
                <div class="no-reviews text-center py-5">
                    <i class="ti ti-message-circle-off text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">{{ __('No reviews yet') }}</h5>
                    <p class="text-muted">{{ __('Be the first to review this service') }}</p>
                </div>
            @endif
        </div>
        {{-- ============ FREE SERVICE DOWNLOAD GATE ============ --}}
        @if($service->isFree() && $service->files->isNotEmpty())
            <x-services.free-download-gate :service="$service" :hasUnlocked="$hasUnlocked" />
        @endif

        {{-- ============ SIMILAR SERVICES ============ --}}
        @if($relatedServices && $relatedServices->isNotEmpty())
            <div class="similar-services-section mt-5">
                <h3 class="section-title">{{ __('Similar Services') }}</h3>
                <div class="row">
                    @foreach($relatedServices->take(6) as $similarService)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="service-card card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-img-top position-relative overflow-hidden">
                                    <img alt="{{ $similarService->title }}" src="{{ asset($similarService->image) }}" class="w-100" style="height: 200px; object-fit: cover;">
                                    @if($similarService->reviews_count > 0)
                                        <div class="position-absolute top-2 end-2 bg-white rounded-2 px-2 py-1 shadow-sm">
                                            <i class="ti ti-star-filled text-warning small"></i>
                                            <span class="small fw-bold">{{ $similarService->average_rating }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body p-3">
                                    <h5 class="card-title h6 mb-2">
                                        <a href="{{ route('services.show', $similarService) }}" class="text-decoration-none text-dark">
                                            {{ Str::limit($similarService->title, 50) }}
                                        </a>
                                    </h5>
                                    <div class="seller-info small text-muted mb-2">
                                        <img alt="{{ $similarService->user->name }}" src="{{ $similarService->user->profile_photo_url }}" class="rounded-circle" style="width: 20px; height: 20px; object-fit: cover;">
                                        {{ $similarService->user->name }}
                                    </div>
                                    <div class="price-info d-flex justify-content-between align-items-center">
                                        <div class="price fw-bold">{{ $similarService->current_price_str() }}</div>
                                        <div class="delivery small text-muted">
                                            <i class="ti ti-clock"></i>
                                            {{ $similarService->delivery_days ?? '3' }} {{ __('days') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        {{-- ============ SHARE MODAL ============ --}}
        <div class="share-modal" id="shareModal">
            <div class="loading-overlay-share" id="shareLoading">
                <div class="share-spinner"></div>
                <span>{{ __('Generating your performance card...') }}</span>
            </div>
            <div class="share-modal-content" id="shareModalContent" style="display: none;">
                <button class="share-modal-close" id="closeShareModal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
                <h4 class="mb-3">{{ __('Your Performance Card') }}</h4>
                <div id="shareCanvasPreviewContainer"></div>
                <div class="share-actions mt-3">
                    <button class="btn-share-final btn-share-native" id="nativeShareBtn" style="display: none;" aria-label="Share">
                        <i class="ti ti-share"></i> {{ __('Share') }}
                    </button>
                    <button class="btn-share-final btn-share-download" id="downloadShareBtn" aria-label="Download">
                        <i class="ti ti-download"></i> {{ __('Download Image') }}
                    </button>
                </div>
            </div>
            <div id="shareCardTemplateContainer" style="position: absolute; left: -9999px; top: -9999px;">
                <div id="serviceShareCard" style="width: 800px; height: 500px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 40px; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden;">
                    <!-- Background Decoration -->
                    <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: rgba(29, 191, 115, 0.05); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -50px; left: -50px; width: 200px; height: 200px; background: rgba(59, 130, 246, 0.05); border-radius: 50%;"></div>
                    <!-- Header -->
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                            <img src="{{ asset($service->user->profile_photo_url) }}" alt="{{ $service->user->name }}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.1);">
                            <div>
                                <h2 style="margin: 0; font-size: 24px; font-weight: 600;">{{ $service->user->name }}</h2>
                                <p style="margin: 0; opacity: 0.8; font-size: 14px;">{{ __('Professional Service Provider') }}</p>
                            </div>
                        </div>
                        <h1 style="margin: 0; font-size: 32px; font-weight: 700; line-height: 1.2; margin-bottom: 20px;">{{ $service->title }}</h1>
                        <p style="margin: 0; opacity: 0.9; font-size: 16px; line-height: 1.5;">{{ Str::limit(strip_tags($service->description_str()), 150) }}</p>
                    </div>
                    <!-- Stats -->
                    <div style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="display: flex; gap: 2px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="ti ti-star" style="color: {{ $i <= round($displayRating) ? '#fbbf24' : 'rgba(255,255,255,0.2)' }}; font-size: 16px;"></i>
                                    @endfor
                                </div>
                                <span style="font-weight: 600;">{{ $displayRating }}</span>
                                <span style="opacity: 0.7;">({{ $reviewCount }} {{ __('reviews') }})</span>
                            </div>
                            <div style="font-size: 28px; font-weight: 700; color: #10b981;">{{ $service->current_price_str() }}</div>
                            <div style="opacity: 0.7; font-size: 14px;">{{ __('Starting from') }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="opacity: 0.7; font-size: 12px; margin-bottom: 5px;">{{ __('Available on') }}</div>
                            <div style="font-size: 18px; font-weight: 600;">{{ config('app.name') }}</div>
                            <div style="opacity: 0.5; font-size: 12px;">{{ route('services.show', $service) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
{{-- ============ JAVASCRIPT FUNCTIONALITY ============ --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============ GALLERY FUNCTIONALITY ============
    function initGallery() {
        const mainImage = document.getElementById('mainGalleryImage');
        const thumbs = document.querySelectorAll('.gallery-thumb');
        const galleryItems = document.querySelectorAll('.gallery-item');
        const galleryThumbs = document.getElementById('portfolioThumbnails');
        const prevBtn = document.getElementById('galleryPrev');
        const nextBtn = document.getElementById('galleryNext');
        const gridViewBtn = document.getElementById('gridViewBtn');
        const carouselViewBtn = document.getElementById('carouselViewBtn');
        let currentIndex = 0;
        let isCarouselView = false;
        if (!galleryItems.length) return;
        // Gallery navigation functions
        function showGalleryItem(index) {
            galleryItems.forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });
            if (galleryThumbs) {
                const thumbElements = galleryThumbs.querySelectorAll('.gallery-thumb');
                thumbElements.forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
            }
            currentIndex = index;
        }
        function nextGalleryItem() {
            currentIndex = (currentIndex + 1) % galleryItems.length;
            showGalleryItem(currentIndex);
        }
        function prevGalleryItem() {
            currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
            showGalleryItem(currentIndex);
        }
        // Event listeners
        if (prevBtn) prevBtn.addEventListener('click', prevGalleryItem);
        if (nextBtn) nextBtn.addEventListener('click', nextGalleryItem);
        if (galleryThumbs) {
            const thumbElements = galleryThumbs.querySelectorAll('.gallery-thumb');
            thumbElements.forEach((thumb, index) => {
                thumb.addEventListener('click', () => showGalleryItem(index));
            });
        }
        // View toggle
        function toggleGalleryView(view) {
            isCarouselView = view === 'carousel';
            const galleryMain = document.getElementById('portfolioGalleryMain');
            if (galleryMain) {
                galleryMain.classList.toggle('carousel-view', isCarouselView);
            }
            if (gridViewBtn) gridViewBtn.classList.toggle('active', !isCarouselView);
            if (carouselViewBtn) carouselViewBtn.classList.toggle('active', isCarouselView);
        }
        if (gridViewBtn) gridViewBtn.addEventListener('click', () => toggleGalleryView('grid'));
        if (carouselViewBtn) carouselViewBtn.addEventListener('click', () => toggleGalleryView('carousel'));
        // Auto-advance carousel
        setInterval(() => {
            if (isCarouselView) {
                nextGalleryItem();
            }
        }, 5000);
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                prevGalleryItem();
            } else if (e.key === 'ArrowRight') {
                nextGalleryItem();
            }
        });
        // Remove skeleton shimmer when images load
        const galleryContainer = document.querySelector('.gallery-main-container');
        const galleryImages = document.querySelectorAll('.gallery-main img, .gallery-main iframe');
        let imagesLoaded = 0;
        function removeSkeletonShimmer() {
            if (galleryContainer) {
                galleryContainer.classList.remove('skeleton-shimmer');
            }
        }
        galleryImages.forEach(img => {
            if (img.tagName === 'IMG') {
                img.addEventListener('load', function() {
                    imagesLoaded++;
                    if (imagesLoaded === 1) removeSkeletonShimmer();
                });
                img.addEventListener('error', function() {
                    imagesLoaded++;
                    if (imagesLoaded === 1) removeSkeletonShimmer();
                });
                if (img.complete) {
                    imagesLoaded++;
                    if (imagesLoaded === 1) removeSkeletonShimmer();
                }
            } else if (img.tagName === 'IFRAME') {
                setTimeout(() => {
                    imagesLoaded++;
                    if (imagesLoaded === 1) removeSkeletonShimmer();
                }, 1000);
            }
        });
    }
    // ============ PACKAGE TABS FUNCTIONALITY ============
    function initPackageTabs() {
        const packageTabs = document.querySelectorAll('.package-tab');
        const packagePanes = document.querySelectorAll('.tab-pane');
        const totalPriceElement = document.getElementById('totalPrice');
        let currentPackagePrice = {{ $service->getPackagePrice('standard') }};
        let extrasTotal = 0;
        if (!packageTabs.length) return;
        // Package tabs functionality
        packageTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                // Update active tab
                packageTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                // Update selected package hidden input
                const selectedPackageInput = document.getElementById('selectedPackage');
                if (selectedPackageInput) {
                    selectedPackageInput.value = this.dataset.tab;
                }
                // Update active pane
                const targetTab = this.dataset.tab;
                packagePanes.forEach(pane => {
                    pane.classList.remove('active');
                    if (pane.id === `${targetTab}-package`) {
                        pane.classList.add('active');
                    }
                });
                // Update price
                currentPackagePrice = parseFloat(this.dataset.price);
                updateTotalPrice();
            });
        });
        // Update total price function
        function updateTotalPrice() {
            const total = currentPackagePrice + extrasTotal;
            if (totalPriceElement) {
                totalPriceElement.textContent = formatMoney(total);
            }
        }
        // Format money function
        function formatMoney(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: '{{ $service->currency }}'
            }).format(amount);
        }
        // Handle extras checkboxes
        const extraCheckboxes = document.querySelectorAll('.extra-checkbox');
        extraCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                extrasTotal = 0;
                extraCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        extrasTotal += parseFloat(cb.dataset.price);
                    }
                });
                updateTotalPrice();
            });
        });
    }
    // ============ REFERRAL LINK COPY ============
    function initReferralCopy() {
        const serviceRefCopyBtn = document.getElementById('serviceRefCopyBtn');
        if (serviceRefCopyBtn) {
            serviceRefCopyBtn.addEventListener('click', function () {
                const inp = document.getElementById('serviceRefLink');
                if (inp && navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(inp.value).then(function () {
                        const span = serviceRefCopyBtn.querySelector('.btn-copy-text');
                        if (span) {
                            span.textContent = serviceRefCopyBtn.getAttribute('data-copied') || 'Copied!';
                            setTimeout(function () { 
                                span.textContent = '{{ __("services.referral_card.copy") }}'; 
                            }, 2000);
                        }
                    });
                }
            });
        }
    }
    // ============ SHARE CARD FUNCTIONALITY ============
    function initShareCard() {
        const shareTrigger = document.getElementById('shareCardTrigger');
        if (shareTrigger) {
            shareTrigger.addEventListener('click', async function () {
                const modal = document.getElementById('shareModal');
                const loading = document.getElementById('shareLoading');
                const content = document.getElementById('shareModalContent');
                const previewContainer = document.getElementById('shareCanvasPreviewContainer');
                const nativeBtn = document.getElementById('nativeShareBtn');
                const downloadBtn = document.getElementById('downloadShareBtn');
                modal.classList.add('active');
                loading.classList.add('active');
                content.style.display = 'none';
                previewContainer.innerHTML = '';
                try {
                    const cardTemplate = document.getElementById('serviceShareCard');
                    // Use html2canvas to capture
                    const canvas = await html2canvas(cardTemplate, {
                        useCORS: true,
                        scale: 2, // Higher quality
                        backgroundColor: '#0f172a'
                    });
                    const imageData = canvas.toDataURL('image/png');
                    const previewImg = document.createElement('img');
                    previewImg.src = imageData;
                    previewContainer.appendChild(previewImg);
                    loading.classList.remove('active');
                    content.style.display = 'block';
                    // NATIVE SHARE
                    nativeBtn.onclick = async () => {
                        try {
                            const blob = await (await fetch(imageData)).blob();
                            const file = new File([blob], 'musoftware-performance-{{ $service->id }}.png', { type: 'image/png' });
                            try {
                                await navigator.share({
                                    title: '{{ $service->title }}',
                                    text: '{{ __("Check out my professional performance on Musoftware!") }}',
                                    files: [file]
                                });
                            } catch (err) {
                                console.error('Share failed:', err);
                            }
                        };
                    };
                    // DOWNLOAD
                    downloadBtn.onclick = () => {
                        const link = document.createElement('a');
                        link.download = 'musoftware-performance-{{ $service->id }}.png';
                        link.href = imageData;
                        link.click();
                    };
                } catch (err) {
                    console.error('Canvas capture failed:', err);
                    alert('{{ __("Failed to generate share card. Please try again.") }}');
                    modal.classList.remove('active');
                }
            });
        }
        // Close modal handlers
        document.getElementById('closeShareModal')?.addEventListener('click', () => {
            document.getElementById('shareModal').classList.remove('active');
        });
    }
    // ============ UTILITY FUNCTIONS ============
    // Toggle reply form visibility
    window.toggleReplyForm = function (reviewId) {
        const form = document.getElementById('reply-form-' + reviewId);
        if (form) {
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    };
    // Initialize all functionality
    initGallery();
    initPackageTabs();
    initReferralCopy();
    initShareCard();
});
</script>
@endpush
{{-- ============ STYLES ============ --}}
@push('styles')
<style>
/* TODO: Move these to main SASS file */
.gallery-main-container.skeleton-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.share-modal.active {
    display: flex;
}
.loading-overlay-share {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}
.share-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.share-modal-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}
.share-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.btn-share-final {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin: 5px;
    transition: all 0.3s ease;
}
.btn-share-native {
    background: #007bff;
    color: white;
}
.btn-share-download {
    background: #28a745;
    color: white;
}
.btn-share-final:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
/* TODO: Extract hardcoded colors to SASS variables */
.portfolio-gallery {
    margin-bottom: 2rem;
}
.gallery-view-toggle {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}
.view-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}
.view-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
.carousel-view .gallery-main {
    display: flex;
    overflow: hidden;
}
.carousel-view .gallery-item {
    min-width: 100%;
    transition: transform 0.5s ease;
}
/* TODO: Magic numbers - convert to CSS variables */
.order-box-wrapper {
    margin-bottom: 2rem;
}
.package-tabs {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.package-tab {
    padding: 1rem;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}
.package-tab.active {
    background: white;
    border-bottom: 3px solid #007bff;
    color: #007bff;
}
.package-tab:hover:not(.active) {
    background: #e9ecef;
}
.referral-service-card {
    /* TODO: Extract gradient to SASS variable */
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}
/* Mobile responsive improvements */
@media (max-width: 767px) {
    .order-box-wrapper.sticky-top {
        position: static;
    }
    .package-tabs {
        flex-direction: column;
    }
    .package-tab {
        border-bottom: 1px solid #dee2e6;
    }
    .package-tab.active {
        border-bottom: 3px solid #007bff;
    }
}
</style>
@endpush
