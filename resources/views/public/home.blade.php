@extends('layouts.public')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
<style>
  .apple-blur { backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); }
  @keyframes pulse { 50% { opacity: .5; } }
  .animate-pulse { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
</style>

<div class="min-h-screen bg-white text-[#1d1d1f] selection:bg-[#0071e3]/10 antialiased" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', Inter, Helvetica, Arial, sans-serif;">

    <div class="flex flex-col gap-3 p-3 bg-white max-w-[1920px] mx-auto">
        
        <!-- ============================================================ -->
        <!-- 1. HERO SECTION (Code. Ship. Scale.) -->
        <!-- ============================================================ -->
        <section class="rounded-[18px] bg-[#f5f5f7] pt-14 md:pt-[72px] pb-10 md:pb-14 overflow-hidden">
            <div class="max-w-[980px] mx-auto px-6 text-center">
                <h1 class="text-[40px] md:text-[56px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    Code. Ship. Scale.
                </h1>
                <p class="mt-4 text-[19px] md:text-[21px] leading-[1.25] tracking-[-0.01em] text-[#1d1d1f]/80 max-w-[620px] mx-auto">
                    Web Apps. Mobile Apps. Desktop Systems. Built to Perfection.
                </p>
                <div class="mt-7 flex items-center justify-center gap-3">
                    <a href="/start-project" class="inline-flex items-center justify-center rounded-[980px] bg-[#0071e3] text-white text-[17px] font-normal px-[22px] h-[36px] hover:bg-[#0077ed] active:bg-[#006edb] transition shadow-sm">
                        View Capabilities
                    </a>
                </div>

                <!-- Hero Platforms Flat Vector Illustration -->
                <div class="mt-10 md:mt-14 max-w-[980px] mx-auto px-4">
                    <div class="rounded-[24px] bg-white shadow-[0_20px_60px_rgba(0,0,0,0.08)] border border-black/5 overflow-hidden p-2 sm:p-4">
                        <img src="/images/illustrations/hero_platforms.jpg" alt="Web Apps, Mobile Apps, Desktop Systems" class="w-full h-auto object-cover object-center rounded-[18px]">
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 2. FULL-STACK SAAS & WEB DEVELOPMENT (Full-Width Light) -->
        <!-- ============================================================ -->
        <section id="web" class="rounded-[18px] bg-[#f5f5f7] pt-12 md:pt-[84px] overflow-hidden">
            <div class="text-center px-6 max-w-[720px] mx-auto">
                <div class="text-[12px] font-semibold tracking-[0.08em] text-[#6e6e73] uppercase">
                    Web Development
                </div>
                <h2 class="mt-2 text-[32px] md:text-[48px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    Full-Stack SaaS.
                </h2>
                <p class="mt-3 text-[17px] md:text-[19px] leading-[1.3] text-[#1d1d1f]/70">
                    React, Next.js, dashboards that scale to millions.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <a href="/start-project" class="rounded-[980px] bg-[#0071e3] text-white text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0077ed] transition">
                        Learn more
                    </a>
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] border border-[#0071e3] text-[#0071e3] text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0071e3]/5 transition">
                        View Stack &gt;
                    </a>
            </div>

            <!-- Full-Width Web Dashboard UI Window -->
            <div class="mt-10 md:mt-12 mx-auto max-w-[840px] px-4 md:px-6">
                <div class="rounded-t-[18px] bg-white shadow-[0_20px_80px_rgba(0,0,0,0.1)] border border-black/5 border-b-0 overflow-hidden p-1.5 sm:p-2.5 pb-0">
                    <img src="/images/illustrations/saas_dashboard.jpg" alt="Full-Stack SaaS Development" class="w-full h-auto object-cover object-top rounded-t-[12px] block">
                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 3. NATIVE MOBILE APPS (Full-Width Pastel Gradient) -->
        <!-- ============================================================ -->
        <section id="mobile" class="rounded-[18px] pt-12 md:pt-[84px] overflow-hidden" style="background: linear-gradient(180deg, #e6f0ff 0%, #f5f5f7 70%);">
            <div class="text-center px-6 max-w-[720px] mx-auto">
                <div class="text-[12px] font-semibold tracking-[0.08em] text-[#6e6e73] uppercase">
                    Mobile Apps
                </div>
                <h2 class="mt-2 text-[32px] md:text-[48px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    iOS &amp; Android.<br>Native Performance.
                </h2>
                <p class="mt-3 text-[17px] md:text-[19px] leading-[1.3] text-[#1d1d1f]/70">
                    Flutter, React Native, Swift. One codebase, two stores.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <a href="/start-project" class="rounded-[980px] bg-[#0071e3] text-white text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0077ed] transition">
                        Learn more
                    </a>
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] border border-[#0071e3] text-[#0071e3] text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-white/60 transition">
                        View Stack &gt;
                    </a>
                </div>
            </div>

            <!-- Native Smartphone Illustration -->
            <div class="mt-8 md:mt-10 mx-auto max-w-[480px] px-4 pb-8 md:pb-12 flex justify-center">
                <div class="rounded-[22px] overflow-hidden shadow-[0_15px_40px_rgba(0,0,0,0.08)] border border-black/5 bg-white p-2">
                    <img src="/images/illustrations/mobile_delivery_app.jpg" alt="iOS & Android Mobile Applications" class="w-full h-auto object-contain rounded-[16px] block">
                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 4. 2-COLUMN BENTO GRID (Cards) -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Bento 1 (Light): Desktop Applications -->
            <div id="desktop" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="flex justify-center gap-2 mb-3 text-[12px]">
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">Windows</span>
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">macOS</span>
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">Linux</span>
                    </div>
                    <h3 class="text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Desktop Applications
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Native desktop software, offline-first databases, and hardware control.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/stock-manager" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">View demos &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_40px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/desktop_computers.jpg" alt="Desktop Applications for Windows, macOS and Linux" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

            <!-- Bento 2 (Light): Cloud Architecture & High Performance Systems -->
            <div id="enterprise" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#30d158] animate-pulse"></span> LIVE TELEMETRY
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Cloud Infrastructure
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        High-throughput APIs, sub-10ms telemetry, and real-time sync.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/chartcash" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Architecture &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/cloud_infrastructure.jpg" alt="Cloud Infrastructure & Real-Time Telemetry" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

            <!-- Bento 3 (Pastel Blue): WhatsApp Cloud CRM -->
            <div id="cloud" class="rounded-[18px] bg-[#e8f0fb] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#1d1d1f]">
                        <span class="w-5 h-5 rounded-full bg-[#25D366] flex items-center justify-center text-white text-[10px]">W</span> Official Meta API
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        WhatsApp Cloud CRM
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Official Meta Graph API. Multi-agent inbox.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/trenz-whatscrm" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">See inbox &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_30px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/whatsapp_crm.jpg" alt="WhatsApp Cloud CRM Meta Official API" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

            <!-- Bento 4 (Light): AI & Automation -->
            <div id="ai" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3]"></span> GPT-4o &amp; LLAMA 3
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        AI &amp; Automation
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Arabic dialects understanding. Grounded database queries.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/stocktalk-ai" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Try prompt &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/ai_automation.jpg" alt="AI & Automation Arabic Understanding" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

            <!-- Bento 5 (Light): E-Commerce & Supply -->
            <div id="ecommerce" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="text-[12px] font-semibold text-[#0071e3] uppercase">5,000+ AFFILIATES</div>
                    <h3 class="mt-2 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        E-commerce &amp; Supply
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Multi-vendor catalogs, manifests, commission wallets.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/kbdny" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Case study &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_30px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/ecommerce_courier.jpg" alt="E-commerce & Supply Courier Manifests Bosta and Mylerz" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

            <!-- Bento 6 (Light): Telecom & SMS -->
            <div id="telecom" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#30d158]"></span> ZERO SMS COST
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Telecom &amp; SMS
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Turn local Android SIM cards into automated SMS gateways.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/am-sms-gateway" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Hardware setup &gt;</a>
                    </div>
                </div>
                <div class="mt-auto pt-8 mx-auto w-full max-w-[480px]">
                    <div class="rounded-t-[16px] bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] border border-black/5 border-b-0 overflow-hidden">
                        <img src="/images/illustrations/telecom_sms.jpg" alt="Telecom and Android SMS Gateway" class="w-full h-auto object-cover object-top block">
                    </div>
                </div>
            </div>

        </div>


        <!-- ============================================================ -->
        <!-- 5. CENTERED SHOWCASE GALLERY (Manual Navigation) -->
        <!-- ============================================================ -->
        <section id="portfolio-showcase" class="rounded-[28px] bg-[#f5f5f7] py-16 md:py-24 px-4 sm:px-6 md:px-12 overflow-hidden text-center">
            
            <!-- Gallery Title -->
            <div class="max-w-[800px] mx-auto mb-6">
                <h3 class="text-[36px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'معرض الأنظمة والمشاريع' : 'Gallery' }}
                </h3>
                <p class="mt-2 text-[16px] text-[#1d1d1f]/60 font-normal">
                    {{ $locale === 'ar' ? 'استعراض المنصات والأنظمة البرمجية المنفذة في بيئات الإنتاج الحية.' : 'High-impact platforms engineered for web, mobile, and desktop.' }}
                </p>
            </div>

            <!-- Top Filter Pill Container -->
            <div class="inline-flex p-1.5 rounded-full bg-[#e8e8ed] border border-black/5 mb-10 shadow-inner">
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 bg-white text-[#1d1d1f] shadow-sm" data-category="web">
                    {{ $locale === 'ar' ? 'تطبيقات الويب' : 'Web Apps' }}
                </button>
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 text-[#1d1d1f]/70 hover:text-[#1d1d1f]" data-category="mobile">
                    {{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Apps' }}
                </button>
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 text-[#1d1d1f]/70 hover:text-[#1d1d1f]" data-category="desktop">
                    {{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop Apps' }}
                </button>
            </div>

            <!-- Featured Stage with Side Floating Buttons -->
            <div class="max-w-[1024px] mx-auto relative flex items-center justify-center">
                
                <!-- Left Nav Arrow Button -->
                <button id="gallery-stage-prev" aria-label="Previous Project" class="absolute -left-2 sm:-left-6 md:-left-8 z-20 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/90 backdrop-blur-md border border-black/10 shadow-[0_4px_20px_rgba(0,0,0,0.08)] flex items-center justify-center text-[#1d1d1f] hover:bg-[#1d1d1f] hover:text-white transition duration-200">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Center Showcase Card Container -->
                <div class="w-full max-w-[880px] bg-white rounded-[24px] sm:rounded-[36px] border border-black/5 shadow-[0_20px_60px_rgba(0,0,0,0.08)] overflow-hidden transition-all duration-300 p-3 sm:p-5">
                    <div class="relative w-full aspect-[16/10] sm:aspect-[16/9] rounded-[18px] sm:rounded-[28px] overflow-hidden bg-[#f0f0f2] flex items-center justify-center">
                        <img id="gallery-stage-img" src="/images/portfolio/kbdny.png" alt="Kbdny Affiliate" class="w-full h-full object-cover object-top transition duration-500 transform hover:scale-[1.02]">
                        
                        <!-- Floating Category Badge -->
                        <div class="absolute top-4 start-4 px-3.5 py-1.5 rounded-full bg-white/90 backdrop-blur-md shadow-sm border border-black/5 text-[11px] font-semibold text-[#1d1d1f] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#30d158]"></span>
                            <span id="gallery-stage-badge">Web &amp; E-Commerce</span>
                        </div>
                    </div>
                </div>

                <!-- Right Nav Arrow Button -->
                <button id="gallery-stage-next" aria-label="Next Project" class="absolute -right-2 sm:-right-6 md:-right-8 z-20 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/90 backdrop-blur-md border border-black/10 shadow-[0_4px_20px_rgba(0,0,0,0.08)] flex items-center justify-center text-[#1d1d1f] hover:bg-[#1d1d1f] hover:text-white transition duration-200">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

            </div>

            <!-- Below Showcase Meta & Caption -->
            <div class="max-w-[680px] mx-auto mt-6 space-y-2">
                <h4 id="gallery-stage-title" class="text-[20px] sm:text-[24px] font-semibold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'منصة كبدني للتجارة والتسويق بالعمولة' : 'Kbdny Affiliate' }}
                </h4>
                <p id="gallery-stage-desc" class="text-[14px] text-[#1d1d1f]/70 leading-relaxed">
                    {{ $locale === 'ar' ? 'نظام متكامل واحترافي للتجارة الإلكترونية والدروبشيبينغ مع تتبع العمولات اللحظي.' : 'Multi-vendor affiliate platform with real-time commission tracking and payouts.' }}
                </p>
                <div class="pt-1 flex items-center justify-center gap-4 text-[13px]">
                    <span id="gallery-stage-metric" class="font-medium text-[#1d1d1f]/80">5,000+ Affiliates</span>
                    <span class="text-black/20">•</span>
                    <a id="gallery-stage-link" href="/portfolio/kbdny" class="font-semibold text-[#0071e3] hover:underline inline-flex items-center gap-1">
                        {{ $locale === 'ar' ? 'عرض تفاصيل النظام >' : 'View case study >' }}
                    </a>
                </div>
            </div>

            <!-- Pagination Dots / Active Bar -->
            <div id="gallery-pagination" class="flex items-center justify-center gap-2 mt-6">
                <!-- Injected via JavaScript -->
            </div>

        </section>

    </div>

</div>

<!-- Centered Gallery Navigation Logic (NO AUTO-MOVE - 100% REAL DATA) -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isArabic = '{{ $locale }}' === 'ar';

        const galleryData = {
            web: [
                {
                    title_en: "Kbdny Affiliate",
                    title_ar: "منصة كبدني للتجارة والتسويق بالعمولة",
                    badge: "Web Platform",
                    desc_en: "Multi-vendor affiliate platform with real-time commission tracking and payouts.",
                    desc_ar: "نظام متكامل واحترافي للتجارة الإلكترونية والدروبشيبينغ مع تتبع العمولات اللحظي.",
                    img: "/images/portfolio/kbdny.png",
                    slug: "kbdny",
                    metric: "5,000+ Affiliates"
                },
                {
                    title_en: "Mini Fatora",
                    title_ar: "منصة Mini Fatora للفوترة السريعة وإدارة الفواتير",
                    badge: "Web SaaS",
                    desc_en: "Online invoicing and billing SaaS for freelancers and small businesses.",
                    desc_ar: "منصة فوترة سحابية خفيفة وسريعة للمستقلين والشركات الناشئة.",
                    img: "/images/portfolio/minifatora.png",
                    slug: "mini-fatora",
                    metric: "Instant PDF Invoicing"
                },
                {
                    title_en: "Trenz whatsCRM",
                    title_ar: "منصة Trenz whatsCRM لإدارة المحادثات والحجوزات",
                    badge: "Web Platform",
                    desc_en: "WhatsApp CRM and appointment scheduling platform designed for agencies.",
                    desc_ar: "منصة سحابية متكاملة لخدمة العملاء عبر واتساب وجدولة المواعيد.",
                    img: "/images/portfolio/trenz-whatscrm.png",
                    slug: "trenz-whatscrm",
                    metric: "Official Meta Cloud API"
                },
                {
                    title_en: "AMC Academy",
                    title_ar: "منصة AMC Academy للتعليم والتدريب الرقمي المشفر",
                    badge: "Web E-Learning",
                    desc_en: "Full e-learning platform with student portals, scheduling, and assessments.",
                    desc_ar: "منصة تعليمية متكاملة تدعم حماية الفيديو من التسجيل والعلامات المائية.",
                    img: "/images/portfolio/amcacademy.jpg",
                    slug: "amc-academy",
                    metric: "DRM Video Protection"
                },
                {
                    title_en: "Vodafone CRM",
                    title_ar: "نظام إدارة الموزعين والعمليات الميدانية لـ Vodafone",
                    badge: "Web Operations",
                    desc_en: "Custom CRM and operations management system for Vodafone Egypt distributor.",
                    desc_ar: "نظام مؤسسي متقدم لإدارة شبكة الموزعين والمناديب الميدانيين لخدمات الاتصالات.",
                    img: "/images/portfolio/vodafone-crm.jpg",
                    slug: "vodafone-crm",
                    metric: "50k+ SIM Records"
                },
                {
                    title_en: "Telecom System",
                    title_ar: "بوابة شحن وإدارة خدمات الاتصالات والإنترنت B2B",
                    badge: "Web Gateway",
                    desc_en: "B2B recharge and ISP management platform with automated billing.",
                    desc_ar: "منصة خدمات الاتصالات والشحن الفوري للشركات وإدارة الاشتراكات.",
                    img: "/images/portfolio/telecom-system.png",
                    slug: "telecom-system",
                    metric: "< 1.2s API Response"
                },
                {
                    title_en: "Altayaraa",
                    title_ar: "منصة التجارة الإلكترونية السريعة الطيارة (Altayaraa)",
                    badge: "Web E-Commerce",
                    desc_en: "Arabic e-commerce and product listing platform with vendor management.",
                    desc_ar: "متجر إلكتروني فائق السرعة مصمم للشراء الفوري ومزامنة المخازن.",
                    img: "/images/portfolio/altayaraa.png",
                    slug: "altayaraa",
                    metric: "Single-Page Checkout"
                },
                {
                    title_en: "Project Manager",
                    title_ar: "منصة إدارة المشاريع والمهام الهندسية Project Manager",
                    badge: "Web Platform",
                    desc_en: "Internal project and team management tool with task boards and timelines.",
                    desc_ar: "بيئة عمل متكاملة لإدارة المشاريع البرمجية والهندسية ولوحات كانبان.",
                    img: "/images/portfolio/projectmanager.png",
                    slug: "project-manager",
                    metric: "Sub-Second Timers"
                }
            ],
            mobile: [
                {
                    title_en: "Nokhpa",
                    title_ar: "تطبيق النخبة للتجارة الإلكترونية والتسوق الفاخر",
                    badge: "Mobile App (iOS / Android)",
                    desc_en: "E-commerce mobile app with native checkout, order tracking, and product filtering.",
                    desc_ar: "تطبيق تسوق إلكتروني متطور للمنتجات الفاخرة يدعم تتبع مسار المندوب بالـ GPS.",
                    img: "/images/portfolio/nokhpa.png",
                    slug: "nokhpa",
                    metric: "Native iOS / Android"
                },
                {
                    title_en: "Forex App",
                    title_ar: "تطبيق إشارات التداول وتحليل أسواق العملات Forex App",
                    badge: "Mobile App (Flutter)",
                    desc_en: "Mobile companion for algorithmic trading with market signals and alerts.",
                    desc_ar: "تطبيق موبايل مالي متخصص في إرسال إشارات التداول اللحظية وتنبيهات السوق.",
                    img: "/images/portfolio/forex-app.png",
                    slug: "forex-app",
                    metric: "< 150ms Push Alerts"
                },
                {
                    title_en: "Wallet App",
                    title_ar: "تطبيق المحفظة الرقمية وتحويل الأموال Wallet App",
                    badge: "Mobile App",
                    desc_en: "Digital currency wallet with real-time exchange, recharge, and transfer capabilities.",
                    desc_ar: "محفظة مالية رقمية على الهواتف الذكية مع أسعار صرف فورية وتحويلات وإعادة شحن.",
                    img: "/images/portfolio/wallet-app.png",
                    slug: "portfolio",
                    metric: "Real-Time Exchange"
                },
                {
                    title_en: "QCoin App",
                    title_ar: "تطبيق QCoin لمتابعة وإدارة الاستثمارات الرقمية",
                    badge: "Mobile App",
                    desc_en: "Crypto investment and tracking mobile app with portfolio management.",
                    desc_ar: "تطبيق جوال لمتابعة الأصول الرقمية وإدارة المحافظ الاستثمارية للمستخدمين.",
                    img: "/images/portfolio/qcoin-app.jpg",
                    slug: "portfolio",
                    metric: "Portfolio Tracker"
                },
                {
                    title_en: "AMC Social",
                    title_ar: "منصة النشر والتواصل الاجتماعي AMC Social",
                    badge: "Mobile App",
                    desc_en: "Internal social platform for AMC Academy students with posts and events.",
                    desc_ar: "تطبيق التواصل والتفاعل الاجتماعي للطلاب لمتابعة الأخبار والفعاليات.",
                    img: "/images/portfolio/amcsocial.png",
                    slug: "amc-social",
                    metric: "Student Community"
                }
            ],
            desktop: [
                {
                    title_en: "Stock Manager",
                    title_ar: "نظام Stock Manager لكاشير ونقاط البيع السريعة",
                    badge: "Desktop Application",
                    desc_en: "Inventory and POS system with multi-location support and KPI reporting.",
                    desc_ar: "برنامج كاشير ومخازن يعمل أوفلاين مع دعم طابعات الإيصالات الحرارية وقارئ الباركود.",
                    img: "/images/portfolio/stockmanager.png",
                    slug: "stock-manager",
                    metric: "< 50ms Barcode Scanning"
                },
                {
                    title_en: "WhatsApp Sender",
                    title_ar: "برنامج إرسال رسائل الواتساب المخصصة WhatsApp Sender",
                    badge: "Desktop Software",
                    desc_en: "Bulk WhatsApp messaging tool with scheduling, templates, and contact lists.",
                    desc_ar: "برنامج سطح مكتب لأتمتة إرسال رسائل الفواتير والتنبيهات المخصصة عبر واتساب.",
                    img: "/images/portfolio/whatsapp-sender.png",
                    slug: "whatsapp-sender",
                    metric: "Random Delay Engine"
                },
                {
                    title_en: "Telegram Sender",
                    title_ar: "برنامج البث والنشر الفوري على تيليجرام Telegram Sender",
                    badge: "Desktop Software",
                    desc_en: "Automated Telegram broadcast tool with group/channel targeting and scheduling.",
                    desc_ar: "برنامج بث ونشر فوري عبر بروتوكول MTProto لآلاف القنوات والمجموعات.",
                    img: "/images/portfolio/telegram-sender.png",
                    slug: "telegram-sender",
                    metric: "MTProto Protocol"
                },
                {
                    title_en: "Inbox Sender",
                    title_ar: "برنامج إرسال البريد الإلكتروني وتدوير الـ SMTP",
                    badge: "Desktop Software",
                    desc_en: "Email bulk sending system with SMTP rotation and delivery rate optimization.",
                    desc_ar: "منظومة إرسال رسائل بريدية مع تدوير ذكي لعناوين الـ IP وخوادم الـ SMTP.",
                    img: "/images/portfolio/inbox-sender.png",
                    slug: "email-sender",
                    metric: "SMTP Pool Rotation"
                },
                {
                    title_en: "ChartCash",
                    title_ar: "منصة التحليلات المالية ولوحات القيادة ChartCash",
                    badge: "Desktop & Analytics",
                    desc_en: "Financial analytics dashboard with real-time charts, KPIs, and P&L tracking.",
                    desc_ar: "منصة ذكاء أعمال وتحليلات مالية متقدمة توفر مؤشرات التدفق النقدي وهوامش الربحية.",
                    img: "/images/portfolio/chartcash.png",
                    slug: "chartcash",
                    metric: "Real-Time Telemetry"
                },
                {
                    title_en: "StockTalk AI",
                    title_ar: "محرك الذكاء الاصطناعي وخدمة العملاء StockTalk AI",
                    badge: "Desktop / Server AI",
                    desc_en: "WhatsApp-based AI customer support agent for automated stock and order queries.",
                    desc_ar: "ربط الذكاء الاصطناعي بقاعدة بيانات المخازن الحية للرد الفوري على العملاء.",
                    img: "/images/portfolio/stocktalk.png",
                    slug: "stocktalk-ai",
                    metric: "ERP RAG Grounding"
                },
                {
                    title_en: "Forex Bot",
                    title_ar: "روبوت التداول الخوارزمي الآلي Forex Bot",
                    badge: "Desktop / VPS Bot",
                    desc_en: "Algorithmic trading bot with adaptive strategy, signal processing, and execution.",
                    desc_ar: "محرك تداول آلي خوارزمي متصل بمنصات التداول لتنفيذ الصفقات وإدارة المخاطر.",
                    img: "/images/portfolio/forex.png",
                    slug: "forex-bot",
                    metric: "Adaptive Execution"
                },
                {
                    title_en: "Duplicate Finder",
                    title_ar: "برنامج البحث عن الملفات المكررة Duplicate Finder",
                    badge: "Windows Application",
                    desc_en: "File indexing and duplicate detection for Windows. Fast scan, SHA comparison.",
                    desc_ar: "فهرسة الملفات واكتشاف الملفات المكررة للويندوز مع فحص سريع ومقارنة SHA.",
                    img: "/images/portfolio/duplicate-finder.jpg",
                    slug: "portfolio",
                    metric: "Fast SHA Scan"
                },
                {
                    title_en: "Map Extractor",
                    title_ar: "أداة استخراج بيانات الشركات والعملاء Map Extractor",
                    badge: "Windows Application",
                    desc_en: "Business leads extraction tool with filtering and export capabilities.",
                    desc_ar: "أداة استخراج العملاء المحتملين والشركات مع الفلترة والتصدير لإكسيل.",
                    img: "/images/portfolio/map-extractor.jpg",
                    slug: "portfolio",
                    metric: "Direct Excel Export"
                },
                {
                    title_en: "Instagram Manager",
                    title_ar: "برنامج إدارة وجدولة حسابات انستجرام Instagram Manager",
                    badge: "Windows Application",
                    desc_en: "Desktop automation suite for account management and content scheduling.",
                    desc_ar: "أتمتة إدارة الحسابات وجدولة المحتوى ونشر المنشورات على انستجرام.",
                    img: "/images/portfolio/instagram-manager.png",
                    slug: "portfolio",
                    metric: "Automated Scheduling"
                },
                {
                    title_en: "HEIC Converter",
                    title_ar: "أداة تحويل صور HEIC لـ JPG/PNG للويندوز",
                    badge: "Windows Application",
                    desc_en: "Batch HEIC to JPG/PNG converter for Windows with drag-and-drop interface.",
                    desc_ar: "تحويل مجمع لصور الآيفون بصيغة HEIC إلى JPG/PNG بالسحب والإفلات.",
                    img: "/images/portfolio/heic-converter.png",
                    slug: "portfolio",
                    metric: "Batch Conversion"
                }
            ]
        };

        let currentCategory = 'web';
        let currentIndex = 0;

        const imgEl = document.getElementById('gallery-stage-img');
        const badgeEl = document.getElementById('gallery-stage-badge');
        const titleEl = document.getElementById('gallery-stage-title');
        const descEl = document.getElementById('gallery-stage-desc');
        const metricEl = document.getElementById('gallery-stage-metric');
        const linkEl = document.getElementById('gallery-stage-link');
        const paginationEl = document.getElementById('gallery-pagination');
        const prevBtn = document.getElementById('gallery-stage-prev');
        const nextBtn = document.getElementById('gallery-stage-next');
        const categoryPills = document.querySelectorAll('.gallery-category-pill');

        function renderSlide(index) {
            const list = galleryData[currentCategory];
            if (!list || list.length === 0) return;

            currentIndex = (index + list.length) % list.length;
            const item = list[currentIndex];

            // Smooth crossfade effect
            imgEl.style.opacity = '0';
            setTimeout(() => {
                imgEl.src = item.img;
                imgEl.alt = item.title_en;
                imgEl.style.opacity = '1';
            }, 150);

            badgeEl.textContent = item.badge;
            titleEl.textContent = isArabic ? item.title_ar : item.title_en;
            descEl.textContent = isArabic ? item.desc_ar : item.desc_en;
            metricEl.textContent = item.metric;
            linkEl.href = item.slug === 'portfolio' ? '/portfolio' : '/portfolio/' + item.slug;

            renderPagination(list.length, currentIndex);
        }

        function renderPagination(total, current) {
            paginationEl.innerHTML = '';
            for (let i = 0; i < total; i++) {
                const dot = document.createElement('button');
                dot.setAttribute('aria-label', `Slide ${i + 1}`);
                if (i === current) {
                    dot.className = 'h-2 w-8 rounded-full bg-[#1d1d1f] transition-all duration-300';
                } else {
                    dot.className = 'h-2 w-2 rounded-full bg-[#1d1d1f]/25 hover:bg-[#1d1d1f]/50 transition-all duration-200';
                }
                dot.addEventListener('click', () => {
                    renderSlide(i);
                });
                paginationEl.appendChild(dot);
            }
        }

        // Arrow Controls
        prevBtn?.addEventListener('click', () => {
            renderSlide(currentIndex - 1);
        });

        nextBtn?.addEventListener('click', () => {
            renderSlide(currentIndex + 1);
        });

        // Category Switcher
        categoryPills.forEach(pill => {
            pill.addEventListener('click', () => {
                const cat = pill.getAttribute('data-category');
                if (cat === currentCategory) return;

                categoryPills.forEach(p => {
                    p.classList.remove('bg-white', 'text-[#1d1d1f]', 'shadow-sm');
                    p.classList.add('text-[#1d1d1f]/70');
                });

                pill.classList.remove('text-[#1d1d1f]/70');
                pill.classList.add('bg-white', 'text-[#1d1d1f]', 'shadow-sm');

                currentCategory = cat;
                currentIndex = 0;
                renderSlide(0);
            });
        });

        // Initial Render
        renderSlide(0);
    });
</script>
@endsection
