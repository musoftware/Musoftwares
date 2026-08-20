@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <!-- Architectural Schema & Problem Statement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'التجارة الإلكترونية والدروب شيبنج الموسع' : 'Enterprise Dropshipping & Multi-Vendor Engine' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'بناء بيئة عمل رقمية تربط آلاف المسوقين بالموردين والشحن' : 'Connecting Thousands of Affiliates, Suppliers, and Couriers into One Backbone' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'منصة كبدني (Kbdny) هي منظومة تجارة إلكترونية متكاملة للدروب شيبنج والتسويق بالعمولة. صُممت بنظام متعدد الأدوار يتيح للموردين إدراج منتجاتهم، وللمسوقين إنشاء طلباتهم وتتبع عمولاتهم، ولغرف العمليات تجهيز كشوفات الشحن المجمعة ومطابقة الحسابات المالية بدقة متناهية.'
                    : 'Kbdny is a high-volume dropshipping and affiliate ecosystem engineered with multi-role isolation (Vendors, Marketers, Warehouse Operators, Logistics Couriers). It automates SKU cataloging, commission calculations, multi-stage delivery lifecycles, and bulk shipping manifest exports.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات المنظومة اللوجستية' : 'FULFILLMENT KERNEL SPECS' }}</span>
                <span class="text-zinc-500">10K+ ORDERS/MO</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'بوابات منفصلة للمسوقين، الموردين، والمخازن' : 'Role-gated dashboards for Marketers, Vendors, & Warehouses.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تتبع حالات الطلبات (تسليم كامل، تسليم جزئي، مرتجع معلق)' : 'Multi-state order lifecycle with partial delivery support.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تصدير واستيراد كشوفات الشحن لشركات التوصيل (Excel / Sheets)' : 'Automated courier manifest exports and tracking imports.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'محفظة مالية رقمية لحساب وسحب العمولات فور التسليم' : 'Instant digital wallet settlements upon confirmed delivery.' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Core Modules Grid -->
    <div class="space-y-6">
        <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660] block">
            {{ $locale === 'ar' ? 'أعمدة المنظومة التشغيلية' : 'Operational Pillars' }}
        </span>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '01 / كتالوج المنتجات و SKU' : '01 / SKU & Inventory Catalog' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'توليد تلقائي لأكواد الـ SKU، ومزامنة أرصدة المخازن بين الموردين والمسوقين.' : 'Dynamic SKU generation, multi-variant tracking, and auto-syncing stock across vendors.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '02 / محرك الطلبات والشحن' : '02 / Shipping Manifest Engine' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'معالجة مجمعة لآلاف الطلبات يومياً وإصدار بوالص الشحن وتتبع خط السير.' : 'Bulk batch processing for thousands of orders daily with automated airway bill generation.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '03 / المحفظة والعمولات' : '03 / Affiliate Payout Wallet' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'حساب العمولات اللحظية، طلبات سحب الأرباح عبر إنستاباي وفودافون كاش.' : 'Real-time commission calculation with automated withdrawal requests and accounting.' }}
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
