{{-- Template 3: Business/Corporate - Professional and formal for B2B services --}}
<style>
    .landing-hero {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3.8rem;
        font-weight: 700;
        margin-bottom: 25px;
        letter-spacing: -1px;
    }
    .landing-hero p {
        font-size: 1.4rem;
        margin-bottom: 35px;
        opacity: 0.95;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .hero-cta {
        background: #ff6b6b;
        color: white;
        padding: 16px 45px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 5px;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    .hero-cta:hover {
        background: #ee5a5a;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        color: white;
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.2rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 50px;
        color: #1e3c72;
        position: relative;
        padding-bottom: 20px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: #2a5298;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 260px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .service-gallery img:hover {
        opacity: 0.95;
    }
    .form-section {
        background: #f5f7fa;
        padding: 70px 0;
        border-top: 1px solid #e0e0e0;
    }
    .form-card {
        background: white;
        border-radius: 8px;
        padding: 50px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        border: 1px solid #e0e0e0;
    }
    .form-card h2 {
        color: #1e3c72;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
    }
    .faq-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 15px;
        overflow: hidden;
        background: white;
    }
    .faq-question {
        background: #f8f9fa;
        padding: 22px;
        margin: 0;
        cursor: pointer;
        transition: background 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #1e3c72;
        border-bottom: 1px solid #e0e0e0;
    }
    .faq-question:hover {
        background: #e9ecef;
    }
    .faq-answer {
        padding: 22px;
        background: white;
        display: none;
        color: #555;
        line-height: 1.8;
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
        border-radius: 8px;
        padding: 35px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        position: relative;
        transition: all 0.3s ease;
        border: 2px solid #e0e0e0;
    }
    .pricing-card:hover {
        
        border-color: #2a5298;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .pricing-card.popular {
        border: 3px solid #2a5298;
        background: linear-gradient(to bottom, #f8f9fa 0%, white 100%);
    }
    .pricing-card.popular::before {
        content: 'RECOMMENDED';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #2a5298;
        color: white;
        padding: 6px 20px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .price-display {
        font-size: 2.8rem;
        font-weight: 700;
        color: #1e3c72;
        margin: 20px 0;
    }
    .price-period {
        color: #6c757d;
        font-size: 1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 25px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        color: #555;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #28a745;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1.1rem;
    }
    .description-section {
        padding: 70px 0;
        background: #f8f9fa;
        border-top: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
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
        border-right: 4px solid #2a5298;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #1e3c72;
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
        border-radius: 8px;
        margin: 25px 0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .landing-hero {
            padding: 70px 0;
        }
        .landing-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .form-card {
            padding: 35px 20px;
        }
        .description-content {
            font-size: 1rem;
            padding: 0 15px;
        }
        .description-content h2,
        .description-content h3,
        .description-content h4 {
            margin-top: 30px;
            margin-bottom: 15px;
        }
    }

    @media (max-width: 576px) {
        .landing-hero {
            padding: 50px 0;
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
        .service-gallery {
            grid-template-columns: 1fr;
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
                        <h3 style="color: #1e3c72; font-weight: 700;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p class="text-muted" style="margin-top: 10px;">{{ $pricing->description }}</p>
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
                        <a href="{{ $pricing->cta_link ?: '#contact-form' }}" class="btn btn-primary w-100" style="background: #2a5298; border: none; padding: 12px; font-weight: 600;">
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
