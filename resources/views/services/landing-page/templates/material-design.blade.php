{{-- Template: Material Design Style - Elevation shadows, cards, clean grids, structured spacing --}}
<style>
    body {
        background: #f5f5f5;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    .landing-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 120px 0;
        text-align: center;
        box-shadow: 0 4px 5px 0 rgba(0,0,0,0.14), 0 1px 10px 0 rgba(0,0,0,0.12), 0 2px 4px -1px rgba(0,0,0,0.2);
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 400;
        margin-bottom: 25px;
        letter-spacing: -0.5px;
    }
    .landing-hero p {
        font-size: 1.25rem;
        margin-bottom: 40px;
        opacity: 0.9;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 300;
    }
    .hero-cta {
        background: white;
        color: #667eea;
        padding: 14px 32px;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 4px;
        border: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
    }
    .hero-cta:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.14);
        transform: translateY(-2px);
    }
    .hero-cta:active {
        box-shadow: 0 1px 2px rgba(0,0,0,0.2), 0 1px 1px rgba(0,0,0,0.14);
        transform: translateY(0);
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.125rem;
        font-weight: 400;
        text-align: center;
        margin-bottom: 48px;
        color: rgba(0,0,0,0.87);
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
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .service-gallery img:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2), 0 4px 8px rgba(0,0,0,0.14);
        transform: translateY(-4px);
    }
    .form-section {
        background: #f5f5f5;
        padding: 80px 0;
    }
    .form-card {
        background: white;
        border-radius: 4px;
        padding: 48px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: rgba(0,0,0,0.87);
        font-weight: 400;
        margin-bottom: 32px;
        text-align: center;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
    }
    .faq-item {
        border: none;
        border-radius: 4px;
        margin-bottom: 16px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .faq-item:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.14);
    }
    .faq-question {
        background: white;
        padding: 24px;
        margin: 0;
        cursor: pointer;
        transition: background 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: rgba(0,0,0,0.87);
        font-size: 1rem;
    }
    .faq-question:hover {
        background: rgba(0,0,0,0.04);
    }
    .faq-answer {
        padding: 0 24px 24px 24px;
        background: white;
        display: none;
        color: rgba(0,0,0,0.6);
        line-height: 1.75;
        font-size: 0.875rem;
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
        background: white;
        border-radius: 4px;
        padding: 32px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .pricing-card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2), 0 4px 8px rgba(0,0,0,0.14);
        transform: translateY(-4px);
    }
    .pricing-card.popular {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.14);
        border-top: 4px solid #667eea;
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: 16px;
        right: 16px;
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 2px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .price-display {
        font-size: 3rem;
        font-weight: 400;
        color: rgba(0,0,0,0.87);
        margin: 24px 0;
        letter-spacing: -1px;
    }
    .price-period {
        color: rgba(0,0,0,0.6);
        font-size: 0.875rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 24px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(0,0,0,0.12);
        color: rgba(0,0,0,0.6);
        font-size: 0.875rem;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✓';
        color: #4caf50;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1rem;
    }
    .description-section {
        padding: 80px 0;
        background: white;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.75;
        font-size: 1rem;
        color: rgba(0,0,0,0.87);
        background: white;
        padding: 48px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
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
        border-right: 4px solid #667eea;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: rgba(102, 126, 234, 0.05);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: rgba(0,0,0,0.87);
        margin-top: 32px;
        margin-bottom: 16px;
        font-weight: 400;
        letter-spacing: -0.5px;
    }
    .description-content p {
        margin-bottom: 16px;
        color: rgba(0,0,0,0.6);
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 24px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
    }
    .btn-primary {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 24px;
        font-weight: 500;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.14);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-primary:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.14);
        transform: translateY(-2px);
    }
    .btn-primary:active {
        box-shadow: 0 1px 2px rgba(0,0,0,0.2), 0 1px 1px rgba(0,0,0,0.14);
        transform: translateY(0);
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
                        <h3 style="color: rgba(0,0,0,0.87); font-weight: 400; margin-bottom: 16px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 8px; color: rgba(0,0,0,0.6); font-size: 0.875rem;">{{ $pricing->description }}</p>
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






