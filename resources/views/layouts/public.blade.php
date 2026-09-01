<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <script>
        (function() {
            try {
                var theme = 'system';
                var stored = localStorage.getItem('app-storage');
                if (stored) {
                    var parsed = JSON.parse(stored);
                    if (parsed && parsed.state && parsed.state.theme) {
                        theme = parsed.state.theme;
                    }
                } else {
                    var legacyTheme = localStorage.getItem('theme');
                    if (legacyTheme) theme = legacyTheme;
                }
                var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.colorScheme = 'dark';
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.colorScheme = 'light';
                }
            } catch(e) {}
        })();
    </script>

    <title>{{ $title ?? 'Musoftwares | Boutique Software Engineering Studio' }}</title>
    <meta name="description" content="{{ $description ?? 'Custom web applications, mobile apps, and enterprise automation platforms engineered by Mahmoud Amin in Suez, Egypt.' }}">

    @php
        $currentUrl = url()->current();
        $canonicalUrl = $canonical ?? $currentUrl;
        $ogImage = $image ?? asset('images/apple/web-mobile-suite.jpg');
        $locale = app()->getLocale();
    @endphp

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

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "SoftwareApplication",
      "name": "Musoftwares",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web, iOS, Android, Windows, Cloud",
      "url": "https://musoftwares.com",
      "author": {
        "@@type": "Person",
        "name": "Mahmoud Amin",
        "jobTitle": "Founder & Chief Software Architect",
        "url": "https://musoftwares.com/about/mahmoud-amin",
        "address": {
          "@@type": "PostalAddress",
          "addressLocality": "Suez",
          "addressCountry": "EG"
        }
      }
    }
    </script>

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
            font-weight: 400;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .apple-pill-primary {
            background-color: #0071e3;
            color: #ffffff;
        }
        .apple-pill-primary:hover {
            background-color: #0077ed;
            transform: scale(1.02);
        }
        .apple-pill-secondary {
            background-color: #f5f5f7;
            color: #0066cc;
            border: 1px solid #d2d2d7;
        }
        .apple-pill-secondary:hover {
            background-color: #e8e8ed;
            color: #0071e3;
            transform: scale(1.02);
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

    <!-- Apple Exact Global Navigation Bar (Unified with React thickness & layout) -->
    <header class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-black/5 transition-all duration-200">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between px-6 sm:px-10 min-h-[56px] py-3">
            
            <!-- Musoftwares Official Studio Monogram Logo & Brand -->
            <div class="flex items-center gap-8">
                <a href="/" class="flex items-center space-x-2.5 rtl:space-x-reverse group focus:outline-none shrink-0" title="Musoftwares Studio">
                    <svg class="w-5 h-5 fill-[#1d1d1f] group-hover:text-[#000000] transition-colors" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg">
                        <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/>
                    </svg>
                    <span class="text-[19px] sm:text-[21px] font-semibold tracking-[-0.03em] text-[#1d1d1f]">
                        Musoftware
                    </span>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/70">
                        STUDIO
                    </span>
                </a>

                <!-- Desktop Navigation Links -->
                @auth
                    <nav class="hidden lg:flex items-center space-x-6 rtl:space-x-reverse text-[13px] font-normal text-[#1d1d1f]/75">
                        <a href="{{ route('library.index') }}" class="hover:text-[#1d1d1f] transition-colors {{ request()->routeIs('library.*') ? 'font-semibold text-[#0071e3]' : '' }}">{{ $locale === 'ar' ? 'المكتبة الرقمية' : 'Digital Library' }}</a>
                        <a href="/platforms" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'المنصات' : 'Platforms' }}</a>
                        <a href="/solutions" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'الحلول' : 'Solutions' }}</a>
                        <a href="/portfolio" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'معرض الأعمال' : 'Portfolio' }}</a>
                        <a href="/estimator" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
                        <a href="/pricing" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'الأسعار' : 'Pricing' }}</a>
                        <a href="/compare/laravel-vs-nodejs" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'المقارنات والتوثيق' : 'Insights' }}</a>
                    </nav>
                @else
                    <nav class="hidden lg:flex items-center space-x-6 rtl:space-x-reverse text-[13px] font-normal text-[#1d1d1f]/75">
                        <a href="{{ route('library.index') }}" class="hover:text-[#1d1d1f] transition-colors {{ request()->routeIs('library.*') ? 'font-semibold text-[#0071e3]' : '' }}">{{ $locale === 'ar' ? 'المكتبة الرقمية' : 'Digital Library' }}</a>
                        <a href="/#web" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'تطبيقات الويب' : 'Web Apps' }}</a>
                        <a href="/#mobile" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Apps' }}</a>
                        <a href="/#desktop" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop Apps' }}</a>
                        <a href="/platforms" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'المنصات' : 'Platforms' }}</a>
                        <a href="/solutions" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'الحلول' : 'Solutions' }}</a>
                        <a href="/portfolio" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'معرض الأعمال' : 'Portfolio' }}</a>
                        <a href="/estimator" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
                        <a href="/about/mahmoud-amin" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'محمود أمين' : 'Founder' }}</a>
                        <a href="/company/contact" class="hover:text-[#1d1d1f] transition-colors">{{ $locale === 'ar' ? 'تواصل' : 'Contact' }}</a>
                    </nav>
                @endauth
            </div>

            <!-- Right Utilities & Action Buttons -->
            <div class="flex items-center space-x-3 sm:space-x-4 rtl:space-x-reverse shrink-0">
                <!-- WhatsApp Direct Action -->
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="hidden lg:inline-flex p-2 text-[#1d1d1f]/70 hover:text-[#25D366] transition-colors" title="{{ $locale === 'ar' ? 'محادثة مباشرة' : 'Direct Consultation' }}">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                </a>

                @auth
                    @php
                        $userBal = (float) auth()->user()->available_balance();
                        $userCurId = auth()->user()->currency_id ?: 1;
                        $formattedBal = \App\Helpers\FinanceHelper::instance()->format_money($userBal, $userCurId);
                    @endphp
                    <!-- Wallet Balance Pill -->
                    <a href="{{ Route::has('billing.invoices.index') ? route('billing.invoices.index') : url('/dashboard') }}" class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1 rounded-[980px] bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[12px] font-medium transition-all shadow-2xs hover:bg-emerald-100/70" title="{{ $locale === 'ar' ? 'رصيد المحفظة المتاح' : 'Available Wallet Balance' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="font-mono font-semibold">{{ $formattedBal }}</span>
                    </a>

                    <a href="/dashboard" class="hidden lg:inline-flex px-3.5 py-1.5 rounded-[980px] border border-black/10 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] text-[12px] font-medium transition-all shadow-sm">
                        {{ $locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }} ➔
                    </a>
                @else
                    <a href="/login" class="hidden lg:inline-flex text-[12px] font-medium text-[#1d1d1f]/75 hover:text-[#1d1d1f] transition-colors px-2">
                        {{ $locale === 'ar' ? 'دخول' : 'Sign in' }}
                    </a>
                    <a href="/start-project" class="hidden lg:inline-flex items-center justify-center rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white text-[12px] font-medium px-3.5 py-1.5 transition-all shadow-sm">
                        {{ $locale === 'ar' ? 'بدء مشروع' : 'Start a Project' }}
                    </a>
                @endauth

                <!-- Mobile Menu Hamburger -->
                <button onclick="document.getElementById('apple-mobile-menu').classList.toggle('hidden')" class="lg:hidden p-2 text-[#1d1d1f]/70 hover:text-[#1d1d1f] transition-colors" aria-label="Toggle navigation">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 17h16"/></svg>
                </button>
            </div>

        </div>

        <!-- Apple Mobile Menu Dropdown -->
        <div id="apple-mobile-menu" class="hidden lg:hidden bg-white/95 backdrop-blur-xl border-b border-black/[0.08] px-6 py-6 space-y-3 text-[14px] font-medium text-[#1d1d1f]">
            <a href="{{ route('library.index') }}" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05] {{ request()->routeIs('library.*') ? 'text-[#0071e3] font-semibold' : '' }}">{{ $locale === 'ar' ? 'المكتبة الرقمية' : 'Digital Library' }}</a>
            @auth
                <a href="/platforms" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'المنصات السحابية' : 'Platforms' }}</a>
                <a href="/solutions" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'الحلول القطاعية' : 'Solutions' }}</a>
                <a href="/portfolio" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'معرض الأعمال' : 'Portfolio' }}</a>
                <a href="/estimator" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
                <a href="/pricing" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'الأسعار' : 'Pricing' }}</a>
                <a href="/dashboard" class="block py-2 text-[#0071e3] font-semibold">{{ $locale === 'ar' ? 'لوحة التحكم ➔' : 'Dashboard ➔' }}</a>
            @else
                <a href="/#web" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'تطبيقات الويب' : 'Web Applications' }}</a>
                <a href="/#mobile" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Apps' }}</a>
                <a href="/#desktop" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop Apps' }}</a>
                <a href="/platforms" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'المنصات السحابية' : 'Platforms' }}</a>
                <a href="/solutions" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'الحلول القطاعية' : 'Solutions' }}</a>
                <a href="/portfolio" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'أرشيف المشاريع' : 'Portfolio' }}</a>
                <a href="/estimator" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'حاسبة التكلفة' : 'Estimator' }}</a>
                <a href="/about/mahmoud-amin" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'عن محمود أمين' : 'About Mahmoud Amin' }}</a>
                <a href="/company/contact" class="block py-1.5 hover:text-[#0071e3] border-b border-black/[0.05]">{{ $locale === 'ar' ? 'تواصل معنا' : 'Contact' }}</a>
                <div class="pt-2 flex items-center gap-3">
                    <a href="/login" class="flex-1 text-center py-2.5 rounded-xl border border-black/10 text-sm font-medium hover:bg-slate-50 transition-colors">{{ $locale === 'ar' ? 'دخول' : 'Sign in' }}</a>
                    <a href="/start-project" class="flex-1 text-center py-2.5 rounded-xl bg-[#0071e3] hover:bg-[#0077ed] text-white text-sm font-medium transition-colors">{{ $locale === 'ar' ? 'بدء مشروع' : 'Start Project' }}</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Apple Exact Announcement Ribbon Banner -->
    <div class="w-full bg-[#fbfbfd] border-b border-black/[0.06] py-2.5 px-4 text-center text-[12px] sm:text-[13px] text-[#1d1d1f]/80">
        <span>
            {{ $locale === 'ar' 
                ? 'استوديو تطوير برمجي متخصص: ملكية كاملة للكود المصدري بدون وسطاء أو اشتراكات شهرية.' 
                : 'Boutique Studio: 100% Direct Source Code Ownership & Lifelong Sovereignty.' }}
        </span>
    </div>

    <!-- Main Content Stream -->
    <main class="flex-1 w-full bg-[#ffffff]">
        @yield('content')
    </main>

    <!-- Apple Exact 5-Column Global Directory Light Footer -->
    <footer class="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-10 pb-12 px-6 sm:px-12 text-[#86868b] text-[12px]">
        <div class="max-w-[1024px] mx-auto space-y-6">
            
            <!-- Numbered Footnotes Disclosures -->
            <div class="space-y-2 border-b border-[#d2d2d7]/60 pb-6 text-[11px] leading-relaxed text-[#86868b]">
                <p>1. Solo-Founder Software Studio: Musoftwares is an independent software engineering practice founded and operated by Mahmoud Amin in Suez, Egypt, delivering direct client source code ownership with zero agency overhead.</p>
                <p>2. Production Performance: Web and mobile applications are built on modern high-speed stacks (Laravel, React, TypeScript, Flutter, PostgreSQL) optimized for sub-10ms operational queries.</p>
                <p>3. WhatsApp Meta Graph API: Direct official webhooks integration providing unlimited team messaging with zero middleman per-message markups.</p>
            </div>

            <!-- Breadcrumb Navigation Trail -->
            <div class="flex items-center space-x-2 rtl:space-x-reverse text-[12px] text-[#86868b]">
                <a href="/" class="hover:text-[#1d1d1f]" title="Musoftwares">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg"><path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/></svg>
                </a>
                <span>›</span>
                <span>{{ $locale === 'ar' ? 'استوديو البرمجيات' : 'Musoftwares Studio' }}</span>
                @if(isset($title))
                    <span>›</span>
                    <span class="text-[#1d1d1f] truncate max-w-[200px]">{{ $title }}</span>
                @endif
            </div>

            <!-- 5-Column Apple Directory Links Grid -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 pt-4 pb-8">
                
                <!-- Col 1: Software Capabilities -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'القدرات البرمجية' : 'Software Services' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="/#web-mobile" class="hover:text-[#0071e3] transition-colors">Web Applications</a></li>
                        <li><a href="/#web-mobile" class="hover:text-[#0071e3] transition-colors">Mobile iOS & Android</a></li>
                        <li><a href="/#whatsapp" class="hover:text-[#0071e3] transition-colors">WhatsApp Meta API</a></li>
                        <li><a href="/#fintech" class="hover:text-[#0071e3] transition-colors">FinTech & POS Terminals</a></li>
                        <li><a href="/platforms" class="hover:text-[#0071e3] transition-colors">Custom Cloud Platforms</a></li>
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
                        <li><a href="/start-project" class="hover:text-[#0071e3] transition-colors">Request Build Proposal</a></li>
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

                <!-- Col 5: Sovereignty & Legal -->
                <div class="space-y-3">
                    <h3 class="text-[12px] font-semibold text-[#1d1d1f]">{{ $locale === 'ar' ? 'السيادة والخصوصية' : 'Sovereignty' }}</h3>
                    <ul class="space-y-2 text-[12px]">
                        <li><a href="/privacy-policy" class="hover:text-[#0071e3] transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms-of-service" class="hover:text-[#0071e3] transition-colors">Terms of Service & SLA</a></li>
                        <li><span class="text-[#86868b]">100% Code Ownership</span></li>
                        <li><span class="text-[#86868b]">Zero Vendor Lock-In</span></li>
                    </ul>
                </div>

            </div>

            <!-- Apple Bottom Legal & Copyright Bar -->
            <div class="border-t border-[#d2d2d7]/60 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-[11px] text-[#86868b]">
                <div>
                    Copyright &copy; {{ date('Y') }} Musoftwares. Mahmoud Amin. All rights reserved. &bull; Engineered in Suez, Egypt.
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <a href="/privacy-policy" class="hover:text-[#1d1d1f] transition-colors">Privacy Policy</a>
                    <span>|</span>
                    <a href="/terms-of-service" class="hover:text-[#1d1d1f] transition-colors">Terms of Use</a>
                    <span>|</span>
                    <a href="/company/contact" class="hover:text-[#1d1d1f] transition-colors">Contact</a>
                    <span>|</span>
                    <a href="/portfolio" class="hover:text-[#1d1d1f] transition-colors">Site Map</a>
                </div>
                <div class="text-[#86868b] hover:text-[#1d1d1f] cursor-pointer transition-colors">
                    {{ $locale === 'ar' ? 'مصر (العربية)' : 'Egypt (English)' }}
                </div>
            </div>

        </div>
    </footer>

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
        });
    </script>

    @stack('scripts')
</body>
</html>
