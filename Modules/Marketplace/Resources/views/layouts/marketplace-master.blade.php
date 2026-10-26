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
    
    @php
        $currentUrl = url()->current();
        $baseUrl = $meta['canonical_url'] ?? $meta['url'] ?? $currentUrl;
        $locale = app()->getLocale();
    @endphp
    <link rel="canonical" href="{{ \App\Support\LocaleUrl::canonical($baseUrl) }}">
    @foreach (\App\Support\LocaleUrl::alternates($baseUrl) as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach

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

    {{-- Same header as every public page (layouts/site-header). Marketplace-only links live in the
         section bar below, the way large sites keep one global header plus a section menu. --}}
    @include('layouts.site-header')

    <nav class="w-full bg-white border-b border-black/[0.06]" aria-label="{{ $locale === 'ar' ? 'قائمة سوق الخدمات' : 'Marketplace menu' }}">
        <div class="max-w-[1240px] mx-auto px-4 sm:px-8 h-12 flex items-center gap-5 overflow-x-auto whitespace-nowrap text-[13px] font-medium">
            <a href="{{ route('marketplace.services.index') }}" class="shrink-0 font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'سوق الخدمات' : 'Marketplace' }}</a>
            <span class="shrink-0 h-4 w-px bg-black/10" aria-hidden="true"></span>
            <a href="{{ route('marketplace.services.index') }}" class="shrink-0 {{ request()->routeIs('marketplace.services.index') ? 'text-[#0071e3]' : 'text-[#1d1d1f]/70 hover:text-[#0071e3]' }}">{{ $locale === 'ar' ? 'استكشف الخدمات' : 'Explore' }}</a>
            <a href="{{ route('marketplace.services.create') }}" class="shrink-0 text-[#1d1d1f]/70 hover:text-[#0071e3]">{{ $locale === 'ar' ? 'أضف خدمتك' : 'Sell a service' }}</a>
            @auth
                <a href="{{ route('marketplace.orders.index') }}" class="shrink-0 {{ request()->routeIs('marketplace.orders.*') ? 'text-[#0071e3]' : 'text-[#1d1d1f]/70 hover:text-[#0071e3]' }}">{{ $locale === 'ar' ? 'طلباتي' : 'My orders' }}</a>
                <a href="{{ route('marketplace.favorites.index') }}" class="shrink-0 {{ request()->routeIs('marketplace.favorites.*') ? 'text-[#0071e3]' : 'text-[#1d1d1f]/70 hover:text-[#0071e3]' }}">{{ $locale === 'ar' ? 'المفضلة' : 'Saved' }}</a>
                <a href="{{ route('marketplace.home') }}" class="shrink-0 {{ request()->routeIs('marketplace.home') ? 'text-[#0071e3]' : 'text-[#1d1d1f]/70 hover:text-[#0071e3]' }}">{{ $locale === 'ar' ? 'لوحة السوق' : 'Seller dashboard' }}</a>
            @endauth
        </div>
    </nav>

    <!-- Apple Exact Announcement Ribbon Banner -->
    <div class="w-full bg-[#fbfbfd] border-b border-black/[0.06] py-2.5 px-4 text-center text-[12px] text-[#1d1d1f] flex items-center justify-center gap-1.5">
        <span class="flex items-center gap-1.5">
            <i class="ri-shield-check-line text-emerald-600"></i>
            <span>{{ __('marketplace_ui.ribbon') }}</span>
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

    {{-- Marketplace trust notes and breadcrumb, then the same footer as every public page. --}}
    <section class="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-10 pb-8 px-6 sm:px-12 text-[#86868b] text-[12px] mt-16">
        <div class="max-w-[1100px] mx-auto space-y-6">
            <!-- Numbered Footnotes Disclosures -->
            <div class="space-y-2 border-b border-[#d2d2d7]/60 pb-6 text-[11px] leading-relaxed text-[#86868b]">
                @foreach (__('marketplace_ui.notes') as $note)
                    <p>{{ $loop->iteration }}. {{ $note }}</p>
                @endforeach
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
                    <span class="text-[#1d1d1f] truncate max-w-[200px]">{{ \Illuminate\Support\Str::before($title ?? $meta['title'], ' | ') }}</span>
                @endif
            </div>
        </div>
    </section>
    @include('layouts.site-footer')

    @stack('scripts')
</body>
</html>
