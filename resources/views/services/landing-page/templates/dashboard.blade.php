{{-- Template: Dashboard / Admin UI Style - Cards, grids, metric widgets, neutral/gray palette --}}
<style>
    body {
        background: #f5f7fa;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .landing-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3rem;
        font-weight: 600;
        margin-bottom: 20px;
        letter-spacing: -0.5px;
    }
    .landing-hero p {
        font-size: 1.2rem;
        margin-bottom: 35px;
        opacity: 0.9;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 400;
    }
    .hero-cta {
        background: white;
        color: #667eea;
        padding: 12px 32px;
        font-size: 0.9375rem;
        font-weight: 600;
        border-radius: 6px;
        border: none;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .hero-cta:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    .section-padding {
        padding: 60px 0;
    }
    .section-title {
        font-size: 1.75rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 40px;
        color: #2d3748;
        letter-spacing: -0.3px;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .service-gallery img:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .form-section {
        background: #ffffff;
        padding: 60px 0;
        border-top: 1px solid #e2e8f0;
    }
    .form-card {
        background: white;
        border-radius: 8px;
        padding: 40px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #2d3748;
        font-weight: 600;
        margin-bottom: 30px;
        text-align: center;
        font-size: 1.5rem;
    }
    .faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }
    .faq-item:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }
    .faq-question {
        background: #f7fafc;
        padding: 16px 20px;
        margin: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: #2d3748;
        font-size: 0.9375rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .faq-question:hover {
        background: #edf2f7;
        color: #667eea;
    }
    .faq-answer {
        padding: 16px 20px;
        background: white;
        display: none;
        color: #4a5568;
        line-height: 1.6;
        font-size: 0.875rem;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    .pricing-card {
        background: white;
        border-radius: 8px;
        padding: 28px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        position: relative;
        transition: all 0.2s ease;
    }
    .pricing-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #cbd5e0;
    }
    .pricing-card.popular {
        border: 2px solid #667eea;
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: 12px;
        right: 12px;
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    .price-display {
        font-size: 2.5rem;
        font-weight: 600;
        color: #2d3748;
        margin: 20px 0;
        letter-spacing: -0.5px;
    }
    .price-period {
        color: #718096;
        font-size: 0.875rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    .pricing-features li {
        padding: 10px 0;
        border-bottom: 1px solid #f7fafc;
        color: #4a5568;
        font-size: 0.875rem;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #48bb78;
        font-weight: bold;
        margin-right: 10px;
        font-size: 0.875rem;
    }
    .description-section {
        padding: 60px 0;
        background: white;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.7;
        font-size: 0.9375rem;
        color: #4a5568;
        background: white;
        padding: 40px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
        border-right: 3px solid #667eea;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: #f7fafc;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #2d3748;
        margin-top: 28px;
        margin-bottom: 16px;
        font-weight: 600;
        letter-spacing: -0.3px;
    }
    .description-content p {
        margin-bottom: 16px;
        color: #4a5568;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 20px 0;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .btn-primary {
        background: #667eea;
        color: white;
        border: none;
        padding: 10px 24px;
        font-weight: 600;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        font-size: 0.875rem;
    }
    .btn-primary:hover {
        background: #5568d3;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        color: white;
    }
</style>

<!-- Hero Section -->
<div class="landing-hero">
    <div class="container">
        <h1>{{ $landingPage->hero_title }}</h1>
        @if($landingPage->hero_description)
            <p>{{ $landingPage->hero_description }}</p>
        @endif
        <a href="#contact-form" class="btn hero-cta">{{ $landingPage->hero_cta_text }}</a>
    </div>
</div>

<div class="container">
    <!-- Description Section -->
    @if($landingPage->description)
        @php
            $descriptionText = strip_tags($landingPage->description);
            $rtlPattern = '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0700}-\x{074F}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
            $isRTL = preg_match($rtlPattern, $descriptionText);
            $textDirection = $isRTL ? 'rtl' : 'ltr';
            $alignment = $isRTL ? 'right' : ($landingPage->description_alignment ?? 'left');
            $alignmentClass = 'text-' . $alignment;
        @endphp
        <div class="description-section">
            <div class="description-content {{ $alignmentClass }}" dir="{{ $textDirection }}">
                {!! $landingPage->description !!}
            </div>
        </div>
    @endif

    <!-- Service Gallery -->
    @if($landingPage->service->images->count() > 0)
        <div class="section-padding">
            <h2 class="section-title">{{ __('general.our_services') }}</h2>
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
    @endif

    <!-- Pricing Tables -->
    @if($landingPage->pricingTables->count() > 0)
        <div class="section-padding">
            <h2 class="section-title">{{ __('general.pricing_plans') }}</h2>
            <div class="pricing-grid">
                @foreach($landingPage->pricingTables as $pricing)
                    <div class="pricing-card {{ $pricing->is_popular ? 'popular' : '' }}">
                        <h3 style="color: #2d3748; font-weight: 600; margin-bottom: 12px; font-size: 1.125rem;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 8px; color: #718096; font-size: 0.875rem;">{{ $pricing->description }}</p>
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
    @endif

    <!-- FAQs Section -->
    @if($landingPage->faqs->count() > 0)
        <div class="section-padding">
            <h2 class="section-title">{{ __('general.frequently_asked_questions') }}</h2>
            <div class="row">
                <div class="col-lg-9 mx-auto">
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
    @endif

    <!-- Contact Form Section -->
    @include('services.landing-page.partials.contact-form', ['landingPage' => $landingPage])
</div>






