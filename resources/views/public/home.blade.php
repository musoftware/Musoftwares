@extends('layouts.public')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
<div class="w-full bg-[#ffffff] text-[#1d1d1f] overflow-hidden antialiased">

    <!-- ============================================================ -->
    <!-- HERO 1: COLLEGE / STUDIO HERO (White Canvas + 3 Stickers) -->
    <!-- ============================================================ -->
    <section class="relative w-full pt-12 sm:pt-16 pb-12 text-center bg-[#ffffff] border-b border-black/[0.04]">
        <div class="max-w-[1024px] mx-auto px-6 space-y-2 apple-reveal">
            <h1 class="text-4xl sm:text-6xl lg:text-[64px] font-semibold tracking-[-0.015em] text-[#1d1d1f] leading-[1.05]">
                {{ $locale === 'ar' ? 'برمجياتك، منظمة ومتقنة.' : 'Software, sorted.' }}
            </h1>
            <p class="text-lg sm:text-[21px] text-[#1d1d1f] font-normal max-w-2xl mx-auto leading-snug">
                {{ $locale === 'ar' 
                    ? 'احصل على ملكية كاملة للكود المصدري بدون وسطاء عند طلب تطبيقات الويب، الموبايل، أو الديسك توب.' 
                    : 'Get 100% full source code ownership when you engineer Web, Mobile, or Desktop systems.' }}
            </p>
            <div class="pt-3 pb-6 flex justify-center">
                <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[14px] px-6 py-2 shadow-sm font-normal">
                    {{ $locale === 'ar' ? 'بدء مشروعك' : 'Start Project' }}
                </a>
            </div>
        </div>

        <!-- 3 Sticker Composition (Borderless, Clean Studio Art) -->
        <div class="max-w-[980px] mx-auto px-4 mt-2 apple-reveal">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end justify-center">
                
                <!-- Sticker 1: Web App -->
                <div class="flex flex-col items-center text-center group">
                    <img src="/images/apple/sticker-web.jpg" alt="Web Development" class="w-64 h-64 object-contain filter drop-shadow-xl group-hover:scale-105 transition-transform duration-500">
                    <div class="mt-2">
                        <span class="text-[15px] font-semibold text-[#1d1d1f] block">{{ $locale === 'ar' ? 'تطبيقات الويب' : 'Web Applications' }}</span>
                        <span class="text-xs text-[#86868b]">React & Laravel Core</span>
                    </div>
                </div>

                <!-- Sticker 2: Mobile App -->
                <div class="flex flex-col items-center text-center group">
                    <img src="/images/apple/sticker-mobile.jpg" alt="Mobile Apps" class="w-64 h-64 object-contain filter drop-shadow-xl group-hover:scale-105 transition-transform duration-500">
                    <div class="mt-2">
                        <span class="text-[15px] font-semibold text-[#1d1d1f] block">{{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Applications' }}</span>
                        <span class="text-xs text-[#86868b]">iOS & Android Native</span>
                    </div>
                </div>

                <!-- Sticker 3: Desktop App -->
                <div class="flex flex-col items-center text-center group">
                    <img src="/images/apple/sticker-desktop.jpg" alt="Desktop POS Software" class="w-64 h-64 object-contain filter drop-shadow-xl group-hover:scale-105 transition-transform duration-500">
                    <div class="mt-2">
                        <span class="text-[15px] font-semibold text-[#1d1d1f] block">{{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop & POS' }}</span>
                        <span class="text-xs text-[#86868b]">Offline-First Systems</span>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================ -->
    <!-- HERO 2: FULL-BLEED WHITE HERO (iPhone Equivalent: Web Apps) -->
    <!-- ============================================================ -->
    <section id="web" class="relative w-full pt-16 sm:pt-24 pb-4 text-center bg-[#ffffff] border-b border-black/[0.04]">
        <div class="max-w-[1024px] mx-auto px-6 space-y-2 apple-reveal">
            <h2 class="text-4xl sm:text-6xl lg:text-[64px] font-semibold tracking-[-0.015em] text-[#1d1d1f] leading-[1.05]">
                {{ $locale === 'ar' ? 'تطبيقات الويب السحابية' : 'Web Applications' }}
            </h2>
            <p class="text-lg sm:text-[24px] text-[#1d1d1f]/80 font-normal max-w-2xl mx-auto leading-snug">
                {{ $locale === 'ar' ? 'منصات سريعة وقوية مصممة لأداء استثنائي.' : 'Engineered for speed, scale, and seamless client flows.' }}
            </p>

            <!-- Dual Pill Buttons -->
            <div class="flex items-center justify-center gap-3 pt-2 pb-6">
                <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[14px] px-5 py-2 shadow-sm">
                    {{ $locale === 'ar' ? 'طلب المنصة' : 'Learn more' }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[14px] px-5 py-2">
                    {{ $locale === 'ar' ? 'استشارة واتساب' : 'Inquire ›' }}
                </a>
            </div>
        </div>

        <!-- Seamless Floating Software Window -->
        <div class="max-w-[1140px] mx-auto px-4 mt-4 apple-reveal">
            <img src="/images/apple/web-mobile-suite.jpg" alt="Web Applications" class="w-full h-auto object-cover max-h-[620px] rounded-t-3xl shadow-2xl mx-auto">
        </div>
    </section>


    <!-- ============================================================ -->
    <!-- HERO 3: FULL-BLEED PASTEL BLUE HERO (MacBook Air Equivalent: Mobile Apps) -->
    <!-- ============================================================ -->
    <section id="mobile" class="relative w-full pt-16 sm:pt-24 pb-4 text-center bg-[#edf5fb] border-b border-black/[0.04]">
        <div class="max-w-[1024px] mx-auto px-6 space-y-2 apple-reveal">
            <h2 class="text-4xl sm:text-6xl lg:text-[64px] font-semibold tracking-[-0.015em] text-[#1d1d1f] leading-[1.05]">
                {{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Apps' }}
            </h2>
            <p class="text-lg sm:text-[24px] text-[#1d1d1f]/80 font-normal max-w-2xl mx-auto leading-snug">
                {{ $locale === 'ar' ? 'تجربة أصلية فائقة السرعة على iOS وأندرويد.' : 'Lean. Fast. Native performance for iOS and Android.' }}
            </p>

            <!-- Dual Pill Buttons -->
            <div class="flex items-center justify-center gap-3 pt-2 pb-6">
                <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[14px] px-5 py-2 shadow-sm">
                    {{ $locale === 'ar' ? 'طلب تطبيق' : 'Learn more' }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[14px] px-5 py-2">
                    {{ $locale === 'ar' ? 'استشارة واتساب' : 'Inquire ›' }}
                </a>
            </div>
        </div>

        <!-- Seamless Floating Mobile Interface -->
        <div class="max-w-[1140px] mx-auto px-4 mt-4 apple-reveal">
            <img src="/images/apple/whatsapp-crm.jpg" alt="Mobile Applications" class="w-full h-auto object-cover max-h-[620px] rounded-t-3xl shadow-2xl mx-auto">
        </div>
    </section>


    <!-- ============================================================ -->
    <!-- 2-COLUMN BENTO GRID (Apple 6 Units: Exact Spacing & Colors) -->
    <!-- ============================================================ -->
    <section class="max-w-[1280px] mx-auto px-3 py-3 space-y-3">
        
        <!-- ROW 1: Light Ice Blue + Pitch Black Luxury -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Bento 1: iPad Air style (Light Ice Blue #edf4fb) -->
            <div class="bg-[#edf4fb] rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop Software' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#1d1d1f]/80 font-normal">
                        {{ $locale === 'ar' ? 'برامج كاشير ومخازن أوفلاين سريعة.' : 'Offline-first cashier and retail systems.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/stock-manager" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-4 py-1.5">Inquire ›</a>
                    </div>
                </div>

                <!-- Seamless Floating Image -->
                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/apple/sticker-desktop.jpg" alt="Desktop POS" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

            <!-- Bento 2: MacBook Pro style (Deep Pitch Black #000000) -->
            <div class="bg-[#000000] text-white rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                        {{ $locale === 'ar' ? 'الأنظمة المالية والـ ERP' : 'Enterprise & Financial' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#86868b] font-normal">
                        {{ $locale === 'ar' ? 'محركات قيود ومطابقة سيولة لحظية.' : 'Sub-10ms cashflow reconciliation & ledgers.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/chartcash" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn text-[13px] px-4 py-1.5 border border-white/20 text-white hover:bg-white hover:text-black">Inquire ›</a>
                    </div>
                </div>

                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/apple/fintech-terminal.jpg" alt="Financial Systems" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

        </div>


        <!-- ROW 2: Soft Rose + Pitch Black Luxury -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Bento 3: Apple Watch style (Soft Rose/Sand #fcf6f4) -->
            <div class="bg-[#fcf6f4] rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'محرك واتساب السحابي' : 'WhatsApp Cloud CRM' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#1d1d1f]/80 font-normal">
                        {{ $locale === 'ar' ? 'ربط رسمي مع Meta Graph API.' : 'Official Meta Graph API team inbox.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/trenz-whatscrm" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-4 py-1.5">Inquire ›</a>
                    </div>
                </div>

                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/apple/whatsapp-crm.jpg" alt="WhatsApp Cloud Engine" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

            <!-- Bento 4: iPad Pro style (Deep Pitch Black #000000) -->
            <div class="bg-[#000000] text-white rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                        {{ $locale === 'ar' ? 'الذكاء الاصطناعي والأتمتة' : 'AI & Custom Bots' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#86868b] font-normal">
                        {{ $locale === 'ar' ? 'مساعد ذكي يفهم اللهجات العربية.' : 'Grounded conversational AI bots.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/stocktalk-ai" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn text-[13px] px-4 py-1.5 border border-white/20 text-white hover:bg-white hover:text-black">Inquire ›</a>
                    </div>
                </div>

                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/apple/ai-ecommerce.jpg" alt="AI Assistant" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

        </div>


        <!-- ROW 3: Trade In & Apple Card style (Clean Light Gray #f5f5f7) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Bento 5: Trade In style (Light Gray #f5f5f7) -->
            <div class="bg-[#f5f5f7] rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'المتاجر والدروب شيبنج' : 'E-Commerce & Supply' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#1d1d1f]/80 font-normal">
                        {{ $locale === 'ar' ? 'ربط آلاف المسوقين وبوالص الشحن.' : 'Multi-vendor catalogs & courier manifests.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/kbdny" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-4 py-1.5">Inquire ›</a>
                    </div>
                </div>

                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/portfolio/kbdny.png" alt="Kbdny Platform" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

            <!-- Bento 6: Apple Card style (Light Gray #f5f5f7) -->
            <div class="bg-[#f5f5f7] rounded-[28px] h-[580px] p-8 sm:p-12 flex flex-col items-center justify-between text-center overflow-hidden apple-bento-card apple-reveal">
                <div class="space-y-1.5 z-10">
                    <h3 class="text-3xl sm:text-4xl font-semibold tracking-tight text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'بوابة اتصالات و SMS OTP' : 'Telecom Hardware' }}
                    </h3>
                    <p class="text-base sm:text-lg text-[#1d1d1f]/80 font-normal">
                        {{ $locale === 'ar' ? 'إرسال رسائل التحقق بدون رسوم وسطاء.' : 'Zero-cost SMS OTP via local SIM bridge.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="/portfolio/am-sms-gateway" class="apple-pill-btn apple-pill-primary text-[13px] px-4 py-1.5">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-4 py-1.5">Inquire ›</a>
                    </div>
                </div>

                <div class="w-full h-[320px] flex items-end justify-center">
                    <img src="/images/apple/whatsapp-engine.jpg" alt="SMS Gateway" class="w-auto h-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

        </div>

    </section>


    <!-- ============================================================ -->
    <!-- ENDLESS ENTERTAINMENT / SHOWCASE STRIP (Exact Apple Carousel) -->
    <!-- ============================================================ -->
    <section class="w-full bg-[#ffffff] pt-20 pb-16 border-t border-black/[0.04] overflow-hidden apple-reveal">
        <div class="max-w-[1280px] mx-auto px-6 mb-8 text-center sm:text-start rtl:sm:text-right flex flex-col sm:flex-row items-center justify-between gap-4">
            <h3 class="text-3xl sm:text-5xl font-semibold text-[#1d1d1f] tracking-tight">
                {{ $locale === 'ar' ? 'إمكانيات بلا حدود.' : 'Endless entertainment.' }}
            </h3>
            <a href="/portfolio" class="apple-pill-btn apple-pill-secondary text-[13px] px-5 py-2 shrink-0">
                {{ $locale === 'ar' ? 'استعراض الأرشيف الكامل ›' : 'Explore All Platforms ›' }}
            </a>
        </div>

        <!-- Full-Bleed Carousel Track with Peek Cards -->
        <div class="flex gap-4 overflow-x-auto no-scrollbar px-6 max-w-[1400px] mx-auto pb-6">
            
            <!-- Carousel Item 1 (Main Hero Style) -->
            <div class="w-[420px] sm:w-[580px] shrink-0 bg-[#000000] text-white rounded-[24px] overflow-hidden relative group shadow-xl">
                <img src="/images/apple/web-mobile-suite.jpg" alt="Web Suite" class="w-full h-[300px] sm:h-[360px] object-cover opacity-80 group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent p-6 sm:p-8 flex flex-col justify-end">
                    <span class="text-xs font-semibold text-[#2997ff] uppercase">Web Applications</span>
                    <h4 class="text-xl sm:text-2xl font-bold text-white">Full-Stack SaaS Platform</h4>
                    <p class="text-xs sm:text-sm text-zinc-300 pt-1">Sub-50ms React & Laravel architecture.</p>
                </div>
            </div>

            <!-- Carousel Item 2 -->
            <div class="w-[340px] sm:w-[460px] shrink-0 bg-[#000000] text-white rounded-[24px] overflow-hidden relative group shadow-xl">
                <img src="/images/apple/whatsapp-crm.jpg" alt="Trenz CRM" class="w-full h-[300px] sm:h-[360px] object-cover opacity-80 group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent p-6 sm:p-8 flex flex-col justify-end">
                    <span class="text-xs font-semibold text-[#30d158] uppercase">Meta Graph API</span>
                    <h4 class="text-xl sm:text-2xl font-bold text-white">Trenz WhatsApp CRM</h4>
                    <p class="text-xs sm:text-sm text-zinc-300 pt-1">Multi-agent team customer inbox.</p>
                </div>
            </div>

            <!-- Carousel Item 3 -->
            <div class="w-[340px] sm:w-[460px] shrink-0 bg-[#000000] text-white rounded-[24px] overflow-hidden relative group shadow-xl">
                <img src="/images/apple/fintech-terminal.jpg" alt="ChartCash" class="w-full h-[300px] sm:h-[360px] object-cover opacity-80 group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent p-6 sm:p-8 flex flex-col justify-end">
                    <span class="text-xs font-semibold text-[#ff9500] uppercase">FinTech Engine</span>
                    <h4 class="text-xl sm:text-2xl font-bold text-white">ChartCash Terminal</h4>
                    <p class="text-xs sm:text-sm text-zinc-300 pt-1">Real-time cashflow telemetry.</p>
                </div>
            </div>

        </div>

        <!-- Carousel Pagination Dots -->
        <div class="flex items-center justify-center gap-2 pt-2">
            <span class="w-2 h-2 rounded-full bg-[#1d1d1f]"></span>
            <span class="w-2 h-2 rounded-full bg-[#d2d2d7]"></span>
            <span class="w-2 h-2 rounded-full bg-[#d2d2d7]"></span>
            <span class="w-2 h-2 rounded-full bg-[#d2d2d7]"></span>
        </div>
    </section>

</div>
@endsection
