<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $meta['title'] ?? 'المكتبة الرقمية | Musoftware' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? 'تصفح وحمّل أفضل الكتب الرقمية والأدلة التطبيقية في التكنولوجيا والأعمال.' }}">
    <link rel="canonical" href="{{ $meta['url'] ?? url()->current() }}">

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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        },
                        dark: {
                            900: '#09090b',
                            800: '#121215',
                            700: '#18181b',
                            600: '#27272a',
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Cairo', 'Inter', sans-serif;
            background-color: #09090b;
            color: #f4f4f5;
        }
        .book-shadow {
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.7), 0 0 20px -5px rgba(99, 102, 241, 0.15);
        }
        .book-hover:hover {
            transform: translateY(-6px) scale(1.02);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .glass {
            background: rgba(18, 18, 21, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
    </style>

    @stack('head')
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-white">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-50 glass border-b border-zinc-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('library.index') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform">
                        <i class="ri-book-read-line text-xl"></i>
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-white flex items-center gap-1.5">
                            المكتبة الرقمية
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-brand-500/20 text-brand-400 border border-brand-500/30 font-semibold uppercase">PDF</span>
                        </span>
                        <p class="text-[11px] text-zinc-400">Musoftware Digital Library</p>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-1 text-sm font-medium text-zinc-300">
                    <a href="{{ route('library.index') }}" class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-zinc-800/60 transition-colors {{ request()->routeIs('library.index') && !request()->has('type') ? 'text-white bg-zinc-800/80 font-semibold' : '' }}">
                        <i class="ri-apps-line me-1.5"></i> كل الكتب
                    </a>
                    <a href="{{ route('library.index', ['type' => 'free']) }}" class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-zinc-800/60 transition-colors {{ request('type') === 'free' ? 'text-emerald-400 bg-emerald-950/40 border border-emerald-800/40 font-semibold' : '' }}">
                        <i class="ri-gift-line me-1.5"></i> الكتب المجانية
                    </a>
                    <a href="{{ route('library.index', ['type' => 'paid']) }}" class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-zinc-800/60 transition-colors {{ request('type') === 'paid' ? 'text-amber-400 bg-amber-950/40 border border-amber-800/40 font-semibold' : '' }}">
                        <i class="ri-vip-crown-line me-1.5"></i> الإصدارات المميزة
                    </a>
                </nav>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('library.my_library') }}" class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium bg-zinc-800/80 hover:bg-zinc-700 text-zinc-200 border border-zinc-700/60 transition-all hover:border-zinc-600 shadow-sm">
                        <i class="ri-book-mark-line text-brand-400"></i>
                        <span>مكتبتي</span>
                    </a>

                    @if(auth()->user()->hasRole('admin') || auth()->user()->is_admin ?? false)
                        <a href="{{ route('admin.digitalproducts.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium bg-brand-600/20 hover:bg-brand-600/30 text-brand-300 border border-brand-500/40 transition-all">
                            <i class="ri-dashboard-line"></i>
                            <span class="hidden sm:inline">لوحة الإدارة</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-medium text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors">
                        تسجيل الدخول
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="ri-checkbox-circle-fill text-emerald-400 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-800/60 text-rose-300 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="ri-error-warning-fill text-rose-400 text-lg"></i>
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
    <footer class="border-t border-zinc-800/80 bg-dark-900 py-12 mt-16 text-zinc-400 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold">
                    M
                </div>
                <p>© {{ date('Y') }} Musoftware. جميع الحقوق محفوظة.</p>
            </div>
            <div class="flex items-center gap-6 text-xs text-zinc-400">
                <a href="{{ route('library.index') }}" class="hover:text-zinc-200 transition-colors">المكتبة الرقمية</a>
                <a href="{{ url('/') }}" class="hover:text-zinc-200 transition-colors">الرئيسية</a>
                <a href="{{ route('library.index', ['type' => 'free']) }}" class="hover:text-zinc-200 transition-colors">كتب مجانية</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
