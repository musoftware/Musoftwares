<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $meta['title'] ?? 'المكتبة الرقمية | Musoftware' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? 'تصفح وحمّل أفضل الكتب الرقمية والأدلة التطبيقية في التكنولوجيا والأعمال.' }}">
    <link rel="canonical" href="{{ $meta['url'] ?? url()->current() }}">

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

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $meta['url'] ?? url()->current() }}">
    <meta property="og:title" content="{{ $meta['title'] ?? 'المكتبة الرقمية | Musoftware' }}">
    <meta property="og:description" content="{{ $meta['description'] ?? 'تصفح وحمّل أفضل الكتب الرقمية والأدلة التطبيقية.' }}">
    @if(!empty($meta['image']))
        <meta property="og:image" content="{{ $meta['image'] }}">
    @endif

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ $meta['url'] ?? url()->current() }}">
    <meta property="twitter:title" content="{{ $meta['title'] ?? 'المكتبة الرقمية | Musoftware' }}">
    <meta property="twitter:description" content="{{ $meta['description'] ?? 'تصفح وحمّل أفضل الكتب الرقمية والأدلة التطبيقية.' }}">
    @if(!empty($meta['image']))
        <meta property="twitter:image" content="{{ $meta['image'] }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- Tailwind CSS CDN for Blade views -->
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
                        },
                        coral: {
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                        },
                        peach: {
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                        },
                        tealish: {
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                        },
                        dark: {
                            900: '#09090b',
                            850: '#0f0f12',
                            800: '#121215',
                            700: '#18181b',
                            600: '#27272a',
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Outfit', 'Inter', 'sans-serif'],
                        display: ['Outfit', 'Cairo', 'Playfair Display', 'serif'],
                        serif: ['Playfair Display', 'Cairo', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-body: #faeee4;
            --text-body: #2d1810;
            --canvas-bg: #ffffff;
            --canvas-border: #f0dfd5;
            --card-sub-bg: #fbf7f4;
            --blob-coral: #ff7a59;
            --blob-teal: #00dfc0;
            --blob-amber: #fec84b;
            --blob-yellow: #fde047;
            --primary-orange: #ff7a59;
            --primary-orange-hover: #f06443;
        }
        html.dark {
            --bg-body: #09090c;
            --text-body: #f4f4f6;
            --canvas-bg: #121217;
            --canvas-border: rgba(255, 255, 255, 0.08);
            --card-sub-bg: #181820;
            --blob-coral: rgba(255, 122, 89, 0.35);
            --blob-teal: rgba(0, 223, 192, 0.3);
            --blob-amber: rgba(254, 200, 75, 0.25);
            --blob-yellow: rgba(253, 224, 71, 0.2);
            --primary-orange: #ff7a59;
            --primary-orange-hover: #f06443;
        }
        body {
            font-family: 'Cairo', 'Outfit', 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            transition: background-color 0.25s ease, color 0.25s ease;
            position: relative;
            overflow-x: hidden;
        }
        .font-editorial {
            font-family: 'Playfair Display', 'Outfit', 'Cairo', serif;
        }
        .bookhouse-canvas {
            background-color: var(--canvas-bg);
            border: 1px solid var(--canvas-border);
            box-shadow: 0 25px 60px -15px rgba(45, 24, 16, 0.08), 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            border-radius: 36px;
        }
        html.dark .bookhouse-canvas {
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.06);
        }
        .book-shadow-3d {
            box-shadow: 
                -15px 20px 35px -8px rgba(35, 15, 10, 0.32),
                0 4px 12px -2px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(0, 0, 0, 0.04);
            transform: perspective(1000px) rotateY(-3deg);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .book-shadow-3d:hover {
            transform: perspective(1000px) rotateY(0deg) translateY(-8px) scale(1.02);
            box-shadow: 
                0 30px 50px -12px rgba(35, 15, 10, 0.42),
                0 12px 24px -6px rgba(255, 122, 89, 0.25);
        }
        html.dark .book-shadow-3d {
            box-shadow: 
                -15px 22px 40px -8px rgba(0, 0, 0, 0.85),
                0 4px 18px -2px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        html.dark .book-shadow-3d:hover {
            box-shadow: 
                0 35px 55px -10px rgba(0, 0, 0, 0.95),
                0 0 35px -5px rgba(255, 122, 89, 0.35);
        }
        .pill-btn-coral {
            background-color: var(--primary-orange);
            color: #ffffff;
            border-radius: 9999px;
            padding: 0.65rem 1.6rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 8px 20px -4px rgba(255, 122, 89, 0.4);
        }
        .pill-btn-coral:hover {
            background-color: var(--primary-orange-hover);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 12px 25px -4px rgba(255, 122, 89, 0.55);
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    @stack('head')
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#ff7a59] selection:text-white">

    <!-- Ambient Corner Background Blobs (BookHouse Canvas DNA) -->
    <div class="fixed -top-24 -left-24 w-96 sm:w-[32rem] h-96 sm:h-[32rem] bg-[#fed7aa] dark:bg-[#fed7aa]/10 rounded-full blur-3xl pointer-events-none -z-10 opacity-70"></div>
    <div class="fixed -bottom-24 -right-24 w-96 sm:w-[36rem] h-96 sm:h-[36rem] bg-[#00dfc0] dark:bg-[#00dfc0]/10 rounded-full blur-3xl pointer-events-none -z-10 opacity-60"></div>
    <div class="fixed top-1/3 -right-20 w-80 h-80 bg-[#ff7a59]/15 dark:bg-[#ff7a59]/5 rounded-full blur-3xl pointer-events-none -z-10 opacity-50"></div>

    <!-- Header / Navbar (BookHouse DNA for Inner Pages) -->
    @if(!request()->routeIs('library.index'))
    <header class="sticky top-0 z-50 glass border-b transition-colors mb-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between py-3">
            
            <!-- Left Side: Menu Trigger & Quick Navigation -->
            <div class="flex items-center gap-4 sm:gap-6">
                <!-- Hamburger Pill / Icon Box -->
                <a href="{{ route('library.index') }}" class="w-10 h-10 rounded-xl bg-[#ff7a59] hover:bg-[#f06443] text-white flex items-center justify-center shadow-md shadow-[#ff7a59]/25 transition-transform hover:scale-105" title="{{ app()->getLocale() === 'ar' ? 'القائمة الرئيسية' : 'Menu' }}">
                    <i class="ri-menu-2-line text-lg font-bold"></i>
                </a>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center gap-5 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-zinc-300">
                    <a href="{{ route('library.index') }}" class="hover:text-[#ff7a59] transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'المجموعات' : 'Collection' }}
                    </a>
                    <a href="{{ route('library.index', ['sort' => 'popular']) }}" class="hover:text-[#ff7a59] transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'الأكثر مبيعاً' : 'Top Selling' }}
                    </a>
                    <a href="{{ route('library.index', ['type' => 'free']) }}" class="hover:text-[#00dfc0] transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'كتب مجانية' : 'Free Books' }}
                    </a>
                </nav>
            </div>

            <!-- Center Brand Logo: Musoftware -->
            <div class="flex items-center justify-center">
                <a href="{{ route('library.index') }}" class="flex items-center gap-1.5 group">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-[#ff7a59] group-hover:scale-105 transition-transform font-editorial">
                        Mu<span class="text-[#2e1f1d] dark:text-white">software</span>
                    </span>
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#ff7a59]/10 text-[#ff7a59] border border-[#ff7a59]/20 uppercase tracking-widest hidden sm:inline-block">
                        Digital
                    </span>
                </a>
            </div>

            <!-- Right Actions: User, Cart/Library, Theme Toggle -->
            <div class="flex items-center gap-2.5 sm:gap-3.5">
                
                <!-- Dark / Light Mode Toggle Button -->
                <button 
                    type="button" 
                    id="theme-toggle-btn"
                    onclick="toggleDarkMode()" 
                    class="w-9 h-9 rounded-full flex items-center justify-center text-slate-600 dark:text-zinc-300 bg-white/90 dark:bg-zinc-800/90 border border-slate-200 dark:border-zinc-700/70 shadow-2xs hover:bg-slate-100 dark:hover:bg-zinc-700 transition-all"
                    title="تبديل المظهر"
                    aria-label="Toggle dark mode"
                >
                    <i id="theme-toggle-icon" class="ri-moon-line text-base dark:hidden"></i>
                    <i id="theme-toggle-icon-dark" class="ri-sun-line text-base hidden dark:inline-block text-amber-400"></i>
                </button>

                @auth
                    <!-- My Library Pill Button -->
                    <a href="{{ route('library.my_library') }}" class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700 shadow-2xs hover:border-[#ff7a59] transition-all">
                        <i class="ri-book-mark-line text-[#ff7a59]"></i>
                        <span class="hidden sm:inline">{{ app()->getLocale() === 'ar' ? 'مكتبتي' : 'My Library' }}</span>
                    </a>

                    <!-- Profile Avatar -->
                    <div class="w-9 h-9 rounded-full bg-[#ff7a59]/10 border border-[#ff7a59]/30 text-[#ff7a59] flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @else
                    <!-- Create Account / Login Pill Button -->
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-full text-xs font-bold text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 transition-all">
                        {{ app()->getLocale() === 'ar' ? 'دخول' : 'Sign In' }}
                    </a>
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex px-4 py-2 rounded-full text-xs font-bold bg-[#ff7a59] hover:bg-[#f06443] text-white shadow-md shadow-[#ff7a59]/20 transition-all">
                        {{ app()->getLocale() === 'ar' ? 'حساب جديد' : 'Create Account' }}
                    </a>
                @endauth
            </div>
        </div>
    </header>
    @endif

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i class="ri-checkbox-circle-fill text-emerald-600 dark:text-emerald-400 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i class="ri-error-warning-fill text-rose-600 dark:text-rose-400 text-lg"></i>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-zinc-800/80 bg-white dark:bg-dark-900 py-12 mt-16 text-slate-500 dark:text-zinc-400 text-sm transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold">
                    M
                </div>
                <p>© {{ date('Y') }} Musoftware. جميع الحقوق محفوظة.</p>
            </div>
            <div class="flex items-center gap-6 text-xs text-slate-500 dark:text-zinc-400">
                <a href="{{ route('library.index') }}" class="hover:text-brand-600 dark:hover:text-zinc-200 transition-colors">المكتبة الرقمية</a>
                <a href="{{ url('/') }}" class="hover:text-brand-600 dark:hover:text-zinc-200 transition-colors">الرئيسية</a>
                <a href="{{ route('library.index', ['type' => 'free']) }}" class="hover:text-brand-600 dark:hover:text-zinc-200 transition-colors">كتب مجانية</a>
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
