<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111111">

    <title>{{ $title ?? 'Musoftwares | Boutique Software Engineering Studio' }}</title>
    <meta name="description" content="{{ $description ?? 'Enterprise ERP systems, custom business software, WhatsApp Cloud API, and high-performance web platforms engineered in Suez, Egypt.' }}">

    @php
        $currentUrl = url()->current();
        $canonicalUrl = $canonical ?? $currentUrl;
        $ogImage = $image ?? asset('images/default-meta.png');
    @endphp

    <link rel="canonical" href="{{ $canonicalUrl }}">
    
    <!-- Open Graph & Social Cards -->
    <meta property="og:site_name" content="Musoftwares">
    <meta property="og:title" content="{{ $title ?? 'Musoftwares | Software Engineering' }}">
    <meta property="og:description" content="{{ $description ?? 'Enterprise ERP and Cloud Solutions' }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@MusoftwareUno">
    <meta name="twitter:title" content="{{ $title ?? 'Musoftwares' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Enterprise ERP and Cloud Solutions' }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- Google Fonts: Inter & Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Musoftwares",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web, Windows, Cloud",
      "url": "https://musoftwares.com",
      "author": {
        "@type": "Organization",
        "name": "Musoftwares Inc.",
        "url": "https://musoftwares.com",
        "address": {
          "@type": "PostalAddress",
          "addressLocality": "Suez",
          "addressCountry": "EG"
        }
      }
    }
    </script>

    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'Inter', 'Cairo', sans-serif;
            background-color: #111111;
            color: #E5E5E5;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#748660] selection:text-white bg-[#111111] text-[#E5E5E5]">

    <!-- Minimalist Sticky Header -->
    <header class="sticky top-0 z-50 w-full border-b border-[#222222] bg-[#111111]/95 backdrop-blur-md">
        <div class="max-w-[1400px] mx-auto flex items-center justify-between px-6 sm:px-12 h-20">
            
            <!-- Brand Logo -->
            <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse group">
                <div class="w-8 h-8 bg-[#748660] text-[#0F140A] flex items-center justify-center font-mono text-sm font-black transition-transform group-hover:scale-105">
                    M
                </div>
                <div class="flex flex-col">
                    <span class="font-bold tracking-[0.2em] font-mono text-sm text-white group-hover:text-[#748660] transition-colors">
                        MUSOFTWARES
                    </span>
                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider">
                        STUDIO &bull; SUEZ, EGYPT
                    </span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center space-x-8 rtl:space-x-reverse text-xs font-mono tracking-wider uppercase text-zinc-400">
                <a href="/#services" class="hover:text-white transition-colors">{{ __('general.solutions') ?? 'Solutions' }}</a>
                <a href="/#estimator" class="hover:text-white transition-colors">{{ __('general.estimator') ?? 'Estimator' }}</a>
                <a href="/portfolio" class="hover:text-white transition-colors">{{ __('general.portfolio') ?? 'Work' }}</a>
                <a href="/about/mahmoud-amin" class="hover:text-white transition-colors">{{ __('general.leadership_bio') ?? 'Chief Architect' }}</a>
                <a href="/company/contact" class="hover:text-white transition-colors">{{ __('general.contact_us') ?? 'Contact' }}</a>
            </nav>

            <!-- Language & Action CTA -->
            <div class="flex items-center space-x-4 rtl:space-x-reverse">
                
                <!-- Language Toggle -->
                @if(app()->getLocale() === 'ar')
                    <a href="/language/en" class="px-2 py-1 text-[11px] font-mono border border-zinc-700 text-zinc-400 hover:text-white hover:border-zinc-400 transition-colors">
                        English
                    </a>
                @else
                    <a href="/language/ar" class="px-2 py-1 text-[11px] font-mono border border-zinc-700 text-zinc-400 hover:text-white hover:border-zinc-400 transition-colors">
                        عربي
                    </a>
                @endif

                @auth
                    <a href="/dashboard" class="px-5 py-2 bg-[#748660] text-[#0F140A] font-bold text-[11px] tracking-widest font-mono uppercase hover:bg-[#60704E] transition-all">
                        {{ __('general.console') ?? 'DASHBOARD' }} ➔
                    </a>
                @else
                    <a href="/login" class="hidden sm:inline-block text-[11px] font-bold font-mono tracking-widest uppercase text-zinc-400 hover:text-white transition-colors">
                        {{ __('general.sign_in') ?? 'SIGN IN' }}
                    </a>
                    <a href="/start-project" class="px-5 py-2 bg-white text-black hover:bg-zinc-200 font-bold text-[11px] tracking-widest font-mono uppercase transition-all">
                        {{ __('general.start_a_project') ?? 'START A PROJECT' }} ➔
                    </a>
                @endauth

                <!-- Mobile Menu Button -->
                <button onclick="document.getElementById('mobile-drawer').classList.toggle('hidden')" class="lg:hidden p-2 text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobile-drawer" class="hidden lg:hidden border-b border-[#222222] bg-[#111111] px-6 py-6 space-y-4">
            <div class="flex flex-col space-y-3 font-mono text-xs uppercase tracking-wider text-zinc-300">
                <a href="/#services" class="py-2 hover:text-white border-b border-[#1E1E1E]">{{ __('general.solutions') ?? 'Solutions' }}</a>
                <a href="/#estimator" class="py-2 hover:text-white border-b border-[#1E1E1E]">{{ __('general.estimator') ?? 'Estimator' }}</a>
                <a href="/portfolio" class="py-2 hover:text-white border-b border-[#1E1E1E]">{{ __('general.portfolio') ?? 'Work' }}</a>
                <a href="/about/mahmoud-amin" class="py-2 hover:text-white border-b border-[#1E1E1E]">{{ __('general.leadership_bio') ?? 'Chief Architect' }}</a>
                <a href="/company/contact" class="py-2 hover:text-white border-b border-[#1E1E1E]">{{ __('general.contact_us') ?? 'Contact' }}</a>
                <a href="/start-project" class="py-2 text-[#748660] font-bold border-b border-[#1E1E1E]">START A PROJECT ➔</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <!-- 4-Column Structured Footer -->
    <footer class="w-full bg-[#0A0A0A] border-t border-[#222222] py-20 px-6 sm:px-12 text-xs text-zinc-400">
        <div class="max-w-[1400px] mx-auto flex flex-col lg:flex-row justify-between gap-16">
            
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-10 lg:gap-16 flex-1">
                <div class="space-y-4">
                    <div class="text-white font-bold tracking-wider font-sans">{{ __('general.contact_us') ?? 'Contact Us' }}</div>
                    <ul class="space-y-2.5 font-sans text-xs">
                        <li><a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors text-[#748660] font-bold">{{ __('general.whatsapp_direct') ?? 'WhatsApp Direct' }}</a></li>
                        <li><a href="mailto:info@musoftwares.com" class="hover:text-white transition-colors">info@musoftwares.com</a></li>
                        <li><a href="/start-project" class="hover:text-white transition-colors">{{ __('general.start_project_wizard') ?? 'System Scoping Wizard ➔' }}</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <div class="text-white font-bold tracking-wider font-sans">{{ __('general.solutions') ?? 'Solutions' }}</div>
                    <ul class="space-y-2.5 font-sans text-xs">
                        <li><a href="/platforms/erp" class="hover:text-white transition-colors">Enterprise ERP</a></li>
                        <li><a href="/platforms/crm" class="hover:text-white transition-colors">WhatsApp Cloud API</a></li>
                        <li><a href="/platforms/cloud" class="hover:text-white transition-colors">Meta Graph Suite</a></li>
                        <li><a href="/start-project" class="hover:text-white transition-colors">Custom System Builder</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <div class="text-white font-bold tracking-wider font-sans">{{ __('general.press_center') ?? 'Press Center' }}</div>
                    <ul class="space-y-2.5 font-sans text-xs">
                        <li><a href="/estimator" class="hover:text-white transition-colors">{{ __('general.estimator') ?? 'Project Estimator' }}</a></li>
                        <li><a href="/portfolio" class="hover:text-white transition-colors">{{ __('general.portfolio') ?? 'Case Studies' }}</a></li>
                        <li><a href="/about/mahmoud-amin" class="hover:text-white transition-colors">Mahmoud Amin (Chief Architect)</a></li>
                        <li><a href="/compare/laravel-vs-nodejs" class="hover:text-white transition-colors">Laravel vs Node.js Benchmarks</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <div class="text-white font-bold tracking-wider font-sans">{{ __('general.legal') ?? 'Legal & Privacy' }}</div>
                    <ul class="space-y-2.5 font-sans text-xs">
                        <li><a href="/privacy-policy" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms-of-service" class="hover:text-white transition-colors">Terms & SLA</a></li>
                        <li><span class="text-zinc-500">Security Architecture</span></li>
                        <li><span class="text-zinc-500">GDPR Compliance</span></li>
                    </ul>
                </div>
            </div>

            <!-- Right Social Links -->
            <div class="flex flex-col justify-between items-start lg:items-end space-y-8">
                <div class="flex items-center space-x-6 rtl:space-x-reverse text-zinc-400 font-sans">
                    <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">LinkedIn</a>
                    <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">GitHub</a>
                    <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">X</a>
                    <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">Facebook</a>
                </div>

                <div class="flex items-center space-x-3 rtl:space-x-reverse text-zinc-500 font-mono text-[11px]">
                    <span>MUSOFTWARES STUDIO</span>
                    <div class="w-6 h-6 border-2 border-zinc-400 rotate-45 flex items-center justify-center">
                        <div class="w-2 h-2 bg-zinc-400"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-[1400px] mx-auto mt-16 pt-8 border-t border-[#1C1C1C] flex flex-col sm:flex-row items-center justify-between text-zinc-400 font-mono text-[11px]">
            <div>&copy; {{ date('Y') }} Musoftwares Inc. {{ __('general.all_rights_reserved') ?? 'All rights reserved.' }}</div>
            <div class="mt-2 sm:mt-0">Suez, Egypt &bull; {{ __('general.worldwide_delivery') ?? 'Worldwide Delivery' }}</div>
        </div>
    </footer>

</body>
</html>
