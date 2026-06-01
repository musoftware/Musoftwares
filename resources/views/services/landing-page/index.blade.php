@extends('layouts.app')

@section('title', 'My Landing Pages')

@section('content')
<div class="dashboard-container at-mobile-scroll-fix container py-5 services-landing-index-page">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">
                        <i class="fas fa-pager text-primary me-2"></i>{{ __('general.my_service_landing_pages') }}</h1>
                    <p class="text-muted">{{ __('general.manage_and_view_all_your_service_landing_pages') }}</p>
                </div>
                <a href="{{ route('services.mine') }}" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-1"></i>{{ __('general.create_new_landing_page') }}</a>
            </div>
        </div>
    </div>

    @if($servicesWithLandingPages->count() > 0)
        <div class="row">
            @foreach($servicesWithLandingPages as $service)
                @php
                    $landingPage = $service->landingPage;
                    $submissionsCount = $landingPage->formSubmissions->count();
                    $variantsCount = $landingPage->variants->count();
                @endphp
                
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0 hover-lift">
                        <div class="card-body">
                            <!-- Service Title  -->
                            <h5 class="card-title mb-2">
                                <i class="fas fa-rocket text-primary me-1"></i>
                                {{ $service->title }}
                            </h5>
                            
                            <!-- Landing Page Title -->
                            <p class="text-muted small mb-3">
                                <i class="fas fa-heading me-1"></i>
                                {{ $landingPage->hero_title }}
                            </p>

                            <!-- Status Badge -->
                            <div class="mb-3">
                                @if($landingPage->is_active)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-pause-circle me-1"></i> Inactive
                                    </span>
                                @endif
                                
                                @if($landingPage->ab_testing_enabled)
                                    <span class="badge bg-info">
                                        <i class="fas fa-flask me-1"></i>{{ __('general.a_b_testing') }}</span>
                                @endif
                            </div>

                            <!-- Statistics -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="stat-box">
                                        <i class="fas fa-envelope text-primary"></i>
                                        <div class="fw-bold">{{ $submissionsCount }}</div>
                                        <small class="text-muted">Submissions</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <i class="fas fa-code-branch text-info"></i>
                                        <div class="fw-bold">{{ $variantsCount }}</div>
                                        <small class="text-muted">Variants</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <i class="fas fa-palette text-success"></i>
                                        <div class="fw-bold text-capitalize">{{ $landingPage->template }}</div>
                                        <small class="text-muted">Template</small>
                                    </div>
                                </div>
                            </div>

                            <!-- URL -->
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    <input type="text" class="form-control" 
                                           value="{{ route('services.landing-page.show', $landingPage->slug) }}" 
                                           readonly 
                                           id="url-{{ $landingPage->id }}">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyToClipboard('url-{{ $landingPage->id }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <a href="{{ route('services.landing-page.show', $landingPage->slug) }}" 
                                   class="btn btn-sm btn-outline-primary" 
                                   target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-external-link-alt me-1"></i>{{ __('general.view_landing_page') }}</a>
                                <a href="{{ route('services.landing-page.edit', $service) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i>{{ __('general.edit_landing_page') }}</a>
                                @if($submissionsCount > 0)
                                    <a href="{{ route('services.landing-page.submissions', $service) }}" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-inbox me-1"></i> View Submissions ({{ $submissionsCount }})
                                    </a>
                                @endif
                                
                                {{-- Analytics/Tracking Button --}}
                                <a href="{{ route('services.landing-page.analytics', $service) }}" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-chart-line me-1"></i>{{ __('general.view_analytics') }}</a>
                                
                                @if($variantsCount > 0)
                                    <button class="btn btn-sm btn-info" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#variants-{{ $landingPage->id }}" 
                                            aria-expanded="false">
                                        <i class="fas fa-code-branch me-1"></i> View Variants ({{ $variantsCount }})
                                    </button>
                                @endif
                            </div>
                            
                            <!-- Variants Collapsible Section -->
                            @if($variantsCount > 0)
                                <div class="collapse mt-3" id="variants-{{ $landingPage->id }}">
                                    <div class="variants-list">
                                        <h6 class="fw-bold mb-2 text-primary">
                                            <i class="fas fa-flask me-1"></i>{{ __('general.a_b_test_variants') }}</h6>
                                        <div class="list-group list-group-flush">
                                            @foreach($landingPage->variants as $variant)
                                                <div class="list-group-item px-0 py-2">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1">
                                                            <strong class="d-block">
                                                                <i class="fas fa-copy me-1 text-muted"></i>
                                                                Variant {{ $variant->variant_name ?? 'N/A' }}
                                                            </strong>
                                                            <small class="text-muted d-block mt-1">
                                                                {{ Str::limit($variant->hero_title, 50) }}
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            @if($variant->is_active)
                                                                <span class="badge bg-success mb-1">Active</span>
                                                            @else
                                                                <span class="badge bg-secondary mb-1">Inactive</span>
                                                            @endif
                                                            @if($variant->is_winner)
                                                                <span class="badge bg-warning text-dark mb-1">
                                                                    <i class="fas fa-trophy"></i> Winner
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-percentage me-1"></i>
                                                            Traffic: {{ $variant->traffic_split_percentage ?? 50 }}%
                                                        </small>
                                                        <div class="btn-group btn-group-sm">
                                                            @if($variant->is_active)
                                                                <a href="{{ route('services.landing-page.show', $variant->slug) }}" 
                                                                   class="btn btn-outline-success btn-sm" 
                                                                   target="_blank" rel="noopener noreferrer"
                                                                   title="{{ __('general.view_this_variant') }}">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            @endif
                                                            <a href="{{ route('services.landing-page.edit', ['service' => $service, 'landingPage' => $variant]) }}" 
                                                               class="btn btn-outline-primary btn-sm"
                                                               title="{{ __('general.edit_this_variant') }}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-light text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            Last updated: {{ $landingPage->updated_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="row mt-4">
            <div class="col-12">
                {{ $servicesWithLandingPages->links() }}
            </div>
        </div>

    @else
        <!-- Empty State -->
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-pager fa-4x text-muted mb-3"></i>
                    <h3>{{ __('general.no_landing_pages_yet') }}</h3>
                    <p class="text-muted mb-4">{{ __('general.start_creating_beautiful_landing_pages_for_your_services_to_convert_more_visitors_into_customers') }}</p>
                    <a href="{{ route('services.mine') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>{{ __('general.create_your_first_landing_page') }}</a>
                </div>
            </div>
        </div>
    @endif
</div>



<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        // Show feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    }
</script>
@endsection
