<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
                            900: '#09090b',
                            850: '#0f0f13',
                            800: '#121215',
                            700: '#18181b',
                            600: '#27272a',
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Cairo', 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0b0c10;
            color: #f3f4f6;
        }
        .glass {
            background: rgba(18, 18, 24, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .card-surface {
            background: rgba(22, 22, 30, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }
        .card-surface:hover {
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 30px -10px rgba(79, 70, 229, 0.2);
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
<body class="min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-white bg-dark-900 text-zinc-100">

    <!-- Top Announcement / Escrow Banner -->
    <div class="bg-gradient-to-r from-brand-900/60 via-brand-800/40 to-dark-800 border-b border-brand-500/20 py-1.5 px-4 text-center text-xs text-brand-200">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1 font-semibold text-brand-400">
                <i class="ri-shield-check-fill text-sm text-emerald-400"></i>
                {{ app()->getLocale() === 'ar' ? 'حماية وضمان المشترين:' : '100% Escrow Buyer Protection:' }}
            </span>
            <span>
                {{ app()->getLocale() === 'ar' ? 'أموالك محفوظة في أمان تام ولا تُحول للبائع إلا بعد استلام الخدمة وموافقتك.' : 'Funds are held securely in escrow and only released upon your final approval.' }}
            </span>
        </div>
    </div>

    <!-- Marketplace Header -->
    <header class="sticky top-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between gap-4 py-3">
            
            <!-- Left: Brand Logo & Title -->
            <div class="flex items-center gap-4 sm:gap-6 flex-shrink-0">
                <a href="{{ route('marketplace.services.index') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 via-indigo-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-all">
                        <i class="ri-store-2-fill text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-black tracking-tight text-white">
                                musoftware
                            </span>
                            <span class="inline-flex items-center rounded-full bg-brand-500/20 border border-brand-500/30 px-2 py-0.5 text-[10px] font-bold text-brand-300 uppercase">
                                Marketplace
                            </span>
                        </div>
                        <span class="text-[10px] font-medium text-zinc-400 hidden sm:block">
                            {{ app()->getLocale() === 'ar' ? 'سوق الخدمات البرمجية والحلول الرقمية' : 'Software Services & Digital Store' }}
                        </span>
                    </div>
                </a>
            </div>

            <!-- Middle: Search Bar (Desktop) -->
            <div class="hidden md:flex flex-1 max-w-xl mx-4">
                <form action="{{ route('marketplace.services.index') }}" method="GET" class="w-full relative flex items-center">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    <div class="relative w-full">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') ?? request('q') }}" 
                            placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن خدمة، برمجة، ذكاء اصطناعي، تصميم...' : 'Search services, scripts, AI bots, web apps...' }}"
                            class="w-full h-11 ps-4 pe-24 rounded-xl bg-zinc-900/90 border border-zinc-700/80 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all shadow-inner"
                        >
                        <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-1.5' : 'right-1.5' }} top-1.5 bottom-1.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold flex items-center gap-1 transition-all shadow-sm">
                            <i class="ri-search-2-line"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Actions & User Navigation -->
            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- Language Switcher -->
                @php
                    $isArabic = app()->getLocale() === 'ar';
                    $switchUrl = $isArabic ? (Str::contains(url()->full(), '?') ? url()->full().'&lang=en' : url()->full().'?lang=en') : (Str::contains(url()->full(), '?') ? url()->full().'&lang=ar' : url()->full().'?lang=ar');
                @endphp
                <a href="{{ $switchUrl }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-zinc-300 hover:text-white bg-zinc-800/80 hover:bg-zinc-700/80 border border-zinc-700/60 transition-colors flex items-center gap-1.5" title="Switch Language">
                    <i class="ri-global-line text-sm text-brand-400"></i>
                    <span>{{ $isArabic ? 'English' : 'العربية' }}</span>
                </a>

                <!-- Add / Publish Service Button -->
                <a href="{{ route('marketplace.services.create') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-500 text-white shadow-md shadow-brand-600/20 transition-all">
                    <i class="ri-add-line text-sm"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'أضف خدمتك' : 'Sell a Service' }}</span>
                </a>

                <!-- Auth Navigation -->
                @auth
                    <div class="relative group">
                        <button type="button" class="flex items-center gap-2 p-1.5 rounded-xl bg-zinc-800/80 hover:bg-zinc-700/80 border border-zinc-700/60 transition-all">
                            <div class="w-8 h-8 rounded-lg bg-brand-600/30 border border-brand-500/40 text-brand-300 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <span class="text-xs font-medium text-zinc-200 hidden lg:inline max-w-[100px] truncate">
                                {{ auth()->user()->name }}
                            </span>
                            <i class="ri-arrow-down-s-line text-zinc-400 text-sm"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-full mt-2 w-56 rounded-2xl bg-zinc-900 border border-zinc-800 shadow-2xl py-2 hidden group-hover:block z-50">
                            <div class="px-4 py-2 border-b border-zinc-800">
                                <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                                @if(isset(auth()->user()->user_balance))
                                    <div class="mt-1 flex items-center justify-between text-xs">
                                        <span class="text-zinc-400">{{ app()->getLocale() === 'ar' ? 'الرصيد:' : 'Balance:' }}</span>
                                        <span class="font-bold text-emerald-400">${{ number_format(auth()->user()->user_balance, 2) }}</span>
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('marketplace.orders.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                                <i class="ri-shopping-bag-3-line text-brand-400"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'طلباتي ومشترياتي' : 'My Orders & Purchases' }}</span>
                            </a>
                            <a href="{{ route('marketplace.favorites.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                                <i class="ri-heart-line text-rose-400"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'المفضلة' : 'Saved Favorites' }}</span>
                            </a>
                            <a href="{{ route('marketplace.home') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                                <i class="ri-dashboard-3-line text-indigo-400"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'لوحة تحكم السوق' : 'Marketplace Dashboard' }}</span>
                            </a>
                            <a href="{{ url('/dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                                <i class="ri-apps-2-line text-amber-400"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'لوحة التحكم الرئيسية' : 'Main Workspace' }}</span>
                            </a>

                            <div class="border-t border-zinc-800 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs text-rose-400 hover:bg-rose-950/30 transition-colors text-start">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'تسجيل الخروج' : 'Log Out' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl text-xs font-medium text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Sign In' }}
                    </a>
                    <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-zinc-800 hover:bg-zinc-700 text-white border border-zinc-700/80 transition-all">
                        {{ app()->getLocale() === 'ar' ? 'حساب جديد' : 'Join' }}
                    </a>
                @endauth
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div class="md:hidden px-4 pb-3">
            <form action="{{ route('marketplace.services.index') }}" method="GET" class="relative flex items-center">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') ?? request('q') }}" 
                    placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث في الخدمات البرمجية...' : 'Search software services...' }}"
                    class="w-full h-10 ps-3 pe-20 rounded-xl bg-zinc-900 border border-zinc-700 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-brand-500"
                >
                <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-1' : 'right-1' }} top-1 bottom-1 px-3 rounded-lg bg-brand-600 text-white text-xs font-medium">
                    {{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}
                </button>
            </form>
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
                        <a href="https://facebook.com/musoftwares" target="_blank" rel="noopener" class="hover:text-brand-400"><i class="ri-facebook-circle-fill"></i></a>
                        <a href="https://twitter.com/musoftwares" target="_blank" rel="noopener" class="hover:text-brand-400"><i class="ri-twitter-x-fill"></i></a>
                        <a href="https://linkedin.com/company/musoftwares" target="_blank" rel="noopener" class="hover:text-brand-400"><i class="ri-linkedin-box-fill"></i></a>
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

    @stack('scripts')
</body>
</html>
