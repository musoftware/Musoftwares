@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'تواصل تقني مباشر' : 'Direct Technical Communication' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'تحدث مباشرة مع مهندس النظام' : 'Talk Directly with the Software Architect' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar' 
                ? 'بدون طبقات إدارة وسطى. تواصل هندسي مباشر، ردود سريعة، ومناقشة تفصيلية لمواصفات وتكلفة مشروعك.' 
                : 'Zero middle management layers. Direct technical communication, rapid responses, and transparent scoping.' }}
        </p>
    </div>

    <!-- Quick Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-center font-mono text-xs mb-20 px-6">
        <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20a%20project." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto text-center bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest transition-all">
            {{ app()->getLocale() === 'ar' ? 'محادثة واتساب فورية ➔' : 'WHATSAPP DIRECT CHAT ➔' }}
        </a>
        <a href="mailto:info@musoftwares.com" class="w-full sm:w-auto text-center border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest transition-all">
            info@musoftwares.com
        </a>
    </div>

    <!-- 3 Channel Cards -->
    <div class="px-6 max-w-[1400px] mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Channel 1: WhatsApp -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        WA
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ app()->getLocale() === 'ar' ? 'واتساب المهندس المباشر' : 'Instant Direct WhatsApp' }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ app()->getLocale() === 'ar' 
                            ? 'أسرع وسيلة للتواصل ومناقشة متطلبات الأنظمة وتقديرات التكلفة الأولية.' 
                            : 'Fastest way to get in touch. Technical consultations, urgent scope reviews, and architecture briefs.' }}
                    </p>
                </div>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="mt-8 text-xs font-mono font-bold text-[#748660] hover:text-white flex items-center gap-1">
                    <span>{{ app()->getLocale() === 'ar' ? 'فتح المحادثة ↗' : 'OPEN CHAT ↗' }}</span>
                </a>
            </div>

            <!-- Channel 2: Email Proposal -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        @
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ app()->getLocale() === 'ar' ? 'بريد العروض والاتفاقيات الرسمية' : 'Formal Proposal Inbox' }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ app()->getLocale() === 'ar' 
                            ? 'إرسال كراسات الشروط (RFP)، ملفات المواصفات الفنية، واتفاقيات عدم الإفصاح (NDA).' 
                            : 'Send RFP documents, NDA agreements, and multi-platform specification files for detailed review.' }}
                    </p>
                </div>
                <a href="mailto:info@musoftwares.com" class="mt-8 text-xs font-mono font-bold text-[#748660] hover:text-white flex items-center gap-1">
                    <span>{{ app()->getLocale() === 'ar' ? 'إرسال إيميل ↗' : 'SEND EMAIL ↗' }}</span>
                </a>
            </div>

            <!-- Channel 3: Headquarters -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        EG
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ app()->getLocale() === 'ar' ? 'مقر الاستوديو والتسليم العالمي' : 'Worldwide Delivery' }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ app()->getLocale() === 'ar' 
                            ? 'المقر الرئيسي بمحافظة السويس، مصر. تشغيل وتسليم أنظمة للمؤسسات والشركات حول العالم.' 
                            : 'Headquartered in Suez, Egypt. Deploying mission-critical platforms to clients worldwide.' }}
                    </p>
                </div>
                <div class="mt-8 text-xs font-mono text-zinc-500">
                    Suez, Egypt &bull; Cairo Timezone (UTC+2 / UTC+3)
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
