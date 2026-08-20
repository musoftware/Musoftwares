@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'أتمتة الرسائل والتسويق المباشر' : 'Enterprise WhatsApp Campaign Infrastructure' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'إرسال مخصص آمن مع محاكاة السلوك البشري وفحص الأرقام' : 'Personalized Bulk Dispatch with Human-Like Delay Modulation' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'برنامج سطح مكتب قوي لأتمتة إرسال رسائل الفواتير والتنبيهات المخصصة عبر واتساب مباشرة من ملفات الإكسيل، مع خوارزميات ذكية لتغيير فترات التأخير الزمني لحماية الأرقام وفلترة الأرقام غير المسجلة على واتساب قبل البدء.'
                    : 'WhatsApp Sender is a desktop-grade communication engine designed for businesses sending transactional notices and verified customer outreach. It features a random delay engine imitating human typing, bulk Excel column variable merging, and pre-flight WhatsApp number validation.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات الأتمتة المكتبية' : 'DESKTOP AUTOMATION SPECS' }}</span>
                <span class="text-zinc-500">RANDOM CADENCE</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'دمج تلقائي لبيانات الإكسيل (الاسم، المبلغ، رقم الفاتورة) داخل الرسالة' : 'Dynamic Excel placeholder tags ({{Name}}, {{Amount}}, {{Invoice}}).' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'محرك تأخير عشوائي ذكي يحاكي الطباعة اليدوية ويحمي الأرقام من الحظر' : 'Stochastic delay engine preventing automated bot detection.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'فاحص مدمج للأرقام لاستبعاد الأرقام غير المشتركة في واتساب قبل الإرسال' : 'Built-in number filter validating WhatsApp registration before send.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'إرفاق الصور والمستندات وملفات الـ PDF ومقاطع الفيديو بنقرة واحدة' : 'Multi-media attachment support (PDF, Images, Video, Audio).' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
