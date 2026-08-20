@extends('layouts.public')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
<div class="w-full bg-[#ffffff] text-[#1d1d1f] pt-16 sm:pt-24 pb-28">
    
    <!-- Hero Header -->
    <div class="max-w-[1024px] mx-auto px-6 text-center space-y-4 mb-16 apple-reveal">
        <span class="text-[14px] font-semibold text-[#86868b] tracking-tight block">
            {{ $locale === 'ar' ? 'البنية التحتية والأنظمة السحابية' : 'PLATFORM ARCHITECTURE' }}
        </span>
        <h1 class="text-4xl sm:text-6xl font-bold tracking-tight text-[#1d1d1f] apple-headline leading-tight">
            {{ $locale === 'ar' ? 'منصات سحابية مخصصة. مصممة للسرعة والأمان.' : 'Bespoke Cloud Platforms. Engineered for Speed.' }}
        </h1>
        <p class="text-lg sm:text-xl text-[#86868b] max-w-2xl mx-auto apple-subhead">
            {{ $locale === 'ar' 
                ? 'أنظمة متكاملة تجمع بين تطبيقات الويب، تطبيقات الهواتف الذكية، وأتمتة العمليات بدون وسطاء.'
                : 'Full-stack software platforms combining web applications, native mobile apps, and direct API automation engines.' }}
        </p>

        <div class="flex items-center justify-center gap-4 pt-4">
            <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[14px] px-6 py-2.5 shadow-sm">
                {{ $locale === 'ar' ? 'بدء معالج المواصفات' : 'Start Project Wizard' }}
            </a>
            <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[14px] px-6 py-2.5">
                {{ $locale === 'ar' ? 'استشارة واتساب ➔' : 'WhatsApp Consultation ➔' }}
            </a>
        </div>
    </div>

    <!-- Platforms Bento Grid -->
    <div class="max-w-[1140px] mx-auto px-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Platform 1: Custom Web & Mobile Ecosystems -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[420px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#0071e3] uppercase tracking-wider block">01 / WEB & MOBILE APPS</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'تطبيقات الويب والموبايل التفاعلية' : 'Modern Web & Mobile Apps' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' 
                            ? 'واجهات مستخدم سريعة وفائقة الاستجابة، متزامنة مع تطبيقات هواتف iOS و Android وقواعد بيانات سحابية موثوقة.'
                            : 'Sub-50ms React/Inertia interfaces with synchronized mobile companion apps and resilient cloud databases.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/#web-mobile" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استكشاف الحلول' : 'Explore Platform' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

            <!-- Platform 2: WhatsApp Meta Automation -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[420px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#34c759] uppercase tracking-wider block">02 / WHATSAPP AUTOMATION</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'محرك محادثات وأتمتة واتساب' : 'WhatsApp Cloud API Engines' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' 
                            ? 'إدارة محادثات العملاء وتأكيد الطلبات وحجز المواعيد تلقائياً عبر واجهة Meta Graph API الرسمية.'
                            : 'Multi-agent customer support, automated transactional notifications, and calendar bookings via official Meta APIs.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/portfolio/trenz-whatscrm" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استعراض دراسة Trenz' : 'View Trenz Case Study' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

            <!-- Platform 3: FinTech & POS Systems -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[420px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#ff9500] uppercase tracking-wider block">03 / FINTECH & RETAIL</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'أنظمة نقاط البيع والحسابات' : 'FinTech & POS Cash Terminals' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' 
                            ? 'معالجة فورية للمبيعات، طباعة الباركود والفواتير الحرارية، ومطابقة عهد الكاشير وحسابات الأرباح.'
                            : 'High-speed barcode checkout, thermal ESC/POS printing, and real-time cashflow audits.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/portfolio/chartcash" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استعراض دراسة ChartCash' : 'View ChartCash Case Study' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

            <!-- Platform 4: Multi-Vendor & Dropshipping -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[420px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#af52de] uppercase tracking-wider block">04 / E-COMMERCE CORE</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'منصات التجارة والدروب شيبنج' : 'Dropshipping & Logistics' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' 
                            ? 'إدارة المنتجات المجمعة، شحن البوالص، ومحفظة العمولات الحية لآلاف المسوقين بالعمولة.'
                            : 'Affiliate commission wallets, courier manifest tracking, and bulk SKU inventory synchronization.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/portfolio/kbdny" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استعراض دراسة كبدني' : 'View Kbdny Case Study' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
