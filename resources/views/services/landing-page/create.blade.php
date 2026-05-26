@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $templates = [
        'Professional' => [
            ['id' => 'modern', 'name' => 'Modern', 'desc' => 'Clean & Minimal', 'icon' => 'ti-layout-board'],
            ['id' => 'business', 'name' => 'Business', 'desc' => 'Professional & Corporate', 'icon' => 'ti-briefcase'],
            ['id' => 'minimal', 'name' => 'Minimal', 'desc' => 'Ultra Clean & Simple', 'icon' => 'ti-minimize'],
            ['id' => 'dashboard', 'name' => 'Dashboard', 'desc' => 'Data-Driven', 'icon' => 'ti-dashboard'],
        ],
        'Creative' => [
            ['id' => 'creative', 'name' => 'Creative', 'desc' => 'Bold & Vibrant', 'icon' => 'ti-brush'],
            ['id' => 'glassmorphism', 'name' => 'Glass', 'desc' => 'Frosted Glass Effect', 'icon' => 'ti-blur'],
            ['id' => 'neumorphism', 'name' => 'Neu', 'desc' => 'Soft Shadows', 'icon' => 'ti-box-model-2'],
            ['id' => 'skeuomorphism', 'name' => 'Skeuo', 'desc' => 'Realistic 3D', 'icon' => 'ti-3d-cube-sphere'],
            ['id' => 'flat-design', 'name' => 'Flat', 'desc' => 'Simple & Colorful', 'icon' => 'ti-palette'],
            ['id' => 'material-design', 'name' => 'Material', 'desc' => "Google's Design", 'icon' => 'ti-brand-google'],
            ['id' => 'fluent-design', 'name' => 'Fluent', 'desc' => "Microsoft's Design", 'icon' => 'ti-brand-windows'],
        ],
        'Product' => [
            ['id' => 'product', 'name' => 'Product', 'desc' => 'Showcase Focus', 'icon' => 'ti-package'],
            ['id' => 'ecommerce', 'name' => 'Shop', 'desc' => 'Retail Focus', 'icon' => 'ti-shopping-cart'],
        ],
        'Themed' => [
            ['id' => 'cyberpunk', 'name' => 'Cyberpunk', 'desc' => 'Neon & Futuristic', 'icon' => 'ti-bulb'],
            ['id' => 'gaming', 'name' => 'Gaming', 'desc' => 'Dynamic', 'icon' => 'ti-device-gamepad'],
            ['id' => 'dark-mode', 'name' => 'Dark', 'desc' => 'High Contrast', 'icon' => 'ti-moon'],
            ['id' => 'brutalism', 'name' => 'Brutal', 'desc' => 'Bold & Raw', 'icon' => 'ti-typography'],
            ['id' => 'retro', 'name' => 'Retro', 'desc' => 'Vintage', 'icon' => 'ti-camera-retro'],
            ['id' => 'pastel', 'name' => 'Pastel', 'desc' => 'Soft Colors', 'icon' => 'ti-color-swatch'],
        ]
    ];
@endphp


@section('content')
    @include('notes.css_header')
    <div class="dashboard-container at-mobile-scroll-fix landing-page-editor">
        <!-- Page Header -->
        <div class="section-header primary">
            <div class="header-content">
                <div class="section-icon primary">
                    <i class="fas fa-file-plus"></i>
                </div>
                <div>
                    <h3 class="section-title">{{ __('Create Landing Page') }}</h3>
                    <p class="section-subtitle">Design a landing page for {{ $service->title }}</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('services.show', $service) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-eye"></i> View Service
                </a>
                <a href="{{ route('services.mine') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card" style="border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div class="card-body" style="padding: 24px;">
                    <div class="alert alert-info border-0 mb-4" style="background: var(--info-light); border-left: 4px solid var(--info-color) !important;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <i class="ti ti-sparkles me-2"></i>
                                <strong>AI-Powered Fill:</strong> Click the button below to use AI to generate optimized landing page content from your service information.
                                <small class="d-block text-muted mt-2">
                                    <i class="ti ti-settings me-1"></i>Using: <strong>{{ strtoupper(auth()->user()->default_ai_model ?? 'gemini') }}</strong>
                                    @if(auth()->user()->default_ai_model === 'openai')
                                        (OpenAI GPT-4o-mini)
                                    @else
                                        (Google Gemini 2.0 Flash)
                                    @endif
                                </small>
                            </div>
                            <button type="button" id="fill-from-service" class="btn btn-primary">
                                <i class="ti ti-sparkles me-1"></i> <span id="fill-button-text">Generate with AI</span>
                                <span id="fill-button-spinner" class="spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('services.landing-page.store', $service) }}" method="POST">
                        @csrf

                        <div class="accordion" id="landingPageAccordion">
                            <!-- Basic Information -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingBasic">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasic" aria-expanded="true" aria-controls="collapseBasic">
                                        <i class="ti ti-settings me-2"></i> Basic Information
                                    </button>
                                </h2>
                                <div id="collapseBasic" class="accordion-collapse collapse show" aria-labelledby="headingBasic" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="mb-3">
                                                <label for="slug" class="form-label">Landing Page URL Slug</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">{{ url('/lp/') }}/</span>
                                                    <input type="text" id="slug" name="slug" required class="form-control"
                                                           value="{{ old('slug', Str::slug($service->title)) }}"
                                                           placeholder="my-service-landing-page">
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="ti ti-link me-1"></i>URL Preview: {{ url('/lp/') }}/<strong id="slug-preview">{{ old('slug', Str::slug($service->title)) }}</strong>
                                                </small>
                                                @error('slug')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_title" class="form-label">Hero Title</label>
                                                <input type="text" id="hero_title" name="hero_title" required class="form-control"
                                                       value="{{ old('hero_title', $service->title) }}"
                                                       placeholder="Enter hero title">
                                                @error('hero_title')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_description" class="form-label">Hero Description</label>
                                                <textarea id="hero_description" name="hero_description" class="form-control" rows="3"
                                                          placeholder="Enter hero description">{{ old('hero_description') }}</textarea>
                                                <small class="text-muted d-block mt-1">A brief description that appears below the hero title</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_cta_text" class="form-label">Hero CTA Button Text</label>
                                                <input type="text" id="hero_cta_text" name="hero_cta_text" class="form-control"
                                                       value="{{ old('hero_cta_text', 'Get Started') }}"
                                                       placeholder="Get Started">
                                                <small class="text-muted d-block mt-1">The text displayed on the call-to-action button</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label d-flex justify-content-between align-items-center">
                                                    <span>Landing Page Template</span>
                                                    <span class="badge bg-light text-primary border" id="selected-template-name">Modern</span>
                                                </label>
                                                
                                                <input type="hidden" id="template" name="template" value="{{ old('template', 'modern') }}" required>
                                                
                                                <div class="template-selector-container bg-light p-3 rounded-3 border">
                                                    <ul class="nav nav-pills mb-3 gap-2" id="pills-tab" role="tablist">
                                                        @foreach($templates as $category => $items)
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                                                        id="pills-{{ Str::slug($category) }}-tab" 
                                                                        data-bs-toggle="pill" 
                                                                        data-bs-target="#pills-{{ Str::slug($category) }}" 
                                                                        type="button" 
                                                                        role="tab" 
                                                                        aria-controls="pills-{{ Str::slug($category) }}" 
                                                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                                    {{ $category }}
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <div class="tab-content" id="pills-tabContent">
                                                        @foreach($templates as $category => $items)
                                                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                                                 id="pills-{{ Str::slug($category) }}" 
                                                                 role="tabpanel" 
                                                                 aria-labelledby="pills-{{ Str::slug($category) }}-tab">
                                                                <div class="row g-3">
                                                                    @foreach($items as $template)
                                                                        <div class="col-6 col-md-4 col-xl-3">
                                                                            <div class="card template-card h-100 shadow-sm {{ old('template', 'modern') == $template['id'] ? 'selected' : '' }}"
                                                                                 onclick="selectTemplate('{{ $template['id'] }}', '{{ $template['name'] }}', this)">
                                                                                <div class="check-icon"><i class="ti ti-check"></i></div>
                                                                                <div class="position-relative">
                                                                                    {{-- Placeholder Image - In production, replace with asset('images/templates/'.$template['id'].'.jpg') --}}
                                                                                    <img alt="{{ $template['name'] }}" src="https://placehold.co/400x300/f1f5f9/64748b?text={{ urlencode($template['name']) }}" 
                                                                                         class="template-preview-img">
                                                                                    <button type="button" class="btn btn-sm btn-light position-absolute bottom-0 end-0 m-2 opacity-75" 
                                                                                            onclick="event.stopPropagation(); showPreview('{{ $template['id'] }}', '{{ $template['name'] }}')"
                                                                                            title="Preview Template">
                                                                                        <i class="ti ti-eye"></i>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="card-body p-3">
                                                                                    <div class="template-icon-badge">
                                                                                        <i class="{{ $template['icon'] }} fs-5"></i>
                                                                                    </div>
                                                                                    <h6 class="card-title fw-bold mb-1">{{ $template['name'] }}</h6>
                                                                                    <p class="card-text text-muted small lh-sm mb-0">{{ $template['desc'] }}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-2">Choose a template style. You can preview larger versions by clicking the eye icon.</small>
                                                @error('template')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingContent">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                                        <i class="ti ti-file-text me-2"></i> Content
                                    </button>
                                </h2>
                                <div id="collapseContent" class="accordion-collapse collapse" aria-labelledby="headingContent" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Service Description</label>
                                                <textarea id="description-editor" name="description" hidden>{{ old('description') }}</textarea>
                                                <small class="text-muted d-block mb-2">
                                                    <i class="ti ti-info-circle me-1"></i>Add detailed description about your service using the rich text editor below.
                                                </small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="description_alignment" class="form-label">Description Text Alignment</label>
                                                <select id="description_alignment" name="description_alignment" class="form-select">
                                                    <option value="left" {{ old('description_alignment', 'left') == 'left' ? 'selected' : '' }}>
                                                        Left Aligned
                                                    </option>
                                                    <option value="center" {{ old('description_alignment') == 'center' ? 'selected' : '' }}>
                                                        Center Aligned
                                                    </option>
                                                    <option value="right" {{ old('description_alignment') == 'right' ? 'selected' : '' }}>
                                                        Right Aligned
                                                    </option>
                                                </select>
                                                <small class="text-muted d-block mt-1">Choose how the description text should be aligned. Note: RTL languages will automatically override this setting.</small>
                                                @error('description_alignment')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSEO">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSEO" aria-expanded="false" aria-controls="collapseSEO">
                                        <i class="ti ti-search me-2"></i> SEO Optimization
                                    </button>
                                </h2>
                                <div id="collapseSEO" class="accordion-collapse collapse" aria-labelledby="headingSEO" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-tag me-2"></i>Basic Meta Tags
                                            </h6>
                                            <div class="mb-3">
                                                <label for="meta_title" class="form-label">
                                                    Meta Title <small class="text-muted">(Recommended: 50-60 characters)</small>
                                                </label>
                                                <input type="text" id="meta_title" name="meta_title" class="form-control"
                                                       value="{{ old('meta_title') }}"
                                                       placeholder="Enter meta title for search engines"
                                                       maxlength="60">
                                                <small class="text-muted d-block mt-1">This appears in search engine results. If left empty, the hero title will be used.</small>
                                                <div class="mt-1">
                                                    <small id="meta_title_length" class="text-muted">0 / 60 characters</small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="meta_description" class="form-label">
                                                    Meta Description <small class="text-muted">(Recommended: 150-160 characters)</small>
                                                </label>
                                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3"
                                                          placeholder="Enter meta description for search engines"
                                                          maxlength="160">{{ old('meta_description') }}</textarea>
                                                <small class="text-muted d-block mt-1">This appears in search engine results. If left empty, the hero description will be used.</small>
                                                <div class="mt-1">
                                                    <small id="meta_description_length" class="text-muted">0 / 160 characters</small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="meta_keywords" class="form-label">
                                                    Meta Keywords <small class="text-muted">(Comma-separated)</small>
                                                </label>
                                                <input type="text" id="meta_keywords" name="meta_keywords" class="form-control"
                                                       value="{{ old('meta_keywords') }}"
                                                       placeholder="keyword1, keyword2, keyword3">
                                                <small class="text-muted d-block mt-1">Enter relevant keywords separated by commas. This is less important for modern SEO but still used by some search engines.</small>
                                            </div>

                                            <hr class="my-4">

                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-brand-facebook me-2"></i>Open Graph Tags (Facebook, LinkedIn, etc.)
                                            </h6>
                                            <div class="mb-3">
                                                <label for="og_title" class="form-label">OG Title</label>
                                                <input type="text" id="og_title" name="og_title" class="form-control"
                                                       value="{{ old('og_title') }}"
                                                       placeholder="Enter Open Graph title">
                                                <small class="text-muted d-block mt-1">If left empty, meta title or hero title will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="og_description" class="form-label">OG Description</label>
                                                <textarea id="og_description" name="og_description" class="form-control" rows="2"
                                                          placeholder="Enter Open Graph description">{{ old('og_description') }}</textarea>
                                                <small class="text-muted d-block mt-1">If left empty, meta description or hero description will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="og_image" class="form-label">OG Image URL</label>
                                                <input type="url" id="og_image" name="og_image" class="form-control"
                                                       value="{{ old('og_image') }}"
                                                       placeholder="https://example.com/image.jpg">
                                                <small class="text-muted d-block mt-1">Full URL to the image that will appear when sharing on social media. Recommended size: 1200x630px.</small>
                                            </div>

                                            <hr class="my-4">

                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-brand-twitter me-2"></i>Twitter Card Tags
                                            </h6>
                                            <div class="mb-3">
                                                <label for="twitter_card_type" class="form-label">Twitter Card Type</label>
                                                <select id="twitter_card_type" name="twitter_card_type" class="form-select">
                                                    <option value="summary" {{ old('twitter_card_type', 'summary_large_image') == 'summary' ? 'selected' : '' }}>Summary</option>
                                                    <option value="summary_large_image" {{ old('twitter_card_type', 'summary_large_image') == 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                                                    <option value="app" {{ old('twitter_card_type') == 'app' ? 'selected' : '' }}>App</option>
                                                    <option value="player" {{ old('twitter_card_type') == 'player' ? 'selected' : '' }}>Player</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_title" class="form-label">Twitter Title</label>
                                                <input type="text" id="twitter_title" name="twitter_title" class="form-control"
                                                       value="{{ old('twitter_title') }}"
                                                       placeholder="Enter Twitter card title">
                                                <small class="text-muted d-block mt-1">If left empty, OG title or meta title will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_description" class="form-label">Twitter Description</label>
                                                <textarea id="twitter_description" name="twitter_description" class="form-control" rows="2"
                                                          placeholder="Enter Twitter card description">{{ old('twitter_description') }}</textarea>
                                                <small class="text-muted d-block mt-1">If left empty, OG description or meta description will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_image" class="form-label">Twitter Image URL</label>
                                                <input type="url" id="twitter_image" name="twitter_image" class="form-control"
                                                       value="{{ old('twitter_image') }}"
                                                       placeholder="https://example.com/image.jpg">
                                                <small class="text-muted d-block mt-1">If left empty, OG image will be used. Recommended size: 1200x675px for large image cards.</small>
                                            </div>

                                            <hr class="my-4">

                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-settings me-2"></i>Additional SEO Settings
                                            </h6>
                                            <div class="mb-3">
                                                <label for="canonical_url" class="form-label">Canonical URL</label>
                                                <input type="url" id="canonical_url" name="canonical_url" class="form-control"
                                                       value="{{ old('canonical_url') }}"
                                                       placeholder="https://example.com/canonical-page">
                                                <small class="text-muted d-block mt-1">Optional. Use this if you want to specify a canonical URL different from the landing page URL.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="robots" class="form-label">Robots Meta Tag</label>
                                                <input type="text" id="robots" name="robots" class="form-control"
                                                       value="{{ old('robots', 'index, follow') }}"
                                                       placeholder="index, follow">
                                                <small class="text-muted d-block mt-1">Control how search engines index this page. Common values: "index, follow", "noindex, nofollow", "index, nofollow", "noindex, follow".</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tracking and Analytics -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTracking">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTracking" aria-expanded="false" aria-controls="collapseTracking">
                                        <i class="ti ti-chart-line me-2"></i> Tracking & Analytics
                                    </button>
                                </h2>
                                <div id="collapseTracking" class="accordion-collapse collapse" aria-labelledby="headingTracking" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>Tip:</strong> Add social media and analytics platform IDs to track your page's performance and accurately measure conversion rates. Your page will automatically send visitor and sales data to these platforms.
                                                <br><small class="d-block mt-2">All these fields are optional - you can add what you need only or leave them empty and come back to edit them later.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="facebook_pixel_id" class="form-label">
                                                    <i class="ti ti-brand-facebook me-1"></i>Facebook Pixel Code (Optional)
                                                </label>
                                                <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" class="form-control"
                                                       value="{{ old('facebook_pixel_id') }}"
                                                       placeholder="Enter Facebook Pixel ID"
                                                       maxlength="100">
                                                <small class="text-muted d-block mt-1">Enter your Facebook Pixel ID to track conversions and optimize your Facebook ads.</small>
                                                <div class="mt-1">
                                                    <small id="facebook_pixel_length" class="text-muted">0 / 100 characters</small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="tiktok_pixel_id" class="form-label">
                                                    <i class="ti ti-brand-tiktok me-1"></i>TikTok Pixel Code (Optional)
                                                </label>
                                                <input type="text" id="tiktok_pixel_id" name="tiktok_pixel_id" class="form-control"
                                                       value="{{ old('tiktok_pixel_id') }}"
                                                       placeholder="Enter TikTok Pixel ID"
                                                       maxlength="100">
                                                <small class="text-muted d-block mt-1">Enter your TikTok Pixel ID to track conversions and optimize your TikTok ads.</small>
                                                <div class="mt-1">
                                                    <small id="tiktok_pixel_length" class="text-muted">0 / 100 characters</small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="snapchat_pixel_id" class="form-label">
                                                    <i class="ti ti-brand-snapchat me-1"></i>Snapchat Pixel Code (Optional)
                                                </label>
                                                <input type="text" id="snapchat_pixel_id" name="snapchat_pixel_id" class="form-control"
                                                       value="{{ old('snapchat_pixel_id') }}"
                                                       placeholder="Enter Snapchat Pixel ID"
                                                       maxlength="100">
                                                <small class="text-muted d-block mt-1">Enter your Snapchat Pixel ID to track conversions and optimize your Snapchat ads.</small>
                                                <div class="mt-1">
                                                    <small id="snapchat_pixel_length" class="text-muted">0 / 100 characters</small>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="google_analytics_id" class="form-label">
                                                    <i class="ti ti-brand-google-analytics me-1"></i>Google Analytics ID (Optional)
                                                </label>
                                                <input type="text" id="google_analytics_id" name="google_analytics_id" class="form-control"
                                                       value="{{ old('google_analytics_id') }}"
                                                       placeholder="Enter Google Analytics ID (e.g. GA_MEASUREMENT_ID)"
                                                       maxlength="100">
                                                <small class="text-muted d-block mt-1">Enter your Google Analytics Measurement ID (e.g., G-XXXXXXXXXX) to track page views and user behavior.</small>
                                                <div class="mt-1">
                                                    <small id="google_analytics_length" class="text-muted">0 / 100 characters</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- A/B Testing -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingABTesting">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseABTesting" aria-expanded="false" aria-controls="collapseABTesting">
                                        <i class="ti ti-chart-bar me-2"></i> A/B Testing
                                    </button>
                                </h2>
                                <div id="collapseABTesting" class="accordion-collapse collapse" aria-labelledby="headingABTesting" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>A/B Testing:</strong> Test different versions of your landing page to see which performs better. You can create variants after creating the main page.
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" id="ab_testing_enabled" name="ab_testing_enabled" value="1" class="form-check-input" {{ old('ab_testing_enabled') ? 'checked' : '' }}>
                                                    <label for="ab_testing_enabled" class="form-check-label fw-bold">Enable A/B Testing</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Enable this to create variants of this landing page for testing.</small>
                                            </div>

                                            <div id="ab-testing-settings" style="display: {{ old('ab_testing_enabled') ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="auto_winner_visits" class="form-label">Auto-Select Winner After (Visits)</label>
                                                    <input type="number" id="auto_winner_visits" name="auto_winner_visits" class="form-control"
                                                           value="{{ old('auto_winner_visits') }}"
                                                           placeholder="e.g., 1000" min="0">
                                                    <small class="text-muted d-block mt-1">Automatically select the winning variant after this many visits. Leave empty to manually select.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Smart CTA Variants -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingCTAVariants">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCTAVariants" aria-expanded="false" aria-controls="collapseCTAVariants">
                                        <i class="ti ti-click me-2"></i> Smart CTA Variants
                                    </button>
                                </h2>
                                <div id="collapseCTAVariants" class="accordion-collapse collapse" aria-labelledby="headingCTAVariants" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>Smart CTAs:</strong> Create multiple call-to-action buttons with conditional display rules based on visitor behavior, device, location, or language.
                                            </div>

                                            <div id="cta-variants-container">
                                                <p class="text-muted text-center py-3">CTA variants can be added after creating the landing page.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sticky CTA & Exit Intent -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingStickyExit">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStickyExit" aria-expanded="false" aria-controls="collapseStickyExit">
                                        <i class="ti ti-device-mobile me-2"></i> Sticky CTA & Exit Intent
                                    </button>
                                </h2>
                                <div id="collapseStickyExit" class="accordion-collapse collapse" aria-labelledby="headingStickyExit" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-device-mobile me-2"></i>Sticky CTA (Mobile)
                                            </h6>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" id="sticky_cta_enabled" name="sticky_cta_enabled" value="1" class="form-check-input" {{ old('sticky_cta_enabled') ? 'checked' : '' }}>
                                                    <label for="sticky_cta_enabled" class="form-check-label fw-bold">Enable Sticky CTA</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a sticky call-to-action button that stays visible while scrolling.</small>
                                            </div>

                                            <div id="sticky-cta-settings" style="display: {{ old('sticky_cta_enabled') ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="sticky_cta_text" class="form-label">Sticky CTA Button Text</label>
                                                    <input type="text" id="sticky_cta_text" name="sticky_cta_text" class="form-control"
                                                           value="{{ old('sticky_cta_text', 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="sticky_cta_position" class="form-label">Position</label>
                                                    <select id="sticky_cta_position" name="sticky_cta_position" class="form-select">
                                                        <option value="bottom" {{ old('sticky_cta_position', 'bottom') == 'bottom' ? 'selected' : '' }}>Bottom</option>
                                                        <option value="top" {{ old('sticky_cta_position') == 'top' ? 'selected' : '' }}>Top</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" id="sticky_cta_mobile_only" name="sticky_cta_mobile_only" value="1" class="form-check-input" {{ old('sticky_cta_mobile_only', true) ? 'checked' : '' }}>
                                                        <label for="sticky_cta_mobile_only" class="form-check-label">Mobile Only</label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Show sticky CTA only on mobile devices.</small>
                                                </div>
                                            </div>

                                            <hr class="my-4">

                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-mouse me-2"></i>Exit Intent Popup (Desktop)
                                            </h6>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" id="exit_intent_enabled" name="exit_intent_enabled" value="1" class="form-check-input" {{ old('exit_intent_enabled') ? 'checked' : '' }}>
                                                    <label for="exit_intent_enabled" class="form-check-label fw-bold">Enable Exit Intent Popup</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a popup when visitors are about to leave the page (desktop only).</small>
                                            </div>

                                            <div id="exit-intent-settings" style="display: {{ old('exit_intent_enabled') ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="exit_intent_title" class="form-label">Popup Title</label>
                                                    <input type="text" id="exit_intent_title" name="exit_intent_title" class="form-control"
                                                           value="{{ old('exit_intent_title') }}"
                                                           placeholder="Wait! Don't miss out">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="exit_intent_message" class="form-label">Popup Message</label>
                                                    <textarea id="exit_intent_message" name="exit_intent_message" class="form-control" rows="3"
                                                              placeholder="Get started today and save 20%!">{{ old('exit_intent_message') }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="exit_intent_cta_text" class="form-label">CTA Button Text</label>
                                                    <input type="text" id="exit_intent_cta_text" name="exit_intent_cta_text" class="form-control"
                                                           value="{{ old('exit_intent_cta_text', 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" id="exit_intent_desktop_only" name="exit_intent_desktop_only" value="1" class="form-check-input" {{ old('exit_intent_desktop_only', true) ? 'checked' : '' }}>
                                                        <label for="exit_intent_desktop_only" class="form-check-label">Desktop Only</label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Show exit intent popup only on desktop devices.</small>
                                                </div>
                                            </div>

                                            <hr class="my-4">

                                            <h6 class="mb-3 text-primary fw-bold">
                                                <i class="ti ti-clock me-2"></i>Time-Based Popup
                                            </h6>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" id="time_based_popup_enabled" name="time_based_popup_enabled" value="1" class="form-check-input" {{ old('time_based_popup_enabled') ? 'checked' : '' }}>
                                                    <label for="time_based_popup_enabled" class="form-check-label fw-bold">Enable Time-Based Popup</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a popup after visitors spend a certain amount of time on the page.</small>
                                            </div>

                                            <div id="time-based-popup-settings" style="display: {{ old('time_based_popup_enabled') ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="time_based_popup_delay" class="form-label">Show After (Seconds)</label>
                                                    <input type="number" id="time_based_popup_delay" name="time_based_popup_delay" class="form-control"
                                                           value="{{ old('time_based_popup_delay', 30) }}"
                                                           placeholder="30" min="1">
                                                    <small class="text-muted d-block mt-1">Number of seconds before showing the popup.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_title" class="form-label">Popup Title</label>
                                                    <input type="text" id="time_based_popup_title" name="time_based_popup_title" class="form-control"
                                                           value="{{ old('time_based_popup_title') }}"
                                                           placeholder="Special Offer!">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_message" class="form-label">Popup Message</label>
                                                    <textarea id="time_based_popup_message" name="time_based_popup_message" class="form-control" rows="3"
                                                              placeholder="Get 20% off your first order!">{{ old('time_based_popup_message') }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_cta_text" class="form-label">CTA Button Text</label>
                                                    <input type="text" id="time_based_popup_cta_text" name="time_based_popup_cta_text" class="form-control"
                                                           value="{{ old('time_based_popup_cta_text', 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn-create">
                                <i class="fas fa-check"></i> Create Landing Page
                            </button>
                            <a href="{{ route('services.mine') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    <div class="modal fade" id="templatePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-eye me-2"></i>Previewing: <span id="modal-template-name" class="fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="height: 70vh; min-height: 500px;">
                    <div id="modal-preview-spinner" class="position-absolute top-50 start-50 translate-middle text-center">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="text-muted small">Loading Template...</div>
                    </div>
                    <iframe src="about:blank" id="modal-preview-iframe" class="w-100 h-100 border-0 position-relative" style="z-index: 10; opacity: 0; transition: opacity 0.3s ease;" title="{{ __('Template preview') }}"></iframe>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="modal-select-btn">
                        Select This Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fill all fields from AI-generated content
        async function fillFromService() {
            const fillButton = document.getElementById('fill-from-service');
            const buttonText = document.getElementById('fill-button-text');
            const buttonSpinner = document.getElementById('fill-button-spinner');
            const alert = document.querySelector('.alert-info');

            // Disable button and show loading state
            fillButton.disabled = true;
            buttonText.textContent = 'Generating...';
            buttonSpinner.classList.remove('d-none');
            alert.classList.remove('alert-info', 'alert-success', 'alert-danger');
            alert.classList.add('alert-warning');
            alert.innerHTML = '<i class="ti ti-loader me-2"></i><strong>AI is working...</strong> Generating optimized landing page content. This may take a few moments.';

            try {
                const response = await fetch('{{ route("services.landing-page.generate-content", $service) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Failed to generate content');
                }

                const data = result.data;
                const aiProvider = result.message?.match(/using\s+(\w+)/i)?.[1]?.toUpperCase() || 'AI';

                // Fill slug - sanitize it to ensure URL-friendliness
                const slugInput = document.getElementById('slug');
                if (data.slug) {
                    // Sanitize the AI-generated slug
                    const sanitizedSlug = data.slug.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                    slugInput.value = sanitizedSlug;
                    document.getElementById('slug-preview').textContent = sanitizedSlug || 'your-slug';
                    // Trigger input event to ensure any other handlers are notified
                    slugInput.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    slugInput.value = '';
                    document.getElementById('slug-preview').textContent = 'your-slug';
                }

                // Fill hero title
                document.getElementById('hero_title').value = data.hero_title || '';

                // Fill hero description
                document.getElementById('hero_description').value = data.hero_description || '';

                // Fill hero CTA text
                if (data.hero_cta_text) {
                    document.getElementById('hero_cta_text').value = data.hero_cta_text;
                }

                // Fill description (full HTML content)
                const descriptionEditor = document.getElementById('description-editor');
                if (descriptionEditor && data.description) {
                    descriptionEditor.value = data.description;
                    // Try to update Laraberg editor if it's initialized
                    setTimeout(function() {
                        if (window.Laraberg && window.Laraberg.editors && window.Laraberg.editors['description-editor']) {
                            try {
                                const editor = window.Laraberg.editors['description-editor'];
                                if (editor && typeof editor.setContent === 'function') {
                                    editor.setContent(data.description);
                                }
                            } catch (e) {
                                console.log('Laraberg editor update failed, value set in textarea');
                            }
                        }
                    }, 100);
                }

                // Fill meta title
                document.getElementById('meta_title').value = data.meta_title || '';
                updateMetaTitleLength();

                // Fill meta description
                document.getElementById('meta_description').value = data.meta_description || '';
                updateMetaDescriptionLength();

                // Fill meta keywords
                if (data.meta_keywords) {
                    document.getElementById('meta_keywords').value = data.meta_keywords;
                }

                // Fill OG title
                document.getElementById('og_title').value = data.og_title || '';

                // Fill OG description
                document.getElementById('og_description').value = data.og_description || '';

                // Fill OG image
                if (data.og_image) {
                    document.getElementById('og_image').value = data.og_image;
                }

                // Fill Twitter card type
                if (data.twitter_card_type) {
                    document.getElementById('twitter_card_type').value = data.twitter_card_type;
                }

                // Fill Twitter title
                document.getElementById('twitter_title').value = data.twitter_title || '';

                // Fill Twitter description
                document.getElementById('twitter_description').value = data.twitter_description || '';

                // Fill Twitter image
                if (data.twitter_image) {
                    document.getElementById('twitter_image').value = data.twitter_image;
                }

                // Fill robots
                if (data.robots) {
                    document.getElementById('robots').value = data.robots;
                }

                // Show success message
                alert.classList.remove('alert-warning');
                alert.classList.add('alert-success');
                alert.innerHTML = '<i class="ti ti-check-circle me-2"></i><strong>Success!</strong> ' + (result.message || 'AI has generated optimized landing page content. Review and adjust as needed.');

            } catch (error) {
                console.error('Error generating content:', error);
                alert.classList.remove('alert-warning');
                alert.classList.add('alert-danger');
                alert.innerHTML = '<i class="ti ti-alert-circle me-2"></i><strong>Error:</strong> ' + (error.message || 'Failed to generate content. Please try again.');
            } finally {
                // Re-enable button and restore text
                fillButton.disabled = false;
                buttonText.textContent = 'Generate with AI';
                buttonSpinner.classList.add('d-none');
            }
        }

        document.getElementById('fill-from-service').addEventListener('click', fillFromService);

        document.getElementById('slug').addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            this.value = slug;
            document.getElementById('slug-preview').textContent = slug || 'your-slug';
        });

        // Character counters for SEO fields
        const metaTitleInput = document.getElementById('meta_title');
        const metaDescriptionInput = document.getElementById('meta_description');
        const metaTitleLength = document.getElementById('meta_title_length');
        const metaDescriptionLength = document.getElementById('meta_description_length');

        function updateMetaTitleLength() {
            const length = metaTitleInput.value.length;
            metaTitleLength.textContent = `${length} / 60 characters`;
            if (length > 60) {
                metaTitleLength.classList.add('text-danger');
                metaTitleLength.classList.remove('text-muted');
            } else if (length > 50) {
                metaTitleLength.classList.add('text-warning');
                metaTitleLength.classList.remove('text-muted', 'text-danger');
            } else {
                metaTitleLength.classList.remove('text-warning', 'text-danger');
                metaTitleLength.classList.add('text-muted');
            }
        }

        function updateMetaDescriptionLength() {
            const length = metaDescriptionInput.value.length;
            metaDescriptionLength.textContent = `${length} / 160 characters`;
            if (length > 160) {
                metaDescriptionLength.classList.add('text-danger');
                metaDescriptionLength.classList.remove('text-muted');
            } else if (length > 150) {
                metaDescriptionLength.classList.add('text-warning');
                metaDescriptionLength.classList.remove('text-muted', 'text-danger');
            } else {
                metaDescriptionLength.classList.remove('text-warning', 'text-danger');
                metaDescriptionLength.classList.add('text-muted');
            }
        }

        if (metaTitleInput) {
            metaTitleInput.addEventListener('input', updateMetaTitleLength);
            updateMetaTitleLength();
        }

        if (metaDescriptionInput) {
            metaDescriptionInput.addEventListener('input', updateMetaDescriptionLength);
            updateMetaDescriptionLength();
        }

        // Character counters for pixel tracking fields
        const facebookPixelInput = document.getElementById('facebook_pixel_id');
        const tiktokPixelInput = document.getElementById('tiktok_pixel_id');
        const snapchatPixelInput = document.getElementById('snapchat_pixel_id');
        const googleAnalyticsInput = document.getElementById('google_analytics_id');

        function updatePixelFieldLength(input, counterId) {
            if (!input) return;
            const counter = document.getElementById(counterId);
            if (!counter) return;
            const length = input.value.length;
            counter.textContent = `${length} / 100 characters`;
        }

        if (facebookPixelInput) {
            facebookPixelInput.addEventListener('input', () => updatePixelFieldLength(facebookPixelInput, 'facebook_pixel_length'));
            updatePixelFieldLength(facebookPixelInput, 'facebook_pixel_length');
        }
        if (tiktokPixelInput) {
            tiktokPixelInput.addEventListener('input', () => updatePixelFieldLength(tiktokPixelInput, 'tiktok_pixel_length'));
            updatePixelFieldLength(tiktokPixelInput, 'tiktok_pixel_length');
        }
        if (snapchatPixelInput) {
            snapchatPixelInput.addEventListener('input', () => updatePixelFieldLength(snapchatPixelInput, 'snapchat_pixel_length'));
            updatePixelFieldLength(snapchatPixelInput, 'snapchat_pixel_length');
        }
        if (googleAnalyticsInput) {
            googleAnalyticsInput.addEventListener('input', () => updatePixelFieldLength(googleAnalyticsInput, 'google_analytics_length'));
            updatePixelFieldLength(googleAnalyticsInput, 'google_analytics_length');
        }

        // Toggle CRO feature settings
        const abTestingEnabled = document.getElementById('ab_testing_enabled');
        const stickyCtaEnabled = document.getElementById('sticky_cta_enabled');
        const exitIntentEnabled = document.getElementById('exit_intent_enabled');
        const timeBasedPopupEnabled = document.getElementById('time_based_popup_enabled');

        if (abTestingEnabled) {
            abTestingEnabled.addEventListener('change', function() {
                document.getElementById('ab-testing-settings').style.display = this.checked ? 'block' : 'none';
            });
        }

        if (stickyCtaEnabled) {
            stickyCtaEnabled.addEventListener('change', function() {
                document.getElementById('sticky-cta-settings').style.display = this.checked ? 'block' : 'none';
            });
        }

        if (exitIntentEnabled) {
            exitIntentEnabled.addEventListener('change', function() {
                document.getElementById('exit-intent-settings').style.display = this.checked ? 'block' : 'none';
            });
        }

        if (timeBasedPopupEnabled) {
            timeBasedPopupEnabled.addEventListener('change', function() {
                document.getElementById('time-based-popup-settings').style.display = this.checked ? 'block' : 'none';
            });
        }

        // Template Selection Logic
        window.selectTemplate = function(id, name, element) {
            // Update input
            document.getElementById('template').value = id;
            document.getElementById('selected-template-name').textContent = name;
            
            // Visual Update
            document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
            if(element) element.classList.add('selected');
        }

        window.showPreview = function(id, name) {
            const modal = new bootstrap.Modal(document.getElementById('templatePreviewModal'));
            document.getElementById('modal-template-name').textContent = name;
            
            const iframe = document.getElementById('modal-preview-iframe');
            const spinner = document.getElementById('modal-preview-spinner');
            
            // Reset state
            iframe.style.opacity = '0';
            spinner.classList.remove('d-none');
            
            // Allow time for modal to be visible before loading iframe to avoid render issues
            setTimeout(() => {
                iframe.src = "{{ url('/lp/templates/preview') }}/" + id;
                
                iframe.onload = function() {
                    iframe.style.opacity = '1';
                    // Optional: hide spinner explicitly if needed, but opacity handles the visual transition
                    // spinner.classList.add('d-none');
                };
            }, 100);
            
            // Set select button action in modal
            const selectBtn = document.getElementById('modal-select-btn');
            selectBtn.onclick = function() {
                // Find grid element to mock click
                const gridCard = document.querySelector(`.template-card[onclick*="'${id}'"]`);
                selectTemplate(id, name, gridCard);
                modal.hide();
            };
            
            modal.show();
        }
    </script>
@endsection

@push('scripts')
    <script>
        // Initialize Laraberg editor
        Laraberg.init('description-editor', { height: '600px', laravelFilemanager: false, sidebar: true });
    </script>
@endpush
