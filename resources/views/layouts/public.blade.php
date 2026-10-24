<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="light" data-theme-fixed="true" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <script>
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-theme-fixed', 'true');
        document.documentElement.style.colorScheme = 'light';
    </script>


    @php
        $currentUrl = url()->current();
        $canonicalUrl = $canonical ?? $currentUrl;
        $ogImage = $image ?? asset('images/apple/web-mobile-suite.jpg');
        $locale = app()->getLocale();
    @endphp

    <title>{{ $title ?? ($locale === 'ar' ? 'موسوفت ويرز | تطوير البرمجيات والأنظمة السحابية والـ ERP' : 'Musoftwares | Enterprise Software, Cloud ERP & Custom Development') }}</title>
    <meta name="description" content="{{ $description ?? 'Custom web applications, mobile apps, and enterprise automation platforms engineered by Mahmoud Amin in Suez, Egypt.' }}">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    
    <!-- Open Graph & Social Cards -->
    <meta property="og:site_name" content="Musoftwares">
    <meta property="og:title" content="{{ $title ?? 'Musoftwares | Software Engineering Studio' }}">
    <meta property="og:description" content="{{ $description ?? 'Custom Web Apps, Mobile Apps & Enterprise Platforms' }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_AR' : 'en_US' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@MusoftwareUno">
    <meta name="twitter:title" content="{{ $title ?? 'Musoftwares' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Custom Web Apps, Mobile Apps & Enterprise Platforms' }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- Apple SF Pro Font Fallbacks + Cairo & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Schema.org JSON-LD: who the site belongs to (the same on every public page) -->
    <script type="application/ld+json">{!! \App\Support\SiteSchema::json() !!}</script>

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

    @vite(['resources/js/app.tsx'])

    @stack('head')

    <style>
        :root {
            --apple-bg: #ffffff;
            --apple-section-bg: #f5f5f7;
            --apple-card-bg: #ffffff;
            --apple-text: #1d1d1f;
            --apple-subtext: #86868b;
            --apple-blue: #0071e3;
            --apple-blue-hover: #0077ed;
            --apple-link: #0066cc;
            --apple-border: #d2d2d7;
            --apple-border-light: rgba(0, 0, 0, 0.08);
        }
        html,
        html body {
            background-color: #ffffff !important;
            color: #1d1d1f !important;
            color-scheme: light !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Inter", "Cairo", sans-serif;
            background-color: var(--apple-bg);
            color: var(--apple-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .apple-headline {
            letter-spacing: -0.015em;
            color: #1d1d1f;
        }
        .apple-subhead {
            letter-spacing: -0.01em;
            color: #86868b;
        }
        .apple-frosted-light {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
        }
        .apple-pill-btn {
            border-radius: 980px;
            padding: 8px 18px;
            font-size: 14px;
            line-height: 1.42857;
            font-weight: 500;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .apple-pill-primary {
            background-color: #0071e3;
            color: #ffffff;
        }
        .apple-pill-primary:hover {
            background-color: #0077ed;
            transform: translateY(-1px);
        }
        .apple-pill-secondary {
            background-color: #f5f5f7;
            color: #1d1d1f;
            border: 1px solid #d2d2d7;
        }
        .apple-pill-secondary:hover {
            background-color: #e8e8ed;
            color: #0071e3;
            transform: translateY(-1px);
        }
        .apple-link-cta {
            color: #0066cc;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: gap 0.2s ease;
        }
        .apple-link-cta:hover {
            text-decoration: underline;
            gap: 7px;
        }

        /* Boutique Studio Design System Tokens & Buttons */
        .btn-studio-primary {
            min-height: 44px;
            padding: 10px 22px;
            border-radius: 12px;
            background-color: #0071e3;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-studio-primary:hover {
            background-color: #0077ed;
            box-shadow: 0 4px 14px rgba(0, 113, 227, 0.25);
            transform: translateY(-1px);
        }
        .btn-studio-primary:active {
            transform: translateY(0);
        }
        .btn-studio-secondary {
            min-height: 44px;
            padding: 10px 22px;
            border-radius: 12px;
            background-color: #ffffff;
            color: #1d1d1f;
            font-size: 15px;
            font-weight: 600;
            border: 1px solid rgba(0, 0, 0, 0.12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-studio-secondary:hover {
            background-color: #f5f5f7;
            border-color: rgba(0, 0, 0, 0.2);
            transform: translateY(-1px);
        }
        .btn-studio-secondary:active {
            transform: translateY(0);
        }

        /* Apple Scroll Reveal Micro-interactions */
        .apple-reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }
        .apple-reveal.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Apple Card Hover Micro-interaction */
        .apple-bento-card {
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .apple-bento-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased bg-[#ffffff] text-[#1d1d1f] selection:bg-[#0071e3] selection:text-white">

    <!-- Navigation Bar -->
    @hasSection('custom_header')
        @yield('custom_header')
    @else
        @include('layouts.site-header')
    @endif

    <!-- Main Content Stream -->

    <!-- Main Content Stream -->
    <main class="flex-1 w-full bg-[#ffffff]">
        @yield('content')
    </main>

    <!-- Footer -->
    @hasSection('custom_footer')
        @yield('custom_footer')
    @else
        @include('layouts.site-footer')
    @endif

    <!-- Apple Smooth Scroll Reveal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reveals = document.querySelectorAll('.apple-reveal');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-revealed');
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

                reveals.forEach(el => observer.observe(el));
            } else {
                reveals.forEach(el => el.classList.add('is-revealed'));
            }

            // Universal Mouse Proximity & Drag Horizontal Scroll Handler for Blade Views
            function setupMouseScroll(container) {
                if (!container || container.__mouseScrollReady) return;
                container.__mouseScrollReady = true;

                let animFrame = null;
                let velocity = 0;
                let isMouseDown = false;
                let startX = 0;
                let scrollLeftStart = 0;
                let hasDragged = false;

                const checkBoundaries = () => {
                    const wrapper = container.closest('.mouse-scroll-wrapper');
                    if (!wrapper) return;
                    const leftFade = wrapper.querySelector('.mouse-scroll-fade-left');
                    const rightFade = wrapper.querySelector('.mouse-scroll-fade-right');
                    const isRtl = getComputedStyle(container).direction === 'rtl';
                    const maxScroll = container.scrollWidth - container.clientWidth;

                    if (maxScroll <= 2) {
                        if (leftFade) leftFade.style.opacity = '0';
                        if (rightFade) rightFade.style.opacity = '0';
                        return;
                    }

                    if (isRtl) {
                        const abs = Math.abs(container.scrollLeft);
                        if (leftFade) leftFade.style.opacity = abs < maxScroll - 2 ? '1' : '0';
                        if (rightFade) rightFade.style.opacity = abs > 2 ? '1' : '0';
                    } else {
                        if (leftFade) leftFade.style.opacity = container.scrollLeft > 2 ? '1' : '0';
                        if (rightFade) rightFade.style.opacity = container.scrollLeft < maxScroll - 2 ? '1' : '0';
                    }
                };

                const startLoop = () => {
                    if (animFrame !== null) return;
                    const loop = () => {
                        if (velocity !== 0) {
                            container.scrollLeft += velocity;
                            checkBoundaries();
                            animFrame = requestAnimationFrame(loop);
                        } else {
                            animFrame = null;
                        }
                    };
                    animFrame = requestAnimationFrame(loop);
                };

                const stopLoop = () => {
                    velocity = 0;
                    if (animFrame !== null) {
                        cancelAnimationFrame(animFrame);
                        animFrame = null;
                    }
                };

                container.addEventListener('mousemove', (e) => {
                    if (isMouseDown) {
                        const dx = e.pageX - startX;
                        if (Math.abs(dx) > 5) hasDragged = true;
                        container.scrollLeft = scrollLeftStart - dx;
                        checkBoundaries();
                        return;
                    }

                    const rect = container.getBoundingClientRect();
                    const mouseX = e.clientX - rect.left;
                    const width = rect.width;
                    if (width <= 0) return;

                    const zone = Math.max(40, width * 0.2);
                    const isRtl = getComputedStyle(container).direction === 'rtl';

                    if (mouseX < zone) {
                        const intensity = 1 - Math.max(0, mouseX) / zone;
                        const dir = isRtl ? 1 : -1;
                        velocity = dir * 16 * intensity;
                        startLoop();
                    } else if (mouseX > width - zone) {
                        const intensity = 1 - Math.max(0, width - mouseX) / zone;
                        const dir = isRtl ? -1 : 1;
                        velocity = dir * 16 * intensity;
                        startLoop();
                    } else {
                        stopLoop();
                    }
                });

                container.addEventListener('mouseleave', () => {
                    stopLoop();
                    isMouseDown = false;
                });

                container.addEventListener('mousedown', (e) => {
                    isMouseDown = true;
                    hasDragged = false;
                    startX = e.pageX;
                    scrollLeftStart = container.scrollLeft;
                });

                window.addEventListener('mouseup', () => {
                    isMouseDown = false;
                });

                container.addEventListener('click', (e) => {
                    if (hasDragged) {
                        e.preventDefault();
                        e.stopPropagation();
                        hasDragged = false;
                    }
                }, true);

                container.addEventListener('wheel', (e) => {
                    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                        container.scrollLeft += e.deltaY;
                        checkBoundaries();
                        e.preventDefault();
                    }
                }, { passive: false });

                container.addEventListener('scroll', checkBoundaries, { passive: true });
                window.addEventListener('resize', checkBoundaries);

                // Auto-reveal child if partially cut off on hover/focus
                container.querySelectorAll('button, a, .scroll-item').forEach((child) => {
                    const reveal = () => {
                        const cRect = container.getBoundingClientRect();
                        const elRect = child.getBoundingClientRect();
                        if (elRect.left < cRect.left || elRect.right > cRect.right) {
                            child.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                            setTimeout(checkBoundaries, 250);
                        }
                    };
                    child.addEventListener('mouseenter', reveal);
                    child.addEventListener('focus', reveal);
                });

                checkBoundaries();
                setTimeout(checkBoundaries, 200);
            }

            document.querySelectorAll('[data-mouse-scroll], .mouse-scroll-container').forEach(setupMouseScroll);
        });
    </script>

    @stack('scripts')
</body>
</html>
