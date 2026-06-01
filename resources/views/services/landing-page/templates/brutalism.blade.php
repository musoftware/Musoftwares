{{-- Template: Brutalism - Bold colors, thick borders, raw unpolished layout --}}
<style>
    body {
        background: #ff00ff;
        font-family: 'Courier New', monospace;
    }
    .landing-hero {
        background: #ffff00;
        color: #000000;
        padding: 100px 0;
        text-align: center;
        border: 10px solid #000000;
        margin: 20px;
    }
    .landing-hero h1 {
        font-size: 4.5rem;
        font-weight: 900;
        margin-bottom: 30px;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 5px;
        line-height: 1.1;
    }
    .landing-hero p {
        font-size: 1.5rem;
        margin-bottom: 50px;
        color: #000000;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 700;
        text-transform: uppercase;
    }
    .hero-cta {
        background: #000000;
        color: #ffff00;
        padding: 20px 60px;
        font-size: 1.3rem;
        font-weight: 900;
        border-radius: 0;
        border: 8px solid #000000;
        transition: all 0.1s ease;
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 3px;
        box-shadow: 10px 10px 0 #ff00ff;
    }
    .hero-cta:hover {
        transform: translate(5px, 5px);
        box-shadow: 5px 5px 0 #ff00ff;
    }
    .section-padding {
        padding: 80px 0;
        margin: 20px;
        border: 8px solid #000000;
        background: #ffffff;
    }
    .section-title {
        font-size: 3.5rem;
        font-weight: 900;
        text-align: center;
        margin-bottom: 50px;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 4px;
        border: 6px solid #000000;
        padding: 20px;
        background: #00ffff;
        display: inline-block;
        width: 100%;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin: 40px 0;
        padding: 20px;
    }
    .service-gallery img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border: 8px solid #000000;
        box-shadow: 15px 15px 0 #ff00ff;
        transition: all 0.1s ease;
        background: #ffffff;
    }
    .service-gallery img:hover {
        transform: translate(5px, 5px);
        box-shadow: 10px 10px 0 #ff00ff;
    }
    .form-section {
        background: #00ffff;
        padding: 80px 0;
        border: 10px solid #000000;
        margin: 20px;
    }
    .form-card {
        background: #ffffff;
        border-radius: 0;
        padding: 50px;
        border: 10px solid #000000;
        box-shadow: 15px 15px 0 #000000;
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #000000;
        font-weight: 900;
        margin-bottom: 40px;
        text-align: center;
        font-size: 2.5rem;
        text-transform: uppercase;
        letter-spacing: 3px;
        border: 6px solid #000000;
        padding: 15px;
        background: #ffff00;
    }
    .faq-item {
        border: 6px solid #000000;
        border-radius: 0;
        margin-bottom: 20px;
        overflow: hidden;
        background: #ffffff;
    }
    .faq-question {
        background: #000000;
        color: #ffff00;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.1s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 1.2rem;
    }
    .faq-question:hover {
        background: #ff00ff;
        color: #ffffff;
    }
    .faq-answer {
        padding: 30px;
        background: #ffffff;
        display: none;
        color: #000000;
        line-height: 1.8;
        font-weight: 700;
        border-top: 6px solid #000000;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin: 40px 0;
        padding: 20px;
    }
    .pricing-card {
        background: #ffffff;
        border-radius: 0;
        padding: 40px;
        border: 8px solid #000000;
        box-shadow: 12px 12px 0 #000000;
        position: relative;
        transition: all 0.1s ease;
    }
    .pricing-card:hover {
        transform: translate(5px, 5px);
        box-shadow: 7px 7px 0 #000000;
    }
    .pricing-card.popular {
        background: #ffff00;
        border: 10px solid #000000;
        box-shadow: 15px 15px 0 #ff00ff;
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        background: #ff00ff;
        color: #ffffff;
        padding: 10px 30px;
        border: 6px solid #000000;
        font-size: 0.9rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 3px;
    }
    .price-display {
        font-size: 4rem;
        font-weight: 900;
        color: #000000;
        margin: 30px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .price-period {
        color: #000000;
        font-size: 1.2rem;
        font-weight: 700;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 15px 0;
        border-bottom: 4px solid #000000;
        color: #000000;
        font-weight: 700;
        text-transform: uppercase;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '■';
        color: #ff00ff;
        font-weight: 900;
        margin-right: 15px;
        font-size: 1.5rem;
    }
    .description-section {
        padding: 80px 0;
        background: #ffffff;
        border: 8px solid #000000;
        margin: 20px;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 2;
        font-size: 1.2rem;
        color: #000000;
        padding: 40px;
        border: 6px solid #000000;
        background: #ffffff;
        font-weight: 700;
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
        border-right: 8px solid #000000;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: #ffff00;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #000000;
        margin-top: 40px;
        margin-bottom: 25px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 3px;
        border: 4px solid #000000;
        padding: 15px;
        background: #00ffff;
    }
    .description-content p {
        margin-bottom: 25px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border: 8px solid #000000;
        margin: 30px 0;
        box-shadow: 10px 10px 0 #ff00ff;
    }
    .btn-primary {
        background: #000000;
        color: #ffff00;
        border: 6px solid #000000;
        padding: 18px 40px;
        font-weight: 900;
        border-radius: 0;
        text-transform: uppercase;
        letter-spacing: 2px;
        box-shadow: 8px 8px 0 #ff00ff;
        transition: all 0.1s ease;
    }
    .btn-primary:hover {
        transform: translate(3px, 3px);
        box-shadow: 5px 5px 0 #ff00ff;
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
                        <h3 style="color: #000000; font-weight: 900; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #000000; font-weight: 700; text-transform: uppercase;">{{ $pricing->description }}</p>
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

