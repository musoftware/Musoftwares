@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'البنية التحتية للحملات البريدية' : 'High-Deliverability Email Infrastructure' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'تدوير خوادم الـ SMTP والوصول لصندوق الوارد الرئيسي بضمان 99%+' : 'Dynamic SMTP Pool Rotation, DKIM Verification, and Zero-Pixel Telemetry' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'منصة سحابية متقدمة لإرسال مئات الآلاف من الرسائل الإخبارية والتنبيهات يومياً، مع تدوير ذكي لعناوين الـ IP وخوادم الـ SMTP، وفحص توقيعات DKIM و SPF، ومعالجة تلقائية للرسائل المرتدة لضمان أعلى معدلات وصول لصندوق الوارد وتجنب مجلد السبام.'
                    : 'Email Sender is a high-velocity campaign dispatch engine built on Laravel and Redis queue pipelines. It implements multi-server IP rotation, automated bounce cleaning, DKIM cryptographic authentication, and granular telemetry on subscriber engagement.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات التسليم البريدي' : 'DELIVERABILITY SPECS' }}</span>
                <span class="text-zinc-500">500K+ EMAILS/DAY</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تدوير خوادم الـ SMTP وتوزيع الأحمال لمنع حظر الدومين' : 'Automated SMTP IP pool rotation distributing throughput.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تتبع لحظي لمعدلات فتح الرسائل والنقر على الروابط والمواقع' : 'Real-time telemetry measuring open rates, clicks, and geo-data.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'معالجة الرسائل المرتدة تلقائياً وتنظيف القوائم البريدية' : 'Automated bounce and spam complaint suppression list management.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'محرر رسائل تفاعلي متوافق مع كافة برامج وتطبيقات البريد' : 'Responsive HTML template builder tested across all email clients.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
