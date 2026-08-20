@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'استوديو هندسة البرمجيات' : 'Boutique Engineering Studio' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'نبني البنية التحتية البرمجية لشركتك' : 'Engineering the Operational Core of Modern Enterprises' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar'
                ? 'ميوسوفت ويرز هو استوديو هندسي متخصص في بناء الأنظمة السحابية المعقدة، دفاتر الحسابات المؤسسية ERP، والربط الرسمي بـ WhatsApp Cloud API.'
                : 'Musoftwares is a software engineering studio dedicated to building high-performance ERP systems, cloud SaaS engines, and verified Meta API automations.' }}
        </p>
    </div>

    <!-- 3 Pillars Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / ARCHITECTURE</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'استقلالية وامتلاك تام للكود' : 'Data Sovereignty & Zero Lock-in' }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'كود النظام وقواعد بياناتك ملك كامل لشركتك. لا اشتراكات احتكارية ولا اعتمادية على أطراف ثالثة تفرض قيوداً على نموك.'
                        : 'Full ownership of your source code and database schemas. No vendor lock-in or restrictive per-user recurring fees.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / RELIABILITY</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'سرعة فائقة وتحمل لحظي' : 'Sub-millisecond Latency' }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'تصميم معماري نظيف يتحمل ملايين المعاملات وحركات المخازن والفواتير بدون بطء أو انهيار في أوقات الذروة.'
                        : 'Optimized PostgreSQL and Redis pipelines built to withstand massive transactional spikes without performance degradation.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / CONTINUITY</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'دعم هندسي مستمر' : 'Long-Term Partnership' }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'نتابع استقرار نظامك ونضمن تطوره مع توسع فروعك وعملياتك التجارية، مع تحديثات أمنية وتوافق دائم.'
                        : 'We stand behind our production deployments with proactive SLAs, security auditing, and continuous architectural upgrades.' }}
                </p>
            </div>
        </div>

        <!-- Leadership Highlight Banner -->
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="space-y-3">
                <span class="text-xs font-mono text-[#748660] uppercase tracking-wider font-bold">Chief Software Architect</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'بإشراف مباشر من المهندس محمود أمين' : 'Led by Mahmoud Amin — Chief Software Architect' }}
                </h2>
                <p class="text-sm text-zinc-400 max-w-2xl font-sans">
                    {{ app()->getLocale() === 'ar'
                        ? 'سجل هندسي يضم أكثر من 30 نظاماً سحابياً ومحركات محاسبية متطورة وأنظمة تداول حية تعمل في السوق.'
                        : 'Author of 30+ enterprise deployments spanning double-entry ledgers, desktop .NET utilities, and high-volume Meta API dispatchers.' }}
                </p>
            </div>

            <div class="flex items-center gap-4 shrink-0 font-mono text-xs">
                <a href="/about/mahmoud-amin" class="px-6 py-3 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-wider transition-colors">
                    {{ app()->getLocale() === 'ar' ? 'السيرة المهنية الكاملة ➔' : 'READ LEADERSHIP BIO ➔' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
