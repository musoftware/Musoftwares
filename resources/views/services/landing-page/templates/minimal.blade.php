{{-- Template: Minimal/Minimalist - Clean UI, large whitespace, thin borders, neutral colors --}}
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: #2c2c2c;
        line-height: 1.6;
    }
    .landing-hero {
        background: #ffffff;
        color: #2c2c2c;
        padding: 150px 0;
        text-align: center;
        border-bottom: 1px solid #e5e5e5;
    }
    .landing-hero h1 {
        font-size: 3.2rem;
        font-weight: 300;
        margin-bottom: 30px;
        letter-spacing: -0.5px;
        color: #1a1a1a;
    }
    .landing-hero p {
        font-size: 1.2rem;
        margin-bottom: 50px;
        color: #666;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 300;
    }
    .hero-cta {
        background: transparent;
        color: #2c2c2c;
        padding: 14px 40px;
        font-size: 1rem;
        font-weight: 400;
        border-radius: 0;
        border: 1px solid #2c2c2c;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        letter-spacing: 0.5px;
    }
    .hero-cta:hover {
        background: #2c2c2c;
        color: #ffffff;
        border-color: #2c2c2c;
    }
    .section-padding {
        padding: 120px 0;
    }
    .section-title {
        font-size: 2rem;
        font-weight: 300;
        text-align: center;
        margin-bottom: 80px;
        color: #1a1a1a;
        letter-spacing: -0.3px;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin: 60px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border: 1px solid #e5e5e5;
        transition: border-color 0.2s ease;
    }
    .service-gallery img:hover {
        border-color: #2c2c2c;
    }
    .form-section {
        background: #fafafa;
        padding: 120px 0;
        border-top: 1px solid #e5e5e5;
    }
    .form-card {
        background: white;
        border-radius: 0;
        padding: 60px;
        border: 1px solid #e5e5e5;
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #1a1a1a;
        font-weight: 300;
        margin-bottom: 40px;
        text-align: center;
        font-size: 1.8rem;
        letter-spacing: -0.3px;
    }
    .faq-item {
        border: none;
        border-bottom: 1px solid #e5e5e5;
        margin-bottom: 0;
        overflow: hidden;
        background: transparent;
    }
    .faq-question {
        background: transparent;
        padding: 30px 0;
        margin: 0;
        cursor: pointer;
        transition: color 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 400;
        color: #1a1a1a;
        font-size: 1.1rem;
    }
    .faq-question:hover {
        color: #666;
    }
    .faq-answer {
        padding: 0 0 30px 0;
        background: transparent;
        display: none;
        color: #666;
        line-height: 1.8;
        font-weight: 300;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 60px;
        margin: 60px 0;
    }
    .pricing-card {
        background: white;
        border-radius: 0;
        padding: 50px 40px;
        border: 1px solid #e5e5e5;
        position: relative;
        transition: border-color 0.2s ease;
    }
    .pricing-card:hover {
        border-color: #2c2c2c;
    }
    .pricing-card.popular {
        border: 1px solid #2c2c2c;
    }
    .pricing-card.popular::before {
        content: 'RECOMMENDED';
        position: absolute;
        top: -12px;
        left: 40px;
        background: white;
        color: #2c2c2c;
        padding: 4px 12px;
        font-size: 0.7rem;
        font-weight: 400;
        letter-spacing: 1px;
    }
    .price-display {
        font-size: 2.5rem;
        font-weight: 300;
        color: #1a1a1a;
        margin: 30px 0;
        letter-spacing: -1px;
    }
    .price-period {
        color: #999;
        font-size: 0.9rem;
        font-weight: 300;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 40px 0;
    }
    .pricing-features li {
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
        color: #666;
        font-weight: 300;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '—';
        color: #2c2c2c;
        margin-right: 12px;
    }
    .description-section {
        padding: 120px 0;
        background: #fafafa;
        border-top: 1px solid #e5e5e5;
        border-bottom: 1px solid #e5e5e5;
    }
    .description-content {
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.1rem;
        color: #666;
        font-weight: 300;
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
        border-right: 1px solid #e5e5e5;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #1a1a1a;
        margin-top: 50px;
        margin-bottom: 25px;
        font-weight: 300;
        letter-spacing: -0.3px;
    }
    .description-content p {
        margin-bottom: 25px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border: 1px solid #e5e5e5;
        margin: 40px 0;
    }
    .btn-primary {
        background: transparent;
        color: #2c2c2c;
        border: 1px solid #2c2c2c;
        padding: 12px 30px;
        font-weight: 400;
        border-radius: 0;
        transition: all 0.2s ease;
    }
    .btn-primary:hover {
        background: #2c2c2c;
        color: white;
        border-color: #2c2c2c;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .landing-hero {
            padding: 80px 0;
        }
        .landing-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .landing-hero p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .btn-primary {
            padding: 10px 25px;
            font-size: 0.9rem;
        }
        .section-padding {
            padding: 60px 0;
        }
        .service-gallery {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .pricing-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .form-card {
            padding: 40px 20px;
        }
        .description-content {
            font-size: 1rem;
            padding: 0 15px;
        }
        .description-content h2,
        .description-content h3,
        .description-content h4 {
            margin-top: 40px;
            margin-bottom: 20px;
        }
    }

    @media (max-width: 576px) {
        .landing-hero {
            padding: 60px 0;
        }
        .landing-hero h1 {
            font-size: 2rem;
        }
        .landing-hero p {
            font-size: 1rem;
        }
        .btn-primary {
            padding: 8px 20px;
            font-size: 0.85rem;
        }
        .section-padding {
            padding: 50px 0;
        }
        .form-card {
            padding: 30px 15px;
        }
        .description-content {
            padding: 0 10px;
        }
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
                        <h3 style="color: #1a1a1a; font-weight: 300; margin-bottom: 20px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #999; font-weight: 300;">{{ $pricing->description }}</p>
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

