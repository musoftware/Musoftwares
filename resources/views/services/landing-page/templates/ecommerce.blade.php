{{-- Template: E-commerce UI Style - Product grids, card shadows, spacing rules, image-focused layout --}}
<style>
    body {
        background: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .landing-hero {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    .landing-hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .landing-hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        opacity: 0.95;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 400;
    }
    .hero-cta {
        background: white;
        color: #f5576c;
        padding: 16px 45px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .hero-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        color: #f5576c;
    }
    .section-padding {
        padding: 80px 0;
    }
    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 50px;
        color: #2d3748;
    }
    .service-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    .service-gallery img {
        width: 100%;
        height: 320px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        background: #f7fafc;
    }
    .service-gallery img:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    .form-section {
        background: #f7fafc;
        padding: 80px 0;
        border-top: 1px solid #e2e8f0;
    }
    .form-card {
        background: white;
        border-radius: 12px;
        padding: 50px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        max-width: 700px;
        margin: 0 auto;
    }
    .form-card h2 {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 35px;
        text-align: center;
        font-size: 2rem;
    }
    .faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    .faq-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }
    .faq-question {
        background: #f7fafc;
        padding: 24px;
        margin: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #2d3748;
        font-size: 1.1rem;
    }
    .faq-question:hover {
        background: #edf2f7;
        color: #f5576c;
    }
    .faq-answer {
        padding: 0 24px 24px 24px;
        background: white;
        display: none;
        color: #4a5568;
        line-height: 1.8;
        font-size: 1rem;
    }
    .faq-answer.active {
        display: block;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    .pricing-card {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        position: relative;
        transition: all 0.3s ease;
    }
    .pricing-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    .pricing-card.popular {
        border: 2px solid #f5576c;
        box-shadow: 0 6px 20px rgba(245, 87, 108, 0.2);
        transform: scale(1.05);
    }
    .pricing-card.popular::before {
        content: 'BEST VALUE';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #f5576c;
        color: white;
        padding: 6px 20px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(245, 87, 108, 0.3);
    }
    .price-display {
        font-size: 3.5rem;
        font-weight: 700;
        color: #2d3748;
        margin: 25px 0;
    }
    .price-period {
        color: #718096;
        font-size: 1rem;
        font-weight: 400;
    }
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 30px 0;
    }
    .pricing-features li {
        padding: 14px 0;
        border-bottom: 1px solid #f7fafc;
        color: #4a5568;
        font-size: 1rem;
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
        padding: 80px 0;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    .description-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.8;
        font-size: 1.1rem;
        color: #4a5568;
        background: white;
        padding: 50px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
        background: #fef5f7;
    }
    .description-content h2,
    .description-content h3,
    .description-content h4 {
        color: #2d3748;
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
        border-radius: 12px;
        margin: 25px 0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    .btn-primary {
        background: #f5576c;
        color: white;
        border: none;
        padding: 14px 35px;
        font-weight: 600;
        border-radius: 50px;
        box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        background: #e74c3c;
        box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
        transform: translateY(-2px);
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
                        <h3 style="color: #2d3748; font-weight: 700; margin-bottom: 15px;">{{ $pricing->plan_name }}</h3>
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






