{{-- Template 2: Creative/Bold - Eye-catching with vibrant colors for creative services --}}
<style>
    .landing-hero {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
        color: white;
        padding: 120px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .landing-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        opacity: 0.3;
    }
    .landing-hero .container {
        position: relative;
        z-index: 1;
    }
    .landing-hero h1 {
        font-size: 4rem;
        font-weight: 900;
        margin-bottom: 25px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        animation: fadeInUp 0.8s ease;
    }
    .landing-hero p {
        font-size: 1.5rem;
        margin-bottom: 40px;
        opacity: 0.95;
        animation: fadeInUp 1s ease;
    }
    .hero-cta {
        background: white;
        color: #f5576c;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 50px;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: fadeInUp 1.2s ease;
    }
    .hero-cta:hover {
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        color: #f5576c;
    }
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .section-padding {
        padding: 100px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 50px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        transition: all 0.4s ease;
        border: 5px solid transparent;
    }
    .service-gallery img:hover {
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        border-color: #f5576c;
    }
    .form-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0;
        color: white;
    }
    .form-card {
        background: white;
        border-radius: 25px;
        padding: 50px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        color: #333;
    }
    .form-card h2 {
        color: #667eea;
        font-weight: 800;
        margin-bottom: 30px;
    }
    .faq-item {
        border: none;
        border-radius: 15px;
        margin-bottom: 20px;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .faq-question {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
    }
    .faq-question:hover {
        background: linear-gradient(135deg, #f5576c 0%, #4facfe 100%);
    }
    .faq-answer {
        padding: 25px;
        background: white;
        display: none;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin: 50px 0;
    }
    .pricing-card {
        background: white;
        border-radius: 25px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        position: relative;
        transition: all 0.4s ease;
        border: 3px solid transparent;
    }
    .pricing-card:hover {
        border-color: #f5576c;
        box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }
    .pricing-card.popular {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        transform: scale(1.1);
        border: none;
    }
    .pricing-card.popular::before {
        content: '⭐ POPULAR';
        position: absolute;
        top: -15px;
        right: 20px;
        background: white;
        color: #f5576c;
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .pricing-card.popular .price-display,
    .pricing-card.popular h3 {
        color: white;
    }
    .price-display {
        font-size: 3.5rem;
        font-weight: 900;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 25px 0;
    }
    .pricing-card.popular .price-display {
        -webkit-text-fill-color: white;
    }
    .price-period {
        color: #6c757d;
        font-size: 1.1rem;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 25px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        font-weight: 500;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '✨';
        margin-right: 10px;
        font-size: 1.2rem;
    }
    .description-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.15rem;
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
        border-right: 4px solid #f5576c;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 800;
    }
    .description-content p {
        margin-bottom: 25px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 20px;
        margin: 25px 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
            <h2 class="section-title">{{ __('general.our_creative_gallery') }}</h2>
            <div class="service-gallery">
                @foreach($landingPage->service->images as $image)
                    <img alt="{{ $landingPage->hero_title }}" src="{{ asset($image->image_path) }}">
                @endforeach
            </div>
            @if($landingPage->service->image)
                <div class="service-gallery mt-4">
                    <img alt="{{ $landingPage->hero_title }}" src="{{ asset($landingPage->service->image) }}">
                </div>
            @endif
        </div>
    @endif

    <!-- Pricing Tables -->
    @if($landingPage->pricingTables->count() > 0)
        <div class="section-padding">
            <h2 class="section-title">{{ __('general.choose_your_plan') }}</h2>
            <div class="pricing-grid">
                @foreach($landingPage->pricingTables as $pricing)
                    <div class="pricing-card {{ $pricing->is_popular ? 'popular' : '' }}">
                        <h3>{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p class="text-muted">{{ $pricing->description }}</p>
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
                        <a href="{{ $pricing->cta_link ?: '#contact-form' }}" class="btn btn-primary w-100" style="padding: 15px; font-weight: 700; font-size: 1.1rem;">
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
            <h2 class="section-title">{{ __('general.got_questions') }}</h2>
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
