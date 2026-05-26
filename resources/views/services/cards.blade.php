@extends('layouts.app')

@section('title', __('Services'))

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix services-cards-page">
        <x-client.section-header
            icon="ti ti-briefcase"
            title="{{ __('user.profile.actions.explore_services') }}"
            subtitle="{{ __('Discover professional services from our community') }}">
            <div class="d-flex align-items-center gap-3 w-100 flex-wrap flex-md-nowrap">
                <x-ui.search-input 
                    :categories="$categories ?? []"
                    placeholder="{{ __('Quick search...') }}"
                    class="flex-grow-1"
                />
                @auth
                    <a href="{{ route('services.create') }}" class="at-btn at-btn-primary at-btn-sm text-nowrap">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>{{ __('Add Service') }}
                    </a>
                @endauth
            </div>
        </x-client.section-header>

        <div class="services-filter-bar mb-4">
            <form action="{{ route('services.index') }}" method="GET" class="at-card p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="filter-group">
                        <label class="small fw-bold text-muted mb-1 d-block">{{ __('Price Range') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" class="at-form-control at-form-control-sm" style="width: 80px;">
                            <span class="text-muted">-</span>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" class="at-form-control at-form-control-sm" style="width: 80px;">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label class="small fw-bold text-muted mb-1 d-block">{{ __('Sort By') }}</label>
                        <select name="sort" class="at-form-control at-form-control-sm" onchange="this.form.submit()">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                            <option value="top_rated" {{ request('sort') == 'top_rated' ? 'selected' : '' }}>{{ __('Top Rated') }}</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 mt-auto">
                    @if(request()->hasAny(['min_price', 'max_price', 'sort', 'q', 'category']))
                        <a href="{{ route('services.index') }}" class="at-btn at-btn-ghost at-btn-sm text-danger">
                            <i class="ti ti-x me-1"></i>{{ __('Clear') }}
                        </a>
                    @endif
                    <button type="submit" class="at-btn at-btn-primary at-btn-sm">
                        <i class="ti ti-filter me-1"></i>{{ __('Apply') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="services-cards-content">
        @if($services->isNotEmpty())
        <div class="services-cards-grid row g-4">
            @foreach($services as $service)
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <article class="at-card service-card h-100 transition-all hover-lift">
                        <div class="card-img-wrapper position-relative overflow-hidden">
                            <a href="{{ route('services.show', $service) }}">
                                <img alt="{{ $service->title }}" src="{{ asset($service->image) }}" class="card-img-top service-card-img" width="400" height="224" loading="lazy">
                            </a>
                            <div class="service-card-badge position-absolute top-0 end-0 m-2">
                                <span class="at-badge at-badge-dark backdrop-blur">
                                    <i class="ti ti-tag me-1"></i>{{ $service->serviceCategory->name ?? 'Service' }}
                                </span>
                            </div>
                        </div>
                        <div class="at-card-body d-flex flex-column p-3">
                            <h2 class="service-card-title mb-2 line-clamp-2" style="height: 3rem;">
                                <a href="{{ route('services.show', $service) }}" class="text-dark text-decoration-none hover-primary"><bdi>{{ $service->title }}</bdi></a>
                            </h2>
                            <div class="service-card-meta d-flex align-items-center justify-content-between mb-3">
                                <a href="{{ route('user.link', $service->user->slug) }}" class="service-card-provider d-flex align-items-center text-muted text-decoration-none small">
                                    <div class="provider-avatar me-2 rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                        <i class="ti ti-user small"></i>
                                    </div>
                                    <span>{{ ucwords($service->user->name) }}</span>
                                </a>
                                
                                @php
                                    $avgRating = round($service->average_rating, 1);
                                    $reviewCount = $service->reviews_count;
                                @endphp
                                <div class="d-flex align-items-center service-card-rating text-warning">
                                    <i class="ti ti-star-filled me-1"></i>
                                    <span class="fw-bold small text-dark">{{ $reviewCount > 0 ? $avgRating : 'New' }}</span>
                                    @if($reviewCount > 0)
                                        <span class="text-muted smaller ms-1">({{ $reviewCount }})</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="service-card-footer mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <div class="price-container">
                                    <span class="text-muted smaller d-block">{{ __('Starting from') }}</span>
                                    @if($service->isFree())
                                        <span class="service-card-price fw-bold text-success fs-5">{{ __('services.free.price_free') }}</span>
                                    @else
                                        <span class="service-card-price fw-bold text-dark fs-5">{{ $service->current_price_str() }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('services.show', $service) }}" class="at-btn at-btn-primary at-btn-sm rounded-pill px-3">
                                    {{ __('Details') }} <i class="ti ti-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        @else
            <div class="services-cards-empty">
                <div class="services-cards-empty-icon-wrap">
                    <i class="ti ti-search-off services-cards-empty-icon" aria-hidden="true"></i>
                </div>
                <h2 class="services-cards-empty-title">{{ __('No services found') }}</h2>
                <p class="services-cards-empty-text">{{ __('Try a different search or category.') }}</p>
            </div>
        @endif
        </div>

        @if($services->hasPages())
            <div class="services-cards-pagination">
                {{ $services->links() }}
            </div>
        @endif

        @guest
            <div class="at-card services-cards-guest-cta">
                <i class="ti ti-rocket guest-cta-icon d-block" aria-hidden="true"></i>
                <h3 class="guest-cta-title">{{ __('Ready to Showcase Your Services?') }}</h3>
                <p class="guest-cta-text">{{ __('Join our community today to start offering your services to a wider audience.') }}</p>
                <div class="guest-cta-actions">
                    <a href="{{ route('login') }}" class="at-btn at-btn-ghost">
                        <i class="ti ti-login me-2" aria-hidden="true"></i>{{ __('auth.sign_in') }}
                    </a>
                    <a href="{{ route('register') }}" class="at-btn at-btn-primary">
                        <i class="ti ti-user-plus me-2" aria-hidden="true"></i>{{ __('auth.sign_up') }}
                    </a>
                    <a href="{{ route('services.create') }}" class="at-btn at-btn-ghost">
                        <i class="ti ti-plus me-2" aria-hidden="true"></i>{{ __('Add New Service') }}
                    </a>
                </div>
            </div>
        @endguest
    </div>
@endsection