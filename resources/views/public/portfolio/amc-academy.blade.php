@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-20 pt-14 border-t border-black/[0.08] space-y-16">

    <!-- Section 1: AMC Academy Overview & Founder Vision -->
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#0071e3]"></span>
            <span class="text-[13px] font-bold text-[#0071e3] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'نبذة عن الشريك ورؤية التأسيس' : 'Partner Overview & Foundation' }}
            </span>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight leading-snug">
                    {{ $locale === 'ar' 
                        ? 'شركة AMC Academy: قيادة التحول في التسويق الرقمي والأتمتة الذكية' 
                        : 'AMC Academy: Pioneering AI-Driven Marketing & Automation' }}
                </h2>
                <p class="text-[15px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'تُعد شركة AMC Academy إحدى أبرز الشركات المصرية المتخصصة في التسويق الرقمي (Digital Marketing)، البرمجيات السحابية، التدريب المتقدم، وتطوير الأعمال والاستشارات التسويقية. تأسست الشركة على يد رائد الأعمال وخبير التسويق الرقمي المصري "أحمد ماهر"، بهدف تمكين أصحاب الأعمال والمسوقين في مصر والوطن العربي عبر حلول تسويقية متطورة تعتمد على الأتمتة (Automation) والذكاء الاصطناعي لتحقيق قفزات في المبيعات بأقل تكلفة ومجهود تشغيلي.'
                        : 'AMC Academy is a premier digital marketing, software development, training, and business consultancy firm founded by Egyptian entrepreneur and digital marketing veteran Ahmed Maher. The academy empowers businesses and marketers across the MENA region by building advanced automation workflows and AI-driven solutions that maximize sales conversion while dramatically slashing manual labor and operational overhead.' }}
                </p>
                <div class="flex items-center gap-4 pt-2">
                    <div class="flex items-center gap-3 bg-[#f5f5f7] border border-black/[0.06] rounded-2xl px-4 py-2.5">
                        <img src="/images/avatars/ahmed_maher.jpg" alt="Ahmed Maher" class="w-10 h-10 rounded-full object-cover">
                        <div>
                            <span class="text-[12px] text-[#86868b] block font-medium">{{ $locale === 'ar' ? 'المؤسس ورئيس مجلس الإدارة:' : 'Founder & Managing Director:' }}</span>
                            <span class="text-[14px] font-bold text-[#1d1d1f] block">{{ $locale === 'ar' ? 'أحمد ماهر' : 'Ahmed Maher' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 bg-[#f5f5f7] rounded-3xl border border-black/[0.06] p-6 sm:p-8 space-y-5">
                <div class="flex items-center justify-between border-b border-black/[0.06] pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#0071e3]">
                        {{ $locale === 'ar' ? 'الأهداف الاستراتيجية للمنظومة' : 'Core Strategic Mission' }}
                    </span>
                    <span class="text-[11px] text-[#86868b] font-medium">ROI & AUTOMATION</span>
                </div>
                <div class="space-y-3.5 text-[13px] text-[#1d1d1f]">
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#0071e3] font-bold">✓</span>
                        <span>{{ $locale === 'ar' ? 'أتمتة عمليات الرد والمبيعات بالكامل عبر الشات بوت والذكاء الاصطناعي.' : 'Full automation of customer inquiries and lead triage via AI chatbots.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#0071e3] font-bold">✓</span>
                        <span>{{ $locale === 'ar' ? 'خفض نفقات التوظيف وخدمة العملاء بنسب تتجاوز 70% عبر أنظمة الاستجابة الفورية.' : 'Slashing customer service payroll overhead by over 70% with 24/7 instant responders.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#0071e3] font-bold">✓</span>
                        <span>{{ $locale === 'ar' ? 'إدارة متعددة القنوات (Omnichannel) من لوحة تحكم سحابية موحدة وموثوقة.' : 'Unified multi-channel dashboard eliminating cross-platform context switching.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#0071e3] font-bold">✓</span>
                        <span>{{ $locale === 'ar' ? 'توفير أدوات نمو متكاملة للمحتوى، الـ SEO، وبناء الروابط الخلفية للمواقع.' : 'End-to-end growth suite encompassing content synthesis, SEO, and backlink engines.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Flagship Product Deep-Dive (AMCTasks.com) -->
    <div class="bg-[#f8f9fc] rounded-3xl border border-[#0071e3]/15 p-8 sm:p-12 space-y-8">
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#0071e3]/10 text-[#0071e3] rounded-full text-[12px] font-bold uppercase tracking-wider">
                AMCTasks.com • Deep-Dive
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                {{ $locale === 'ar' ? 'تقرير مفصل: منصة AMCTasks.com لإدارة السوشيال ميديا وأتمتة الردود' : 'AMCTasks.com: Flagship Multi-Channel Marketing & Chatbot Platform' }}
            </h3>
            <p class="text-[14px] sm:text-[15px] text-[#515154] leading-relaxed max-w-3xl">
                {{ $locale === 'ar'
                    ? 'يُعد AMCTasks.com المنتج الأقوى والأشمل ضمن حزمة شركة AMC، وهو عبارة عن منصة تسويقية (Marketing System) متكاملة تتيح للمسوقين وأصحاب الشركات التحكم الكامل في كافة أصولهم الرقمية من مكان واحد.'
                    : 'AMCTasks.com represents the pinnacle of AMC Academy software offerings—a robust, high-availability marketing automation platform enabling businesses to orchestrate their entire social presence and conversational bots under one roof.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-black/[0.06] p-5 space-y-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center font-bold text-sm">
                    01
                </div>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">
                    {{ $locale === 'ar' ? 'إدارة القنوات الموحدة' : 'Unified Omnichannel Hub' }}
                </h4>
                <p class="text-[13px] text-[#86868b] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'إدارة حسابات فيسبوك، تويتر (X)، إنستجرام، تليجرام، فايبر، وشبكة VK الروسية من لوحة تحكم واحدة متكاملة.'
                        : 'Single control panel for Facebook, Instagram, Twitter (X), Telegram, Viber, and VK without logging into multiple tools.' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-black/[0.06] p-5 space-y-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center font-bold text-sm">
                    02
                </div>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">
                    {{ $locale === 'ar' ? 'روبوتات الرد الذكية 24/7' : '24/7 Intelligent Chatbots' }}
                </h4>
                <p class="text-[13px] text-[#86868b] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'برمجة شات بوت ذكي للرد الفوري على تعليقات المنشورات ورسائل الخاص على مدار الساعة دون أي انقطاع.'
                        : 'Automated conversational bots responding instantly to post comments and private inboxes 24 hours a day, 7 days a week.' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-black/[0.06] p-5 space-y-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center font-bold text-sm">
                    03
                </div>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">
                    {{ $locale === 'ar' ? 'تقليص تكاليف التشغيل' : 'Operating Cost Reduction' }}
                </h4>
                <p class="text-[13px] text-[#86868b] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'يقوم النظام بدور خدمة العملاء في الردود الأساسية وتأهيل العملاء، مما يوفر نفقات تعيين فرق دعم ضخمة.'
                        : 'Performs first-line customer service and lead qualification automatically, saving companies major hiring and staffing costs.' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-black/[0.06] p-5 space-y-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center font-bold text-sm">
                    04
                </div>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">
                    {{ $locale === 'ar' ? 'استقرار وموثوقية معتمدة' : 'Proven Track Record & Trust' }}
                </h4>
                <p class="text-[13px] text-[#86868b] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'مشروع مستقر يعمل بنجاح منذ سنوات عديدة، مع تصنيف أمان كامل (Legit) على منصات فحص الأمان كـ ScamAdviser.'
                        : 'Active and stable in production for multiple years with verified legitimacy ratings across global security auditors.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Section 3: The Complete Software Ecosystem of AMC Academy -->
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#0071e3]"></span>
            <span class="text-[13px] font-bold text-[#0071e3] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'منظومة البرامج والأدوات التابعة لـ AMC' : 'The AMC Software & Tool Ecosystem' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Suite 1: Social & Turbo -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.06] p-6 space-y-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0071e3] block">
                    {{ $locale === 'ar' ? 'أنظمة الأتمتة والسوشيال' : 'Automation & Chatbot Suites' }}
                </span>
                <h4 class="text-lg font-bold text-[#1d1d1f]">AMC Turbo & AMC Social</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'منظومة متكاملة لإدارة الحسابات على فيسبوك وتليجرام وواتساب، وبناء سيناريوهات الرد التلقائي، وجدولة البوستات، والتحكم في الرسائل الجماعية بكفاءة عالية.'
                        : 'Specialized desktop and cloud systems for high-speed multi-account management on Facebook, Telegram, and WhatsApp with custom automated response logic.' }}
                </p>
            </div>

            <!-- Suite 2: SEO & Content Engine -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.06] p-6 space-y-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0071e3] block">
                    {{ $locale === 'ar' ? 'أدوات الـ SEO والمحتوى' : 'Content & SEO Growth Engine' }}
                </span>
                <h4 class="text-lg font-bold text-[#1d1d1f]">Article Maker & Backlinks Suite</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'حزمة أدوات متقدمة تضم AMC Article Maker لصناعة المقالات التسويقية، وبرنامج AMC Backlinks لبناء الروابط الخلفية للمواقع، وأداة AMC لتحليل محتوى فيسبوك وتحديد أفضل البوستات تفاعلاً.'
                        : 'Proprietary SEO suite comprising AMC Article Maker for rapid content authoring, AMC Backlinks automation, and Facebook content analytics for viral engagement discovery.' }}
                </p>
            </div>

            <!-- Suite 3: Satellite Platforms -->
            <div class="bg-[#f5f5f7] rounded-3xl border border-black/[0.06] p-6 space-y-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0071e3] block">
                    {{ $locale === 'ar' ? 'المنصات التابعة والشريكة' : 'Ecosystem Sister Platforms' }}
                </span>
                <h4 class="text-lg font-bold text-[#1d1d1f]">Kbdny • Whatscontact • Salesup</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'تكامل البنية التحتية مع منصات رائدة تابعة تشمل Kbdny.com للتجارة والدروبشيبينغ، Whatscontact.com لخدمات واتساب للأعمال، و Salesup.me لإدارة المبيعات وقنوات التحويل.'
                        : 'Synergistic ecosystem integrations powering brother platforms including Kbdny.com (dropshipping), Whatscontact.com (WhatsApp CRM), and Salesup.me (sales enablement).' }}
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
