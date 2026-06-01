{{-- Template 4: Product/Showcase - Focus on images and features for product-based services --}}
<style>
    .landing-hero {
        background: linear-gradient(135deg, #434343 0%, #000000 100%);
        color: white;
        padding: 80px 0;
        text-align: center;
        position: relative;
    }
    .landing-hero::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="60" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></svg>');
    }
    .landing-hero .container {
        position: relative;
        z-index: 1;
    }
    .landing-hero h1 {
        font-size: 4.2rem;
        font-weight: 800;
        margin-bottom: 25px;
        letter-spacing: -2px;
    }
    .landing-hero p {
        font-size: 1.5rem;
        margin-bottom: 40px;
        opacity: 0.9;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    .hero-cta {
        background: #ff6b35;
        color: white;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 5px;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .hero-cta:hover {
        background: #e55a2b;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        box-shadow: 0 10px 30px rgba(255, 107, 53, 0.4);
        color: white;
    }
    .section-padding {
        padding: 100px 0;
    }
    .section-title {
        font-size: 2.8rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 60px;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        margin: 50px 0;
    }
    .gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 15px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        transition: all 0.4s ease;
    }
    .gallery-item:hover {
        box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    }
    .gallery-item img {
        width: 100%;
        height: 350px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .gallery-item:hover img {
        filter: brightness(1.05);
    }
    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 30px;
        color: white;
        transform: translateY(100%);
        transition: transform 0.3s ease;
    }
    .gallery-item:hover .gallery-overlay {
        transform: translateY(0);
    }
    .form-section {
        background: #1a1a1a;
        padding: 90px 0;
        color: white;
    }
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 50px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        color: #333;
    }
    .form-card h2 {
        color: #ff6b35;
        font-weight: 800;
        margin-bottom: 35px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .faq-item {
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
    }
    .faq-item:hover {
        border-color: #ff6b35;
        box-shadow: 0 5px 20px rgba(255, 107, 53, 0.2);
    }
    .faq-question {
        background: #f8f9fa;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        color: #333;
        font-size: 1.1rem;
    }
    .faq-question:hover {
        background: #ff6b35;
        color: white;
    }
    .faq-answer {
        padding: 25px;
        background: white;
        display: none;
        color: #555;
        line-height: 1.9;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 40px;
        margin: 50px 0;
    }
    .pricing-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        position: relative;
        transition: all 0.4s ease;
        border: 3px solid transparent;
    }
    .pricing-card:hover {
        border-color: #ff6b35;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    }
    .pricing-card.popular {
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        color: white;
        transform: scale(1.05);
        border: none;
    }
    .pricing-card.popular::before {
        content: 'BEST VALUE';
        position: absolute;
        top: -15px;
        right: 20px;
        background: white;
        color: #ff6b35;
        padding: 8px 25px;
        border-radius: 25px;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 1px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .pricing-card.popular h3,
    .pricing-card.popular .price-display {
        color: white;
    }
    .price-display {
        font-size: 3.5rem;
        font-weight: 900;
        color: #ff6b35;
        margin: 25px 0;
    }
    .price-period {
        color: #6c757d;
        font-size: 1.1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 15px 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        font-weight: 500;
        font-size: 1.05rem;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '▶';
        color: #ff6b35;
        font-weight: bold;
        margin-right: 12px;
        font-size: 0.8rem;
    }
    .pricing-card.popular .pricing-features li::before {
        color: white;
    }
    .description-section {
        padding: 90px 0;
        background: #f8f9fa;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.15rem;
        color: #444;
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
        border-right: 4px solid #ff6b35;
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
        margin-top: 40px;
        margin-bottom: 25px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .description-content p {
        margin-bottom: 25px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 15px;
        margin: 30px 0;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
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
            // Auto-detect RTL/LTR based on content
            $descriptionText = strip_tags($landingPage->description);
            // Check for RTL characters (Arabic, Hebrew, Persian, Urdu, etc.)
            $rtlPattern = '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0700}-\x{074F}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
            $isRTL = preg_match($rtlPattern, $descriptionText);
            $textDirection = $isRTL ? 'rtl' : 'ltr';

            // Get alignment setting (RTL overrides alignment to 'right')
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
            <h2 class="section-title">{{ __('general.product_showcase') }}</h2>
            <div class="service-gallery">
                @foreach($landingPage->service->images as $image)
                    <div class="gallery-item">
                        <img alt="{{ $landingPage->hero_title }}" src="{{ asset($image->image_path) }}">
                        <div class="gallery-overlay">
                            <h4 style="margin: 0; font-weight: 700;">{{ $landingPage->hero_title }}</h4>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($landingPage->service->image)
                <div class="service-gallery mt-4">
                    <div class="gallery-item">
                        <img alt="{{ $landingPage->hero_title }}" src="{{ asset($landingPage->service->image) }}">
                        <div class="gallery-overlay">
                            <h4 style="margin: 0; font-weight: 700;">{{ $landingPage->hero_title }}</h4>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Pricing Tables -->
    @if($landingPage->pricingTables->count() > 0)
        <div class="section-padding">
            <h2 class="section-title">{{ __('general.pricing_options') }}</h2>
            <div class="pricing-grid">
                @foreach($landingPage->pricingTables as $pricing)
                    <div class="pricing-card {{ $pricing->is_popular ? 'popular' : '' }}">
                        <h3 style="font-weight: 800; margin-bottom: 15px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; opacity: 0.8;">{{ $pricing->description }}</p>
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
                        <a href="{{ $pricing->cta_link ?: '#contact-form' }}" class="btn w-100" style="background: #ff6b35; color: white; padding: 15px; font-weight: 700; font-size: 1.1rem; border: none;">
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
            <h2 class="section-title">{{ __('general.questions_answers') }}</h2>
            <div class="row">
                <div class="col-lg-10 mx-auto">
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
