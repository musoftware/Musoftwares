@extends('layouts.app')

@php
    // ============================================
    // Template Selection & Validation
    // ============================================
    $template = $landingPage->template ?? 'modern';
    $validTemplates = [
        'modern', 'creative', 'business', 'product', 'minimal', 'dashboard',
        'glassmorphism', 'neumorphism', 'skeuomorphism', 'flat-design',
        'material-design', 'fluent-design', 'ecommerce', 'cyberpunk',
        'gaming', 'dark-mode', 'brutalism', 'retro', 'pastel'
    ];
    $templatePath = resource_path('views/services/landing-page/templates/' . $template . '.blade.php');
    if (!in_array($template, $validTemplates) || !file_exists($templatePath)) {
        $template = 'modern';
    }

    // ============================================
    // Meta Tags Preparation
    // ============================================
    $metaTitle = $landingPage->meta_title ?: $landingPage->hero_title;
    $metaDescription = $landingPage->meta_description ?: $landingPage->hero_description ?: strip_tags($landingPage->description ?? '');
    $metaKeywords = $landingPage->meta_keywords ?? '';
    $robots = $landingPage->robots ?? 'index, follow';
    $canonicalUrl = $landingPage->canonical_url ?: url()->current();

    // Open Graph Meta Tags
    $ogTitle = $landingPage->og_title ?: $metaTitle;
    $ogDescription = $landingPage->og_description ?: $metaDescription;
    $ogImage = $landingPage->og_image ?: ($landingPage->service->image ? asset($landingPage->service->image) : '');

    // Twitter Card Meta Tags
    $twitterTitle = $landingPage->twitter_title ?: $ogTitle;
    $twitterDescription = $landingPage->twitter_description ?: $ogDescription;
    $twitterImage = $landingPage->twitter_image ?: $ogImage;
    $twitterCardType = $landingPage->twitter_card_type ?? 'summary_large_image';
@endphp

@section('title', $metaTitle . ' | ' . config('app.name', 'Laravel'))

@section('description', $metaDescription)

@section('head_code')
{{-- Basic Meta Tags --}}
    @if($metaKeywords)
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- Open Graph Meta Tags --}}
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'Musoftware') }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $ogTitle }}">
    @endif

    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="{{ $twitterCardType }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    @if($twitterImage)
        <meta name="twitter:image" content="{{ $twitterImage }}">
    @endif
@endsection

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix service-landing-page-show">
    @if(isset($isPreview) && $isPreview)
        
    @endif
    @include('services.landing-page.templates.' . $template, ['landingPage' => $landingPage])
    </div>
@endsection

@push('scripts')
    {{-- ============================================ --}}
    {{-- Pixel Tracking Codes --}}
    {{-- ============================================ --}}

    @if($landingPage->facebook_pixel_id)
        {{-- Facebook Pixel --}}
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $landingPage->facebook_pixel_id }}');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img alt="{{ __('Facebook tracking pixel') }}" height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $landingPage->facebook_pixel_id }}&ev=PageView&noscript=1"
            />
        </noscript>
    @endif

    @if($landingPage->tiktok_pixel_id)
        {{-- TikTok Pixel --}}
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                ttq.load('{{ $landingPage->tiktok_pixel_id }}');
                ttq.page();
            }(window, document, 'ttq');
        </script>
    @endif

    @if($landingPage->snapchat_pixel_id)
        {{-- Snapchat Pixel --}}
        <script>
            (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
            {a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
            a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
            r.src=n;var u=t.getElementsByTagName(s)[0];
            u.parentNode.insertBefore(r,u);})(window,document,
            'https://sc-static.net/scevent.min.js');
            snaptr('init', '{{ $landingPage->snapchat_pixel_id }}', {
                'user_email': ''
            });
            snaptr('track', 'PAGE_VIEW');
        </script>
    @endif

    @if($landingPage->google_analytics_id)
        {{-- Google Analytics --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $landingPage->google_analytics_id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $landingPage->google_analytics_id }}');
        </script>
    @endif

    {{-- ============================================ --}}
    {{-- Form Submission Tracking --}}
    {{-- ============================================ --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smart CTA Logic (Hero)
            @php
                $heroVariants = $landingPage->ctaVariants->where('position', 'hero')->values();
            @endphp
            
            const heroVariants = @json($heroVariants);
            const heroCtaBtn = document.getElementById('hero-cta');

            if (heroCtaBtn && heroVariants.length > 0) {
                const isMobile = window.innerWidth <= 768;
                const isReturning = localStorage.getItem('visited_{{ $landingPage->id }}');
                
                if (!isReturning) {
                    localStorage.setItem('visited_{{ $landingPage->id }}', 'true');
                }
                
                const matchedVariant = heroVariants.find(variant => {
                    if (!variant.is_active) return false;
                    if (isMobile && !variant.show_on_mobile) return false;
                    if (!isMobile && !variant.show_on_desktop) return false;
                    if (!isReturning && !variant.show_on_first_visit) return false;
                    if (isReturning && !variant.show_on_returning_visit) return false;
                    return true;
                });
                
                if (matchedVariant) {
                    heroCtaBtn.textContent = matchedVariant.cta_text;
                    if (matchedVariant.cta_link) heroCtaBtn.href = matchedVariant.cta_link;
                    if (matchedVariant.cta_style) heroCtaBtn.classList.add('cta-style-' + matchedVariant.cta_style);
                }
            }

            const form = document.querySelector('form[action*="submit-form"]');
            if (!form) return;

            form.addEventListener('submit', function() {
                @if($landingPage->facebook_pixel_id)
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'Lead');
                    }
                @endif

                @if($landingPage->tiktok_pixel_id)
                    if (typeof ttq !== 'undefined') {
                        ttq.track('CompleteRegistration');
                    }
                @endif

                @if($landingPage->snapchat_pixel_id)
                    if (typeof snaptr !== 'undefined') {
                        snaptr('track', 'SIGN_UP');
                    }
                @endif

                @if($landingPage->google_analytics_id)
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'conversion', {
                            'send_to': '{{ $landingPage->google_analytics_id }}/conversion',
                            'event_category': 'engagement',
                            'event_label': 'form_submission'
                        });
                    }
                @endif
            });
        });

        {{-- ============================================ --}}
        {{-- Sticky CTA (Mobile) --}}
        {{-- ============================================ --}}
        @if($landingPage->sticky_cta_enabled)
            (function() {
                const isMobile = window.innerWidth <= 768;
                const mobileOnly = {{ $landingPage->sticky_cta_mobile_only ? 'true' : 'false' }};

                if (mobileOnly && !isMobile) return;

                const stickyCta = document.createElement('div');
                stickyCta.id = 'sticky-cta';
                stickyCta.style.cssText = `
                    position: fixed;
                    {{ $landingPage->sticky_cta_position === 'top' ? 'top: 0;' : 'bottom: 0;' }}
                    left: 0;
                    right: 0;
                    background: #667eea;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    z-index: 9999;
                    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                `;

                const ctaButton = document.createElement('a');
                ctaButton.href = '#contact-form';
                ctaButton.textContent = @json($landingPage->sticky_cta_text ?? "Get Started");
                ctaButton.style.cssText = 'display: inline-block; padding: 12px 30px; background: white; color: #667eea; text-decoration: none; border-radius: 5px; font-weight: bold;';
                ctaButton.onclick = function(e) {
                    e.preventDefault();
                    const form = document.querySelector('#contact-form') || document.querySelector('form');
                    if (form) {
                        form.scrollIntoView({ behavior: 'smooth' });
                    }
                };

                stickyCta.appendChild(ctaButton);
                document.body.appendChild(stickyCta);
            })();
        @endif

        {{-- ============================================ --}}
        {{-- Exit Intent Popup (Desktop) --}}
        {{-- ============================================ --}}
        @if($landingPage->exit_intent_enabled)
            (function() {
                const desktopOnly = {{ $landingPage->exit_intent_desktop_only ? 'true' : 'false' }};
                const isMobile = window.innerWidth <= 768;

                if (desktopOnly && isMobile) return;

                let exitIntentShown = localStorage.getItem('exitIntentShown_{{ $landingPage->id }}');

                document.addEventListener('mouseout', function(e) {
                    if (!e.toElement && !e.relatedTarget && e.clientY < 10) {
                        if (!exitIntentShown) {
                            showExitIntentPopup();
                            exitIntentShown = true;
                            localStorage.setItem('exitIntentShown_{{ $landingPage->id }}', 'true');
                        }
                    }
                });

                function showExitIntentPopup() {
                    const overlay = document.createElement('div');
                    overlay.id = 'exit-intent-overlay';
                    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';

                    const popup = document.createElement('div');
                    popup.style.cssText = 'background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; text-align: center; position: relative;';

                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.cssText = 'position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;';
                    closeBtn.onclick = function() {
                        overlay.remove();
                    };

                    const title = document.createElement('h3');
                    title.textContent = @json($landingPage->exit_intent_title ?? "Wait! Don't miss out");
                    title.style.cssText = 'margin-bottom: 15px; color: #333;';

                    const message = document.createElement('p');
                    message.textContent = @json($landingPage->exit_intent_message ?? "Get started today and save 20%!");
                    message.style.cssText = 'margin-bottom: 20px; color: #666;';

                    const ctaBtn = document.createElement('a');
                    ctaBtn.href = '#contact-form';
                    ctaBtn.textContent = @json($landingPage->exit_intent_cta_text ?? "Get Started");
                    ctaBtn.style.cssText = 'display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;';
                    ctaBtn.onclick = function(e) {
                        e.preventDefault();
                        overlay.remove();
                        const form = document.querySelector('#contact-form') || document.querySelector('form');
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth' });
                        }
                    };

                    popup.appendChild(closeBtn);
                    popup.appendChild(title);
                    popup.appendChild(message);
                    popup.appendChild(ctaBtn);
                    overlay.appendChild(popup);
                    document.body.appendChild(overlay);

                    overlay.onclick = function(e) {
                        if (e.target === overlay) {
                            overlay.remove();
                        }
                    };
                }
            })();
        @endif

        {{-- ============================================ --}}
        {{-- Time-Based Popup --}}
        {{-- ============================================ --}}
        @if($landingPage->time_based_popup_enabled)
            (function() {
                const delay = {{ $landingPage->time_based_popup_delay ?? 30 }} * 1000; // Convert to milliseconds
                let timeBasedShown = localStorage.getItem('timeBasedShown_{{ $landingPage->id }}');

                if (timeBasedShown) return;

                setTimeout(function() {
                    if (!timeBasedShown) {
                        showTimeBasedPopup();
                        localStorage.setItem('timeBasedShown_{{ $landingPage->id }}', 'true');
                    }
                }, delay);

                function showTimeBasedPopup() {
                    const overlay = document.createElement('div');
                    overlay.id = 'time-based-overlay';
                    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';

                    const popup = document.createElement('div');
                    popup.style.cssText = 'background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; text-align: center; position: relative;';

                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.cssText = 'position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;';
                    closeBtn.onclick = function() {
                        overlay.remove();
                    };

                    const title = document.createElement('h3');
                    title.textContent = @json($landingPage->time_based_popup_title ?? "Special Offer!");
                    title.style.cssText = 'margin-bottom: 15px; color: #333;';

                    const message = document.createElement('p');
                    message.textContent = @json($landingPage->time_based_popup_message ?? "Get 20% off your first order!");
                    message.style.cssText = 'margin-bottom: 20px; color: #666;';

                    const ctaBtn = document.createElement('a');
                    ctaBtn.href = '#contact-form';
                    ctaBtn.textContent = @json($landingPage->time_based_popup_cta_text ?? "Get Started");
                    ctaBtn.style.cssText = 'display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;';
                    ctaBtn.onclick = function(e) {
                        e.preventDefault();
                        overlay.remove();
                        const form = document.querySelector('#contact-form') || document.querySelector('form');
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth' });
                        }
                    };

                    popup.appendChild(closeBtn);
                    popup.appendChild(title);
                    popup.appendChild(message);
                    popup.appendChild(ctaBtn);
                    overlay.appendChild(popup);
                    document.body.appendChild(overlay);

                    overlay.onclick = function(e) {
                        if (e.target === overlay) {
                            overlay.remove();
                        }
                    };
                }
            })();
        @endif

        {{-- ============================================ --}}
        {{-- Scroll Depth Tracking --}}
        {{-- ============================================ --}}
        (function() {
            let maxScroll = 0;
            let tracked = {25: false, 50: false, 75: false, 100: false};
            
            function trackScroll() {
                const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const scrollPos = window.scrollY;
                if (docHeight === 0) return;

                const percent = Math.round((scrollPos / docHeight) * 100);
                
                if (percent > maxScroll) {
                    maxScroll = percent;
                }

                [25, 50, 75, 100].forEach(milestone => {
                    if (percent >= milestone && !tracked[milestone]) {
                        tracked[milestone] = true;
                        sendScrollMetric(milestone);
                    }
                });
            }

            function sendScrollMetric(depth) {
                fetch('{{ route("services.landing-page.track-scroll", $landingPage) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ depth: depth })
                }); // Fire and forget
            }

            // Debounce scroll event
            let timeout;
            window.addEventListener('scroll', function() {
                clearTimeout(timeout);
                timeout = setTimeout(trackScroll, 100);
            });
        })();
    </script>
@endpush
