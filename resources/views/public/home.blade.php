@extends('layouts.public')

@section('content')

<!-- 1. CONVERSATIONAL HERO SECTION -->
<section class="w-full relative bg-black overflow-hidden border-b border-[#222222]">
    
    <!-- Hero Slider / Top Panorama -->
    <div class="relative w-full h-[360px] sm:h-[480px] lg:h-[520px] bg-zinc-950">
        <div id="hero-slider" class="w-full h-full relative">
            
            <!-- Slide 1: Programs & ERP -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-100" data-index="0">
                <img src="/images/hero/hero_erp.jpg" alt="Enterprise ERP Systems" class="w-full h-full object-cover brightness-[0.85] contrast-[1.1]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

            <!-- Slide 2: Websites & Apps -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-0 pointer-events-none" data-index="1">
                <img src="/images/hero/hero_fintech.jpg" alt="Websites & Apps" class="w-full h-full object-cover brightness-[0.85] contrast-[1.1]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

            <!-- Slide 3: WhatsApp Automation -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-700 opacity-0 pointer-events-none" data-index="2">
                <img src="/images/hero/hero_meta.jpg" alt="WhatsApp Automation" class="w-full h-full object-cover brightness-[0.85] contrast-[1.1]">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            </div>

        </div>
    </div>

    <!-- Sage Green Accent Bar (#748660) - Direct Conversational Banners -->
    <div class="w-full bg-[#748660] text-[#111111] py-8 px-6 sm:px-12 border-b border-[#5E6D4E]">
        <div class="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <!-- Banner Content Dynamic Switcher -->
            <div id="banner-text-container" class="space-y-1">
                
                <!-- Banner 1 -->
                <div class="banner-text-item" data-index="0">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ app()->getLocale() === 'ar' ? 'أنظمة وبرامج الشركات • ERP' : 'Enterprise Systems • Cloud ERP' }}
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ app()->getLocale() === 'ar' ? 'عايز تعمل برنامج يدير شركتك أو مصنعك بالكامل؟' : 'Need a custom system to run your entire enterprise?' }}
                    </h1>
                </div>

                <!-- Banner 2 -->
                <div class="banner-text-item hidden" data-index="1">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ app()->getLocale() === 'ar' ? 'مواقع ومتاجر وتطبيقات • Web & Mobile' : 'High-Scale Web & Mobile Apps' }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ app()->getLocale() === 'ar' ? 'عايز موقع أو متجر إلكتروني يشيل آلاف الطلبات والزوار؟' : 'Need a lightning-fast store or web platform?' }}
                    </h2>
                </div>

                <!-- Banner 3 -->
                <div class="banner-text-item hidden" data-index="2">
                    <div class="text-xs font-mono uppercase tracking-[0.2em] text-[#28321E] font-bold">
                        {{ app()->getLocale() === 'ar' ? 'أتمتة وربط واتساب • WhatsApp Cloud API' : 'WhatsApp Automation & Cloud APIs' }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                        {{ app()->getLocale() === 'ar' ? 'عايز خدمة عملاء وفواتير وإشعارات واتساب أوتوماتيك؟' : 'Automate notifications, OTPs & customer chats 24/7' }}
                    </h2>
                </div>

            </div>

            <!-- Actions & Pagination -->
            <div class="flex items-center space-x-6 rtl:space-x-reverse shrink-0">
                <a href="/start-project" class="px-6 py-2.5 bg-[#0F140A] text-[#748660] hover:bg-black text-xs font-bold font-mono tracking-widest uppercase transition-colors">
                    {{ app()->getLocale() === 'ar' ? 'ابدأ مشروعك ➔' : 'START PROJECT ➔' }}
                </a>

                <!-- Pagination Arrows (< 1 / 3 >) -->
                <div class="flex items-center space-x-3 rtl:space-x-reverse text-[#0F140A] font-mono text-sm font-bold">
                    <button onclick="prevSlide()" class="p-1 hover:opacity-60 transition-opacity cursor-pointer">
                        &larr;
                    </button>
                    <span id="slide-counter" class="tracking-widest">1 / 3</span>
                    <button onclick="nextSlide()" class="p-1 hover:opacity-60 transition-opacity cursor-pointer">
                        &rarr;
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- 2. WHAT WE BUILD (4 DIRECT CAPABILITIES) -->
<section id="services" class="py-24 px-6 sm:px-12 max-w-[1400px] mx-auto">
    <div class="mb-16 space-y-3">
        <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
            {{ app()->getLocale() === 'ar' ? 'خدماتنا الهندسية' : 'What We Build' }}
        </span>
        <h2 class="text-3xl sm:text-4xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'بنعمل إيه لمشروعك؟ برمجيات حقيقية بتدخل فلوس' : 'Engineered for Performance. Built for Real Business.' }}
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Card 1: ERP -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    01
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'برامج ERP وحسابات ومخازن' : 'Enterprise ERP & Ledgers' }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar' 
                        ? 'حسابات قيود مزدوجة، إدارة مخازن متعددة، وربط مع الفاتورة الإلكترونية المصرية (ETA) وهيئة الزكاة (ZATCA).' 
                        : 'Double-entry ledgers, multi-warehouse stock control, and official tax compliance.' }}
                </p>
            </div>
            <a href="/start-project" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ app()->getLocale() === 'ar' ? 'اطلب نظام شركتك ➔' : 'Scope ERP System ➔' }}
            </a>
        </div>

        <!-- Card 2: Websites & Stores -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    02
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'مواقع ومتاجر إلكترونية فائقة السرعة' : 'Web & E-Commerce' }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar' 
                        ? 'متاجر سريعة تتحمل آلاف الطلبات المتزامنة، مربوطة ببوابات الدفع (Visa, Mastercard, Fawry, Vodafone Cash).' 
                        : 'Ultra-fast storefronts handling massive traffic surges with local payment gateway integrations.' }}
                </p>
            </div>
            <a href="/start-project" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ app()->getLocale() === 'ar' ? 'اطلب متجرك أو موقعك ➔' : 'Build Web Platform ➔' }}
            </a>
        </div>

        <!-- Card 3: WhatsApp Automation -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    03
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'أتمتة الواتساب وخدمة العملاء' : 'WhatsApp Cloud Engine' }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar' 
                        ? 'ربط رسمي WhatsApp Cloud API بدون حظر، إرسال إشعارات الفواتير، وتوزيع الشات بين فريق المبيعات.' 
                        : 'Official WhatsApp API integration, transactional OTP alerts, and multi-agent customer routing.' }}
                </p>
            </div>
            <a href="/platforms/crm" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ app()->getLocale() === 'ar' ? 'استكشف ربط الواتساب ➔' : 'Explore Engine ➔' }}
            </a>
        </div>

        <!-- Card 4: POS & Desktop .NET -->
        <div class="bg-[#161616] border border-[#262626] p-8 hover:border-[#748660] transition-colors flex flex-col justify-between">
            <div class="space-y-4">
                <div class="w-10 h-10 bg-black border border-[#2B2B2B] text-[#748660] font-mono font-bold flex items-center justify-center text-sm">
                    04
                </div>
                <h3 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'برامج كاشير POS وسطح مكتب' : 'POS & Desktop Apps (.NET)' }}
                </h3>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar' 
                        ? 'برامج أوفلاين سريعة بلغة C#، ربط مباشر مع طابعات الباركود والإيصالات، وشاشات لمس للمحلات والمطاعم.' 
                        : 'Offline-first high-speed desktop tools in .NET, receipt printers, and multi-lane retail registers.' }}
                </p>
            </div>
            <a href="/start-project" class="mt-8 text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                {{ app()->getLocale() === 'ar' ? 'اطلب نظام نقاط البيع ➔' : 'Deploy POS System ➔' }}
            </a>
        </div>

    </div>
</section>


<!-- 3. INSTANT PRICING ESTIMATOR -->
<section id="estimator" class="py-24 px-6 sm:px-12 bg-[#0E0E0E] border-y border-[#222222]">
    <div class="max-w-[1200px] mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                {{ app()->getLocale() === 'ar' ? 'حاسبة التكلفة الفورية' : 'Instant Architecture Estimator' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold text-white font-sans">
                {{ app()->getLocale() === 'ar' ? 'احسب تكلفة مشروعك بنفسك في دقيقة' : 'Calculate Your Project Estimate Transparently' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans">
                {{ app()->getLocale() === 'ar' ? 'شفافية هندسية كاملة، بدون رسوم مخفية، مع مخرجات وتسعير فوري بالجنيه المصري والدولار.' : 'Zero hidden fees, transparent modular breakdown, and instant quotation dispatch.' }}
            </p>
        </div>

        <!-- Calculator Box -->
        <div class="bg-[#141414] border border-[#2B2B2B] p-8 sm:p-12 space-y-10">
            
            <!-- Step A: Platform -->
            <div class="space-y-4">
                <label class="text-xs font-mono text-zinc-400 uppercase tracking-wider font-bold block">
                    {{ app()->getLocale() === 'ar' ? '1. اختر نوع وبيئة تشغيل النظام:' : '1. Select Deployment Platform:' }}
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 font-mono text-xs">
                    <button type="button" onclick="selectPlatform('web', 800)" class="platform-btn p-4 border border-[#748660] bg-[#1E2619] text-white font-bold text-center transition-colors">
                        🌐 Web Platform
                    </button>
                    <button type="button" onclick="selectPlatform('mobile', 1200)" class="platform-btn p-4 border border-[#2B2B2B] bg-black text-zinc-400 hover:text-white font-bold text-center transition-colors">
                        📱 Mobile App (iOS/Android)
                    </button>
                    <button type="button" onclick="selectPlatform('desktop', 1000)" class="platform-btn p-4 border border-[#2B2B2B] bg-black text-zinc-400 hover:text-white font-bold text-center transition-colors">
                        💻 Desktop (.NET)
                    </button>
                    <button type="button" onclick="selectPlatform('suite', 2200)" class="platform-btn p-4 border border-[#2B2B2B] bg-black text-zinc-400 hover:text-white font-bold text-center transition-colors">
                        🚀 Full Multi-Platform Suite
                    </button>
                </div>
            </div>

            <!-- Step B: Number of Screens -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-mono text-zinc-400 uppercase tracking-wider font-bold">
                        {{ app()->getLocale() === 'ar' ? '2. حجم وشاشات النظام التقديرية:' : '2. Estimated Custom Screens:' }}
                    </label>
                    <span id="screen-count-label" class="text-[#748660] font-mono font-bold text-base">8 Screens</span>
                </div>
                <input type="range" id="screen-slider" min="3" max="30" value="8" oninput="updateEstimate()" class="w-full accent-[#748660] bg-zinc-800 h-2 cursor-pointer">
            </div>

            <!-- Price Output Box -->
            <div class="pt-8 border-t border-[#222222] flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <span class="text-xs font-mono text-zinc-400 block uppercase tracking-wider">
                        {{ app()->getLocale() === 'ar' ? 'التكلفة التقديرية للاستثمار البرمجي:' : 'Estimated Investment:' }}
                    </span>
                    <div class="flex items-baseline gap-3 mt-1">
                        <span id="estimated-price-egp" class="text-3xl sm:text-4xl font-bold text-white font-mono">35,000 EGP</span>
                        <span id="estimated-price-usd" class="text-sm text-zinc-400 font-mono">($720 USD)</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a id="estimator-wa-btn" href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I%20calculated%20an%20estimate%20for%20my%20system." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto text-center px-8 py-3.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold font-mono text-xs uppercase tracking-wider transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'ناقش التقدير على واتساب ➔' : 'DISCUSS ESTIMATE ON WHATSAPP ➔' }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- 4. PRODUCTION SYSTEMS SHOWCASE (LIVE WORK) -->
<section id="work" class="py-24 px-6 sm:px-12 max-w-[1400px] mx-auto">
    <div class="mb-16 flex flex-col sm:flex-row sm:items-end justify-between gap-6">
        <div class="space-y-3">
            <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                {{ app()->getLocale() === 'ar' ? 'سابقة الأعمال الحية' : 'Case Studies Archive' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold text-white font-sans tracking-tight">
                {{ app()->getLocale() === 'ar' ? 'أنظمة حقيقية تعمل الآن في السوق' : 'Production Systems Driving Millions in Revenue' }}
            </h2>
        </div>
        <a href="/portfolio" class="text-xs font-mono text-zinc-400 hover:text-white uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'عرض كافة الأعمال (30+) ➔' : 'View Full Archive (30+) ➔' }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- System 1: RevFlow -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
            <div class="h-56 bg-zinc-900 overflow-hidden relative">
                <img src="/images/portfolio/revflow.jpg" alt="RevFlow ERP" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                    ENTERPRISE ERP
                </div>
            </div>
            <div class="p-6 space-y-3">
                <h3 class="text-lg font-bold text-white font-sans">RevFlow Double-Entry Kernel</h3>
                <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                    محرك حسابات مؤسسي معتمد بالقيود المزدوجة، يربط الفواتير والمخازن وتوزيع الأرباح لحظياً.
                </p>
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <span class="text-zinc-500">Laravel &bull; PostgreSQL</span>
                    <a href="/portfolio/revflow" class="text-[#748660] hover:text-white font-bold">Details ↗</a>
                </div>
            </div>
        </div>

        <!-- System 2: WeBill POS -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
            <div class="h-56 bg-zinc-900 overflow-hidden relative">
                <img src="/images/portfolio/gold-pos.jpg" alt="WeBill FinTech POS" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                    FINTECH POS
                </div>
            </div>
            <div class="p-6 space-y-3">
                <h3 class="text-lg font-bold text-white font-sans">Real-Time Gold & POS Terminal</h3>
                <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                    محطة نقاط بيع وتداول لأسعار الذهب والعملات، مربوطة بالـ WebSockets وطابعات الباركود.
                </p>
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <span class="text-zinc-500">WebSocket &bull; .NET &bull; React</span>
                    <a href="/portfolio/gold-pos" class="text-[#748660] hover:text-white font-bold">Details ↗</a>
                </div>
            </div>
        </div>

        <!-- System 3: WhatsApp Cloud Suite -->
        <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
            <div class="h-56 bg-zinc-900 overflow-hidden relative">
                <img src="/images/portfolio/whatsapp-suite.jpg" alt="WhatsApp Cloud Engine" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                    META GRAPH API
                </div>
            </div>
            <div class="p-6 space-y-3">
                <h3 class="text-lg font-bold text-white font-sans">Meta Cloud & Multi-Agent CRM</h3>
                <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                    محرك معالجة 1M+ رسالة وإشعار يومياً، مع نظام رد آلي وربط فوري بأنظمة الشركات.
                </p>
                <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                    <span class="text-zinc-500">Meta API &bull; Redis &bull; Queue</span>
                    <a href="/platforms/crm" class="text-[#748660] hover:text-white font-bold">Details ↗</a>
                </div>
            </div>
        </div>

    </div>
</section>


<!-- 5. FINAL CALL TO ACTION (START PROJECT OR CHAT) -->
<section class="py-24 px-6 sm:px-12 bg-gradient-to-b from-[#111111] to-[#0A0A0A] border-t border-[#222222]">
    <div class="max-w-4xl mx-auto text-center space-y-8">
        <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
            {{ app()->getLocale() === 'ar' ? 'خطوة واحدة للبدء' : 'Direct Engagement' }}
        </span>
        <h2 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'جاهز تبني نظام شركتك أو تبدأ مشروعك الجديد؟' : 'Ready to Engineer Your Next Enterprise System?' }}
        </h2>
        <p class="text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar' 
                ? 'تواصل مباشرة مع المهندس محمود أمين أو استخدم معالج تصميم النظام لتحديد المواصفات والحصول على كود المشروع.' 
                : 'Direct communication with Chief Software Architect Mahmoud Amin. Zero middle management layers.' }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 font-mono text-xs">
            <a href="/start-project" class="w-full sm:w-auto px-8 py-4 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-widest transition-all">
                {{ app()->getLocale() === 'ar' ? 'ابدأ معالج تصميم النظام ➔' : 'START SCOPING WIZARD ➔' }}
            </a>
            <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20a%20new%20system." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-8 py-4 border border-[#333333] hover:border-white text-zinc-300 hover:text-white font-bold uppercase tracking-widest transition-all">
                {{ app()->getLocale() === 'ar' ? 'محادثة واتساب مباشرة' : 'WHATSAPP DIRECT' }}
            </a>
        </div>
    </div>
</section>

<!-- Interactive Scripts for Hero Slider & Estimator -->
<script>
    let currentSlideIdx = 0;
    const totalSlides = 3;

    function showSlide(idx) {
        currentSlideIdx = (idx + totalSlides) % totalSlides;
        
        // Slides
        document.querySelectorAll('.hero-slide').forEach((slide, i) => {
            if (i === currentSlideIdx) {
                slide.classList.remove('opacity-0', 'pointer-events-none');
                slide.classList.add('opacity-100');
            } else {
                slide.classList.add('opacity-0', 'pointer-events-none');
                slide.classList.remove('opacity-100');
            }
        });

        // Banner Texts
        document.querySelectorAll('.banner-text-item').forEach((item, i) => {
            if (i === currentSlideIdx) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });

        document.getElementById('slide-counter').innerText = `${currentSlideIdx + 1} / ${totalSlides}`;
    }

    function nextSlide() { showSlide(currentSlideIdx + 1); }
    function prevSlide() { showSlide(currentSlideIdx - 1); }

    // Auto-advance hero banner every 6 seconds
    setInterval(nextSlide, 6000);

    // Estimator Logic
    let basePlatformCost = 800;
    let selectedPlatformName = 'Web Platform';

    function selectPlatform(type, baseCost) {
        basePlatformCost = baseCost;
        selectedPlatformName = type;
        
        document.querySelectorAll('.platform-btn').forEach(btn => {
            btn.classList.remove('border-[#748660]', 'bg-[#1E2619]', 'text-white');
            btn.classList.add('border-[#2B2B2B]', 'bg-black', 'text-zinc-400');
        });

        event.currentTarget.classList.remove('border-[#2B2B2B]', 'bg-black', 'text-zinc-400');
        event.currentTarget.classList.add('border-[#748660]', 'bg-[#1E2619]', 'text-white');

        updateEstimate();
    }

    function updateEstimate() {
        const screens = parseInt(document.getElementById('screen-slider').value);
        document.getElementById('screen-count-label').innerText = `${screens} Screens`;

        const totalUsd = Math.round(basePlatformCost + (screens * 45));
        const totalEgp = Math.round(totalUsd * 49.5);

        document.getElementById('estimated-price-egp').innerText = `${totalEgp.toLocaleString('en-US')} EGP`;
        document.getElementById('estimated-price-usd').innerText = `($${totalUsd.toLocaleString('en-US')} USD)`;

        const waText = encodeURIComponent(`Hello Mahmoud, I estimated a ${selectedPlatformName} with ${screens} screens on Musoftware. Estimated budget: ${totalEgp.toLocaleString('en-US')} EGP ($${totalUsd} USD). Can we discuss?`);
        document.getElementById('estimator-wa-btn').href = `https://wa.me/201015218548?text=${waText}`;
    }
</script>

@endsection
