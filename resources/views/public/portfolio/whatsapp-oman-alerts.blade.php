@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-20 pt-14 border-t border-black/[0.08] space-y-16">

    {{-- Section 1: The Problem --}}
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#25D366]"></span>
            <span class="text-[13px] font-bold text-[#25D366] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'المشكلة والتحديات' : 'The Challenge' }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight leading-snug">
                    {{ $locale === 'ar'
                        ? 'شركة ناشئة في عُمان: كيف تتواصل مع العملاء في اللحظة المناسبة وبشكل رسمي؟'
                        : 'An Oman-Based Startup: How to Reach Customers at the Right Moment, Officially?' }}
                </h2>
                <p class="text-[15px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'كانت الشركة تبني أنظمة تقنية متطورة في السوق العُماني، لكنها واجهت تحدياً محورياً: الإشعارات الآنية للعملاء والموظفين كانت تصلهم عبر رسائل SMS قديمة بسيطة لا تتضمن أي طابع مرئي للشركة، أو عبر إيميلات لا يفتحها أحد. لم تكن هناك قناة تواصل فورية وموثوقة ورسمية تحمل هوية الشركة وتضمن وصول الرسالة وقراءتها. علاوة على ذلك، كانت الشركة تحتاج إلى إطلاق حملات إعلانية رسمية عبر واتساب بطريقة مرخصة ومعتمدة دون المخاطرة بحظر الأرقام.'
                        : 'The company was building advanced technical systems for the Omani market, but faced a core challenge: real-time notifications to clients and staff arrived via plain SMS with no brand identity, or via emails nobody opened. There was no instant, reliable, and official communication channel carrying the company identity and guaranteeing delivery. On top of that, they needed to launch official WhatsApp advertising campaigns in a licensed, approved way without risking phone number bans.' }}
                </p>
            </div>

            <div class="lg:col-span-5 bg-[#f0fdf4] rounded-3xl border border-[#25D366]/20 p-6 sm:p-8 space-y-5">
                <div class="flex items-center justify-between border-b border-[#25D366]/15 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#25D366]">
                        {{ $locale === 'ar' ? 'ثلاثة تحديات رئيسية' : 'Three Core Problems' }}
                    </span>
                    <span class="text-[11px] text-[#86868b] font-medium">WHATSAPP BUSINESS API</span>
                </div>
                <div class="space-y-3.5 text-[13px] text-[#1d1d1f]">
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#dc2626] font-bold mt-0.5">X</span>
                        <span>{{ $locale === 'ar' ? 'غياب قناة تواصل فوري ترتدي هوية الشركة وتضمن وصول الرسالة.' : 'No branded real-time communication channel that guarantees message delivery.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#dc2626] font-bold mt-0.5">X</span>
                        <span>{{ $locale === 'ar' ? 'إشعارات تشغيلية مبعثرة: SMS قديم لا يُقرأ وإيميل لا يُفتح.' : 'Fragmented operational alerts: unread SMS and ignored email notifications.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#dc2626] font-bold mt-0.5">X</span>
                        <span>{{ $locale === 'ar' ? 'خطر حظر الأرقام عند إرسال حملات واتساب جماعية بطرق غير رسمية.' : 'Risk of number bans when sending bulk WhatsApp campaigns through unofficial methods.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Two-System Solution --}}
    <div class="bg-[#f0fdf4] rounded-3xl border border-[#25D366]/15 p-8 sm:p-12 space-y-8">
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#25D366]/10 text-[#16a34a] rounded-full text-[12px] font-bold uppercase tracking-wider">
                {{ $locale === 'ar' ? 'الحل المُنفَّذ • منظومتان متكاملتان' : 'The Solution • Two Integrated Systems' }}
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                {{ $locale === 'ar'
                    ? 'نظام تنبيهات ذكي + منصة حملات إعلانية رسمية عبر واتساب'
                    : 'Intelligent Alert Engine + Official WhatsApp Campaign Platform' }}
            </h3>
            <p class="text-[15px] text-[#515154] leading-relaxed max-w-3xl">
                {{ $locale === 'ar'
                    ? 'صممنا وطورنا منظومتين برمجيتين متكاملتين: الأولى نظام تنبيهات ذكي يُطلق رسائل واتساب آنية مرتبطة بأحداث النظام الداخلي، والثانية منصة لإدارة وإطلاق حملات واتساب رسمية ومعتمدة عبر WhatsApp Business API بما يضمن صفر حظر وأعلى معدلات التسليم.'
                    : 'We designed and built two integrated systems: first, an intelligent alert engine that fires automatic real-time WhatsApp messages triggered by internal system events; second, a platform for managing and launching official, approved WhatsApp advertising campaigns via the WhatsApp Business API, ensuring zero number bans and maximum delivery rates.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-black/[0.06] p-6 space-y-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: rgba(37,211,102,0.1)">
                        <svg class="w-5 h-5" style="color:#25D366;stroke:#25D366" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider block" style="color:#25D366">
                            {{ $locale === 'ar' ? 'النظام الأول' : 'System 01' }}
                        </span>
                        <span class="text-[15px] font-bold text-[#1d1d1f]">
                            {{ $locale === 'ar' ? 'محرك التنبيهات الذكي' : 'Intelligent Alert Engine' }}
                        </span>
                    </div>
                </div>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'نظام مرتبط مباشرة بالأحداث الداخلية للمنصة يطلق رسائل واتساب آنية ومخصصة لكل حالة: تأكيد طلب، إشعار دفع، تحديث حالة، أو تنبيه أمني. يدعم القوالب الديناميكية والمتغيرات الشخصية.'
                        : 'A system directly wired to internal platform events that fires personalized, real-time WhatsApp messages for each scenario: order confirmation, payment notification, status update, or security alert. Supports dynamic templates and personal variables.' }}
                </p>
                <div class="space-y-2 pt-2 border-t border-black/[0.06]">
                    @foreach([
                        ['ar' => 'تشغيل تلقائي مرتبط بأحداث الباك-إند', 'en' => 'Event-driven auto-trigger from backend hooks'],
                        ['ar' => 'قوالب رسائل ديناميكية بمتغيرات شخصية', 'en' => 'Dynamic message templates with personal variables'],
                        ['ar' => 'إرسال فوري بأقل من ثانية واحدة من حدوث الحدث', 'en' => 'Sub-second delivery from event occurrence'],
                        ['ar' => 'لوحة تحكم لمراقبة حالة الإرسال والأخطاء', 'en' => 'Admin panel for delivery status and error monitoring'],
                    ] as $item)
                        <div class="flex items-center gap-2 text-[12px] text-[#1d1d1f]">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color:#25D366"></span>
                            <span>{{ $locale === 'ar' ? $item['ar'] : $item['en'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-black/[0.06] p-6 space-y-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: rgba(0,113,227,0.1)">
                        <svg class="w-5 h-5" style="stroke:#0071e3" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider block" style="color:#0071e3">
                            {{ $locale === 'ar' ? 'النظام الثاني' : 'System 02' }}
                        </span>
                        <span class="text-[15px] font-bold text-[#1d1d1f]">
                            {{ $locale === 'ar' ? 'منصة الحملات الإعلانية الرسمية' : 'Official Ad Campaign Platform' }}
                        </span>
                    </div>
                </div>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'منصة متكاملة لإدارة وإطلاق حملات إعلانية واتساب معتمدة بالكامل عبر WhatsApp Business API الرسمي، مع لوحة تحليلات تُظهر معدلات التسليم والفتح والتفاعل لكل حملة.'
                        : 'A full platform for managing and launching WhatsApp advertising campaigns fully approved through the official WhatsApp Business API, with an analytics dashboard showing delivery, open, and engagement rates per campaign.' }}
                </p>
                <div class="space-y-2 pt-2 border-t border-black/[0.06]">
                    @foreach([
                        ['ar' => 'تكامل رسمي مع WhatsApp Business API المعتمد', 'en' => 'Official WhatsApp Business API integration'],
                        ['ar' => 'إدارة القوالب المعتمدة وإرسالها للمراجعة تلقائياً', 'en' => 'Approved template management with auto-submission'],
                        ['ar' => 'استهداف جماهير مخصصة مع جدولة إطلاق الحملات', 'en' => 'Custom audience targeting with campaign scheduling'],
                        ['ar' => 'تقارير لحظية: معدل التسليم والفتح والنقرات', 'en' => 'Real-time reports: delivery, opens, and click-through rates'],
                    ] as $item)
                        <div class="flex items-center gap-2 text-[12px] text-[#1d1d1f]">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color:#0071e3"></span>
                            <span>{{ $locale === 'ar' ? $item['ar'] : $item['en'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3: Technical Architecture --}}
    <div class="space-y-8">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#0071e3]"></span>
            <span class="text-[13px] font-bold text-[#0071e3] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'البنية التقنية' : 'Technical Architecture' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $archItems = [
                    [
                        'label_en' => 'Event-Driven Core',
                        'label_ar' => 'نواة مدفوعة بالأحداث',
                        'desc_en' => 'Laravel event/listener system fires WhatsApp messages the instant a backend state changes — zero polling, zero delay.',
                        'desc_ar' => 'نظام الأحداث والمستمعين في Laravel يطلق رسائل واتساب فور تغيير حالة الباك-إند — بدون polling وبدون تأخير.',
                        'color' => '#25D366',
                    ],
                    [
                        'label_en' => 'Official WhatsApp Business API',
                        'label_ar' => 'WhatsApp Business API الرسمي',
                        'desc_en' => 'All messages sent via the Meta-approved Business API, eliminating number ban risk and guaranteeing deliverability.',
                        'desc_ar' => 'جميع الرسائل ترسل عبر API الرسمي المعتمد من Meta، مما يلغي خطر حظر الأرقام ويضمن أعلى معدلات الوصول.',
                        'color' => '#0071e3',
                    ],
                    [
                        'label_en' => 'Queue & Retry Engine',
                        'label_ar' => 'محرك القوائم وإعادة المحاولة',
                        'desc_en' => 'Failed deliveries are automatically queued, retried with exponential backoff, and fully logged for diagnosis.',
                        'desc_ar' => 'حالات الفشل تُدرج تلقائياً في قوائم الانتظار وتُعاد محاولتها بنظام Exponential Backoff مع تسجيل كامل للأخطاء.',
                        'color' => '#7c3aed',
                    ],
                ];
            @endphp
            @foreach($archItems as $item)
                <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.05] p-6 space-y-3">
                    <div class="w-8 h-1 rounded-full" style="background-color: {{ $item['color'] }};"></div>
                    <h4 class="text-[14px] font-bold text-[#1d1d1f]">
                        {{ $locale === 'ar' ? $item['label_ar'] : $item['label_en'] }}
                    </h4>
                    <p class="text-[13px] text-[#515154] leading-relaxed">
                        {{ $locale === 'ar' ? $item['desc_ar'] : $item['desc_en'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 4: Results --}}
    <div class="rounded-3xl overflow-hidden border border-black/[0.07]">
        <div class="bg-[#0a1628] px-8 sm:px-12 py-8 space-y-2">
            <span class="text-[12px] font-bold uppercase tracking-wider" style="color:#25D366">
                {{ $locale === 'ar' ? 'النتائج والتأثير الفعلي' : 'Results & Business Impact' }}
            </span>
            <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                {{ $locale === 'ar'
                    ? 'من إشعارات لا يراها أحد إلى رسائل يُقرأ 98% منها'
                    : 'From Ignored Notifications to a 98% Read Rate' }}
            </h3>
        </div>

        <div class="bg-white px-8 sm:px-12 py-8">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                @foreach([
                    ['val' => '98%', 'key_en' => 'Message Read Rate', 'key_ar' => 'معدل قراءة الرسائل'],
                    ['val' => '< 1s', 'key_en' => 'Alert Delivery Time', 'key_ar' => 'وقت وصول التنبيه'],
                    ['val' => '0', 'key_en' => 'Numbers Banned', 'key_ar' => 'أرقام محظورة'],
                    ['val' => '94%', 'key_en' => 'Campaign Delivery Rate', 'key_ar' => 'معدل تسليم الحملات'],
                ] as $metric)
                    <div class="bg-[#f5f5f7] rounded-2xl p-4 text-center space-y-1 border border-black/[0.05]">
                        <span class="text-[28px] font-bold text-[#1d1d1f] block">{{ $metric['val'] }}</span>
                        <span class="text-[11px] text-[#86868b] uppercase tracking-wider font-medium block">
                            {{ $locale === 'ar' ? $metric['key_ar'] : $metric['key_en'] }}
                        </span>
                    </div>
                @endforeach
            </div>

            <p class="text-[14px] text-[#515154] leading-relaxed max-w-3xl">
                {{ $locale === 'ar'
                    ? 'انتقلت الشركة من بيئة تواصل مبعثرة وغير موثوقة إلى منظومة واتساب رسمية ومتكاملة تمثل الآن العمود الفقري لكل تواصل مع العملاء — سواء للإشعارات التشغيلية الآنية أو حملات التسويق الرسمية.'
                    : 'The company moved from a fragmented, unreliable communication environment to an official, integrated WhatsApp system that now forms the backbone of all client communication — both real-time operational alerts and official marketing campaigns.' }}
            </p>
        </div>
    </div>

</div>
@endsection