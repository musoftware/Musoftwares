@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'إدارة المخازن وسرعة نقاط البيع' : 'High-Throughput Retail & Inventory Architecture' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'معالجة فواتير الكاشير بسرعة فائقة وتتبع فوري للنواقص' : 'Sub-50ms Barcode Scanning with Automated Stock Reorder Triggers' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'نظام StockManager صُمم خصيصاً لسلاسل التجزئة والمحلات التجارية، حيث يتيح للكاشير البيع الفوري عبر الباركود واختصارات لوحة المفاتيح دون لمس الفأرة، مع خصم المخزون لحظياً وتنبيه مسؤولي المشتريات عند وصول أي صنف لحد الأمان.'
                    : 'StockManager is a rapid inventory & POS suite optimized for high-volume retail environments. It provides keyboard-driven cashier workflows, ESC/POS thermal printing, multi-warehouse stock replenishment, and automatic low-stock purchase order generation.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات محرك نقاط البيع' : 'POS KERNEL SPECS' }}</span>
                <span class="text-zinc-500">ESC/POS READY</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'دعم كافة قارئات الباركود وطابعات الإيصالات الحرارية' : 'Universal barcode scanner & ESC/POS printer driver support.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'إدارة الشيفتات وأدراج النقدية ومطابقة العجز والزيادة' : 'Shift opening/closing balance reconciliation with discrepancy audits.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تحويلات مخزنية مؤكدة بين الفروع برقم إذن صرف واستلام' : 'Inter-branch stock transfer manifests with verification checkpoints.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تقارير أرباح يومية وأصناف راكدة والأعلى مبيعاً' : 'Daily gross profit reports, dead stock detection, and top performers.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
