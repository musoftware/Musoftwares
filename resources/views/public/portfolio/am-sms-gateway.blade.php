@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'بوابة الرسائل القصيرة OTP' : 'Android Hardware SMS Telecommunication Bridge' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'تحويل هواتف الأندرويد العادية إلى بوابة رسائل SMS مؤسسية' : 'Zero-Markup Automated SMS & OTP Dispatch via Local Hardware Bridge' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'جسر اتصالات متطور يربط الخوادم السحابية بتطبيقات أندرويد تعمل في الخلفية على أجهزة متصلة بشرائح اتصالات محلية، لإرسال رسائل التحقق (OTP) وتنبيهات المعاملات تلقائياً عبر واجهة REST API مع إشعارات تسليم حية (Webhooks).'
                    : 'AM SMS Gateway is a dual-layer hardware bridge allowing web applications to dispatch transactional and OTP SMS messages directly through local Android SIM cards. It eliminates exorbitant third-party aggregator markups while offering real-time delivery webhooks and multi-SIM load balancing.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات الجسر البرمجي' : 'SMS BRIDGE SPECS' }}</span>
                <span class="text-zinc-500">ZERO GATEWAY MARKUP</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'واجهة REST API سهلة الربط مع أي موقع أو تطبيق أو نظام ERP' : 'Standard REST API endpoints for instant server-to-phone dispatch.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تقارير تسليم حية (Webhooks) تؤكد استلام العميل للرسالة' : 'Real-time delivery status webhooks verifying handset receipt.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'توزيع ذكي للرسائل بين شرائح الاتصال المتعددة (Dual SIM)' : 'Multi-SIM automatic load balancing and quota management.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'طابور محلي غير متزامن يضمن إعادة الإرسال عند انقطاع الشبكة' : 'Offline resilient queue guaranteeing retry upon network recovery.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
