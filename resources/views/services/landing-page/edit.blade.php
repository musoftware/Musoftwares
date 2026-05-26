@extends('layouts.app')


@section('content')
    @include('notes.css_header')
    <div class="dashboard-container at-mobile-scroll-fix landing-page-editor">
        <!-- Page Header -->
        <div class="section-header primary">
            <div class="header-content">
                <div class="section-icon primary">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h3 class="section-title">Edit Landing Page</h3>
                    <p class="section-subtitle">Customize landing page for {{ $service->title }}</p>
                </div>
            </div>
            <div class="header-actions">
                @if($landingPage->is_active)
                    <a href="{{ route('services.landing-page.show', $landingPage->slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm me-2">
                        <i class="fas fa-external-link-alt"></i> View Live
                    </a>
                @else
                    <span class="badge bg-warning text-dark me-2">
                        <i class="fas fa-eye-slash"></i> Inactive
                    </span>
                @endif
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
                    <form action="{{ route('services.landing-page.update', ['service' => $service, 'landingPage' => $landingPage]) }}" method="POST" id="landingPageForm">
                        @csrf
                        @method('PUT')

                        <div class="accordion" id="landingPageAccordion">
                            <!-- Basic Settings -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingBasic">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasic" aria-expanded="true" aria-controls="collapseBasic">
                                        <i class="ti ti-settings me-2"></i> Basic Settings
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
                                                           value="{{ old('slug', $landingPage->slug) }}"
                                                           @if($landingPage->parent_variant_id) readonly @endif>
                                                </div>
                                                @if($landingPage->parent_variant_id)
                                                    <small class="text-info d-block mt-1">
                                                        <i class="ti ti-info-circle me-1"></i>
                                                        <strong>A/B Testing:</strong> This variant shares the same URL as the parent page. The slug cannot be edited for variants.
                                                    </small>
                                                @endif
                                                @error('slug')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_title" class="form-label">Hero Title</label>
                                                <input type="text" id="hero_title" name="hero_title" required class="form-control"
                                                       value="{{ old('hero_title', $landingPage->hero_title) }}">
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_description" class="form-label d-flex justify-content-between align-items-center">
                                                    Hero Description
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0" onclick="rewriteText('hero_description')">
                                                        <i class="ti ti-wand me-1"></i> Rewrite with AI
                                                    </button>
                                                </label>
                                                <div class="input-group">
                                                    <textarea id="hero_description" name="hero_description" class="form-control" rows="3">{{ old('hero_description', $landingPage->hero_description) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="hero_cta_text" class="form-label">Hero CTA Button Text</label>
                                                <input type="text" id="hero_cta_text" name="hero_cta_text" class="form-control"
                                                       value="{{ old('hero_cta_text', $landingPage->hero_cta_text) }}">
                                            </div>

                                            <div class="mb-3">
                                                <label for="template" class="form-label">Landing Page Template</label>
                                                <select id="template" name="template" class="form-select" required>
                                                    <optgroup label="Professional & Business">
                                                        <option value="modern" {{ old('template', $landingPage->template ?? 'modern') == 'modern' ? 'selected' : '' }}>
                                                            Modern - Clean & Minimal (Professional Services)
                                                        </option>
                                                        <option value="business" {{ old('template', $landingPage->template ?? 'modern') == 'business' ? 'selected' : '' }}>
                                                            Business - Professional & Corporate (B2B Services)
                                                        </option>
                                                        <option value="minimal" {{ old('template', $landingPage->template ?? 'modern') == 'minimal' ? 'selected' : '' }}>
                                                            Minimal - Ultra Clean & Simple
                                                        </option>
                                                        <option value="dashboard" {{ old('template', $landingPage->template ?? 'modern') == 'dashboard' ? 'selected' : '' }}>
                                                            Dashboard - Data-Driven & Analytical
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Creative & Design">
                                                        <option value="creative" {{ old('template', $landingPage->template ?? 'modern') == 'creative' ? 'selected' : '' }}>
                                                            Creative - Bold & Vibrant (Creative Services)
                                                        </option>
                                                        <option value="glassmorphism" {{ old('template', $landingPage->template ?? 'modern') == 'glassmorphism' ? 'selected' : '' }}>
                                                            Glassmorphism - Frosted Glass Effect
                                                        </option>
                                                        <option value="neumorphism" {{ old('template', $landingPage->template ?? 'modern') == 'neumorphism' ? 'selected' : '' }}>
                                                            Neumorphism - Soft Shadows & Elevation
                                                        </option>
                                                        <option value="skeuomorphism" {{ old('template', $landingPage->template ?? 'modern') == 'skeuomorphism' ? 'selected' : '' }}>
                                                            Skeuomorphism - Realistic 3D Elements
                                                        </option>
                                                        <option value="flat-design" {{ old('template', $landingPage->template ?? 'modern') == 'flat-design' ? 'selected' : '' }}>
                                                            Flat Design - Simple & Colorful
                                                        </option>
                                                        <option value="material-design" {{ old('template', $landingPage->template ?? 'modern') == 'material-design' ? 'selected' : '' }}>
                                                            Material Design - Google's Design Language
                                                        </option>
                                                        <option value="fluent-design" {{ old('template', $landingPage->template ?? 'modern') == 'fluent-design' ? 'selected' : '' }}>
                                                            Fluent Design - Microsoft's Design System
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Product & E-commerce">
                                                        <option value="product" {{ old('template', $landingPage->template ?? 'modern') == 'product' ? 'selected' : '' }}>
                                                            Product - Showcase Focus (Product-Based Services)
                                                        </option>
                                                        <option value="ecommerce" {{ old('template', $landingPage->template ?? 'modern') == 'ecommerce' ? 'selected' : '' }}>
                                                            E-commerce - Shopping & Retail Focus
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Themed & Specialized">
                                                        <option value="cyberpunk" {{ old('template', $landingPage->template ?? 'modern') == 'cyberpunk' ? 'selected' : '' }}>
                                                            Cyberpunk - Neon Glows & Futuristic
                                                        </option>
                                                        <option value="gaming" {{ old('template', $landingPage->template ?? 'modern') == 'gaming' ? 'selected' : '' }}>
                                                            Gaming - Dynamic & Interactive
                                                        </option>
                                                        <option value="dark-mode" {{ old('template', $landingPage->template ?? 'modern') == 'dark-mode' ? 'selected' : '' }}>
                                                            Dark Mode - Dark Theme & High Contrast
                                                        </option>
                                                        <option value="brutalism" {{ old('template', $landingPage->template ?? 'modern') == 'brutalism' ? 'selected' : '' }}>
                                                            Brutalism - Bold & Raw Aesthetic
                                                        </option>
                                                        <option value="retro" {{ old('template', $landingPage->template ?? 'modern') == 'retro' ? 'selected' : '' }}>
                                                            Retro - Vintage & Nostalgic
                                                        </option>
                                                        <option value="pastel" {{ old('template', $landingPage->template ?? 'modern') == 'pastel' ? 'selected' : '' }}>
                                                            Pastel - Soft & Gentle Colors
                                                        </option>
                                                    </optgroup>
                                                </select>
                                                <small class="text-muted d-block mt-1">Choose a template that best fits your service type and brand style.</small>
                                                @error('template')
                                                    <div class="text-danger mt-1"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                                                @enderror
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Publishing & Performance -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPublishing">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePublishing" aria-expanded="false" aria-controls="collapsePublishing">
                                        <i class="ti ti-calendar-stats me-2"></i> Publishing & Schedule
                                    </button>
                                </h2>
                                <div id="collapsePublishing" class="accordion-collapse collapse" aria-labelledby="headingPublishing" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="status_display" class="form-label">Status</label>
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <span class="badge bg-{{ $landingPage->is_active ? 'success' : 'secondary' }}">
                                                            {{ $landingPage->is_active ? 'Active' : 'Draft' }}
                                                        </span>
                                                        @if($landingPage->published_at)
                                                            <small class="text-muted">Published: {{ $landingPage->published_at->format('M d, Y H:i') }}</small>
                                                        @endif
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" {{ old('is_active', $landingPage->is_active) ? 'checked' : '' }}>
                                                        <label for="is_active" class="form-check-label">Enable / Live</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="scheduled_at" class="form-label">Schedule Publication</label>
                                                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control"
                                                           value="{{ old('scheduled_at', $landingPage->scheduled_at ? $landingPage->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                                                    <small class="text-muted">Automatically publish at this date and time.</small>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="alert alert-light border">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="ti ti-robot me-1 text-primary"></i>
                                                                <strong>AI SEO Score:</strong>
                                                                <span class="badge bg-{{ ($landingPage->ai_seo_score >= 80) ? 'success' : (($landingPage->ai_seo_score >= 50) ? 'warning' : 'danger') }}">
                                                                    {{ $landingPage->ai_seo_score ?? 0 }}/100
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <strong>Target Persona:</strong> {{ $landingPage->ai_persona ?? 'General Audience' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="ai_seo_score" value="{{ $landingPage->ai_seo_score }}">
                                                    <input type="hidden" name="ai_persona" value="{{ $landingPage->ai_persona }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Layout & Global Styles -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingLayoutStyle">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLayoutStyle" aria-expanded="false" aria-controls="collapseLayoutStyle">
                                        <i class="ti ti-layout-dashboard me-2"></i> Layout & Design
                                    </button>
                                </h2>
                                <div id="collapseLayoutStyle" class="accordion-collapse collapse" aria-labelledby="headingLayoutStyle" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <h6 class="mb-3 fw-bold">Section Visibility & Order</h6>
                                            @php
                                                $layoutConfig = $landingPage->layout_config ?? [
                                                    'hero' => true,
                                                    'features' => true,
                                                    'gallery' => true,
                                                    'pricing' => true,
                                                    'testimonials' => true,
                                                    'faq' => true,
                                                    'cta' => true,
                                                    'order' => ['hero', 'features', 'gallery', 'pricing', 'testimonials', 'faq', 'cta']
                                                ];
                                            @endphp
                                            <input type="hidden" name="layout_config" id="layout_config" value="{{ json_encode($layoutConfig) }}">

                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="hero" {{ ($layoutConfig['hero'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Hero Section</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="features" {{ ($layoutConfig['features'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Features Section</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="gallery" {{ ($layoutConfig['gallery'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Gallery Section</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="pricing" {{ ($layoutConfig['pricing'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Pricing Section</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="testimonials" {{ ($layoutConfig['testimonials'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Testimonials</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="faq" {{ ($layoutConfig['faq'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">FAQ Section</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" class="form-check-input section-toggle" data-section="cta" {{ ($layoutConfig['cta'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label">Bottom CTA</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mb-3 fw-bold">Global Styles</h6>
                                            @php
                                                $styleConfig = $landingPage->style_config ?? [
                                                    'font_family' => 'Inter',
                                                    'primary_color' => '#0d6efd',
                                                    'border_radius' => 'rounded',
                                                ];
                                            @endphp
                                            <input type="hidden" name="style_config" id="style_config" value="{{ json_encode($styleConfig) }}">

                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Font Family</label>
                                                    <select class="form-select style-input" data-style="font_family">
                                                        <option value="Inter" {{ ($styleConfig['font_family'] ?? '') == 'Inter' ? 'selected' : '' }}>Inter (Default)</option>
                                                        <option value="Roboto" {{ ($styleConfig['font_family'] ?? '') == 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                                        <option value="Open Sans" {{ ($styleConfig['font_family'] ?? '') == 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                                                        <option value="Montserrat" {{ ($styleConfig['font_family'] ?? '') == 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Primary Color</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text p-1"><input type="color" class="form-control form-control-color border-0 p-0" title="Choose color" value="{{ $styleConfig['primary_color'] ?? '#0d6efd' }}" onchange="this.parentElement.nextElementSibling.value = this.value; updateStyleConfig();"></span>
                                                        <input type="text" class="form-control style-input" data-style="primary_color" value="{{ $styleConfig['primary_color'] ?? '#0d6efd' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Button Radius</label>
                                                    <select class="form-select style-input" data-style="border_radius">
                                                        <option value="rounded-0" {{ ($styleConfig['border_radius'] ?? '') == 'rounded-0' ? 'selected' : '' }}>Square (0px)</option>
                                                        <option value="rounded" {{ ($styleConfig['border_radius'] ?? '') == 'rounded' ? 'selected' : '' }}>Rounded (4px)</option>
                                                        <option value="rounded-pill" {{ ($styleConfig['border_radius'] ?? '') == 'rounded-pill' ? 'selected' : '' }}>Pill (Full)</option>
                                                    </select>
                                                </div>
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
                                                <textarea id="description-editor" name="description" hidden>{{ old('description', $landingPage->description) }}</textarea>
                                                <small class="text-muted d-block mb-2">
                                                    <i class="ti ti-info-circle me-1"></i>Add detailed description about your service using the rich text editor below.
                                                </small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="description_alignment" class="form-label">Description Text Alignment</label>
                                                <select id="description_alignment" name="description_alignment" class="form-select">
                                                    <option value="left" {{ old('description_alignment', $landingPage->description_alignment ?? 'left') == 'left' ? 'selected' : '' }}>
                                                        Left Aligned
                                                    </option>
                                                    <option value="center" {{ old('description_alignment', $landingPage->description_alignment ?? 'left') == 'center' ? 'selected' : '' }}>
                                                        Center Aligned
                                                    </option>
                                                    <option value="right" {{ old('description_alignment', $landingPage->description_alignment ?? 'left') == 'right' ? 'selected' : '' }}>
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
                                                       value="{{ old('meta_title', $landingPage->meta_title) }}"
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
                                                          maxlength="160">{{ old('meta_description', $landingPage->meta_description) }}</textarea>
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
                                                       value="{{ old('meta_keywords', $landingPage->meta_keywords) }}"
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
                                                       value="{{ old('og_title', $landingPage->og_title) }}"
                                                       placeholder="Enter Open Graph title">
                                                <small class="text-muted d-block mt-1">If left empty, meta title or hero title will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="og_description" class="form-label">OG Description</label>
                                                <textarea id="og_description" name="og_description" class="form-control" rows="2"
                                                          placeholder="Enter Open Graph description">{{ old('og_description', $landingPage->og_description) }}</textarea>
                                                <small class="text-muted d-block mt-1">If left empty, meta description or hero description will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="og_image" class="form-label">OG Image URL</label>
                                                <input type="url" id="og_image" name="og_image" class="form-control"
                                                       value="{{ old('og_image', $landingPage->og_image) }}"
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
                                                    <option value="summary" {{ old('twitter_card_type', $landingPage->twitter_card_type ?? 'summary_large_image') == 'summary' ? 'selected' : '' }}>Summary</option>
                                                    <option value="summary_large_image" {{ old('twitter_card_type', $landingPage->twitter_card_type ?? 'summary_large_image') == 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                                                    <option value="app" {{ old('twitter_card_type', $landingPage->twitter_card_type) == 'app' ? 'selected' : '' }}>App</option>
                                                    <option value="player" {{ old('twitter_card_type', $landingPage->twitter_card_type) == 'player' ? 'selected' : '' }}>Player</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_title" class="form-label">Twitter Title</label>
                                                <input type="text" id="twitter_title" name="twitter_title" class="form-control"
                                                       value="{{ old('twitter_title', $landingPage->twitter_title) }}"
                                                       placeholder="Enter Twitter card title">
                                                <small class="text-muted d-block mt-1">If left empty, OG title or meta title will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_description" class="form-label">Twitter Description</label>
                                                <textarea id="twitter_description" name="twitter_description" class="form-control" rows="2"
                                                          placeholder="Enter Twitter card description">{{ old('twitter_description', $landingPage->twitter_description) }}</textarea>
                                                <small class="text-muted d-block mt-1">If left empty, OG description or meta description will be used.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="twitter_image" class="form-label">Twitter Image URL</label>
                                                <input type="url" id="twitter_image" name="twitter_image" class="form-control"
                                                       value="{{ old('twitter_image', $landingPage->twitter_image) }}"
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
                                                       value="{{ old('canonical_url', $landingPage->canonical_url) }}"
                                                       placeholder="https://example.com/canonical-page">
                                                <small class="text-muted d-block mt-1">Optional. Use this if you want to specify a canonical URL different from the landing page URL.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="robots" class="form-label">Robots Meta Tag</label>
                                                <input type="text" id="robots" name="robots" class="form-control"
                                                       value="{{ old('robots', $landingPage->robots ?? 'index, follow') }}"
                                                       placeholder="index, follow">
                                                <small class="text-muted d-block mt-1">Control how search engines index this page. Common values: "index, follow", "noindex, nofollow", "index, nofollow", "noindex, follow".</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Builder (Replaces Simple Form Questions) -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFormBuilder">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormBuilder" aria-expanded="false" aria-controls="collapseFormBuilder">
                                        <i class="ti ti-forms me-2"></i> Form Builder
                                    </button>
                                </h2>
                                <div id="collapseFormBuilder" class="accordion-collapse collapse" aria-labelledby="headingFormBuilder" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <!-- Keep existing questions UI for compatibility, but maybe hide it if using advanced builder?
                                             For this step, sticking to the existing UI but ensuring it syncs to form_config if needed.
                                             Or better, just use the JSON builder mainly. -->

                                        <div class="form-section">
                                            <div class="builder-section-header">
                                                <h6 class="mb-0 fw-bold">Customize Lead Form</h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="generateQuestionsBtn" onclick="generateQuestionsWithAI()">
                                                    <i class="ti ti-wand me-1"></i> Generate with AI
                                                </button>
                                            </div>

                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                Configure what information to collect from leads.
                                            </div>

                                            @php
                                                $formConfig = $landingPage->form_config ?? [
                                                    'form_title' => 'Get in Touch',
                                                    'submit_button_text' => 'Submit',
                                                    'success_message' => 'Thank you! We will get back to you shortly.',
                                                ];
                                            @endphp

                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Form Title</label>
                                                    <input type="text" class="form-control form-config-input" data-key="form_title" value="{{ $formConfig['form_title'] ?? 'Get in Touch' }}">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Submit Button Text</label>
                                                    <input type="text" class="form-control form-config-input" data-key="submit_button_text" value="{{ $formConfig['submit_button_text'] ?? 'Submit' }}">
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Success Message</label>
                                                    <input type="text" class="form-control form-config-input" data-key="success_message" value="{{ $formConfig['success_message'] ?? 'Thank you! We will get back to you shortly.' }}">
                                                </div>
                                            </div>

                                            <!-- Legacy Questions UI with synchronization to JSON -->
                                            <div id="questions-container">
                                                @forelse($landingPage->questions as $index => $question)
                                                    <div class="dynamic-row question-row" data-index="{{ $index }}">
                                                        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Question Text</label>
                                                                <input type="text" name="questions[{{ $index }}][question_text]" class="form-control" value="{{ $question->question_text }}" required>
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="form-label">Field Type</label>
                                                                <select name="questions[{{ $index }}][field_type]" class="form-select" onchange="toggleFieldOptions(this)">
                                                                    <option value="text" {{ $question->field_type == 'text' ? 'selected' : '' }}>Text</option>
                                                                    <option value="textarea" {{ $question->field_type == 'textarea' ? 'selected' : '' }}>Textarea</option>
                                                                    <option value="email" {{ $question->field_type == 'email' ? 'selected' : '' }}>Email</option>
                                                                    <option value="phone" {{ $question->field_type == 'phone' ? 'selected' : '' }}>Phone</option>
                                                                    <option value="number" {{ $question->field_type == 'number' ? 'selected' : '' }}>Number</option>
                                                                    <option value="date" {{ $question->field_type == 'date' ? 'selected' : '' }}>Date</option>
                                                                    <option value="select" {{ $question->field_type == 'select' ? 'selected' : '' }}>Select</option>
                                                                    <option value="radio" {{ $question->field_type == 'radio' ? 'selected' : '' }}>Radio</option>
                                                                    <option value="checkbox" {{ $question->field_type == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="form-label">Required</label>
                                                                <div class="form-check form-switch mt-2">
                                                                    <input type="checkbox" name="questions[{{ $index }}][is_required]" value="1" class="form-check-input" {{ $question->is_required ? 'checked' : '' }}>
                                                                    <label class="form-check-label">{{ __('common.yes') }}</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Placeholder</label>
                                                                <input type="text" name="questions[{{ $index }}][placeholder]" class="form-control" value="{{ $question->placeholder }}">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Help Text</label>
                                                                <input type="text" name="questions[{{ $index }}][help_text]" class="form-control" value="{{ $question->help_text }}">
                                                            </div>
                                                            <div class="col-md-12 mb-3 field-options-container" style="display: {{ in_array($question->field_type, ['select', 'radio', 'checkbox']) ? 'block' : 'none' }}">
                                                                <label class="form-label">Field Options (one per line)</label>
                                                                <textarea name="questions[{{ $index }}][field_options]" class="form-control" rows="3">{{ is_array($question->field_options) ? implode("\n", $question->field_options) : $question->field_options }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted text-center py-4">No questions added yet. Click "Add Question" to start.</p>
                                                @endforelse
                                            </div>

                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-outline-primary" onclick="addQuestion()">
                                                    <i class="ti ti-plus me-1"></i> Add Question
                                                </button>
                                            </div>

                                            <!-- Hidden JSON Form Config -->
                                            <input type="hidden" name="form_config" id="form_config" value="{{ json_encode($landingPage->form_config ?? []) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Lead Routing & Integrations -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingLeadRouting">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeadRouting" aria-expanded="false" aria-controls="collapseLeadRouting">
                                        <i class="ti ti-plug-connected me-2"></i> Lead Routing & Integrations
                                    </button>
                                </h2>
                                <div id="collapseLeadRouting" class="accordion-collapse collapse" aria-labelledby="headingLeadRouting" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                Configure where you want to receive new leads. You can receive them via Email, Webhook, or WhatsApp.
                                            </div>

                                            @php
                                                $leadConfig = $landingPage->lead_routing_config ?? [];
                                            @endphp
                                            <input type="hidden" name="lead_routing_config" id="lead_routing_config" value="{{ json_encode($leadConfig) }}">

                                            <!-- Email Notifications -->
                                            <div class="mb-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input type="checkbox" class="form-check-input lead-input" data-key="email_notification"
                                                           {{ ($leadConfig['email_notification'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold">Email Notifications</label>
                                                </div>
                                                <div id="email-notification-settings" style="display: {{ ($leadConfig['email_notification'] ?? false) ? 'block' : 'none' }};" class="ps-4 border-start ml-2">
                                                    <div class="mb-3">
                                                        <label class="form-label">Recipients (comma separated)</label>
                                                        <input type="text" class="form-control lead-input" data-key="notification_email"
                                                               value="{{ $leadConfig['notification_email'] ?? auth()->user()->email }}" placeholder="primary@example.com, sales@example.com">
                                                        <small class="text-muted">Leads will be sent to these email addresses.</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <!-- Webhook Integration -->
                                            <div class="mb-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input type="checkbox" class="form-check-input lead-input" data-key="webhook_enabled"
                                                           {{ ($leadConfig['webhook_enabled'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold">Webhook Integration</label>
                                                </div>
                                                <div id="webhook-settings" style="display: {{ ($leadConfig['webhook_enabled'] ?? false) ? 'block' : 'none' }};" class="ps-4 border-start ml-2">
                                                    <div class="mb-3">
                                                        <label class="form-label">Webhook URL</label>
                                                        <input type="url" class="form-control lead-input" data-key="webhook_url"
                                                               value="{{ $leadConfig['webhook_url'] ?? '' }}" placeholder="https://api.crm.com/hooks/catch/...">
                                                        <small class="text-muted">We will POST lead data (JSON) to this URL immediately after submission.</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <!-- WhatsApp Integration -->
                                            <div class="mb-4">
                                                <div class="form-check form-switch mb-2">
                                                    <input type="checkbox" class="form-check-input lead-input" data-key="whatsapp_enabled"
                                                           {{ ($leadConfig['whatsapp_enabled'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold">WhatsApp Forwarding</label>
                                                </div>
                                                <div id="whatsapp-settings" style="display: {{ ($leadConfig['whatsapp_enabled'] ?? false) ? 'block' : 'none' }};" class="ps-4 border-start ml-2">
                                                    <div class="mb-3">
                                                        <label class="form-label">WhatsApp Number</label>
                                                        <input type="tel" class="form-control lead-input" data-key="whatsapp_number"
                                                               value="{{ $leadConfig['whatsapp_number'] ?? '' }}" placeholder="+1234567890">
                                                        <small class="text-muted">Receive a WhatsApp message when a new lead arrives (requires connected WhatsApp channel).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQs -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFAQs">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQs" aria-expanded="false" aria-controls="collapseFAQs">
                                        <i class="ti ti-message-circle me-2"></i> FAQs
                                    </button>
                                </h2>
                                <div id="collapseFAQs" class="accordion-collapse collapse" aria-labelledby="headingFAQs" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="builder-section-header">
                                                <h6 class="mb-0 fw-bold">Manage Frequently Asked Questions</h6>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-success me-2" onclick="generateFAQsWithAI()" id="generateFAQsBtn">
                                                        <i class="ti ti-sparkles me-1"></i> Generate with AI
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="addFaq()">
                                                        <i class="ti ti-plus me-1"></i> Add FAQ
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="faqs-container">
                                                @forelse($landingPage->faqs as $index => $faq)
                                                    <div class="dynamic-row faq-row" data-index="{{ $index }}">
                                                        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
                                                            <i class="ti ti-x"></i>
                                                        </button>

                                                        <div class="mb-3">
                                                            <label class="form-label">Question</label>
                                                            <input type="text" name="faqs[{{ $index }}][question]"
                                                                   class="form-control" value="{{ $faq->question }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Answer</label>
                                                            <textarea name="faqs[{{ $index }}][answer]" class="form-control" rows="3" required>{{ $faq->answer }}</textarea>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted text-center py-4">No FAQs yet. Click "Add FAQ" to create one.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Tables -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPricing">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePricing" aria-expanded="false" aria-controls="collapsePricing">
                                        <i class="ti ti-currency-dollar me-2"></i> Pricing Tables
                                    </button>
                                </h2>
                                <div id="collapsePricing" class="accordion-collapse collapse" aria-labelledby="headingPricing" data-bs-parent="#landingPageAccordion">
                                    <div class="accordion-body">
                                        <div class="form-section">
                                            <div class="builder-section-header">
                                                <h6 class="mb-0 fw-bold">Manage Pricing Plans</h6>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-info" id="generate-pricing-btn" onclick="generatePricingTables()">
                                                        <i class="ti ti-sparkles me-1"></i> <span id="generate-pricing-text">Generate with AI</span>
                                                        <span id="generate-pricing-spinner" class="spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="addPricing()">
                                                        <i class="ti ti-plus me-1"></i> Add Pricing Plan
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="pricing-container">
                                                @forelse($landingPage->pricingTables as $index => $pricing)
                                                    <div class="dynamic-row pricing-row" data-index="{{ $index }}">
                                                        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
                                                            <i class="ti ti-x"></i>
                                                        </button>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Plan Name</label>
                                                                <input type="text" name="pricing_tables[{{ $index }}][plan_name]"
                                                                       class="form-control" value="{{ $pricing->plan_name }}" required>
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="form-label">Price</label>
                                                                <input type="number" step="0.01" name="pricing_tables[{{ $index }}][price]"
                                                                       class="form-control" value="{{ $pricing->price }}" required>
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="form-label">Currency</label>
                                                                <input type="text" name="pricing_tables[{{ $index }}][currency_code]"
                                                                       class="form-control" value="{{ $pricing->currency_code }}" placeholder="USD">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Period</label>
                                                                <input type="text" name="pricing_tables[{{ $index }}][period]"
                                                                       class="form-control" value="{{ $pricing->period }}" placeholder="per month">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">CTA Text</label>
                                                                <input type="text" name="pricing_tables[{{ $index }}][cta_text]"
                                                                       class="form-control" value="{{ $pricing->cta_text }}" placeholder="Get Started">
                                                            </div>
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Description</label>
                                                                <textarea name="pricing_tables[{{ $index }}][description]" class="form-control" rows="2">{{ $pricing->description }}</textarea>
                                                            </div>
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Features (one per line)</label>
                                                                <textarea name="pricing_tables[{{ $index }}][features]" class="form-control" rows="4">{{ $pricing->features ? implode("\n", $pricing->features) : '' }}</textarea>
                                                                <small class="text-muted d-block mt-1">Enter each feature on a new line</small>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input type="checkbox" name="pricing_tables[{{ $index }}][is_popular]" value="1"
                                                                           class="form-check-input" {{ $pricing->is_popular ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Mark as Popular</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">CTA Link (optional)</label>
                                                                <input type="text" name="pricing_tables[{{ $index }}][cta_link]"
                                                                       class="form-control" value="{{ $pricing->cta_link }}" placeholder="https://...">
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted text-center py-4">No pricing plans yet. Click "Add Pricing Plan" to create one.</p>
                                                @endforelse
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
                                                       value="{{ old('facebook_pixel_id', $landingPage->facebook_pixel_id) }}"
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
                                                       value="{{ old('tiktok_pixel_id', $landingPage->tiktok_pixel_id) }}"
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
                                                       value="{{ old('snapchat_pixel_id', $landingPage->snapchat_pixel_id) }}"
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
                                                       value="{{ old('google_analytics_id', $landingPage->google_analytics_id) }}"
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
                                                <strong>A/B Testing:</strong> Test different versions of your landing page to see which performs better. Create variants and track their performance.
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" id="ab_testing_enabled" name="ab_testing_enabled" value="1" class="form-check-input" {{ old('ab_testing_enabled', $landingPage->ab_testing_enabled ?? false) ? 'checked' : '' }}>
                                                    <label for="ab_testing_enabled" class="form-check-label fw-bold">Enable A/B Testing</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Enable this to create variants of this landing page for testing.</small>
                                            </div>

                                            <div id="ab-testing-settings" style="display: {{ old('ab_testing_enabled', $landingPage->ab_testing_enabled ?? false) ? 'block' : 'none' }};">
                                                @if($landingPage->parent_variant_id)
                                                    <div class="alert alert-warning mb-3">
                                                        <i class="ti ti-info-circle me-2"></i>
                                                        This is a variant of another landing page. Parent: <strong>{{ $landingPage->parentVariant->slug ?? 'N/A' }}</strong>
                                                    </div>
                                                @endif

                                                @if($landingPage->variants->count() > 0)
                                                    <div class="mb-3">
                                                        <h6 class="fw-bold mb-2">Existing Variants:</h6>
                                                        <div class="list-group">
                                                            @foreach($landingPage->variants as $variant)
                                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <strong>Variant {{ $variant->variant_name }}</strong>
                                                                        <br>
                                                                        <small class="text-muted">Traffic: {{ $variant->traffic_split_percentage }}% |
                                                                        Status: <span class="badge bg-{{ $variant->is_active ? 'success' : 'secondary' }}">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span>
                                                                        @if($variant->is_winner)
                                                                            <span class="badge bg-warning text-dark ms-2">Winner</span>
                                                                        @endif
                                                                        </small>
                                                                    </div>
                                                                    <a href="{{ route('services.landing-page.edit', ['service' => $service, 'landingPage' => $variant]) }}" class="btn btn-sm btn-info">
                                                                        <i class="ti ti-edit me-1"></i> Edit
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="mb-3">
                                                    <button type="button" class="btn btn-primary" id="createVariantBtn" onclick="createVariant()">
                                                        <i class="ti ti-copy me-1"></i> Create Variant
                                                    </button>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="auto_winner_visits" class="form-label">Auto-Select Winner After (Visits)</label>
                                                    <input type="number" id="auto_winner_visits" name="auto_winner_visits" class="form-control"
                                                           value="{{ old('auto_winner_visits', $landingPage->auto_winner_visits) }}"
                                                           placeholder="e.g., 1000" min="0">
                                                    <small class="text-muted d-block mt-1">Automatically select the winning variant after this many visits. Leave empty to manually select.</small>
                                                </div>

                                                @if($landingPage->parent_variant_id && $landingPage->parentVariant)
                                                    <div class="mb-3">
                                                        <label for="traffic_split_percentage" class="form-label">Traffic Split Percentage</label>
                                                        <input type="number" id="traffic_split_percentage" name="traffic_split_percentage" class="form-control"
                                                               value="{{ old('traffic_split_percentage', $landingPage->traffic_split_percentage ?? 50) }}"
                                                               min="0" max="100">
                                                        <small class="text-muted d-block mt-1">Percentage of traffic to send to this variant (0-100).</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input type="checkbox" id="is_winner" name="is_winner" value="1" class="form-check-input" {{ old('is_winner', $landingPage->is_winner ?? false) ? 'checked' : '' }}>
                                                            <label for="is_winner" class="form-check-label fw-bold">Mark as Winner</label>
                                                        </div>
                                                        <small class="text-muted d-block mt-1">Mark this variant as the winning version.</small>
                                                    </div>
                                                @endif
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
                                            <div class="builder-section-header">
                                                <h6 class="mb-0 fw-bold">Manage CTA Variants</h6>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="addCtaVariant()">
                                                    <i class="ti ti-plus me-1"></i> Add CTA Variant
                                                </button>
                                            </div>

                                            <div class="alert alert-info border-0 mb-4">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>Smart CTAs:</strong> Create multiple call-to-action buttons with conditional display rules based on visitor behavior, device, location, or language.
                                            </div>

                                            <div id="cta-variants-container">
                                                @forelse($landingPage->ctaVariants as $index => $cta)
                                                    <div class="dynamic-row cta-variant-row" data-index="{{ $index }}">
                                                        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
                                                            <i class="ti ti-x"></i>
                                                        </button>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">CTA Text</label>
                                                                <input type="text" name="cta_variants[{{ $index }}][cta_text]" class="form-control" value="{{ $cta->cta_text }}" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">CTA Link (optional)</label>
                                                                <input type="text" name="cta_variants[{{ $index }}][cta_link]" class="form-control" value="{{ $cta->cta_link }}" placeholder="https://...">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Position</label>
                                                                <select name="cta_variants[{{ $index }}][position]" class="form-select">
                                                                    <option value="hero" {{ $cta->position == 'hero' ? 'selected' : '' }}>Hero</option>
                                                                    <option value="sticky" {{ $cta->position == 'sticky' ? 'selected' : '' }}>Sticky</option>
                                                                    <option value="exit_intent" {{ $cta->position == 'exit_intent' ? 'selected' : '' }}>Exit Intent</option>
                                                                    <option value="time_based" {{ $cta->position == 'time_based' ? 'selected' : '' }}>Time-Based</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Style</label>
                                                                <select name="cta_variants[{{ $index }}][cta_style]" class="form-select">
                                                                    <option value="primary" {{ $cta->cta_style == 'primary' ? 'selected' : '' }}>Primary</option>
                                                                    <option value="secondary" {{ $cta->cta_style == 'secondary' ? 'selected' : '' }}>Secondary</option>
                                                                    <option value="success" {{ $cta->cta_style == 'success' ? 'selected' : '' }}>Success</option>
                                                                    <option value="info" {{ $cta->cta_style == 'info' ? 'selected' : '' }}>Info</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Status</label>
                                                                <div class="form-check form-switch mt-2">
                                                                    <input type="checkbox" name="cta_variants[{{ $index }}][is_active]" value="1" class="form-check-input" {{ $cta->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Active</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input type="checkbox" name="cta_variants[{{ $index }}][show_on_first_visit]" value="1" class="form-check-input" {{ $cta->show_on_first_visit ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Show on First Visit</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input type="checkbox" name="cta_variants[{{ $index }}][show_on_returning_visit]" value="1" class="form-check-input" {{ $cta->show_on_returning_visit ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Show on Returning Visit</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input type="checkbox" name="cta_variants[{{ $index }}][show_on_mobile]" value="1" class="form-check-input" {{ $cta->show_on_mobile ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Show on Mobile</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input type="checkbox" name="cta_variants[{{ $index }}][show_on_desktop]" value="1" class="form-check-input" {{ $cta->show_on_desktop ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Show on Desktop</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted text-center py-4">No CTA variants yet. Click "Add CTA Variant" to create one.</p>
                                                @endforelse
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
                                                    <input type="checkbox" id="sticky_cta_enabled" name="sticky_cta_enabled" value="1" class="form-check-input" {{ old('sticky_cta_enabled', $landingPage->sticky_cta_enabled ?? false) ? 'checked' : '' }}>
                                                    <label for="sticky_cta_enabled" class="form-check-label fw-bold">Enable Sticky CTA</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a sticky call-to-action button that stays visible while scrolling.</small>
                                            </div>

                                            <div id="sticky-cta-settings" style="display: {{ old('sticky_cta_enabled', $landingPage->sticky_cta_enabled ?? false) ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="sticky_cta_text" class="form-label">Sticky CTA Button Text</label>
                                                    <input type="text" id="sticky_cta_text" name="sticky_cta_text" class="form-control"
                                                           value="{{ old('sticky_cta_text', $landingPage->sticky_cta_text ?? 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="sticky_cta_position" class="form-label">Position</label>
                                                    <select id="sticky_cta_position" name="sticky_cta_position" class="form-select">
                                                        <option value="bottom" {{ old('sticky_cta_position', $landingPage->sticky_cta_position ?? 'bottom') == 'bottom' ? 'selected' : '' }}>Bottom</option>
                                                        <option value="top" {{ old('sticky_cta_position', $landingPage->sticky_cta_position ?? 'bottom') == 'top' ? 'selected' : '' }}>Top</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" id="sticky_cta_mobile_only" name="sticky_cta_mobile_only" value="1" class="form-check-input" {{ old('sticky_cta_mobile_only', $landingPage->sticky_cta_mobile_only ?? true) ? 'checked' : '' }}>
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
                                                    <input type="checkbox" id="exit_intent_enabled" name="exit_intent_enabled" value="1" class="form-check-input" {{ old('exit_intent_enabled', $landingPage->exit_intent_enabled ?? false) ? 'checked' : '' }}>
                                                    <label for="exit_intent_enabled" class="form-check-label fw-bold">Enable Exit Intent Popup</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a popup when visitors are about to leave the page (desktop only).</small>
                                            </div>

                                            <div id="exit-intent-settings" style="display: {{ old('exit_intent_enabled', $landingPage->exit_intent_enabled ?? false) ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="exit_intent_title" class="form-label">Popup Title</label>
                                                    <input type="text" id="exit_intent_title" name="exit_intent_title" class="form-control"
                                                           value="{{ old('exit_intent_title', $landingPage->exit_intent_title) }}"
                                                           placeholder="Wait! Don't miss out">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="exit_intent_message" class="form-label">Popup Message</label>
                                                    <textarea id="exit_intent_message" name="exit_intent_message" class="form-control" rows="3"
                                                              placeholder="Get started today and save 20%!">{{ old('exit_intent_message', $landingPage->exit_intent_message) }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="exit_intent_cta_text" class="form-label">CTA Button Text</label>
                                                    <input type="text" id="exit_intent_cta_text" name="exit_intent_cta_text" class="form-control"
                                                           value="{{ old('exit_intent_cta_text', $landingPage->exit_intent_cta_text ?? 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" id="exit_intent_desktop_only" name="exit_intent_desktop_only" value="1" class="form-check-input" {{ old('exit_intent_desktop_only', $landingPage->exit_intent_desktop_only ?? true) ? 'checked' : '' }}>
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
                                                    <input type="checkbox" id="time_based_popup_enabled" name="time_based_popup_enabled" value="1" class="form-check-input" {{ old('time_based_popup_enabled', $landingPage->time_based_popup_enabled ?? false) ? 'checked' : '' }}>
                                                    <label for="time_based_popup_enabled" class="form-check-label fw-bold">Enable Time-Based Popup</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Show a popup after visitors spend a certain amount of time on the page.</small>
                                            </div>

                                            <div id="time-based-popup-settings" style="display: {{ old('time_based_popup_enabled', $landingPage->time_based_popup_enabled ?? false) ? 'block' : 'none' }};">
                                                <div class="mb-3">
                                                    <label for="time_based_popup_delay" class="form-label">Show After (Seconds)</label>
                                                    <input type="number" id="time_based_popup_delay" name="time_based_popup_delay" class="form-control"
                                                           value="{{ old('time_based_popup_delay', $landingPage->time_based_popup_delay ?? 30) }}"
                                                           placeholder="30" min="1">
                                                    <small class="text-muted d-block mt-1">Number of seconds before showing the popup.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_title" class="form-label">Popup Title</label>
                                                    <input type="text" id="time_based_popup_title" name="time_based_popup_title" class="form-control"
                                                           value="{{ old('time_based_popup_title', $landingPage->time_based_popup_title) }}"
                                                           placeholder="Special Offer!">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_message" class="form-label">Popup Message</label>
                                                    <textarea id="time_based_popup_message" name="time_based_popup_message" class="form-control" rows="3"
                                                              placeholder="Get 20% off your first order!">{{ old('time_based_popup_message', $landingPage->time_based_popup_message) }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="time_based_popup_cta_text" class="form-label">CTA Button Text</label>
                                                    <input type="text" id="time_based_popup_cta_text" name="time_based_popup_cta_text" class="form-control"
                                                           value="{{ old('time_based_popup_cta_text', $landingPage->time_based_popup_cta_text ?? 'Get Started') }}"
                                                           placeholder="Get Started">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="ti ti-device-floppy me-1"></i> Save Landing Page
                            </button>
                            <a href="{{ route('services.mine') }}" class="btn btn-secondary btn-lg">
                                <i class="ti ti-x me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script data-cfasync="false">
let questionIndex = {{ $landingPage->questions->count() }};
let faqIndex = {{ $landingPage->faqs->count() }};
let pricingIndex = {{ $landingPage->pricingTables->count() }};
let ctaVariantIndex = {{ $landingPage->ctaVariants->count() }};

// Fix for "invalid form control is not focusable" error
// When form validation fails, expand all accordion sections so browser can focus on invalid fields
document.getElementById('landingPageForm').addEventListener('invalid', function(e) {
    // Find all collapsed accordion sections and expand them
    const collapsedSections = document.querySelectorAll('.accordion-collapse:not(.show)');
    collapsedSections.forEach(section => {
        const bsCollapse = new bootstrap.Collapse(section, {
            toggle: false
        });
        bsCollapse.show();
    });
}, true); // Use capture phase to catch events from child elements

function addQuestion() {
    const container = document.getElementById('questions-container');
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row question-row';
    row.setAttribute('data-index', questionIndex);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Question Text</label>
                <input type="text" name="questions[${questionIndex}][question_text]" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Field Type</label>
                <select name="questions[${questionIndex}][field_type]" class="form-select" onchange="toggleFieldOptions(this)">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Select</option>
                    <option value="radio">Radio</option>
                    <option value="checkbox">Checkbox</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Required</label>
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" name="questions[${questionIndex}][is_required]" value="1" class="form-check-input">
                    <label class="form-check-label">{{ __('common.yes') }}</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Placeholder</label>
                <input type="text" name="questions[${questionIndex}][placeholder]" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Help Text</label>
                <input type="text" name="questions[${questionIndex}][help_text]" class="form-control">
            </div>
            <div class="col-md-12 mb-3 field-options-container" style="display: none">
                <label class="form-label">Field Options (one per line, for select/radio/checkbox)</label>
                <textarea name="questions[${questionIndex}][field_options]" class="form-control" rows="3"></textarea>
                <small class="text-muted d-block mt-1">Enter each option on a new line</small>
            </div>
        </div>
    `;

    container.appendChild(row);
    questionIndex++;
}

function addFaq() {
    const container = document.getElementById('faqs-container');
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row faq-row';
    row.setAttribute('data-index', faqIndex);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="mb-3">
            <label class="form-label">Question</label>
            <input type="text" name="faqs[${faqIndex}][question]" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Answer</label>
            <textarea name="faqs[${faqIndex}][answer]" class="form-control" rows="3" required></textarea>
        </div>
    `;

    container.appendChild(row);
    faqIndex++;
}

function addPricing() {
    const container = document.getElementById('pricing-container');
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row pricing-row';
    row.setAttribute('data-index', pricingIndex);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Plan Name</label>
                <input type="text" name="pricing_tables[${pricingIndex}][plan_name]" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="pricing_tables[${pricingIndex}][price]" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Currency</label>
                <input type="text" name="pricing_tables[${pricingIndex}][currency_code]" class="form-control" value="USD" placeholder="USD">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Period</label>
                <input type="text" name="pricing_tables[${pricingIndex}][period]" class="form-control" placeholder="per month">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Text</label>
                <input type="text" name="pricing_tables[${pricingIndex}][cta_text]" class="form-control" value="Get Started">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <textarea name="pricing_tables[${pricingIndex}][description]" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Features (one per line)</label>
                <textarea name="pricing_tables[${pricingIndex}][features]" class="form-control" rows="4"></textarea>
                <small class="text-muted d-block mt-1">Enter each feature on a new line</small>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="pricing_tables[${pricingIndex}][is_popular]" value="1" class="form-check-input">
                    <label class="form-check-label">Mark as Popular</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Link (optional)</label>
                <input type="text" name="pricing_tables[${pricingIndex}][cta_link]" class="form-control" placeholder="https://...">
            </div>
        </div>
    `;

    container.appendChild(row);
    pricingIndex++;
}

function removeRow(btn) {
    if (confirm('Are you sure you want to remove this item?')) {
        btn.closest('.dynamic-row').remove();
    }
}

function toggleFieldOptions(select) {
    const row = select.closest('.question-row');
    const optionsContainer = row.querySelector('.field-options-container');
    const needsOptions = ['select', 'radio', 'checkbox'].includes(select.value);
    optionsContainer.style.display = needsOptions ? 'block' : 'none';
}

// Initialize field options visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select[name*="[field_type]"]').forEach(select => {
        toggleFieldOptions(select);
    });
});

// Initialize Laraberg editor for description
Laraberg.init('description-editor', { height: '600px', laravelFilemanager: false, sidebar: true });

// Character counters for SEO fields
const metaTitleInput = document.getElementById('meta_title');
const metaDescriptionInput = document.getElementById('meta_description');
const metaTitleLength = document.getElementById('meta_title_length');
const metaDescriptionLength = document.getElementById('meta_description_length');

function updateMetaTitleLength() {
    if (!metaTitleInput || !metaTitleLength) return;
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
    if (!metaDescriptionInput || !metaDescriptionLength) return;
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

// AI Generation Functions
function generateQuestionsWithAI() {
    const btn = document.getElementById('generateQuestionsBtn');
    const originalHtml = btn.innerHTML;

    // Show loading state
    btn.disabled = true;
    btn.classList.add('ai-loading');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating...';

    // Get service information for context
    const serviceTitle = '{{ $service->title }}';
    const heroTitle = document.getElementById('hero_title')?.value || '';
    const heroDescription = document.getElementById('hero_description')?.value || '';
    const description = document.getElementById('description-editor')?.value || '';

    // Make AJAX request
    fetch('{{ route("services.landing-page.generate-questions", $service) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            service_title: serviceTitle,
            hero_title: heroTitle,
            hero_description: heroDescription,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.questions) {
            // Add generated questions to the form
            data.questions.forEach(question => {
                addQuestionFromAI(question);
            });

            // Show success message
            showAlert('success', `Successfully generated ${data.questions.length} form question(s) with AI!`);
        } else {
            showAlert('danger', data.message || 'Failed to generate questions. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while generating questions. Please try again.');
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btn.classList.remove('ai-loading');
        btn.innerHTML = originalHtml;
    });
}

function generateFAQsWithAI() {
    const btn = document.getElementById('generateFAQsBtn');
    const originalHtml = btn.innerHTML;

    // Show loading state
    btn.disabled = true;
    btn.classList.add('ai-loading');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating...';

    // Get service information for context
    const serviceTitle = '{{ $service->title }}';
    const heroTitle = document.getElementById('hero_title')?.value || '';
    const heroDescription = document.getElementById('hero_description')?.value || '';
    const description = document.getElementById('description-editor')?.value || '';

    // Make AJAX request
    fetch('{{ route("services.landing-page.generate-faqs", $service) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            service_title: serviceTitle,
            hero_title: heroTitle,
            hero_description: heroDescription,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.faqs) {
            // Add generated FAQs to the form
            data.faqs.forEach(faq => {
                addFaqFromAI(faq);
            });

            // Show success message
            showAlert('success', `Successfully generated ${data.faqs.length} FAQ(s) with AI!`);
        } else {
            showAlert('danger', data.message || 'Failed to generate FAQs. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while generating FAQs. Please try again.');
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btn.classList.remove('ai-loading');
        btn.innerHTML = originalHtml;
    });
}

function addQuestionFromAI(question) {
    const container = document.getElementById('questions-container');

    // Remove empty message if exists
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row question-row';
    row.setAttribute('data-index', questionIndex);

    const fieldType = question.field_type || 'text';
    const needsOptions = ['select', 'radio', 'checkbox'].includes(fieldType);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Question Text</label>
                <input type="text" name="questions[${questionIndex}][question_text]" class="form-control" value="${escapeHtml(question.question_text || '')}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Field Type</label>
                <select name="questions[${questionIndex}][field_type]" class="form-select" onchange="toggleFieldOptions(this)">
                    <option value="text" ${fieldType === 'text' ? 'selected' : ''}>Text</option>
                    <option value="textarea" ${fieldType === 'textarea' ? 'selected' : ''}>Textarea</option>
                    <option value="email" ${fieldType === 'email' ? 'selected' : ''}>Email</option>
                    <option value="phone" ${fieldType === 'phone' ? 'selected' : ''}>Phone</option>
                    <option value="number" ${fieldType === 'number' ? 'selected' : ''}>Number</option>
                    <option value="date" ${fieldType === 'date' ? 'selected' : ''}>Date</option>
                    <option value="select" ${fieldType === 'select' ? 'selected' : ''}>Select</option>
                    <option value="radio" ${fieldType === 'radio' ? 'selected' : ''}>Radio</option>
                    <option value="checkbox" ${fieldType === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Required</label>
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" name="questions[${questionIndex}][is_required]" value="1" class="form-check-input" ${question.is_required ? 'checked' : ''}>
                    <label class="form-check-label">{{ __('common.yes') }}</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Placeholder</label>
                <input type="text" name="questions[${questionIndex}][placeholder]" class="form-control" value="${escapeHtml(question.placeholder || '')}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Help Text</label>
                <input type="text" name="questions[${questionIndex}][help_text]" class="form-control" value="${escapeHtml(question.help_text || '')}">
            </div>
            <div class="col-md-12 mb-3 field-options-container" style="display: ${needsOptions ? 'block' : 'none'}">
                <label class="form-label">Field Options (one per line, for select/radio/checkbox)</label>
                <textarea name="questions[${questionIndex}][field_options]" class="form-control" rows="3">${escapeHtml((question.field_options || []).join('\n'))}</textarea>
                <small class="text-muted d-block mt-1">Enter each option on a new line</small>
            </div>
        </div>
    `;

    container.appendChild(row);
    questionIndex++;
}

function addFaqFromAI(faq) {
    const container = document.getElementById('faqs-container');

    // Remove empty message if exists
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row faq-row';
    row.setAttribute('data-index', faqIndex);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="mb-3">
            <label class="form-label">Question</label>
            <input type="text" name="faqs[${faqIndex}][question]" class="form-control" value="${escapeHtml(faq.question || '')}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Answer</label>
            <textarea name="faqs[${faqIndex}][answer]" class="form-control" rows="3" required>${escapeHtml(faq.answer || '')}</textarea>
        </div>
    `;

    container.appendChild(row);
    faqIndex++;
}

window.rewriteText = function(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field || !field.value.trim()) {
        alert('Please enter some text first to rewrite.');
        return;
    }

    const originalText = field.value;
    const btn = event.target.closest('button');
    const originalBtnText = btn.innerHTML;

    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader fa-spin me-1"></i> Rewriting...';

    // Make AJAX request
    fetch('{{ route("services.landing-page.generate-content", $service) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            prompt_type: 'rewrite',
            current_text: originalText,
            field_name: fieldId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.content) {
            field.value = data.content;
            // Highlight change
            field.style.transition = 'background-color 0.5s';
            field.style.backgroundColor = '#e6fffa';
            setTimeout(() => {
                field.style.backgroundColor = '';
            }, 1000);
        } else {
            alert(data.message || 'Failed to rewrite text');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while rewriting text');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalBtnText;
    });
};

function generatePricingTables() {
    const btn = document.getElementById('generate-pricing-btn');
    const btnText = document.getElementById('generate-pricing-text');
    const btnSpinner = document.getElementById('generate-pricing-spinner');
    const originalHtml = btn.innerHTML;

    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Generating...';
    btnSpinner.classList.remove('d-none');

    // Get service information for context
    const serviceTitle = '{{ $service->title }}';
    const servicePrice = {{ $service->price ?? 0 }};
    const serviceCurrency = '{{ $service->currency ?? "" }}';
    const heroTitle = document.getElementById('hero_title')?.value || '';
    const heroDescription = document.getElementById('hero_description')?.value || '';
    const description = document.getElementById('description-editor')?.value || '';

    // Make AJAX request
    fetch('{{ route("services.landing-page.generate-pricing", $service) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            service_title: serviceTitle,
            hero_title: heroTitle,
            hero_description: heroDescription,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.pricing_tables) {
            // Add generated pricing tables to the form
            data.pricing_tables.forEach(pricing => {
                addPricingFromAI(pricing);
            });

            // Show success message
            showAlert('success', `Successfully generated ${data.pricing_tables.length} pricing plan(s) with AI! ${data.message || ''}`);
        } else {
            showAlert('danger', data.message || 'Failed to generate pricing tables. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while generating pricing tables. Please try again.');
    })
    .finally(() => {
        // Restore button state
        btn.disabled = false;
        btnText.textContent = 'Generate with AI';
        btnSpinner.classList.add('d-none');
    });
}

function addPricingFromAI(pricing) {
    const container = document.getElementById('pricing-container');

    // Remove empty message if exists
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row pricing-row';
    row.setAttribute('data-index', pricingIndex);

    // Convert features array to newline-separated string
    const featuresText = Array.isArray(pricing.features)
        ? pricing.features.join('\n')
        : (pricing.features || '');

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Plan Name</label>
                <input type="text" name="pricing_tables[${pricingIndex}][plan_name]" class="form-control" value="${escapeHtml(pricing.plan_name || '')}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="pricing_tables[${pricingIndex}][price]" class="form-control" value="${pricing.price || 0}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Currency</label>
                <input type="text" name="pricing_tables[${pricingIndex}][currency_code]" class="form-control" value="${escapeHtml(pricing.currency_code || 'USD')}" placeholder="USD">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Period</label>
                <input type="text" name="pricing_tables[${pricingIndex}][period]" class="form-control" value="${escapeHtml(pricing.period || '')}" placeholder="per month">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Text</label>
                <input type="text" name="pricing_tables[${pricingIndex}][cta_text]" class="form-control" value="${escapeHtml(pricing.cta_text || 'Get Started')}" placeholder="Get Started">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <textarea name="pricing_tables[${pricingIndex}][description]" class="form-control" rows="2">${escapeHtml(pricing.description || '')}</textarea>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Features (one per line)</label>
                <textarea name="pricing_tables[${pricingIndex}][features]" class="form-control" rows="4">${escapeHtml(featuresText)}</textarea>
                <small class="text-muted d-block mt-1">Enter each feature on a new line</small>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="pricing_tables[${pricingIndex}][is_popular]" value="1" class="form-check-input" ${pricing.is_popular ? 'checked' : ''}>
                    <label class="form-check-label">Mark as Popular</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Link (optional)</label>
                <input type="text" name="pricing_tables[${pricingIndex}][cta_link]" class="form-control" value="${escapeHtml(pricing.cta_link || '')}" placeholder="https://...">
            </div>
        </div>
    `;

    container.appendChild(row);
    pricingIndex++;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-auto-dismiss');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-dismiss shadow-sm`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <i class="ti ti-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert at the top of the form
    const form = document.getElementById('landingPageForm');
    form.insertBefore(alertDiv, form.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function addCtaVariant() {
    const container = document.getElementById('cta-variants-container');
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row cta-variant-row';
    row.setAttribute('data-index', ctaVariantIndex);

    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeRow(this)" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Text</label>
                <input type="text" name="cta_variants[${ctaVariantIndex}][cta_text]" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CTA Link (optional)</label>
                <input type="text" name="cta_variants[${ctaVariantIndex}][cta_link]" class="form-control" placeholder="https://...">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Position</label>
                <select name="cta_variants[${ctaVariantIndex}][position]" class="form-select">
                    <option value="hero">Hero</option>
                    <option value="sticky">Sticky</option>
                    <option value="exit_intent">Exit Intent</option>
                    <option value="time_based">Time-Based</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Style</label>
                <select name="cta_variants[${ctaVariantIndex}][cta_style]" class="form-select">
                    <option value="primary">Primary</option>
                    <option value="secondary">Secondary</option>
                    <option value="success">Success</option>
                    <option value="info">Info</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" name="cta_variants[${ctaVariantIndex}][is_active]" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="cta_variants[${ctaVariantIndex}][show_on_first_visit]" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Show on First Visit</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="cta_variants[${ctaVariantIndex}][show_on_returning_visit]" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Show on Returning Visit</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="cta_variants[${ctaVariantIndex}][show_on_mobile]" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Show on Mobile</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="cta_variants[${ctaVariantIndex}][show_on_desktop]" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Show on Desktop</label>
                </div>
            </div>
        </div>
    `;

    container.appendChild(row);
    ctaVariantIndex++;
}

// Toggle CRO feature settings
document.addEventListener('DOMContentLoaded', function() {
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

    // Toggle Lead Routing Settings
    const emailNotifToggle = document.querySelector('.lead-input[data-key="email_notification"]');
    if(emailNotifToggle) {
        emailNotifToggle.addEventListener('change', function() {
            document.getElementById('email-notification-settings').style.display = this.checked ? 'block' : 'none';
        });
    }

    const webhookToggle = document.querySelector('.lead-input[data-key="webhook_enabled"]');
    if(webhookToggle) {
        webhookToggle.addEventListener('change', function() {
            document.getElementById('webhook-settings').style.display = this.checked ? 'block' : 'none';
        });
    }

    const whatsappToggle = document.querySelector('.lead-input[data-key="whatsapp_enabled"]');
    if(whatsappToggle) {
        whatsappToggle.addEventListener('change', function() {
            document.getElementById('whatsapp-settings').style.display = this.checked ? 'block' : 'none';
        });
    }

    // Config Updaters
    window.updateLayoutConfig = function() {
        // Implementation for updating hidden JSON from checkboxes can go here
        // For simple toggles we might just read on submit or use event listeners
        const layout = JSON.parse(document.getElementById('layout_config').value || '{}');
        document.querySelectorAll('.section-toggle').forEach(el => {
            layout[el.dataset.section] = el.checked;
        });
        document.getElementById('layout_config').value = JSON.stringify(layout);
    };

    window.updateStyleConfig = function() {
        const style = JSON.parse(document.getElementById('style_config').value || '{}');
        document.querySelectorAll('.style-input').forEach(el => {
            style[el.dataset.style] = el.value;
        });
        document.getElementById('style_config').value = JSON.stringify(style);
    };

    window.updateLeadConfig = function() {
        const lead = JSON.parse(document.getElementById('lead_routing_config').value || '{}');
        document.querySelectorAll('.lead-input').forEach(el => {
            if(el.type === 'checkbox') {
                lead[el.dataset.key] = el.checked;
            } else {
                lead[el.dataset.key] = el.value;
            }
        });
        document.getElementById('lead_routing_config').value = JSON.stringify(lead);
    };

    window.updateFormConfig = function() {
        const form = JSON.parse(document.getElementById('form_config').value || '{}');
        document.querySelectorAll('.form-config-input').forEach(el => {
            form[el.dataset.key] = el.value;
        });
        document.getElementById('form_config').value = JSON.stringify(form);
    };

    // Attach listeners
    document.querySelectorAll('.section-toggle').forEach(el => el.addEventListener('change', window.updateLayoutConfig));
    document.querySelectorAll('.style-input').forEach(el => el.addEventListener('change', window.updateStyleConfig));
    document.querySelectorAll('.lead-input').forEach(el => el.addEventListener('change', window.updateLeadConfig));
    document.querySelectorAll('.lead-input').forEach(el => el.addEventListener('input', window.updateLeadConfig));
    document.querySelectorAll('.form-config-input').forEach(el => el.addEventListener('input', window.updateFormConfig));

    // Form Config Sync (Optional: Sync older questions system to new JSON config if needed)
    // For now we trust the controller to handle 'questions' array primarily, unless we fully deprecate it.

});
</script>
@endpush
