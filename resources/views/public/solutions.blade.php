@extends('layouts.public')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
<div class="w-full bg-[#ffffff] text-[#1d1d1f] pt-16 sm:pt-24 pb-28">
    
    <!-- Hero Header -->
    <div class="max-w-[1024px] mx-auto px-6 text-center space-y-4 mb-16 apple-reveal">
        <span class="text-[14px] font-semibold text-[#86868b] tracking-tight block">
            {{ $locale === 'ar' ? 'الحلول القطاعية المتخصصة' : 'Industry Solutions' }}
        </span>
        <h1 class="text-4xl sm:text-6xl font-bold tracking-tight text-[#1d1d1f] apple-headline leading-tight">
            {{ $locale === 'ar' ? 'حلول برمجية مخصصة لكل قطاع تشغيلي.' : 'Bespoke Engineering for High-Growth Sectors.' }}
        </h1>
        <p class="text-lg sm:text-xl text-[#86868b] max-w-2xl mx-auto apple-subhead">
            {{ $locale === 'ar' ? 'بنية تقنية موثوقة مصممة لتلبية متطلبات التجارة، الرعاية الصحية، التوزيع اللوجستي، والخدمات المالية.' : 'Purpose-built software architectures solving domain friction in commerce, healthcare, logistics, and financial ops.' }}
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

    <!-- Solutions Bento Grid -->
    <div class="max-w-[1140px] mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Solution 1: Healthcare & Clinics -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[380px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#34c759] uppercase tracking-wider block">01 / HEALTHCARE</span>
                    <h2 class="text-2xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'العيادات والمراكز الطبية' : 'Clinical Ops & Telehealth' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' ? 'إدارة السجلات الطبية للمرضى (EMR)، حجز المواعيد الآلي عبر واتساب، والفوترة الضريبية.' : 'Electronic Medical Records (EMR), automated WhatsApp booking, and compliance billing.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/start-project" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'طلب النظام' : 'Request Architecture' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

            <!-- Solution 2: E-Commerce & Dropshipping -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[380px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#0071e3] uppercase tracking-wider block">02 / ECOMMERCE</span>
                    <h2 class="text-2xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'التجارة والدروب شيبنج' : 'Dropshipping & Supply' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' ? 'منظومات متعددة التجار، كشوفات شحن البوالص المجمعة، ومحفظة العمولات الفورية.' : 'Multi-vendor catalogs, bulk courier manifest engines, and instant affiliate commission payouts.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/portfolio/kbdny" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استعراض دراسة كبدني' : 'View Kbdny Case Study' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

            <!-- Solution 3: FinTech & POS -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] p-8 sm:p-10 flex flex-col justify-between apple-bento-card min-h-[380px] apple-reveal">
                <div class="space-y-3">
                    <span class="text-[12px] font-semibold text-[#ff9500] uppercase tracking-wider block">03 / FINTECH & RETAIL</span>
                    <h2 class="text-2xl font-bold text-[#1d1d1f] tracking-tight">
                        {{ $locale === 'ar' ? 'نقاط البيع وسلاسل التجزئة' : 'Retail & Cash Terminals' }}
                    </h2>
                    <p class="text-sm text-[#86868b] leading-relaxed">
                        {{ $locale === 'ar' ? 'محطات كاشير بالباركود والطباعة الحرارية، مطابقة عهد النقدية، ومراقبة المخازن.' : 'Sub-50ms barcode checkout, ESC/POS thermal printing, and multi-branch stock audits.' }}
                    </p>
                </div>
                <div class="pt-6">
                    <a href="/portfolio/chartcash" class="apple-link-cta">
                        <span>{{ $locale === 'ar' ? 'استعراض دراسة ChartCash' : 'View ChartCash Case Study' }}</span> <span>›</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
