<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">

    <!-- SEO Meta Tags -->
    <title>{{ $meta['title'] ?? __('marketplace.meta_title') ?? 'Software & Digital Services Marketplace | MuSoftwares' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? __('marketplace.meta_description') ?? 'Discover, buy, and hire top software developers, custom tools, scripts, AI bots, and digital services with secure escrow protection.' }}">
    <meta name="robots" content="{{ $meta['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">
    
    <link rel="canonical" href="{{ $meta['canonical_url'] ?? $meta['url'] ?? url()->current() }}">
    
    @php
        $currentUrl = url()->current();
        $enUrl = $meta['en_url'] ?? (Str::contains($currentUrl, '?') ? $currentUrl.'&lang=en' : $currentUrl.'?lang=en');
        $arUrl = $meta['ar_url'] ?? (Str::contains($currentUrl, '?') ? $currentUrl.'&lang=ar' : $currentUrl.'?lang=ar');
        $locale = app()->getLocale();
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
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_AR' : 'en_US' }}">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $meta['url'] ?? $currentUrl }}">
    <meta name="twitter:title" content="{{ $meta['title'] ?? 'Software & Digital Services Marketplace | MuSoftwares' }}">
    <meta name="twitter:description" content="{{ $meta['description'] ?? 'Discover, buy, and hire top software developers and digital services with secure escrow protection.' }}">
    <meta name="twitter:image" content="{{ $meta['image'] ?? url('/v8main/img/logo.png') }}">

    <link rel="shortcut icon" type="image/svg+xml" href="/favicon.svg" />

    <!-- Fonts: Apple SF Pro Fallbacks + Cairo & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

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
                            500: '#0071e3',
                            600: '#0066cc',
                            700: '#004fc7',
                            800: '#003eb3',
                            900: '#002f99',
                        },
                        apple: {
                            bg: '#ffffff',
                            section: '#f5f5f7',
                            card: '#ffffff',
                            text: '#1d1d1f',
                            subtext: '#86868b',
                            blue: '#0071e3',
                            blueHover: '#0077ed',
                            link: '#0066cc',
                            border: '#d2d2d7',
                            borderLight: 'rgba(0, 0, 0, 0.08)',
                        }
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'SF Pro Text', 'SF Pro Display', 'Inter', 'Cairo', 'system-ui', 'sans-serif'],
                        display: ['-apple-system', 'BlinkMacSystemFont', 'SF Pro Display', 'Cairo', 'Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Menlo', 'monospace'],
                    }
                }
            }
        }
    </script>

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
            background-color: rgba(255, 255, 255, 0.82);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
        }
        .apple-pill-btn {
            border-radius: 980px;
            padding: 7px 16px;
            font-size: 13px;
            line-height: 1.4;
            font-weight: 500;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .apple-bento-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 18px;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease;
        }
        .apple-bento-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 113, 227, 0.3);
            box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.08);
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
            background-color: #e5e5ea;
            border-radius: 9999px;
            z-index: 1;
        }
        .range-slider-progress {
            position: absolute;
            height: 4px;
            background-color: #0071e3;
            border-radius: 9999px;
            z-index: 2;
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
            background: #ffffff;
            border: 2px solid #0071e3;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
            cursor: pointer;
            transition: transform 0.15s ease;
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
<body class="min-h-screen flex flex-col antialiased bg-[#ffffff] text-[#1d1d1f] selection:bg-[#0071e3] selection:text-white">

    <!-- Apple Exact Global Navigation Bar (Unified with Homepage Studio Header) -->
    <header class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-black/5 transition-all duration-200">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between px-6 sm:px-10 min-h-[56px] py-3">
            
            <!-- Left: Musoftwares Official Studio Monogram Logo & Brand -->
            <div class="flex items-center gap-8">
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5 rtl:space-x-reverse group focus:outline-none shrink-0" title="Musoftwares Studio">
                    <svg class="w-5 h-5 fill-[#1d1d1f] group-hover:text-[#000000] transition-colors" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg">
                        <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/>
                    </svg>
                    <span class="text-[19px] sm:text-[21px] font-semibold tracking-[-0.03em] text-[#1d1d1f]">
                        Musoftware
                    </span>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#f5f5f7] border border-black/5 text-[#0071e3]">
                        Marketplace
                    </span>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center space-x-6 rtl:space-x-reverse text-[13px] font-normal text-[#1d1d1f]/75">
                    <a href="{{ route('marketplace.services.index') }}" class="font-medium text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'استكشف الخدمات والأدوات' : 'Explore Store' }}</a>
                    <a href="/platforms" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'المنصات' : 'Platforms' }}</a>
                    <a href="/solutions" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'الحلول' : 'Solutions' }}</a>
                    <a href="/portfolio" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'معرض الأعمال' : 'Portfolio' }}</a>
                    <a href="/estimator" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
                    <a href="{{ route('library.index') }}" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'المكتبة الرقمية' : 'Library' }}</a>
                </nav>
            </div>

            <!-- Right: Actions & User Navigation -->
            <div class="flex items-center space-x-3 sm:space-x-4 rtl:space-x-reverse shrink-0">
                
                <!-- Language Switcher -->
                @php
                    $isArabic = $locale === 'ar';
                    $switchUrl = $isArabic ? (Str::contains(url()->full(), '?') ? url()->full().'&lang=en' : url()->full().'?lang=en') : (Str::contains(url()->full(), '?') ? url()->full().'&lang=ar' : url()->full().'?lang=ar');
                @endphp
                <a href="{{ $switchUrl }}" class="px-2.5 py-1 rounded-[980px] text-[12px] font-medium text-[#1d1d1f]/75 hover:text-[#1d1d1f] hover:bg-[#f5f5f7] transition-all" title="Switch Language">
                    <span>{{ $isArabic ? 'English' : 'العربية' }}</span>
                </a>

                <!-- Add / Publish Service Button -->
                <a href="{{ route('marketplace.services.create') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-[980px] text-[12px] font-medium border border-black/10 bg-[#f5f5f7] text-[#1d1d1f] hover:bg-[#e8e8ed] transition-all shadow-xs">
                    <i class="ri-add-line text-sm"></i>
                    <span>{{ $locale === 'ar' ? 'أضف خدمتك' : 'Sell a Service' }}</span>
                </a>

                <!-- Auth Navigation & Wallet Balance -->
                @auth
                    <!-- Wallet Balance Pill -->
                    <a href="{{ Route::has('billing.invoices.index') ? route('billing.invoices.index') : url('/dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-[980px] bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[12px] font-medium transition-all shadow-2xs" title="{{ $locale === 'ar' ? 'رصيد المحفظة المتاح' : 'Available Balance' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="font-mono font-semibold">${{ number_format(auth()->user()->available_balance(), 2) }}</span>
                    </a>

                    <div class="relative group">
                        <button type="button" class="flex items-center gap-1.5 p-1 rounded-full hover:bg-[#f5f5f7] transition-all">
                            <div class="w-7 h-7 rounded-full bg-[#1d1d1f] text-white flex items-center justify-center font-bold text-[11px]">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <i class="ri-arrow-down-s-line text-[#86868b] text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute {{ $locale === 'ar' ? 'left-0' : 'right-0' }} top-full mt-2 w-56 rounded-2xl bg-white border border-black/10 shadow-xl py-2 hidden group-hover:block z-50">
                            <div class="px-4 py-2 border-b border-black/5">
                                <p class="text-xs font-bold text-[#1d1d1f] truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-[#86868b] truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('marketplace.orders.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">
                                <i class="ri-shopping-bag-3-line text-[#0071e3]"></i>
                                <span>{{ $locale === 'ar' ? 'طلباتي ومشترياتي' : 'My Orders' }}</span>
                            </a>
                            <a href="{{ route('marketplace.favorites.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">
                                <i class="ri-bookmark-line text-rose-500"></i>
                                <span>{{ $locale === 'ar' ? 'المفضلة' : 'Saved' }}</span>
                            </a>
                            <a href="{{ route('marketplace.home') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">
                                <i class="ri-dashboard-3-line text-indigo-500"></i>
                                <span>{{ $locale === 'ar' ? 'لوحة تحكم السوق' : 'Marketplace Command' }}</span>
                            </a>
                            <a href="/dashboard" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">
                                <i class="ri-apps-2-line text-slate-700"></i>
                                <span>{{ $locale === 'ar' ? 'بوابة الأعمال الرئيسية' : 'Studio Portal' }}</span>
                            </a>
                            <div class="border-t border-black/5 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors text-start">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>{{ $locale === 'ar' ? 'تسجيل الخروج' : 'Log Out' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-[12px] font-medium text-[#1d1d1f]/75 hover:text-[#1d1d1f] transition-colors px-2">
                        {{ $locale === 'ar' ? 'دخول' : 'Sign in' }}
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white text-[12px] font-medium px-3.5 py-1.5 transition-all shadow-sm">
                        {{ $locale === 'ar' ? 'انضم للمنصة' : 'Join' }}
                    </a>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button onclick="document.getElementById('market-mobile-menu').classList.toggle('hidden')" class="lg:hidden p-2 text-[#1d1d1f]/70 hover:text-[#1d1d1f] transition-colors" aria-label="Toggle navigation">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 17h16"/></svg>
                </button>
            </div>

        </div>

        <!-- Apple Mobile Menu Dropdown -->
        <div id="market-mobile-menu" class="hidden lg:hidden bg-white/95 backdrop-blur-xl border-b border-black/[0.08] px-6 py-6 space-y-3 text-[14px] font-medium text-[#1d1d1f]">
            <a href="{{ route('marketplace.services.index') }}" class="block py-1.5 text-[#0071e3] font-semibold border-b border-black/[0.05]">{{ $locale === 'ar' ? 'استكشف الخدمات والأدوات' : 'Explore Store' }}</a>
            <a href="/platforms" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'المنصات السحابية' : 'Platforms' }}</a>
            <a href="/solutions" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'الحلول القطاعية' : 'Solutions' }}</a>
            <a href="/portfolio" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'معرض الأعمال' : 'Portfolio' }}</a>
            <a href="/estimator" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
            <a href="{{ route('library.index') }}" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'المكتبة الرقمية' : 'Library' }}</a>
            @auth
                <a href="{{ route('marketplace.orders.index') }}" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'طلباتي' : 'My Orders' }}</a>
                <a href="{{ route('marketplace.home') }}" class="block py-1.5 text-[#0071e3] font-semibold">{{ $locale === 'ar' ? 'لوحة تحكم السوق ➔' : 'Marketplace Dashboard ➔' }}</a>
            @else
                <div class="pt-2 flex items-center gap-3">
                    <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-xl border border-black/10 text-sm font-medium">{{ $locale === 'ar' ? 'دخول' : 'Sign in' }}</a>
                    <a href="{{ route('register') }}" class="flex-1 text-center py-2 rounded-xl bg-[#0071e3] text-white text-sm font-medium">{{ $locale === 'ar' ? 'انضمام' : 'Join' }}</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Apple Exact Announcement Ribbon Banner -->
    <div class="w-full bg-[#fbfbfd] border-b border-black/[0.06] py-2.5 px-4 text-center text-[12px] text-[#1d1d1f] flex items-center justify-center gap-1.5">
        <span class="flex items-center gap-1.5">
            <i class="ri-shield-check-line text-emerald-600"></i>
            <span>
                {{ $locale === 'ar' 
                    ? 'سوق الخدمات البرمجية والحلول الرقمية المعتمدة: حماية مالية بنظام الضمان (Escrow) 100% بدون مخاطرة.' 
                    : 'Verified Software Marketplace: 100% Escrow-Protected Deliverables & Instant Digital Deployments.' }}
            </span>
        </span>
    </div>

    <!-- Flash Notifications (Apple Banner Style) -->
    @if(session('success'))
        <div class="max-w-[1280px] mx-auto px-6 mt-4 w-full">
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between text-xs font-medium shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="max-w-[1280px] mx-auto px-6 mt-4 w-full">
            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between text-xs font-medium shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="ri-error-warning-fill text-rose-600 text-base"></i>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-1 w-full bg-[#ffffff]">
        @yield('content')
    </main>

    <!-- Apple Exact 5-Column Global Directory Footer -->
    <footer class="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-12 pb-12 px-6 sm:px-12 text-[#86868b] text-[12px] mt-16">
        <div class="max-w-[1024px] mx-auto space-y-6">
            
            <!-- Numbered Footnotes Disclosures -->
            <div class="space-y-2 border-b border-[#d2d2d7]/60 pb-6 text-[11px] leading-relaxed text-[#86868b]">
                <p>1. Escrow Protection: Funds for custom software projects and digital tools are locked safely until buyer confirmation and approval of final deliverables.</p>
                <p>2. Direct Source & Verification: Every service package includes transparent timeline commitments, direct revisions, and direct developer communication.</p>
                <p>3. 1-Click Instant Delivery: Digital licenses, source code repositories, and pre-packaged extensions activate immediately via platform wallet balance.</p>
            </div>

            <!-- Breadcrumb Navigation Trail -->
            <div class="flex items-center space-x-2 rtl:space-x-reverse text-[12px] text-[#86868b]">
                <a href="/" class="hover:text-[#1d1d1f]" title="Musoftwares">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg"><path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/>
                    </svg>
                </a>
                <span>›</span>
                <a href="{{ route('marketplace.services.index') }}" class="hover:text-[#1d1d1f]">
                    <span>{{ $locale === 'ar' ? 'سوق الخدمات' : 'Marketplace' }}</span>
                </a>
                @if(isset($title) || isset($meta['title']))
                    <span>›</span>
                    <span class="text-[#1d1d1f] truncate max-w-[200px]">{{ $title ?? $meta['title'] }}</span>
                @endif
            </div>

            <!-- 5-Column Apple Directory Links Grid -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 pt-4 pb-8">
                
                <!-- Col 1: Software Categories -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'أقسام الخدمات' : 'Categories' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'web-development']) }}" class="hover:text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'تطوير الويب والمتاجر' : 'Web & Platforms' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'graphic-design']) }}" class="hover:text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'تصميم واجهات UI/UX' : 'UI/UX Design' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'programming-tech']) }}" class="hover:text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'الأدوات والبوتات' : 'Scripts & Bots' }}</a></li>
                        <li><a href="{{ route('marketplace.services.index', ['category' => 'digital-marketing']) }}" class="hover:text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'أتمتة التسويق' : 'Marketing & SEO' }}</a></li>
                    </ul>
                </div>

                <!-- Col 2: Studio Architecture -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'الهندسة والمعايير' : 'Architecture' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="/estimator" class="hover:text-[#0071e3] transition-colors">Project Scope Estimator</a></li>
                        <li><a href="/portfolio" class="hover:text-[#0071e3] transition-colors">Production Case Studies</a></li>
                        <li><a href="/compare" class="hover:text-[#0071e3] transition-colors">Tech Benchmarks</a></li>
                        <li><a href="/start-project" class="hover:text-[#0071e3] transition-colors">Custom System Builder</a></li>
                    </ul>
                </div>

                <!-- Col 3: Direct Consultation -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'الاستشارات المباشرة' : 'Direct Access' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="hover:text-[#0071e3] transition-colors text-[#0066cc] font-medium">WhatsApp Direct ➔</a></li>
                        <li><a href="mailto:admin@musoftwares.com" class="hover:text-[#0071e3] transition-colors">admin@musoftwares.com</a></li>
                        <li><a href="/company/contact" class="hover:text-[#0071e3] transition-colors">Suez Engineering Lab</a></li>
                        <li><a href="{{ route('marketplace.services.create') }}" class="hover:text-[#0071e3] transition-colors">{{ $locale === 'ar' ? 'انضم كبائع معتمد' : 'Become a Seller' }}</a></li>
                    </ul>
                </div>

                <!-- Col 4: Mahmoud Amin (Founder) -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'عن المهندس' : 'Solo Founder' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="/about/mahmoud-amin" class="hover:text-[#0071e3] transition-colors">Mahmoud Amin (Profile)</a></li>
                        <li><a href="/about" class="hover:text-[#0071e3] transition-colors">Studio Philosophy</a></li>
                        <li><a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" class="hover:text-[#0071e3] transition-colors">GitHub Repository</a></li>
                        <li><a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" class="hover:text-[#0071e3] transition-colors">LinkedIn Profile</a></li>
                    </ul>
                </div>

                <!-- Col 5: Security & Escrow -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'الأمان والضمان' : 'Trust & Escrow' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="/privacy-policy" class="hover:text-[#0071e3] transition-colors">Security Architecture</a></li>
                        <li><a href="/terms-of-service" class="hover:text-[#0071e3] transition-colors">100% Escrow Guarantee</a></li>
                        <li><a href="/terms-of-service" class="hover:text-[#0071e3] transition-colors">Terms of Service</a></li>
                        <li><a href="/cookie-policy" class="hover:text-[#0071e3] transition-colors">Privacy & Data</a></li>
                    </ul>
                </div>

            </div>

            <!-- Footer Copyright Bottom Row -->
            <div class="border-t border-[#d2d2d7]/60 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-[#86868b]">
                <p>© {{ date('Y') }} Musoftwares Inc. {{ $locale === 'ar' ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}</p>
                <div class="flex items-center gap-4">
                    <a href="/privacy-policy" class="hover:text-[#1d1d1f]">Privacy Policy</a>
                    <a href="/terms-of-service" class="hover:text-[#1d1d1f]">Terms of Use</a>
                    <a href="/cookie-policy" class="hover:text-[#1d1d1f]">Legal</a>
                    <a href="{{ route('marketplace.services.index') }}" class="text-[#0071e3]">Marketplace</a>
                </div>
            </div>

        </div>
    </footer>

    @stack('scripts')
</body>
</html>
