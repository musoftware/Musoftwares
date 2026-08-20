@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <!-- Architectural Schema & Problem Statement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'أتمتة المحادثات وخدمة العملاء' : 'Conversational Infrastructure & Meta Cloud API' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'معالجة قنوات الواتساب لآلاف المحادثات في ثوانٍ معدودة' : 'Scaling Enterprise WhatsApp Pipelines to Handle 100k+ Inbound Messages' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'طورنا منصة Trenz whatsCRM لتمكين الشركات والعيادات والوكالات من إدارة المحادثات وحجز المواعيد وإرسال التنبيهات عبر واجهة Meta Cloud API الرسمية، مع صندوق وارد موحد متعدد الموظفين (Multi-Agent Inbox) وروبوتات رد تفاعلية تقلل الضغط على فريق الدعم بنسبة تتجاوز 70%.'
                    : 'Trenz whatsCRM was engineered to eliminate chaotic unorganized customer communication by integrating directly with official Meta Graph API webhooks. It delivers a multi-agent team inbox, automated appointment reminders, interactive button flows, and zero-drop message queues.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات محرك الواتساب' : 'META GRAPH ENGINE SPECS' }}</span>
                <span class="text-zinc-500">OFFICIAL CLOUD API</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'ربط رسمي كامل مع Meta Cloud API ومزامنة قوالب الرسائل' : 'Official Meta Graph API with real-time template sync.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'صندوق وارد موحد مع توزيع المحادثات والملاحظات الداخلية' : 'Unified multi-agent inbox with collision prevention.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'محرك حجز مواعيد ذكي يرسل تذكيرات تلقائية للعملاء' : 'Appointment booking engine with automated reminder webhooks.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'روبوتات رد تفاعلية بالأزرار والقوائم مع تحويل للوكلاء' : 'Interactive chatbot menus with smart human agent handoff.' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Core Modules Grid -->
    <div class="space-y-6">
        <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660] block">
            {{ $locale === 'ar' ? 'المكونات التشغيلية لمنصة Trenz' : 'Operational Pillars' }}
        </span>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '01 / صندوق الوارد الموحد' : '01 / Multi-Agent Inbox' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'محادثات فورية عبر WebSockets، تصنيف بالوسوم، وتعيين المحادثة للموظف المسؤول.' : 'Real-time chat streams, custom tag taxonomy, and dynamic lead assignment.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '02 / جدول المواعيد الذكي' : '02 / Booking Engine' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'حجز مواعيد متزامن مع تقويم العيادات والمراكز مع منع التعارض وإرسال تأكيد الحجز.' : 'Synchronized calendar booking preventing double-booking with instant confirmation.' }}
                </p>
            </div>
            <div class="bg-[#161616] border border-[#262626] p-6 space-y-3">
                <span class="text-xs font-mono font-bold text-white uppercase tracking-wider block">{{ $locale === 'ar' ? '03 / البث والتنبيهات المجدولة' : '03 / Broadcast Dispatcher' }}</span>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $locale === 'ar' ? 'إرسال حملات إعلانية وتنبيهات مخصصة بمتغيرات الأسماء والبيانات بأعلى موثوقية.' : 'High-deliverability broadcast campaigns with dynamic client variable tags.' }}
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
