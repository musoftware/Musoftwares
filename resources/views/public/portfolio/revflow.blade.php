@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <!-- Architectural Schema & Problem Statement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'التحدي الهندسي وحجم المشكلة' : 'The Core Engineering Challenge' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'التحول من جداول الإكسيل إلى دفتر أستاذ متزامن بالكامل' : 'Migrating from Fragile Spreadsheets to a Provable General Ledger' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'كانت تعاني الشركات التجارية من تضارب أرصدة المخازن وفروقات القيود اليدوية وتأخر إصدار القوائم المالية لأسابيع. قمنا بتطوير محرك RevFlow ليربط كل حركة بيع، شراء، سداد، أو تحويل مخزني بقيد مزدوج (Double-Entry Debit/Credit) يُسجل آلياً في أجزاء من الثانية مع منع أي تعديل بأثر رجعي على الفترات المقفلة.'
                    : 'Wholesale and retail distributors often suffer from inventory discrepancies, manual accounting mismatches, and delayed financial closes. RevFlow solved this by strictly enforcing immutability: every business event automatically executes balanced debit/credit journal transactions within an ACID database transaction.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'محرك القيود التلقائي' : 'JOURNAL KERNEL SPECS' }}</span>
                <span class="text-zinc-500">ACID ISOLATION</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'توازن القيود الإجباري (Total Debit == Total Credit)' : 'Strict balancing assertion on every journal write.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'حساب تكلفة المخزون المرجح لحظياً عند كل حركة بيع' : 'Real-time Weighted Average Costing for inventory items.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'دعم الفوترة الإلكترونية متوافق مع هيئة الزكاة والضريبة ZATCA' : 'ZATCA Phase 2 cryptographic QR & XML invoice signing.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'توليد فوري لميزان المراجعة وقائمة الدخل والميزانية العمومية' : 'Zero-delay trial balance, balance sheet, and P&L.' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Core Modules Grid -->
    <div class="space-y-6">
        <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660] block">
            {{ $locale === 'ar' ? 'الوحدات والمكونات المعمارية' : 'System Architectural Modules' }}
        </span>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '01 / الحسابات العامة' : '01 / General Ledger' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'شجرة حسابات ديناميكية متعددة المستويات، مراكز تكلفة، وإقفال دوري محمي ضد التعديل.' : 'Dynamic multi-tier Chart of Accounts, cost center allocations, and tamper-evident period locks.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '02 / المخازن والمستودعات' : '02 / Warehouse Engine' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'إدارة المستودعات المتعددة، تتبع الصلاحيات والباركود، وأوامر التحويل والاستلام المؤكدة.' : 'Multi-warehouse stock movements, batch/expiry tracking, and digital receipt verification.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '03 / نقاط البيع والتحصيل' : '03 / Billing & Cashflow' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'إصدار فواتير ضريبية، تسوية مدفوعات العملاء، ومتابعة أعمار الديون والمطالبات.' : 'Tax invoices, partial client settlements, accounts receivable aging, and collections.' }}
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
