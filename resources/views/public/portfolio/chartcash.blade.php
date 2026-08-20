@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <!-- Architectural Schema & Problem Statement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'الذكاء المالي والتحليلات اللحظية' : 'Financial Intelligence & Telemetry' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'رؤية مالية متكاملة للتدفقات النقدية ومعدلات حرق السيولة' : 'Consolidated Cashflow Visibility & Predictive Burn Rate Analysis' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'صُممت منصة ChartCash لتمنح الرؤساء التنفيذيين والمديرين الماليين لوحة تحكم حية تعكس صافي التدفق النقدي، هوامش الربح لكل منتج وخدمة، ومؤشرات الأداء الرئيسية (KPIs) دون الحاجة لانتظار إقفال الشهر المحاسبي، مع معالجة آلاف الحركات في أجزاء من الثانية.'
                    : 'ChartCash was built to provide executive leadership and CFOs with instant, transparent metrics: net cash flow velocity, profit margins per service tier, and automated runaway projections without relying on manual batch calculations.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات محرك التحليلات' : 'BI TELEMETRY SPECS' }}</span>
                <span class="text-zinc-500">SUB-10MS AGGREGATION</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'معالجة وتجميع البيانات المالية عبر WebSockets و Redis' : 'Real-time financial streaming via WebSockets & Redis caching.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تتبع القيمة الدائمة للعميل (LTV) وتكلفة الاستحواذ (CAC)' : 'Automated cohort analysis for LTV and CAC tracking.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'خرائط حرارية تفاعلية لمعدلات الشراء والإيرادات الشهرية' : 'Interactive revenue heatmaps and cash runaway forecasts.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تصدير تقارير تنفيذية بصيغ PDF و Excel بضغطة زر واحدة' : 'Instant executive PDF & spreadsheet report generation.' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Core Modules Grid -->
    <div class="space-y-6">
        <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660] block">
            {{ $locale === 'ar' ? 'محاور لوحة القيادة الذكية' : 'Executive Dashboard Modules' }}
        </span>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '01 / نبض السيولة النقدية' : '01 / Cash Pulse Engine' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'تتبع لحظي لأرصدة الخزائن والبنوك والمحافظ الرقمية مع توقعات السيولة لـ 90 يوماً.' : 'Real-time bank and digital wallet tracking with 90-day liquidity forecasting.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '02 / تحليل هوامش الربحية' : '02 / Margin Diagnostics' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'حساب هامش الربح الصافي بعد خصم المصروفات التشغيلية والعمولات المباشرة.' : 'Net profit margin diagnostics factoring COGS and transaction overheads.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '03 / تنبيهات الشذوذ المالي' : '03 / Anomaly Alerts' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'خوارزميات ذكية ترصد أي قفزات غير اعتيادية في المصروفات أو تراجع مفاجئ في المبيعات.' : 'Statistical anomaly detection flagging unusual expense surges or sales drops.' }}
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
