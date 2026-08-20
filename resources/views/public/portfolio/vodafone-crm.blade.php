@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'إدارة التوزيع والعمليات الميدانية' : 'Telecom Distribution & Field Operations' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'تتبع ملايين الأرقام التسلسلية وإدارة عهد المناديب' : 'Tracking 50,000+ SIM Card Serials with Real-Time Field Commission Engine' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'نظام مخصص لأحد أكبر موزعي خدمات الاتصالات في مصر، يتولى إدارة دورة حياة شرائح الخطوط وبطاقات الشحن من لحظة خروجها من المخزن الرئيسي وتوزيعها على خطوط سير المناديب وحتى بيعها وتفعيلها في المحلات مع حساب العمولات تلقائياً.'
                    : 'A bespoke operational CRM and field force automation platform built for a premier telecom master distributor. It oversees thousands of SIM card serial inventories, representative route schedules, cash collections, and tiered sales incentives.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات نظام التوزيع' : 'DISTRIBUTION SPECS' }}</span>
                <span class="text-zinc-500">REAL-TIME SERIAL AUDIT</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'فحص ومطابقة الأرقام التسلسلية بالباركود الشريطي المجمع' : 'Bulk barcode range scanning for instant carton intake.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تحديد خطوط سير المناديب اليومية ومتابعة تحصيل العهد النقدية' : 'Representative daily route sheets with digital custody sign-off.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'احتساب شرائح العمولات آلياً بناءً على الأهداف وحجم التفعيل' : 'Automated tiered commission matrix tied to activation volume.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'مطابقة أرصدة نهاية اليوم ومنع أي تسريب أو فروقات في النقدية' : 'End-of-day cash reconciliation auditing every single sold serial.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
