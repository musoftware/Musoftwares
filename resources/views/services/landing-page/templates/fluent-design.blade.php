{{-- Template: Fluent Design (Microsoft) - Acrylic blur, soft edges, depth, light-based shadows --}}
<style>
    body {
        background: linear-gradient(135deg, #0078d4 0%, #106ebe 50%, #005a9e 100%);
        background-attachment: fixed;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .landing-hero {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        color: white;
        padding: 120px 0;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .landing-hero h1 {
        font-size: 3.75rem;
        font-weight: 600;
        margin-bottom: 24px;
        letter-spacing: -0.5px;
    }
    .landing-hero p {
        font-size: 1.25rem;
        margin-bottom: 40px;
        opacity: 0.9;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 400;
    }
    .hero-cta {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: white;
        padding: 12px 32px;
        font-size: 0.9375rem;
        font-weight: 600;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .hero-cta:hover {
        background: rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    .hero-cta:active {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.25rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 48px;
        color: white;
        letter-spacing: -0.5px;
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
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .service-gallery img:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2), 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    .form-section {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        padding: 80px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .form-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 8px;
        padding: 48px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: white;
        font-weight: 600;
        margin-bottom: 32px;
        text-align: center;
        font-size: 1.75rem;
        letter-spacing: -0.5px;
    }
    .faq-item {
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        margin-bottom: 16px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
    }
    .faq-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        background: rgba(255, 255, 255, 0.08);
    }
    .faq-question {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 20px 24px;
        margin: 0;
        cursor: pointer;
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: white;
        font-size: 1rem;
    }
    .faq-question:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    .faq-answer {
        padding: 0 24px 24px 24px;
        background: transparent;
        display: none;
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.75;
        font-size: 0.9375rem;
        font-weight: 400;
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
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 8px;
        padding: 32px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
    }
    .pricing-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15);
        background: rgba(255, 255, 255, 0.15);
    }
    .pricing-card.popular {
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2), 0 3px 10px rgba(0, 0, 0, 0.15);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: white;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .price-display {
        font-size: 2.75rem;
        font-weight: 600;
        color: white;
        margin: 24px 0;
        letter-spacing: -1px;
    }
    .price-period {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9375rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 24px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9375rem;
        font-weight: 400;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #4fc3f7;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1rem;
    }
    .description-section {
        padding: 80px 0;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.75;
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        padding: 48px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
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
        border-right: 4px solid rgba(255, 255, 255, 0.3);
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: rgba(255, 255, 255, 0.05);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: white;
        margin-top: 32px;
        margin-bottom: 16px;
        font-weight: 600;
        letter-spacing: -0.5px;
    }
    .description-content p {
        margin-bottom: 16px;
        color: rgba(255, 255, 255, 0.9);
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 24px 0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .btn-primary {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 12px 24px;
        font-weight: 600;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
    }
    .btn-primary:hover {
        background: rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    .btn-primary:active {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
                        <h3 style="color: white; font-weight: 600; margin-bottom: 16px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 8px; color: rgba(255, 255, 255, 0.8); font-size: 0.9375rem;">{{ $pricing->description }}</p>
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






