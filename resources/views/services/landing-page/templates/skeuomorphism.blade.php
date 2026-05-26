{{-- Template: Skeuomorphism - Realistic textures, shadows, and physical-like components --}}
<style>
    body {
        background: #d4d0c8;
        background-image:
            linear-gradient(45deg, rgba(255,255,255,0.1) 25%, transparent 25%),
            linear-gradient(-45deg, rgba(255,255,255,0.1) 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.1) 75%),
            linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.1) 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        font-family: 'Georgia', 'Times New Roman', serif;
    }
    .landing-hero {
        background: linear-gradient(to bottom, #e8e6e1 0%, #d4d0c8 100%);
        color: #2c2c2c;
        padding: 120px 0;
        text-align: center;
        border-bottom: 3px solid #b8b4ae;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.5), 0 2px 4px rgba(0,0,0,0.3);
    }
    .landing-hero h1 {
        font-size: 3.8rem;
        font-weight: 700;
        margin-bottom: 25px;
        color: #1a1a1a;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3), -1px -1px 0 rgba(255,255,255,0.5);
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: #3a3a3a;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
    }
    .hero-cta {
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        color: #2c2c2c;
        padding: 16px 45px;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 8px;
        border: 2px outset #d4d0c8;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            inset 0 -1px 0 rgba(0,0,0,0.1),
            0 4px 8px rgba(0,0,0,0.3);
        text-shadow: 1px 1px 1px rgba(255,255,255,0.5);
    }
    .hero-cta:hover {
        border-style: inset;
        box-shadow:
            inset 0 2px 4px rgba(0,0,0,0.2),
            inset 0 -1px 0 rgba(255,255,255,0.3);
    }
    .hero-cta:active {
        border-style: inset;
        box-shadow: inset 0 3px 6px rgba(0,0,0,0.3);
    }
    .section-padding {
        padding: 100px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 60px;
        color: #1a1a1a;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3), -1px -1px 0 rgba(255,255,255,0.5);
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin: 50px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        border-radius: 8px;
        border: 3px outset #c4c0b8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.5),
            0 6px 12px rgba(0,0,0,0.4),
            0 0 0 1px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        background: #e8e6e1;
    }
    .service-gallery img:hover {
        border-style: inset;
        box-shadow:
            inset 0 2px 4px rgba(0,0,0,0.3),
            inset 0 -1px 0 rgba(255,255,255,0.2);
        transform: translateY(2px);
    }
    .form-section {
        background: linear-gradient(to bottom, #e8e6e1 0%, #d4d0c8 100%);
        padding: 100px 0;
        border-top: 3px solid #b8b4ae;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.5);
    }
    .form-card {
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        border-radius: 12px;
        padding: 50px;
        border: 3px outset #d4d0c8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            inset 0 -1px 0 rgba(0,0,0,0.1),
            0 8px 16px rgba(0,0,0,0.3);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #1a1a1a;
        font-weight: 700;
        margin-bottom: 35px;
        text-align: center;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2), -1px -1px 0 rgba(255,255,255,0.5);
    }
    .faq-item {
        border: 2px outset #d4d0c8;
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            0 4px 8px rgba(0,0,0,0.3);
    }
    .faq-question {
        background: linear-gradient(to bottom, #e8e6e1 0%, #d4d0c8 100%);
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        color: #1a1a1a;
        border-bottom: 2px inset #c4c0b8;
        text-shadow: 1px 1px 1px rgba(255,255,255,0.5);
    }
    .faq-question:hover {
        background: linear-gradient(to bottom, #d4d0c8 0%, #c4c0b8 100%);
    }
    .faq-answer {
        padding: 25px;
        background: #f5f3f0;
        display: none;
        color: #3a3a3a;
        line-height: 1.8;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 35px;
        margin: 50px 0;
    }
    .pricing-card {
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        border-radius: 12px;
        padding: 40px;
        border: 3px outset #d4d0c8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            inset 0 -1px 0 rgba(0,0,0,0.1),
            0 6px 12px rgba(0,0,0,0.3);
        position: relative;
        transition: all 0.2s ease;
    }
    .pricing-card:hover {
        border-style: inset;
        box-shadow:
            inset 0 2px 4px rgba(0,0,0,0.2),
            inset 0 -1px 0 rgba(255,255,255,0.3);
    }
    .pricing-card.popular {
        border: 4px outset #8b7355;
        background: linear-gradient(to bottom, #d4c5a9 0%, #c4b599 100%);
    }
    .pricing-card.popular::before {
        content: 'RECOMMENDED';
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(to bottom, #8b7355 0%, #6b5a42 100%);
        color: #f5f3f0;
        padding: 8px 25px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 2px outset #6b5a42;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }
    .price-display {
        font-size: 3.2rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 25px 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3), -1px -1px 0 rgba(255,255,255,0.5);
    }
    .price-period {
        color: #5a5a5a;
        font-size: 1rem;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        color: #3a3a3a;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #2d5016;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1.2rem;
        text-shadow: 1px 1px 1px rgba(255,255,255,0.5);
    }
    .description-section {
        padding: 100px 0;
        background: linear-gradient(to bottom, #e8e6e1 0%, #d4d0c8 100%);
        border-top: 3px solid #b8b4ae;
        border-bottom: 3px solid #b8b4ae;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.5);
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.1rem;
        color: #3a3a3a;
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        padding: 50px;
        border-radius: 12px;
        border: 3px outset #d4d0c8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            0 6px 12px rgba(0,0,0,0.3);
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
        border-right: 4px solid #8b7355;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: linear-gradient(to right, rgba(212, 192, 168, 0.3) 0%, transparent 100%);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #1a1a1a;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2), -1px -1px 0 rgba(255,255,255,0.5);
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 25px 0;
        border: 3px outset #d4d0c8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.5),
            0 6px 12px rgba(0,0,0,0.3);
    }
    .btn-primary {
        background: linear-gradient(to bottom, #f5f3f0 0%, #e8e6e1 100%);
        color: #2c2c2c;
        border: 2px outset #d4d0c8;
        padding: 14px 35px;
        font-weight: 700;
        border-radius: 8px;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            inset 0 -1px 0 rgba(0,0,0,0.1),
            0 4px 8px rgba(0,0,0,0.3);
        transition: all 0.2s ease;
        text-shadow: 1px 1px 1px rgba(255,255,255,0.5);
    }
    .btn-primary:hover {
        border-style: inset;
        box-shadow:
            inset 0 2px 4px rgba(0,0,0,0.2),
            inset 0 -1px 0 rgba(255,255,255,0.3);
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
                        <h3 style="color: #1a1a1a; font-weight: 700; margin-bottom: 15px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #5a5a5a;">{{ $pricing->description }}</p>
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

