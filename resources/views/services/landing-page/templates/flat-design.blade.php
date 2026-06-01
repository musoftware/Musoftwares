{{-- Template: Flat Design - No shadows, solid colors, simple geometry --}}
<style>
    body {
        background: #ecf0f1;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .landing-hero {
        background: #3498db;
        color: white;
        padding: 120px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        color: white;
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: white;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.95;
    }
    .hero-cta {
        background: #e74c3c;
        color: white;
        padding: 16px 45px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 0;
        border: none;
        transition: background 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }
    .hero-cta:hover {
        background: #c0392b;
        color: white;
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 50px;
        color: #2c3e50;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        border: 4px solid #34495e;
        transition: border-color 0.2s ease;
    }
    .service-gallery img:hover {
        border-color: #e74c3c;
    }
    .form-section {
        background: #34495e;
        padding: 80px 0;
        color: white;
    }
    .form-card {
        background: white;
        border-radius: 0;
        padding: 50px;
        border: 4px solid #2c3e50;
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 35px;
        text-align: center;
        font-size: 2rem;
    }
    .faq-item {
        border: 4px solid #bdc3c7;
        border-radius: 0;
        margin-bottom: 20px;
        overflow: hidden;
        background: white;
    }
    .faq-question {
        background: #95a5a6;
        color: white;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: background 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .faq-question:hover {
        background: #7f8c8d;
    }
    .faq-answer {
        padding: 25px;
        background: white;
        display: none;
        color: #2c3e50;
        line-height: 1.8;
        font-weight: 400;
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
        border-radius: 0;
        padding: 40px;
        border: 4px solid #3498db;
        position: relative;
        transition: border-color 0.2s ease;
    }
    .pricing-card:hover {
        border-color: #e74c3c;
    }
    .pricing-card.popular {
        border: 6px solid #e74c3c;
        background: #ecf0f1;
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        background: #e74c3c;
        color: white;
        padding: 8px 25px;
        font-size: 0.8rem;
        font-weight: 700;
        border: 4px solid #c0392b;
    }
    .price-display {
        font-size: 3.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 25px 0;
    }
    .price-period {
        color: #7f8c8d;
        font-size: 1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 2px solid #ecf0f1;
        color: #2c3e50;
        font-weight: 400;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #27ae60;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1.2rem;
    }
    .description-section {
        padding: 80px 0;
        background: white;
        border-top: 4px solid #3498db;
        border-bottom: 4px solid #3498db;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.8;
        font-size: 1.1rem;
        color: #2c3e50;
        background: white;
        padding: 50px;
        border: 4px solid #bdc3c7;
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
        border-right: 6px solid #3498db;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: #ecf0f1;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #2c3e50;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 700;
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border: 4px solid #34495e;
        margin: 25px 0;
    }
    .btn-primary {
        background: #3498db;
        color: white;
        border: none;
        padding: 14px 35px;
        font-weight: 600;
        border-radius: 0;
        transition: background 0.2s ease;
    }
    .btn-primary:hover {
        background: #2980b9;
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
                        <h3 style="color: #2c3e50; font-weight: 700; margin-bottom: 15px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #7f8c8d;">{{ $pricing->description }}</p>
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






