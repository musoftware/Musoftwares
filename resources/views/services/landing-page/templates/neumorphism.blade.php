{{-- Template: Neumorphism (Soft UI) - Soft shadows, rounded shapes, subtle highlights --}}
<style>
    body {
        background: #e0e5ec;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .landing-hero {
        background: #e0e5ec;
        color: #4a5568;
        padding: 120px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 600;
        margin-bottom: 25px;
        color: #2d3748;
        text-shadow: 3px 3px 6px rgba(163, 177, 198, 0.6), -3px -3px 6px rgba(255, 255, 255, 0.5);
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: #4a5568;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    .hero-cta {
        background: #e0e5ec;
        color: #2d3748;
        padding: 16px 45px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 20px;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
    }
    .hero-cta:hover {
        box-shadow: inset 8px 8px 16px rgba(163, 177, 198, 0.6), inset -8px -8px 16px rgba(255, 255, 255, 0.5);
    }
    .section-padding {
        padding: 100px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 60px;
        color: #2d3748;
        text-shadow: 2px 2px 4px rgba(163, 177, 198, 0.6), -2px -2px 4px rgba(255, 255, 255, 0.5);
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
        border-radius: 25px;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
        transition: all 0.3s ease;
    }
    .service-gallery img:hover {
        box-shadow: inset 8px 8px 16px rgba(163, 177, 198, 0.6), inset -8px -8px 16px rgba(255, 255, 255, 0.5);
    }
    .form-section {
        background: #e0e5ec;
        padding: 100px 0;
    }
    .form-card {
        background: #e0e5ec;
        border-radius: 30px;
        padding: 50px;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #2d3748;
        font-weight: 600;
        margin-bottom: 35px;
        text-align: center;
        text-shadow: 2px 2px 4px rgba(163, 177, 198, 0.6), -2px -2px 4px rgba(255, 255, 255, 0.5);
    }
    .faq-item {
        border: none;
        border-radius: 20px;
        margin-bottom: 20px;
        overflow: hidden;
        background: #e0e5ec;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
    }
    .faq-question {
        background: #e0e5ec;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #2d3748;
        border-radius: 20px;
    }
    .faq-question:hover {
        box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.5);
    }
    .faq-answer {
        padding: 25px;
        background: #e0e5ec;
        display: none;
        color: #4a5568;
        line-height: 1.8;
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
        background: #e0e5ec;
        border-radius: 30px;
        padding: 40px;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
        position: relative;
        transition: all 0.3s ease;
    }
    .pricing-card:hover {
        box-shadow: 12px 12px 24px rgba(163, 177, 198, 0.6), -12px -12px 24px rgba(255, 255, 255, 0.5);
        
    }
    .pricing-card.popular {
        box-shadow: 10px 10px 20px rgba(163, 177, 198, 0.7), -10px -10px 20px rgba(255, 255, 255, 0.5);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: #e0e5ec;
        color: #2d3748;
        padding: 8px 25px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.6), -4px -4px 8px rgba(255, 255, 255, 0.5);
    }
    .price-display {
        font-size: 3rem;
        font-weight: 700;
        color: #2d3748;
        margin: 25px 0;
        text-shadow: 2px 2px 4px rgba(163, 177, 198, 0.6), -2px -2px 4px rgba(255, 255, 255, 0.5);
    }
    .price-period {
        color: #718096;
        font-size: 1rem;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(163, 177, 198, 0.3);
        color: #4a5568;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #48bb78;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1.1rem;
    }
    .description-section {
        padding: 100px 0;
        background: #e0e5ec;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.1rem;
        color: #4a5568;
        background: #e0e5ec;
        padding: 50px;
        border-radius: 25px;
        box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.5);
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
        border-right: 3px solid #a3b1c6;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #2d3748;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(163, 177, 198, 0.6), -1px -1px 2px rgba(255, 255, 255, 0.5);
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 20px;
        margin: 25px 0;
        box-shadow: 8px 8px 16px rgba(163, 177, 198, 0.6), -8px -8px 16px rgba(255, 255, 255, 0.5);
    }
    .btn-primary {
        background: #e0e5ec;
        color: #2d3748;
        border: none;
        padding: 14px 35px;
        font-weight: 600;
        border-radius: 20px;
        box-shadow: 6px 6px 12px rgba(163, 177, 198, 0.6), -6px -6px 12px rgba(255, 255, 255, 0.5);
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        box-shadow: inset 6px 6px 12px rgba(163, 177, 198, 0.6), inset -6px -6px 12px rgba(255, 255, 255, 0.5);
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
                        <h3 style="color: #2d3748; font-weight: 600; margin-bottom: 15px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #718096;">{{ $pricing->description }}</p>
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






