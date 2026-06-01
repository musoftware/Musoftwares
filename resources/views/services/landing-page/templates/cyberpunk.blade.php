{{-- Template: Cyberpunk / Neon - Strong contrast, neon glows, futuristic accents --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
    body {
        background: #0a0a0a;
        color: #00ffff;
        font-family: 'Orbitron', monospace;
        overflow-x: hidden;
    }
    .landing-hero {
        background: linear-gradient(135deg, #0a0a0a 0%, #1a0033 50%, #0a0a0a 100%);
        color: #00ffff;
        padding: 120px 0;
        text-align: center;
        position: relative;
        border-bottom: 3px solid #ff00ff;
        box-shadow: 0 0 30px rgba(255, 0, 255, 0.5);
    }
    .landing-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 20% 50%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(255, 0, 255, 0.1) 0%, transparent 50%);
        animation: pulse 3s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }
    .landing-hero .container {
        position: relative;
        z-index: 1;
    }
    .landing-hero h1 {
        font-size: 4.5rem;
        font-weight: 900;
        margin-bottom: 30px;
        color: #00ffff;
        text-transform: uppercase;
        letter-spacing: 8px;
        text-shadow:
            0 0 10px #00ffff,
            0 0 20px #00ffff,
            0 0 30px #00ffff,
            0 0 40px #00ffff;
        animation: glow 2s ease-in-out infinite alternate;
    }
    @keyframes glow {
        from {
            text-shadow:
                0 0 10px #00ffff,
                0 0 20px #00ffff,
                0 0 30px #00ffff;
        }
        to {
            text-shadow:
                0 0 20px #00ffff,
                0 0 30px #00ffff,
                0 0 40px #00ffff,
                0 0 50px #00ffff,
                0 0 60px #00ffff;
        }
    }
    .landing-hero p {
        font-size: 1.4rem;
        margin-bottom: 50px;
        color: #ff00ff;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        text-shadow: 0 0 10px rgba(255, 0, 255, 0.8);
        font-weight: 700;
    }
    .hero-cta {
        background: transparent;
        color: #00ffff;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 0;
        border: 3px solid #00ffff;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 3px;
        box-shadow:
            0 0 10px #00ffff,
            inset 0 0 10px rgba(0, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }
    .hero-cta::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.4), transparent);
        transition: left 0.5s;
    }
    .hero-cta:hover::before {
        left: 100%;
    }
    .hero-cta:hover {
        box-shadow:
            0 0 20px #00ffff,
            0 0 30px #00ffff,
            inset 0 0 20px rgba(0, 255, 255, 0.3);
    }
    .section-padding {
        padding: 100px 0;
        background: #0a0a0a;
        border-top: 2px solid #00ffff;
        border-bottom: 2px solid #ff00ff;
        box-shadow:
            inset 0 0 20px rgba(0, 255, 255, 0.1),
            inset 0 0 20px rgba(255, 0, 255, 0.1);
    }
    .section-title {
        font-size: 3rem;
        font-weight: 900;
        text-align: center;
        margin-bottom: 60px;
        color: #00ffff;
        text-transform: uppercase;
        letter-spacing: 5px;
        text-shadow:
            0 0 10px #00ffff,
            0 0 20px #00ffff;
        border: 2px solid #00ffff;
        padding: 20px;
        box-shadow:
            0 0 15px rgba(0, 255, 255, 0.5),
            inset 0 0 15px rgba(0, 255, 255, 0.1);
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
        border: 3px solid #00ffff;
        box-shadow:
            0 0 20px rgba(0, 255, 255, 0.6),
            inset 0 0 20px rgba(0, 255, 255, 0.1);
        transition: all 0.3s ease;
        filter: brightness(0.9) contrast(1.1);
    }
    .service-gallery img:hover {
        box-shadow:
            0 0 30px rgba(0, 255, 255, 0.8),
            0 0 40px rgba(255, 0, 255, 0.6),
            inset 0 0 30px rgba(0, 255, 255, 0.2);
        border-color: #ff00ff;
        filter: brightness(1.1) contrast(1.2);
    }
    .form-section {
        background: linear-gradient(135deg, #1a0033 0%, #0a0a0a 100%);
        padding: 100px 0;
        border-top: 3px solid #ff00ff;
        box-shadow: 0 0 30px rgba(255, 0, 255, 0.3);
    }
    .form-card {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 0;
        padding: 50px;
        border: 3px solid #00ffff;
        box-shadow:
            0 0 30px rgba(0, 255, 255, 0.5),
            inset 0 0 30px rgba(0, 255, 255, 0.1);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #00ffff;
        font-weight: 900;
        margin-bottom: 35px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 3px;
        text-shadow: 0 0 10px #00ffff;
    }
    .faq-item {
        border: 2px solid #00ffff;
        border-radius: 0;
        margin-bottom: 20px;
        overflow: hidden;
        background: rgba(10, 10, 10, 0.8);
        box-shadow:
            0 0 15px rgba(0, 255, 255, 0.4),
            inset 0 0 15px rgba(0, 255, 255, 0.05);
    }
    .faq-question {
        background: linear-gradient(90deg, rgba(0, 255, 255, 0.1) 0%, rgba(255, 0, 255, 0.1) 100%);
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        color: #00ffff;
        text-transform: uppercase;
        letter-spacing: 2px;
        border-bottom: 2px solid #ff00ff;
    }
    .faq-question:hover {
        background: linear-gradient(90deg, rgba(0, 255, 255, 0.2) 0%, rgba(255, 0, 255, 0.2) 100%);
        box-shadow:
            inset 0 0 20px rgba(0, 255, 255, 0.3),
            inset 0 0 20px rgba(255, 0, 255, 0.3);
        color: #ffffff;
    }
    .faq-answer {
        padding: 25px;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        color: #00ffff;
        line-height: 1.8;
        font-weight: 400;
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
        background: rgba(10, 10, 10, 0.9);
        border-radius: 0;
        padding: 40px;
        border: 3px solid #00ffff;
        box-shadow:
            0 0 25px rgba(0, 255, 255, 0.5),
            inset 0 0 25px rgba(0, 255, 255, 0.1);
        position: relative;
        transition: all 0.3s ease;
    }
    .pricing-card:hover {
        box-shadow:
            0 0 35px rgba(0, 255, 255, 0.7),
            0 0 45px rgba(255, 0, 255, 0.5),
            inset 0 0 35px rgba(0, 255, 255, 0.2);
        border-color: #ff00ff;
    }
    .pricing-card.popular {
        border: 4px solid #ff00ff;
        background: rgba(26, 0, 51, 0.9);
        box-shadow:
            0 0 30px rgba(255, 0, 255, 0.7),
            0 0 40px rgba(0, 255, 255, 0.5),
            inset 0 0 30px rgba(255, 0, 255, 0.2);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        background: #ff00ff;
        color: #0a0a0a;
        padding: 10px 30px;
        border: 2px solid #00ffff;
        font-size: 0.8rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 3px;
        box-shadow: 0 0 20px rgba(255, 0, 255, 0.8);
    }
    .price-display {
        font-size: 3.5rem;
        font-weight: 900;
        color: #00ffff;
        margin: 25px 0;
        text-shadow:
            0 0 10px #00ffff,
            0 0 20px #00ffff;
        text-transform: uppercase;
    }
    .price-period {
        color: #ff00ff;
        font-size: 1rem;
        text-shadow: 0 0 10px rgba(255, 0, 255, 0.8);
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(0, 255, 255, 0.3);
        color: #00ffff;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '▶';
        color: #ff00ff;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1rem;
        text-shadow: 0 0 10px rgba(255, 0, 255, 0.8);
    }
    .description-section {
        padding: 100px 0;
        background: #0a0a0a;
        border-top: 2px solid #ff00ff;
        border-bottom: 2px solid #00ffff;
        box-shadow:
            inset 0 0 30px rgba(255, 0, 255, 0.1),
            inset 0 0 30px rgba(0, 255, 255, 0.1);
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.1rem;
        color: #00ffff;
        background: rgba(10, 10, 10, 0.8);
        padding: 50px;
        border: 3px solid #00ffff;
        box-shadow:
            0 0 30px rgba(0, 255, 255, 0.5),
            inset 0 0 30px rgba(0, 255, 255, 0.1);
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
        border-right: 4px solid #ff00ff;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        box-shadow: 0 0 15px rgba(255, 0, 255, 0.5);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #00ffff;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 3px;
        text-shadow: 0 0 10px #00ffff;
        border: 2px solid #00ffff;
        padding: 15px;
        box-shadow:
            0 0 15px rgba(0, 255, 255, 0.5),
            inset 0 0 15px rgba(0, 255, 255, 0.1);
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border: 3px solid #00ffff;
        margin: 25px 0;
        box-shadow:
            0 0 25px rgba(0, 255, 255, 0.6),
            inset 0 0 25px rgba(0, 255, 255, 0.1);
        filter: brightness(0.9) contrast(1.1);
    }
    .btn-primary {
        background: transparent;
        color: #00ffff;
        border: 3px solid #00ffff;
        padding: 14px 35px;
        font-weight: 700;
        border-radius: 0;
        text-transform: uppercase;
        letter-spacing: 2px;
        box-shadow:
            0 0 15px rgba(0, 255, 255, 0.5),
            inset 0 0 15px rgba(0, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        box-shadow:
            0 0 25px rgba(0, 255, 255, 0.7),
            0 0 35px rgba(255, 0, 255, 0.5),
            inset 0 0 25px rgba(0, 255, 255, 0.2);
        border-color: #ff00ff;
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
                        <h3 style="color: #00ffff; font-weight: 900; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #ff00ff; text-shadow: 0 0 10px rgba(255, 0, 255, 0.8);">{{ $pricing->description }}</p>
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

