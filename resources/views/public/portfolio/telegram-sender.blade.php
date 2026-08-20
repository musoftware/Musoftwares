@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'البث والنشر الفوري تيليجرام MTProto' : 'Telegram MTProto High-Speed Broadcaster' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'نشر فوري عبر بروتوكول MTProto الأصلي لآلاف القنوات والمجموعات' : 'Native MTProto Broadcast Architecture with Multi-Account Session Management' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'أداة سطح مكتب فائقة السرعة تستخدم بروتوكول تيليجرام الأصلي MTProto للبث والنشر المجدول للإعلانات والإشارات في مئات القنوات والمجموعات بالتزامن، مع دعم الأزرار الشفافة التفاعلية، استخراج الأعضاء النشطين، وإدارة جلسات متعددة بدون تسجيل خروج.'
                    : 'Telegram Sender is a specialized desktop automation tool communicating over binary MTProto. It enables community managers and marketers to broadcast announcements across hundreds of channels, extract active discussion members, and schedule rich formatted messages with inline buttons.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات محرك تيليجرام' : 'MTPROTO SPECS' }}</span>
                <span class="text-zinc-500">BINARY DISPATCH</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'سرعة بث قياسية عبر الاتصال الثنائي المباشر بخوادم تيليجرام' : 'Direct binary MTProto connection with zero HTTP overhead.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'إدارة جلسات متعددة وتشغيل حسابات لا محدودة بأمان تام' : 'Unlimited multi-session account switcher without re-authenticating.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'استخراج أعضاء المجموعات النشطين وتصديرهم لقوائم مستهدفة' : 'Active group participant extractor with online status filtering.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'دعم الأزرار الشفافة التفاعلية والوسائط وتثبيت الرسائل التلقائي' : 'Inline URL button support, photo/video captions, and pin triggers.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
