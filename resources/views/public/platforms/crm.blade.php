@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'أتمتة الواتساب والمحادثات' : 'WhatsApp Cloud API & CRM' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'ربط WhatsApp Cloud API وأتمتة خدمة العملاء' : 'High-Throughput Meta Graph & WhatsApp Engine' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar'
                ? 'ربط رسمي مباشر بـ WhatsApp Cloud API بدون حظر، إرسال إشعارات الفواتير وأكواد التأكيد (OTP)، وتوزيع المحادثات على الوكلاء.'
                : 'Verified Meta Cloud API pipelines, multi-agent unified inbox, 24/7 automated bots, and zero-ban transactional notification delivery.' }}
        </p>
    </div>

    <!-- 4 Key Advantages -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / OFFICIAL API</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'ربط رسمي بدون حظر للأرقام' : 'Official WhatsApp Cloud API' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'ربط مباشر مع سيرفرات Meta عبر الـ Cloud API، بدون محاكيات متصفح وبدون أي مخاطرة بحظر أرقام شركتك.'
                        : 'Direct infrastructure connection to Meta Graph endpoints without fragile browser sessions or risk of account bans.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / NOTIFICATIONS</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'إشعارات الفواتير وتأكيد الطلبات' : 'Transactional OTP & Alerts' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'إرسال فواتير الـ PDF، تأكيدات الشحن، وأكواد الـ OTP لحظياً للعملاء عبر قوالب معتمدة من Meta.'
                        : 'Instant delivery of PDF invoices, shipment tracking, and login OTP codes via pre-approved Meta message templates.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / MULTI-AGENT</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'صندوق وارد موحد وتوزيع ذكي' : 'Multi-Agent Unified Inbox' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'رقم واتساب واحد للشركة يعمل عليه 10 أو 50 موظف في نفس الوقت، مع تحويل الشات ومتابعة سرعة الرد.'
                        : 'Single corporate WhatsApp number shared across entire support team with intelligent routing and response metrics.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">04 / BOTS & AI</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'رد آلي ذكي وسيناريوهات 24 ساعة' : 'Automated Interactive Bots' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'رد فوري على أسئلة الأسعار والعناوين والطلبات بأزرار تفاعلية (Interactive Buttons & Lists) وقوائم سريعة.'
                        : 'Interactive buttons, menu lists, and AI auto-responders qualifying leads and answering customers around the clock.' }}
                </p>
            </div>

        </div>

        <!-- Action Box -->
        <div class="bg-[#161616] border border-[#262626] p-10 sm:p-14 text-center space-y-6">
            <h3 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ app()->getLocale() === 'ar' ? 'عايز تربط نظام شركتك أو متجرك بالواتساب؟' : 'Ready to Connect WhatsApp Automation to Your Business?' }}
            </h3>
            <p class="text-sm text-zinc-400 max-w-xl mx-auto font-sans">
                {{ app()->getLocale() === 'ar'
                    ? 'فريقنا يقوم بتهيئة حساب Meta Business وتفعيل الـ Cloud API وربطها بنظامك في وقت قياسي.'
                    : 'We configure your Meta Business verification, template approvals, and webhook architecture end-to-end.' }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs pt-2">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ app()->getLocale() === 'ar' ? 'اطلب ربط الواتساب ➔' : 'REQUEST INTEGRATION ➔' }}
                </a>
                <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20integrate%20WhatsApp%20Cloud%20API." target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ app()->getLocale() === 'ar' ? 'استشارة واتساب مباشرة' : 'DISCUSS ON WHATSAPP' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
