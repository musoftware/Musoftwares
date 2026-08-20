@extends('layouts.public')

@section('content')

<!-- 1. CONVERSATIONAL HERO SECTION -->
<section class="w-full relative bg-black overflow-hidden border-b border-[#222222]">
    
    <!-- Hero Slider / Top Panorama -->
    <div class="relative w-full h-[360px] sm:h-[480px] lg:h-[520px] bg-zinc-950">
        <div id="hero-slider" class="w-full h-full relative">
            
            <!-- Slide 1: Programs & ERP -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-100" data-index="0">
                <img src="/images/banners/hero_erp.jpg" alt="Enterprise ERP Systems Architecture" class="w-full h-full object-cover brightness-[0.9] contrast-[1.05]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

            <!-- Slide 2: Websites & Apps -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-0 pointer-events-none" data-index="1">
                <img src="/images/banners/hero_web.jpg" alt="High-Scale Web & E-Commerce" class="w-full h-full object-cover brightness-[0.9] contrast-[1.05]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

            <!-- Slide 3: WhatsApp Automation -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-0 pointer-events-none" data-index="2">
                <img src="/images/banners/hero_whatsapp.jpg" alt="Meta WhatsApp Cloud API Automation" class="w-full h-full object-cover brightness-[0.9] contrast-[1.05]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

        </div>
    </div>

    <!-- Sage Green Accent Bar (#748660) - Direct Conversational Banners -->
    <div class="w-full bg-[#748660] text-[#111111] py-8 px-6 sm:px-12 border-b border-[#5E6D4E]">
        <div class="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <!-- Banner Content Dynamic Switcher -->
            <div id="banner-text-container" class="space-y-1">
                
                <!-- Banner 1 -->
                <div class="banner-text-item" data-index="0">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ __('landing_home.hero_badge_1') }}
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ __('landing_home.hero_title_1') }}
                    </h1>
                </div>

                <!-- Banner 2 -->
                <div class="banner-text-item hidden" data-index="1">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ __('landing_home.hero_badge_2') }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ __('landing_home.hero_title_2') }}
                    </h2>
                </div>

                <!-- Banner 3 -->
                <div class="banner-text-item hidden" data-index="2">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ __('landing_home.hero_badge_3') }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ __('landing_home.hero_title_3') }}
                    </h2>
                </div>

            </div>

            <!-- Actions & Pagination -->
            <div class="flex items-center space-x-6 rtl:space-x-reverse shrink-0">
                <a href="/start-project" class="px-6 py-2.5 bg-[#0F140A] text-[#748660] hover:bg-black text-xs font-bold font-mono tracking-widest uppercase transition-colors">
                    {{ __('general.start_a_project') }} ➔
                </a>

                <!-- Pagination Arrows (< 1 / 3 >) -->
                <div class="flex items-center space-x-3 rtl:space-x-reverse text-[#0F140A] font-mono text-sm font-bold">
                    <button onclick="prevSlide()" class="p-1 hover:opacity-60 transition-opacity cursor-pointer">
                        &larr;
                    </button>
                    <span id="slide-counter" class="tracking-widest">1 / 3</span>
                    <button onclick="nextSlide()" class="p-1 hover:opacity-60 transition-opacity cursor-pointer">
                        &rarr;
                    </button>
                </div>
            </div>

    </div>

    <!-- Studio Architectural Metrics Strip -->
    <div class="w-full bg-[#141414] border-b border-[#222222] py-6 px-6 sm:px-12">
        <div class="max-w-[1400px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 font-mono text-center sm:text-left rtl:sm:text-right">
            <div class="space-y-1">
                <div class="text-xl sm:text-2xl font-bold text-white">30+ Systems</div>
                <div class="text-[11px] text-[#748660] font-bold uppercase tracking-wider">Shipped in Production</div>
            </div>
            <div class="space-y-1">
                <div class="text-xl sm:text-2xl font-bold text-white">100% Ownership</div>
                <div class="text-[11px] text-[#748660] font-bold uppercase tracking-wider">Source Code & Database</div>
            </div>
            <div class="space-y-1">
                <div class="text-xl sm:text-2xl font-bold text-white">Direct 1-on-1</div>
                <div class="text-[11px] text-[#748660] font-bold uppercase tracking-wider">With Chief Architect</div>
            </div>
            <div class="space-y-1">
                <div class="text-xl sm:text-2xl font-bold text-white">&lt; 10ms Latency</div>
                <div class="text-[11px] text-[#748660] font-bold uppercase tracking-wider">High-Speed Architecture</div>
            </div>
        </div>
    </div>
</section>


<!-- 2. WHAT WE BUILD (4 DIRECT CAPABILITIES) -->
<section id="services" class="py-24 px-6 sm:px-12 max-w-[1400px] mx-auto">
    <div class="mb-16 space-y-3">
        <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
            {{ __('landing_home.services_badge') }}
        </span>
        <h2 class="text-3xl sm:text-4xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_home.services_title') }}
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Card 1: ERP -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between group">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    01
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_erp_title') }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_erp_desc') }}
                </p>
                <div class="flex flex-wrap gap-1.5 pt-2">
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">Laravel 12</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">PostgreSQL</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-[#748660]">ETA / ZATCA</span>
                </div>
            </div>
            <a href="/platforms/erp" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ __('landing_home.service_erp_action') }}
            </a>
        </div>

        <!-- Card 2: Websites & Stores -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between group">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    02
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_web_title') }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_web_desc') }}
                </p>
                <div class="flex flex-wrap gap-1.5 pt-2">
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">React &bull; Inertia</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">Redis &bull; Octane</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-[#748660]">Visa / Fawry</span>
                </div>
            </div>
            <a href="/start-project" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ __('landing_home.service_web_action') }}
            </a>
        </div>

        <!-- Card 3: WhatsApp Automation -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between group">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    03
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_wa_title') }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_wa_desc') }}
                </p>
                <div class="flex flex-wrap gap-1.5 pt-2">
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">Meta Graph API</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">Webhook Queue</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-[#748660]">OTP & Multi-Agent</span>
                </div>
            </div>
            <a href="/platforms/crm" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ __('landing_home.service_wa_action') }}
            </a>
        </div>

        <!-- Card 4: POS & Desktop .NET -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between group">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    04
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_pos_title') }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_pos_desc') }}
                </p>
                <div class="flex flex-wrap gap-1.5 pt-2">
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">.NET 9 / WPF</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-zinc-300">Offline SQLite</span>
                    <span class="px-2 py-0.5 bg-black/60 border border-[#333333] text-[10px] font-mono text-[#748660]">Hardware ESC/POS</span>
                </div>
            </div>
            <a href="/start-project" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ __('landing_home.service_pos_action') }}
            </a>
        </div>

    </div>
</section>


<!-- 3. TRANSPARENT PRICING & ARCHITECTURE ESTIMATOR -->
<section id="estimator" class="py-24 px-6 sm:px-12 bg-[#0E0E0E] border-y border-[#222222]">
    <div class="max-w-[1200px] mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
                {{ __('landing_home.estimator_badge') }}
            </span>
            <h2 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
                {{ __('landing_home.estimator_title') }}
            </h2>
            <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_home.estimator_desc') }}
            </p>
        </div>

        <!-- Official Pricing Framework Box -->
        <div class="bg-[#141414] border border-[#2B2B2B] p-8 sm:p-12 space-y-10">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 font-mono text-xs">
                <!-- Unit Rate 1: Web -->
                <div class="bg-[#181818] border border-[#262626] p-6 space-y-3">
                    <div class="text-[#748660] font-bold text-sm">🌐 WEB APPLICATIONS</div>
                    <div class="text-2xl font-bold text-white">$10 <span class="text-xs font-normal text-zinc-400">/ page</span></div>
                    <p class="text-zinc-400 text-xs font-sans leading-relaxed">
                        {{ __('landing_home.estimator_unit_web') }}
                    </p>
                </div>

                <!-- Unit Rate 2: Mobile -->
                <div class="bg-[#181818] border border-[#262626] p-6 space-y-3">
                    <div class="text-[#748660] font-bold text-sm">📱 MOBILE APPS (iOS/Android)</div>
                    <div class="text-2xl font-bold text-white">$15 <span class="text-xs font-normal text-zinc-400">/ screen</span></div>
                    <p class="text-zinc-400 text-xs font-sans leading-relaxed">
                        {{ __('landing_home.estimator_unit_mobile') }}
                    </p>
                </div>

                <!-- Unit Rate 3: Desktop & POS -->
                <div class="bg-[#181818] border border-[#262626] p-6 space-y-3">
                    <div class="text-[#748660] font-bold text-sm">💻 DESKTOP & POS (.NET)</div>
                    <div class="text-2xl font-bold text-white">$25 <span class="text-xs font-normal text-zinc-400">/ screen</span></div>
                    <p class="text-zinc-400 text-xs font-sans leading-relaxed">
                        {{ __('landing_home.estimator_unit_desktop') }}
                    </p>
                </div>
            </div>

            <div class="pt-6 border-t border-[#222222] flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="space-y-1 text-center sm:text-left rtl:sm:text-right">
                    <div class="text-sm font-bold text-white font-sans">
                        Instant Breakdown • Live Currency Exchange (USD &bull; EGP) • Official PDF Quotations
                    </div>
                    <p class="text-xs text-zinc-400 font-sans">
                        Combine platforms, select add-on modules, and calculate transparent investment estimates in real-time.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto font-mono text-xs">
                    <a href="/estimator" class="w-full sm:w-auto text-center px-8 py-4 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-widest transition-all">
                        {{ __('landing_home.estimator_open_button') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- 4. PRODUCTION SYSTEMS SHOWCASE (LIVE WORK) -->
<section id="work" class="py-24 px-6 sm:px-12 max-w-[1400px] mx-auto">
    <div class="mb-16 flex flex-col sm:flex-row sm:items-end justify-between gap-6">
        <div class="space-y-3">
            <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                {{ __('landing_home.portfolio_badge') }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold text-white font-sans tracking-tight">
                {{ __('landing_home.portfolio_title') }}
            </h2>
        </div>
        <a href="/portfolio" class="text-xs font-mono text-zinc-400 hover:text-white uppercase tracking-wider">
            {{ __('landing_home.portfolio_view_all') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- System 1: RevFlow -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div>
                <div class="h-56 bg-zinc-900 overflow-hidden relative">
                    <img src="/images/portfolio/revFlow.png" alt="RevFlow ERP System" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                        ENTERPRISE ERP
                    </div>
                </div>
                <div class="p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white font-sans">RevFlow Double-Entry Kernel</h3>
                    <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                        محرك حسابات مؤسسي معتمد بالقيود المزدوجة، يربط الفواتير والمخازن وتوزيع الأرباح لحظياً.
                    </p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'm%20interested%20in%20an%20ERP%20system%20similar%20to%20RevFlow." target="_blank" rel="noopener noreferrer" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('general.whatsapp_direct') }}</span> ➔
                    </a>
                    <a href="/portfolio" class="text-zinc-500 hover:text-white">Archive ↗</a>
                </div>
            </div>
        </div>

        <!-- System 2: ChartCash POS -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div>
                <div class="h-56 bg-zinc-900 overflow-hidden relative">
                    <img src="/images/portfolio/chartcash.png" alt="ChartCash FinTech & POS" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                        FINTECH POS
                    </div>
                </div>
                <div class="p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white font-sans">ChartCash Financial Terminal</h3>
                    <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                        محطة نقاط بيع وحسابات تدفقات نقدية فورية، مربوطة بالـ WebSockets وطابعات الباركود.
                    </p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'm%20interested%20in%20a%20POS/FinTech%20system%20similar%20to%20ChartCash." target="_blank" rel="noopener noreferrer" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('general.whatsapp_direct') }}</span> ➔
                    </a>
                    <a href="/portfolio" class="text-zinc-500 hover:text-white">Archive ↗</a>
                </div>
            </div>
        </div>

        <!-- System 3: Trenz WhatsApp CRM -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div>
                <div class="h-56 bg-zinc-900 overflow-hidden relative">
                    <img src="/images/portfolio/trenz-whatscrm.png" alt="Trenz WhatsApp CRM Engine" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                        META GRAPH API
                    </div>
                </div>
                <div class="p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white font-sans">Trenz Multi-Agent CRM</h3>
                    <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                        محرك معالجة ملايين الرسائل مع شات موحد للوكلاء وربط WhatsApp Cloud API رسمي.
                    </p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'm%20interested%20in%20a%20WhatsApp%20CRM%20system%20similar%20to%20Trenz." target="_blank" rel="noopener noreferrer" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('general.whatsapp_direct') }}</span> ➔
                    </a>
                    <a href="/portfolio" class="text-zinc-500 hover:text-white">Archive ↗</a>
                </div>
            </div>
        </div>

    </div>
</section>


<!-- 5. WHY MUSOFTWARES (COMPARISON MATRIX & DATA SOVEREIGNTY) -->
<section class="py-24 px-6 sm:px-12 bg-[#0E0E0E] border-t border-[#222222]">
    <div class="max-w-[1200px] mx-auto space-y-16">
        
        <div class="text-center max-w-3xl mx-auto space-y-4">
            <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
                {{ __('landing_home.comparison_badge') }}
            </span>
            <h2 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
                {{ __('landing_home.comparison_title') }}
            </h2>
            <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_home.comparison_desc') }}
            </p>
        </div>

        <!-- Comparison Table -->
        <div class="bg-[#141414] border border-[#2B2B2B] overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-4 bg-[#1C1C1C] p-5 font-mono text-xs text-white font-bold border-b border-[#2B2B2B] gap-4">
                <div class="text-zinc-400">{{ __('landing_home.comp_col_metric') }}</div>
                <div class="text-[#748660]">{{ __('landing_home.comp_col_musoftware') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_col_agency') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_col_freelance') }}</div>
            </div>

            <!-- Row 1: Communication -->
            <div class="grid grid-cols-1 md:grid-cols-4 p-5 font-mono text-xs border-b border-[#222222] gap-4 items-center">
                <div class="text-white font-bold">{{ __('landing_home.comp_row_comm_title') }}</div>
                <div class="text-[#748660] font-bold">{{ __('landing_home.comp_row_comm_musoftware') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_comm_agency') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_comm_freelance') }}</div>
            </div>

            <!-- Row 2: Code Ownership -->
            <div class="grid grid-cols-1 md:grid-cols-4 p-5 font-mono text-xs border-b border-[#222222] gap-4 items-center bg-black/30">
                <div class="text-white font-bold">{{ __('landing_home.comp_row_code_title') }}</div>
                <div class="text-[#748660] font-bold">{{ __('landing_home.comp_row_code_musoftware') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_code_agency') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_code_freelance') }}</div>
            </div>

            <!-- Row 3: Architecture -->
            <div class="grid grid-cols-1 md:grid-cols-4 p-5 font-mono text-xs gap-4 items-center">
                <div class="text-white font-bold">{{ __('landing_home.comp_row_quality_title') }}</div>
                <div class="text-[#748660] font-bold">{{ __('landing_home.comp_row_quality_musoftware') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_quality_agency') }}</div>
                <div class="text-zinc-400">{{ __('landing_home.comp_row_quality_freelance') }}</div>
            </div>
        </div>

    </div>
</section>


<!-- 6. FINAL CALL TO ACTION (START PROJECT OR CHAT) -->
<section class="py-24 px-6 sm:px-12 bg-gradient-to-b from-[#111111] to-[#0A0A0A] border-t border-[#222222]">
    <div class="max-w-4xl mx-auto text-center space-y-8">
        <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
            {{ __('landing_home.cta_badge') }}
        </span>
        <h2 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_home.cta_title') }}
        </h2>
        <p class="text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_home.cta_desc') }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 font-mono text-xs">
            <a href="/start-project" class="w-full sm:w-auto px-8 py-4 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-widest transition-all">
                {{ __('landing_home.cta_start_wizard') }}
            </a>
            <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20a%20new%20system." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-8 py-4 border border-[#333333] hover:border-white text-zinc-300 hover:text-white font-bold uppercase tracking-widest transition-all">
                {{ __('landing_home.cta_whatsapp') }}
            </a>
        </div>
    </div>
</section>

<!-- Interactive Scripts for Hero Slider & Estimator -->
<script>
    let currentSlideIdx = 0;
    const totalSlides = 3;

    function showSlide(idx) {
        currentSlideIdx = (idx + totalSlides) % totalSlides;
        
        // Slides
        document.querySelectorAll('.hero-slide').forEach((slide, i) => {
            if (i === currentSlideIdx) {
                slide.classList.remove('opacity-0', 'pointer-events-none');
                slide.classList.add('opacity-100');
            } else {
                slide.classList.add('opacity-0', 'pointer-events-none');
                slide.classList.remove('opacity-100');
            }
        });

        // Banner Texts
        document.querySelectorAll('.banner-text-item').forEach((item, i) => {
            if (i === currentSlideIdx) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });

        document.getElementById('slide-counter').innerText = `${currentSlideIdx + 1} / ${totalSlides}`;
    }

    function nextSlide() { showSlide(currentSlideIdx + 1); }
    function prevSlide() { showSlide(currentSlideIdx - 1); }

    // Auto-advance hero banner every 6 seconds
    setInterval(nextSlide, 6000);
</script>

@endsection
