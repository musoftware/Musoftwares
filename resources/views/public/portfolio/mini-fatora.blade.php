@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'الفوترة الإلكترونية السحابية' : 'Cloud Invoicing & Instant Client Portals' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'إصدار فواتير احترافية وسداد فوري بروابط مباشرة' : 'Instant PDF Generation, Payment Links, and Automated Reminder Dispatch' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'منصة Mini Fatora طُوّرت لخدمة المستقلين والشركات الصغيرة لإصدار عروض أسعار وفواتير ضريبية في ثوانٍ معدودة، ومشاركتها مع العملاء عبر واتساب والبريد الإلكتروني، مع إتاحة الدفع الإلكتروني المباشر ببطاقات الائتمان ومحافظ الهاتف.'
                    : 'Mini Fatora is an agile cloud invoicing SaaS engineered for freelancers and micro-agencies. It eliminates the friction of invoicing by producing branded PDF invoices in milliseconds and providing client payment links with automatic overdue reminder alerts.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات منصة الفوترة' : 'INVOICING ENGINE SPECS' }}</span>
                <span class="text-zinc-500">INSTANT PDF & PAY</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'توليد ملفات PDF الفاتورة في أقل من 300ms مع كود الـ QR الضريبي' : 'Sub-300ms PDF invoice compilation with cryptographic tax QR.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'بوابة دفع مخصصة للعميل تدعم فيزا وماستركارد ومحافظ الهاتف' : 'Client checkout portal supporting credit cards and mobile wallets.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تذكيرات دفع تلقائية ترسل عبر البريد الإلكتروني وواتساب قبل الاستحقاق' : 'Automated WhatsApp & email reminder triggers upon due date.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'دعم كامل للفواتير المتكررة والاشتراكات الشهرية والسنوية' : 'Recurring invoice schedules for retainer clients and subscriptions.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
