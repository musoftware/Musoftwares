{{-- Template: Dark Mode UI - Contrast-neutral dark palettes, soft borders, subtle shadows --}}
<style>
    body {
        background: #0d1117;
        color: #c9d1d9;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .landing-hero {
        background: linear-gradient(135deg, #161b22 0%, #0d1117 100%);
        color: #c9d1d9;
        padding: 120px 0;
        text-align: center;
        border-bottom: 1px solid #21262d;
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 600;
        margin-bottom: 25px;
        color: #f0f6fc;
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: #8b949e;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    .hero-cta {
        background: #238636;
        color: #ffffff;
        padding: 14px 32px;
        font-size: 1rem;
        font-weight: 500;
        border-radius: 6px;
        border: 1px solid #2ea043;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }
    .hero-cta:hover {
        background: #2ea043;
        border-color: #3fb950;
        box-shadow: 0 2px 6px rgba(46, 160, 67, 0.3);
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 50px;
        color: #f0f6fc;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #30363d;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        transition: all 0.3s ease;
        background: #161b22;
    }
    .service-gallery img:hover {
        border-color: #58a6ff;
        box-shadow: 0 4px 12px rgba(88, 166, 255, 0.2);
    }
    .form-section {
        background: #161b22;
        padding: 80px 0;
        border-top: 1px solid #21262d;
    }
    .form-card {
        background: #0d1117;
        border-radius: 8px;
        padding: 48px;
        border: 1px solid #30363d;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #f0f6fc;
        font-weight: 600;
        margin-bottom: 32px;
        text-align: center;
        font-size: 1.75rem;
    }
    .faq-item {
        border: 1px solid #30363d;
        border-radius: 8px;
        margin-bottom: 16px;
        overflow: hidden;
        background: #161b22;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }
    .faq-item:hover {
        border-color: #58a6ff;
        box-shadow: 0 2px 6px rgba(88, 166, 255, 0.1);
    }
    .faq-question {
        background: #0d1117;
        padding: 20px 24px;
        margin: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: #f0f6fc;
        font-size: 1rem;
        border-bottom: 1px solid #21262d;
    }
    .faq-question:hover {
        background: #161b22;
        color: #58a6ff;
    }
    .faq-answer {
        padding: 20px 24px;
        background: #0d1117;
        display: none;
        color: #8b949e;
        line-height: 1.75;
        font-size: 0.9375rem;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin: 40px 0;
    }
    .pricing-card {
        background: #161b22;
        border-radius: 8px;
        padding: 32px;
        border: 1px solid #30363d;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        position: relative;
        transition: all 0.3s ease;
    }
    .pricing-card:hover {
        border-color: #58a6ff;
        box-shadow: 0 4px 12px rgba(88, 166, 255, 0.2);
    }
    .pricing-card.popular {
        border: 2px solid #238636;
        background: #0d2818;
        box-shadow: 0 4px 12px rgba(35, 134, 54, 0.2);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #238636;
        color: #ffffff;
        padding: 4px 16px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid #2ea043;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    .price-display {
        font-size: 3rem;
        font-weight: 600;
        color: #f0f6fc;
        margin: 24px 0;
    }
    .price-period {
        color: #8b949e;
        font-size: 1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 24px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid #21262d;
        color: #c9d1d9;
        font-size: 0.9375rem;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #3fb950;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1rem;
    }
    .description-section {
        padding: 80px 0;
        background: #161b22;
        border-top: 1px solid #21262d;
        border-bottom: 1px solid #21262d;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.75;
        font-size: 1rem;
        color: #c9d1d9;
        background: #0d1117;
        padding: 48px;
        border-radius: 8px;
        border: 1px solid #30363d;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
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
        border-right: 3px solid #58a6ff;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: rgba(88, 166, 255, 0.05);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #f0f6fc;
        margin-top: 32px;
        margin-bottom: 16px;
        font-weight: 600;
    }
    .description-content p {
        margin-bottom: 16px;
        color: #8b949e;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 24px 0;
        border: 1px solid #30363d;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    }
    .btn-primary {
        background: #238636;
        color: #ffffff;
        border: 1px solid #2ea043;
        padding: 12px 24px;
        font-weight: 500;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        transition: all 0.2s ease;
    }
    .btn-primary:hover {
        background: #2ea043;
        border-color: #3fb950;
        box-shadow: 0 2px 6px rgba(46, 160, 67, 0.3);
        color: #ffffff;
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
            <h2 class="section-title">Our Services</h2>
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
            <h2 class="section-title">Pricing Plans</h2>
            <div class="pricing-grid">
                @foreach($landingPage->pricingTables as $pricing)
                    <div class="pricing-card {{ $pricing->is_popular ? 'popular' : '' }}">
                        <h3 style="color: #f0f6fc; font-weight: 600; margin-bottom: 16px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 8px; color: #8b949e; font-size: 0.9375rem;">{{ $pricing->description }}</p>
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
            <h2 class="section-title">Frequently Asked Questions</h2>
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






