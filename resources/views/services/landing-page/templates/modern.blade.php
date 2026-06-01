{{-- Template 1: Modern/Minimal --}}
@php
    $layout = $landingPage->layout_config ?? [];
    $styles = $landingPage->style_config ?? [];
    
    // Default values
    $primaryColor = $styles['primary_color'] ?? '#667eea';
    $secondaryColor = '#764ba2'; // Could be derived or configurable
    $fontFamily = $styles['font_family'] ?? 'Inter';
    $borderRadius = $styles['border_radius'] ?? 'rounded';
    
    // Map bootstrap classes/radii based on selection
    $radiusValue = '5px';
    if($borderRadius === 'rounded-0') $radiusValue = '0px';
    if($borderRadius === 'rounded-pill') $radiusValue = '20px'; // Moderate curve for cards
    
    // Section Visibility Defaults
    $showHero = $layout['hero'] ?? true;
    $showFeatures = $layout['features'] ?? true; // Maps to Description/Content
    $showGallery = $layout['gallery'] ?? true; // New key if we want to granularly control it, or bundle with features
    $showPricing = $layout['pricing'] ?? true;
    $showTestimonials = $layout['testimonials'] ?? true; // Template doesn't have this yet
    $showFaq = $layout['faq'] ?? true;
    $showCta = $layout['cta'] ?? true; // Maps to Contact Form
@endphp

<style>
    :root {
        --primary-color: {{ $primaryColor }};
        --border-radius: {{ $radiusValue }};
        --font-family: '{{ $fontFamily }}', sans-serif;
    }
    body {
        font-family: var(--font-family);
    }
    .landing-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, #4a5568 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.95;
    }
    .hero-cta {
        background: white;
        color: var(--primary-color);
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px; /* Always pill for CTA button usually looks best, or use variable */
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    .hero-cta:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        color: var(--primary-color);
    }
    .section-padding {
        padding: 80px 0;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        border-radius: var(--border-radius);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .service-gallery img:hover {
        filter: brightness(1.03);
    }
    .form-section {
        background: #f8f9fa;
        padding: 60px 0;
    }
    .form-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .faq-item {
        border: 1px solid #e9ecef;
        border-radius: var(--border-radius);
        margin-bottom: 15px;
        overflow: hidden;
    }
    .faq-question {
        background: #f8f9fa;
        padding: 20px;
        margin: 0;
        cursor: pointer;
        transition: background 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .faq-question:hover {
        background: #e9ecef;
    }
    .faq-answer {
        padding: 20px;
        background: white;
        display: none;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    .pricing-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        position: relative;
        transition: transform 0.3s ease;
    }
    .pricing-card:hover {
        
    }
    .pricing-card.popular {
        border: 3px solid var(--primary-color);
        transform: scale(1.05);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -10px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .price-display {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 20px 0;
    }
    .price-period {
        color: #6c757d;
        font-size: 1rem;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    .pricing-features li {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #28a745;
        font-weight: bold;
        margin-right: 10px;
    }
    .description-section {
        padding: 60px 0;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.8;
        font-size: 1.1rem;
        color: #555;
    }
    .description-content[dir="rtl"] {
        text-align: right !important;
    }
    .description-content[dir="rtl"] h2,
    .description-content[dir="rtl"] h3,
    .description-content[dir="rtl"] h4 {
        text-align: right !important;
    }
    .description-content.text-center h2,
    .description-content.text-center h3,
    .description-content.text-center h4 {
        text-align: center;
    }
    .description-content.text-left h2,
    .description-content.text-left h3,
    .description-content.text-left h4 {
        text-align: left;
    }
    .description-content.text-right h2,
    .description-content.text-right h3,
    .description-content.text-right h4 {
        text-align: right;
    }
    .description-content[dir="rtl"] ul,
    .description-content[dir="rtl"] ol {
        padding-right: 20px;
        padding-left: 0;
    }
    .description-content[dir="rtl"] blockquote {
        border-right: 4px solid var(--primary-color);
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #333;
        margin-top: 30px;
        margin-bottom: 15px;
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin: 20px 0;
    }
    .testimonial-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    .testimonial-card:hover {
        
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .landing-hero {
            padding: 60px 0;
        }
        .landing-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .landing-hero p {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        .hero-cta {
            padding: 12px 30px;
            font-size: 1rem;
        }
        .section-padding {
            padding: 50px 0;
        }
        .service-gallery {
            grid-template-columns: 1fr;
            gap: 15px;
            margin: 30px 0;
        }
        .service-gallery img {
            height: 200px;
        }
        .form-card {
            padding: 30px 20px;
        }
        .description-content {
            font-size: 1rem;
            padding: 0 10px;
        }
        .description-content h2,
        .description-content h3,
        .description-content h4 {
            margin-top: 25px;
        }
        .testimonial-card {
            padding: 20px;
        }
    }

    @media (max-width: 576px) {
        .landing-hero {
            padding: 40px 0;
        }
        .landing-hero h1 {
            font-size: 2rem;
        }
        .landing-hero p {
            font-size: 1rem;
        }
        .hero-cta {
            padding: 10px 25px;
            font-size: 0.9rem;
        }
        .section-padding {
            padding: 40px 0;
        }
        .form-card {
            padding: 25px 15px;
        }
        .price-display {
            font-size: 2.5rem;
        }
        .testimonial-card {
            padding: 15px;
        }
    }
</style>

<!-- Hero Section -->
@php
    $order = $layout['order'] ?? ['hero', 'features', 'gallery', 'pricing', 'faq', 'cta'];
@endphp

@foreach($order as $section)
    @switch($section)
        @case('hero')
            @if($showHero)
                <div class="landing-hero">
                    <div class="container">
                        <h1>{{ $landingPage->hero_title }}</h1>
                        @if($landingPage->hero_description)
                            <p>{{ $landingPage->hero_description }}</p>
                        @endif
                        <a href="#contact-form" id="hero-cta" class="btn hero-cta">{{ $landingPage->hero_cta_text }}</a>
                    </div>
                </div>
            @endif
            @break

        @case('features')
            @if($showFeatures && $landingPage->description)
                @php
                    $descriptionText = strip_tags($landingPage->description);
                    $rtlPattern = '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0700}-\x{074F}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
                    $isRTL = preg_match($rtlPattern, $descriptionText);
                    $textDirection = $isRTL ? 'rtl' : 'ltr';
                    $alignment = $isRTL ? 'right' : ($landingPage->description_alignment ?? 'left');
                    $alignmentClass = 'text-' . $alignment;
                @endphp
                <div class="container">
                    <div class="description-section">
                        <div class="description-content {{ $alignmentClass }}" dir="{{ $textDirection }}">
                            {!! $landingPage->description !!}
                        </div>
                    </div>
                </div>
            @endif
            @break

        @case('gallery')
            @if($showGallery && ($landingPage->service->images->count() > 0 || $landingPage->service->image))
                <div class="container">
                    <div class="section-padding">
                        <h2 class="text-center mb-4">{{ __('general.our_service_gallery') }}</h2>
                        <div class="service-gallery">
                            @foreach($landingPage->service->images as $image)
                                <img alt="{{ $landingPage->hero_title }}" src="{{ asset($image->image_path) }}">
                            @endforeach
                        </div>
                        @if($landingPage->service->image)
                            <div class="service-gallery mt-3">
                                <img alt="{{ $landingPage->hero_title }}" src="{{ asset($landingPage->service->image) }}">
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            @break

        @case('pricing')
            @if($showPricing && $landingPage->pricingTables->count() > 0)
                <div class="container">
                    <div class="section-padding">
                        <h2 class="text-center mb-4">{{ __('general.pricing_plans') }}</h2>
                        <div class="pricing-grid">
                            @foreach($landingPage->pricingTables as $pricing)
                                <div class="pricing-card {{ $pricing->is_popular ? 'popular' : '' }}">
                                    <h3>{{ $pricing->plan_name }}</h3>
                                    @if($pricing->description)
                                        <p class="text-muted">{{ $pricing->description }}</p>
                                    @endif
                                    <div class="price-display">
                                        {{ $pricing->currency_code }} {{ number_format($pricing->price, 2) }}
                                        @if($pricing->period)
                                            <span class="price-period">/ {{ $pricing->period }}</span>
                                        @endif
                                    </div>
                                    @if($pricing->features)
                                        <ul class="pricing-features">
                                            @foreach($pricing->features as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <a href="{{ $pricing->cta_link ?: '#contact-form' }}" class="btn btn-primary w-100">
                                        {{ $pricing->cta_text }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            @break

        @case('faq')
            @if($showFaq && $landingPage->faqs->count() > 0)
                <div class="container">
                    <div class="section-padding">
                        <h2 class="text-center mb-4">{{ __('general.frequently_asked_questions') }}</h2>
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                @foreach($landingPage->faqs as $faq)
                                    <div class="faq-item">
                                        <div class="faq-question" onclick="toggleFaq(this)">
                                            <span>{{ $faq->question }}</span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="faq-answer">
                                            {!! nl2br(e($faq->answer)) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @break

        @case('cta')
            @if($showCta)
                <div class="container">
                    @include('services.landing-page.partials.contact-form', ['landingPage' => $landingPage])
                </div>
            @endif
            @break
            
        @case('testimonials')
            @if($showTestimonials && $landingPage->service->approvedReviews->count() > 0)
                <div class="container">
                    <div class="section-padding">
                        <h2 class="text-center mb-4">{{ __('general.what_our_clients_say') }}</h2>
                        <div class="row">
                            @foreach($landingPage->service->approvedReviews as $review)
                                <div class="col-md-4 mb-4">
                                    <div class="testimonial-card h-100">
                                        <div class="testimonial-body">
                                            <div class="mb-3 text-warning">
                                                @for($i = 0; $i < 5; $i++)
                                                    <i class="fas fa-star{{ $i < $review->rating ? '' : '-o' }}"></i>
                                                @endfor
                                            </div>
                                            <p class="mb-4">"{{ $review->comment }}"</p>
                                            <div class="testimonial-author">
                                                <h5 class="mb-0">{{ $review->user->name }}</h5>
                                                <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            @break
    @endswitch
@endforeach
