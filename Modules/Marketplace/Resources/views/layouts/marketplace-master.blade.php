<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Theme Initialization Script (Prevents FOUC) -->
    <script>
        (function() {
            try {
                const storedTheme = localStorage.getItem('musoftware_theme') || localStorage.getItem('theme');
                if (storedTheme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else if (storedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- SEO Meta Tags -->
    <title>{{ $meta['title'] ?? __('marketplace.meta_title') ?? 'Software & Digital Services Marketplace | MuSoftwares' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? __('marketplace.meta_description') ?? 'Discover, buy, and hire top software developers, custom tools, scripts, AI bots, and digital services with secure escrow protection.' }}">
    <meta name="robots" content="{{ $meta['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">
    
    <link rel="canonical" href="{{ $meta['canonical_url'] ?? $meta['url'] ?? url()->current() }}">
    
    @php
        $currentUrl = url()->current();
        $enUrl = $meta['en_url'] ?? (Str::contains($currentUrl, '?') ? $currentUrl.'&lang=en' : $currentUrl.'?lang=en');
        $arUrl = $meta['ar_url'] ?? (Str::contains($currentUrl, '?') ? $currentUrl.'&lang=ar' : $currentUrl.'?lang=ar');
    @endphp
    <link rel="alternate" hreflang="en" href="{{ $enUrl }}">
    <link rel="alternate" hreflang="ar" href="{{ $arUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $meta['canonical_url'] ?? $currentUrl }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:site_name" content="MuSoftwares Marketplace">
    <meta property="og:url" content="{{ $meta['url'] ?? $currentUrl }}">
    <meta property="og:title" content="{{ $meta['title'] ?? 'Software & Digital Services Marketplace | MuSoftwares' }}">
    <meta property="og:description" content="{{ $meta['description'] ?? 'Discover, buy, and hire top software developers and digital services with secure escrow protection.' }}">
    <meta property="og:image" content="{{ $meta['image'] ?? url('/v8main/img/logo.png') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $meta['url'] ?? $currentUrl }}">
    <meta name="twitter:title" content="{{ $meta['title'] ?? 'Software & Digital Services Marketplace | MuSoftwares' }}">
    <meta name="twitter:description" content="{{ $meta['description'] ?? 'Discover, buy, and hire top software developers and digital services with secure escrow protection.' }}">
    <meta name="twitter:image" content="{{ $meta['image'] ?? url('/v8main/img/logo.png') }}">

    <link rel="shortcut icon" type="image/svg+xml" href="/favicon.svg" />

    <!-- Google Fonts: Plus Jakarta Sans (English) + Alexandria & IBM Plex Sans Arabic (Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        },
                        dark: {
                            950: '#060608',
                            900: '#09090b',
                            850: '#0f0f13',
                            800: '#121217',
                            750: '#15151c',
                            700: '#18181f',
                            600: '#27272a',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Alexandria', 'IBM Plex Sans Arabic', 'Inter', 'system-ui', 'sans-serif'],
                        arabic: ['Alexandria', 'IBM Plex Sans Arabic', 'system-ui', 'sans-serif'],
                        display: ['Plus Jakarta Sans', 'Alexandria', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-body: #f8f9fc;
            --text-body: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(226, 232, 240, 0.9);
            --card-surface: #ffffff;
            --card-border: #e2e8f0;
            --card-sub-bg: #f8fafc;
            --pill-active-bg: #0f172a;
            --pill-active-text: #ffffff;
            --pill-inactive-bg: #ffffff;
            --pill-inactive-border: #e2e8f0;
            --pill-inactive-text: #334155;
            --accent-brand: #6366f1;
        }
        html.dark {
            --bg-body: #09090b;
            --text-body: #f3f4f6;
            --glass-bg: rgba(15, 15, 19, 0.88);
            --glass-border: rgba(255, 255, 255, 0.08);
            --card-surface: #111116;
            --card-border: rgba(255, 255, 255, 0.08);
            --card-sub-bg: #16161d;
            --pill-active-bg: #4f46e5;
            --pill-active-text: #ffffff;
            --pill-inactive-bg: #15151c;
            --pill-inactive-border: rgba(255, 255, 255, 0.1);
            --pill-inactive-text: #d4d4d8;
            --accent-brand: #6366f1;
        }
        body {
            font-family: 'Cairo', 'Outfit', 'Inter', system-ui, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            transition: background-color 0.25s ease, color 0.25s ease;
        }
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
        }
        .explore-card {
            background: var(--card-surface);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .explore-card:hover {
            border-color: rgba(99, 102, 241, 0.45);
            box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(99, 102, 241, 0.15);
            transform: translateY(-3px);
        }
        html.dark .explore-card:hover {
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(99, 102, 241, 0.3);
        }
        .filter-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
        }
        .filter-pill.active {
            background-color: var(--pill-active-bg);
            color: var(--pill-active-text);
            border: 1px solid var(--pill-active-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .filter-pill.inactive {
            background-color: var(--pill-inactive-bg);
            color: var(--pill-inactive-text);
            border: 1px solid var(--pill-inactive-border);
        }
        .filter-pill.inactive:hover {
            border-color: #6366f1;
            color: #6366f1;
        }
        /* Custom Dual Range Slider Styling */
        .range-slider-wrapper {
            position: relative;
            height: 24px;
            display: flex;
            align-items: center;
        }
        .range-slider-track {
            position: absolute;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 9999px;
            z-index: 1;
        }
        html.dark .range-slider-track {
            background-color: #27272a;
        }
        .range-slider-progress {
            position: absolute;
            height: 4px;
            background-color: #0f172a;
            border-radius: 9999px;
            z-index: 2;
        }
        html.dark .range-slider-progress {
            background-color: #ffffff;
        }
        .range-slider-input {
            position: absolute;
            width: 100%;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
            background: transparent !important;
            height: 4px;
            z-index: 3;
            outline: none;
            margin: 0;
        }
        .range-slider-input::-webkit-slider-thumb {
            pointer-events: auto;
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #0f172a;
            border: 2.5px solid #ffffff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
            cursor: pointer;
            transition: transform 0.15s ease;
        }
        html.dark .range-slider-input::-webkit-slider-thumb {
            background: #ffffff;
            border: 2.5px solid #09090b;
        }
        .range-slider-input::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- Structured Data (JSON-LD) for Search Engines -->
    @if(!empty($meta['schema_json']))
        <script type="application/ld+json">
            {!! is_string($meta['schema_json']) ? $meta['schema_json'] : json_encode($meta['schema_json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
    @stack('schema')
    @stack('head')
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-white bg-[#f8f9fa] dark:bg-dark-900 text-slate-900 dark:text-zinc-100">

    <!-- Minimalist Marketplace Header -->
    <header class="sticky top-0 z-50 glass transition-colors border-b border-slate-200/60 dark:border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- Left: Brand Logo & Title -->
            <div class="flex items-center gap-4 flex-shrink-0">
                <a href="{{ route('marketplace.services.index') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-950 flex items-center justify-center font-black text-lg shadow-sm transition-transform group-hover:scale-105">
                        <i class="ri-store-2-line"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black tracking-tight text-slate-900 dark:text-white font-sans">
                            musoftware
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-dark-800 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-zinc-300">
                            Marketplace
                        </span>
                    </div>
                </a>
            </div>

            <!-- Right: Actions & User Navigation -->
            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- Dark / Light Mode Toggle Button -->
                <button 
                    type="button" 
                    id="theme-toggle-btn"
                    onclick="toggleDarkMode()" 
                    class="w-9 h-9 rounded-full flex items-center justify-center text-slate-600 dark:text-zinc-300 hover:bg-slate-200/70 dark:hover:bg-dark-800 transition-colors"
                    title="تبديل المظهر (داكن / فاتح)"
                    aria-label="Toggle dark mode"
                >
                    <i id="theme-toggle-icon" class="ri-moon-line text-base dark:hidden"></i>
                    <i id="theme-toggle-icon-dark" class="ri-sun-line text-base hidden dark:inline-block text-amber-400"></i>
                </button>

                <!-- Language Switcher -->
                @php
                    $isArabic = app()->getLocale() === 'ar';
                    $switchUrl = $isArabic ? (Str::contains(url()->full(), '?') ? url()->full().'&lang=en' : url()->full().'?lang=en') : (Str::contains(url()->full(), '?') ? url()->full().'&lang=ar' : url()->full().'?lang=ar');
                @endphp
                <a href="{{ $switchUrl }}" class="px-3 py-1.5 rounded-full text-xs font-bold text-slate-700 dark:text-zinc-300 hover:bg-slate-200/70 dark:hover:bg-dark-800 transition-colors" title="Switch Language">
                    <span>{{ $isArabic ? 'English' : 'العربية' }}</span>
                </a>

                <!-- Add / Publish Service Button -->
                <a href="{{ route('marketplace.services.create') }}" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 transition-all shadow-sm">
                    <i class="ri-add-line text-sm font-bold"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'أضف خدمتك' : 'Sell a Service' }}</span>
                </a>

                <!-- Auth Navigation -->
                @auth
                    <div class="relative group">
                        <button type="button" class="flex items-center gap-2 p-1 rounded-full hover:bg-slate-200/60 dark:hover:bg-dark-800 transition-all">
                            <div class="w-8 h-8 rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <i class="ri-arrow-down-s-line text-slate-500 dark:text-zinc-400 text-sm pe-1"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-full mt-2 w-56 rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-white/10 shadow-xl py-2 hidden group-hover:block z-50">
                            <div class="px-4 py-2 border-b border-slate-100 dark:border-white/5">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('marketplace.orders.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-dark-800 transition-colors">
                                <i class="ri-shopping-bag-3-line text-brand-500"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'طلباتي ومشترياتي' : 'My Orders' }}</span>
                            </a>
                            <a href="{{ route('marketplace.favorites.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-dark-800 transition-colors">
                                <i class="ri-bookmark-line text-rose-500"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'المفضلة' : 'Saved' }}</span>
                            </a>
                            <a href="{{ route('marketplace.home') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-dark-800 transition-colors">
                                <i class="ri-dashboard-3-line text-indigo-500"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}</span>
                            </a>
                            <div class="border-t border-slate-100 dark:border-white/5 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors text-start">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'تسجيل الخروج' : 'Log Out' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Sign In' }}
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-1.5 rounded-full text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 shadow-sm transition-all">
                        {{ app()->getLocale() === 'ar' ? 'حساب جديد' : 'Join' }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-2xl bg-emerald-950/70 border border-emerald-800/80 text-emerald-300 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2.5">
                    <i class="ri-checkbox-circle-fill text-emerald-400 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-2xl bg-rose-950/70 border border-rose-800/80 text-rose-300 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2.5">
                    <i class="ri-error-warning-fill text-rose-400 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('error') ?? $errors->first() }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Trust & Security Highlights Section -->
    <section class="border-t border-zinc-800/80 bg-zinc-950/80 py-12 px-4 sm:px-6 lg:px-8 mt-16">
        <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400 text-2xl flex-shrink-0">
                    <i class="ri-shield-check-line"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-white mb-1">
                        {{ app()->getLocale() === 'ar' ? 'حماية الدفع بالضمان (Escrow)' : 'Secure Escrow Payment' }}
                    </h4>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'أموالك محمية حتى تفحص وتسلم مشروعك بالكامل وتوافق عليه.' : 'Funds are held in escrow and released only after your satisfaction.' }}
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-2xl flex-shrink-0">
                    <i class="ri-verified-badge-line"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-white mb-1">
                        {{ app()->getLocale() === 'ar' ? 'مطورون وخبراء معتمدون' : 'Verified Software Experts' }}
                    </h4>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'خدمات برمجية متميزة وفحص دقيق للكفاءة وجودة الأكواد.' : 'Vetted freelancers with verified skills and customer ratings.' }}
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-2xl flex-shrink-0">
                    <i class="ri-customer-service-2-line"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-white mb-1">
                        {{ app()->getLocale() === 'ar' ? 'دعم فني وتواصل مباشر' : 'Direct Workspace Support' }}
                    </h4>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'محادثات مباشرة ومتابعة تسليمات كل مرحلة داخل النظام.' : 'Live project chats, deliverables review, and revision management.' }}
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-2xl flex-shrink-0">
                    <i class="ri-refund-2-line"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-white mb-1">
                        {{ app()->getLocale() === 'ar' ? 'ضمان استرداد الأموال 100%' : '100% Money-Back Guarantee' }}
                    </h4>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'استرجاع فوري للأموال في حال عدم تسليم الخدمة في الموعد المحدد.' : 'Full refund if the seller fails to deliver within agreed deadlines.' }}
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-zinc-800 bg-dark-900 py-12 text-zinc-400 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                
                <!-- Col 1: About -->
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold text-sm">
                            M
                        </div>
                        <span class="text-lg font-black text-white">MuSoftwares</span>
                    </div>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'المنصة المتكاملة للبرمجيات، الخدمات السحابية، والحلول الرقمية للشركات ورواد الأعمال.' : 'Leading digital marketplace for verified software services, automation bots, and cloud solutions.' }}
                    </p>
                </div>

                <!-- Col 2: Categories -->
                <div>
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                        {{ app()->getLocale() === 'ar' ? 'أقسام السوق' : 'Categories' }}
                    </h5>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'web-development']) }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'تطوير المواقع والويب' : 'Web Development' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'graphic-design']) }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'التصميم وتجربة المستخدم' : 'Graphic & UI/UX Design' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'programming-tech']) }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'البرمجة والتطبيقات' : 'Programming & Tech' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'digital-marketing']) }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'التسويق الرقمي و SEO' : 'Digital Marketing & SEO' }}</a></li>
                    </ul>
                </div>

                <!-- Col 3: Ecosystem -->
                <div>
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                        {{ app()->getLocale() === 'ar' ? 'المنظومة' : 'Ecosystem' }}
                    </h5>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('marketplace.services.index') }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'سوق الخدمات' : 'Marketplace Services' }}</a></li>
                        <li><a href="{{ route('library.index') }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'المكتبة الرقمية' : 'Digital Library & E-Books' }}</a></li>
                        <li><a href="{{ url('/dashboard') }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'بوابة الأعمال' : 'Business Portal' }}</a></li>
                        <li><a href="{{ route('marketplace.services.create') }}" class="hover:text-brand-400 transition-colors">{{ app()->getLocale() === 'ar' ? 'انضم كبائع / فريلانسر' : 'Become a Seller' }}</a></li>
                    </ul>
                </div>

                <!-- Col 4: Trust & Contact -->
                <div>
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                        {{ app()->getLocale() === 'ar' ? 'الأمان والحماية' : 'Security & Trust' }}
                    </h5>
                    <p class="text-xs text-zinc-400 mb-3">
                        {{ app()->getLocale() === 'ar' ? 'نظام دفع آمن 100% مع ضمان فحص الكود وجودة التنفيذ.' : 'All transactions are strictly protected through cryptographic escrow verification.' }}
                    </p>
                    <div class="flex items-center gap-3 text-lg text-zinc-400">
                        <a href="https://facebook.com/musoftwares.com.page" target="_blank" rel="noopener" class="hover:text-brand-400"><i class="ri-facebook-circle-fill"></i></a>
                        <a href="https://github.com/musoftwares" target="_blank" rel="noopener" class="hover:text-brand-400"><i class="ri-github-fill"></i></a>
                    </div>
                </div>

            </div>

            <div class="border-t border-zinc-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-500">
                <p>© {{ date('Y') }} MuSoftwares. {{ app()->getLocale() === 'ar' ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}</p>
                <div class="flex items-center gap-6">
                    <a href="{{ route('marketplace.services.index') }}" class="hover:text-zinc-300">Services</a>
                    <a href="{{ url('/') }}" class="hover:text-zinc-300">Home</a>
                    <a href="{{ route('library.index') }}" class="hover:text-zinc-300">Library</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('musoftware_theme', isDark ? 'dark' : 'light');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>

    @stack('scripts')
</body>
</html>
