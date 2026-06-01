{{-- Template: Retro / Vintage UI - Muted colors, rounded fonts, old-school gradients --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fredoka+One&family=Comfortaa:wght@400;700&display=swap');
    body {
        background: #f4e4c1;
        background-image: 
            repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(139, 69, 19, 0.03) 10px, rgba(139, 69, 19, 0.03) 20px);
        font-family: 'Comfortaa', cursive;
    }
    .landing-hero {
        background: linear-gradient(135deg, #d4a574 0%, #c19a6b 50%, #b8956a 100%);
        color: #3d2817;
        padding: 120px 0;
        text-align: center;
        border-bottom: 8px double #8b4513;
        box-shadow: inset 0 0 30px rgba(139, 69, 19, 0.2);
    }
    .landing-hero h1 {
        font-size: 4rem;
        font-weight: 700;
        margin-bottom: 25px;
        color: #3d2817;
        font-family: 'Fredoka One', cursive;
        text-shadow: 3px 3px 0 rgba(139, 69, 19, 0.3);
        letter-spacing: 2px;
    }
    .landing-hero p {
        font-size: 1.4rem;
        margin-bottom: 40px;
        color: #5c4033;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 400;
        text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.3);
    }
    .hero-cta {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 30px;
        border: 4px solid #c0392b;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 
            0 6px 0 #a93226,
            0 8px 15px rgba(0, 0, 0, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Fredoka One', cursive;
    }
    .hero-cta:hover {
        box-shadow: 
            0 3px 0 #a93226,
            0 5px 10px rgba(0, 0, 0, 0.3);
    }
    .section-padding {
        padding: 100px 0;
    }
    .section-title {
        font-size: 3rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 60px;
        color: #8b4513;
        font-family: 'Fredoka One', cursive;
        text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.5);
        letter-spacing: 2px;
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
        border-radius: 20px;
        border: 6px solid #d4a574;
        box-shadow: 
            0 8px 0 #8b4513,
            0 12px 20px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
        background: #f4e4c1;
    }
    .service-gallery img:hover {
        box-shadow: 
            0 4px 0 #8b4513,
            0 8px 15px rgba(0, 0, 0, 0.3);
    }
    .form-section {
        background: linear-gradient(135deg, #e8d5b7 0%, #d4a574 100%);
        padding: 100px 0;
        border-top: 8px double #8b4513;
        box-shadow: inset 0 0 30px rgba(139, 69, 19, 0.2);
    }
    .form-card {
        background: #f4e4c1;
        border-radius: 20px;
        padding: 50px;
        border: 6px solid #d4a574;
        box-shadow: 
            0 8px 0 #8b4513,
            0 12px 25px rgba(0, 0, 0, 0.3);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #8b4513;
        font-weight: 700;
        margin-bottom: 35px;
        text-align: center;
        font-size: 2.5rem;
        font-family: 'Fredoka One', cursive;
        text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.5);
    }
    .faq-item {
        border: 4px solid #d4a574;
        border-radius: 15px;
        margin-bottom: 20px;
        overflow: hidden;
        background: #f4e4c1;
        box-shadow: 
            0 4px 0 #8b4513,
            0 6px 15px rgba(0, 0, 0, 0.2);
    }
    .faq-question {
        background: linear-gradient(135deg, #d4a574 0%, #c19a6b 100%);
        color: #3d2817;
        padding: 25px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 1.2rem;
        text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.3);
        border-bottom: 4px solid #8b4513;
    }
    .faq-question:hover {
        background: linear-gradient(135deg, #c19a6b 0%, #b8956a 100%);
    }
    .faq-answer {
        padding: 25px;
        background: #f4e4c1;
        display: none;
        color: #5c4033;
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
        background: #f4e4c1;
        border-radius: 20px;
        padding: 40px;
        border: 6px solid #d4a574;
        box-shadow: 
            0 6px 0 #8b4513,
            0 10px 20px rgba(0, 0, 0, 0.3);
        position: relative;
        transition: all 0.3s ease;
    }
    .pricing-card:hover {
        box-shadow: 
            0 3px 0 #8b4513,
            0 6px 15px rgba(0, 0, 0, 0.3);
    }
    .pricing-card.popular {
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        border: 8px solid #ff8c00;
        box-shadow: 
            0 8px 0 #cc7700,
            0 12px 25px rgba(0, 0, 0, 0.3);
    }
    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
        padding: 10px 30px;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 700;
        border: 4px solid #c0392b;
        box-shadow: 
            0 4px 0 #a93226,
            0 6px 15px rgba(0, 0, 0, 0.3);
        font-family: 'Fredoka One', cursive;
        text-transform: uppercase;
    }
    .price-display {
        font-size: 3.5rem;
        font-weight: 700;
        color: #8b4513;
        margin: 25px 0;
        font-family: 'Fredoka One', cursive;
        text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.5);
    }
    .price-period {
        color: #5c4033;
        font-size: 1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 12px 0;
        border-bottom: 2px solid #d4a574;
        color: #5c4033;
        font-weight: 400;
    }
    .pricing-features li:last-child {
        border-bottom: none;
    }
    .pricing-features li::before {
        content: '★';
        color: #ff8c00;
        font-weight: bold;
        margin-right: 12px;
        font-size: 1.2rem;
    }
    .description-section {
        padding: 100px 0;
        background: linear-gradient(135deg, #f4e4c1 0%, #e8d5b7 100%);
        border-top: 8px double #8b4513;
        border-bottom: 8px double #8b4513;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 1.1rem;
        color: #5c4033;
        background: #f4e4c1;
        padding: 50px;
        border-radius: 20px;
        border: 6px solid #d4a574;
        box-shadow: 
            0 6px 0 #8b4513,
            0 10px 20px rgba(0, 0, 0, 0.3);
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
        border-right: 6px solid #8b4513;
        border-left: none;
        padding-right: 20px;
        padding-left: 0;
        margin-right: 20px;
        margin-left: 0;
        background: linear-gradient(90deg, rgba(212, 165, 116, 0.3) 0%, transparent 100%);
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #8b4513;
        margin-top: 35px;
        margin-bottom: 20px;
        font-weight: 700;
        font-family: 'Fredoka One', cursive;
        text-shadow: 2px 2px 0 rgba(255, 255, 255, 0.5);
    }
    .description-content p {
        margin-bottom: 20px;
    }
    .description-content img {
        max-width: 100%;
        height: auto;
        border-radius: 20px;
        margin: 25px 0;
        border: 6px solid #d4a574;
        box-shadow: 
            0 6px 0 #8b4513,
            0 10px 20px rgba(0, 0, 0, 0.3);
    }
    .btn-primary {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
        border: 4px solid #c0392b;
        padding: 14px 35px;
        font-weight: 700;
        border-radius: 30px;
        box-shadow: 
            0 4px 0 #a93226,
            0 6px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
        font-family: 'Fredoka One', cursive;
        text-transform: uppercase;
    }
    .btn-primary:hover {
        box-shadow: 
            0 2px 0 #a93226,
            0 4px 10px rgba(0, 0, 0, 0.3);
        color: white;
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
                        <h3 style="color: #8b4513; font-weight: 700; margin-bottom: 15px; font-family: 'Fredoka One', cursive;">{{ $pricing->plan_name }}</h3>
                        @if($pricing->description)
                            <p style="margin-top: 10px; color: #5c4033;">{{ $pricing->description }}</p>
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






