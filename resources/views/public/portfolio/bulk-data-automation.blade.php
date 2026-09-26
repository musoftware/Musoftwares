@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-20 pt-14 border-t border-black/[0.08] space-y-16">

    <!-- Section 1: The High-Scale Challenge & Bottlenecks -->
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#059669]"></span>
            <span class="text-[13px] font-bold text-[#059669] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'المشكلة والتحديات المعمارية' : 'The Challenge & Technical Bottlenecks' }}
            </span>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight leading-snug">
                    {{ $locale === 'ar' 
                        ? 'تحدي ملايين السجلات: عندما تعجز الطرق التقليدية وقواعد البيانات العادية' 
                        : 'Scaling to Millions: When Traditional Databases & Manual Querying Fail' }}
                </h2>
                <p class="text-[15px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'واجه العميل معضلة تشغيلية حرجة في التحقق من ملايين السجلات وتحديث حالتها دورياً عبر بوابات إلكترونية خارجية متعددة. المحاولات اليدوية كانت مستحيلة ومكلفة جداً، في حين أن المحاولات الآلية البسيطة كانت تصطدم فوراً بجدران الحظر وقيود عدد الطلبات (Rate Limiting)، فضلاً عن بطء السيرفرات التقليدية وانهيار قواعد البيانات العادية عند محاولة تصفية ملفات Excel و CSV المليونية بشكل لحظي.'
                        : 'The client faced a mission-critical operational bottleneck: validating and synchronizing the status of millions of dynamic records across external multi-portal platforms. Manual processing was economically unfeasible, while naive automation scripts instantly triggered IP bans and aggressive rate limits. Compounding the challenge, standard relational databases buckled under the load of real-time search and multi-million-row CSV/Excel ingestion.' }}
                </p>
            </div>

            <div class="lg:col-span-5 bg-[#f5f5f7] rounded-3xl border border-black/[0.06] p-6 sm:p-8 space-y-5">
                <div class="flex items-center justify-between border-b border-black/[0.06] pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#059669]">
                        {{ $locale === 'ar' ? 'التحديات الثلاثة الكبرى' : 'Three Critical Hurdles' }}
                    </span>
                    <span class="text-[11px] text-[#86868b] font-medium">HIGH-THROUGHPUT</span>
                </div>
                <div class="space-y-3.5 text-[13px] text-[#1d1d1f]">
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#059669] font-bold">✕</span>
                        <span>{{ $locale === 'ar' ? 'استحالة المعالجة اليدوية: آلاف الساعات البشرية وتكاليف تشغيل باهظة.' : 'Manual Infeasibility: Thousands of labor hours with unacceptable delivery delays.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#059669] font-bold">✕</span>
                        <span>{{ $locale === 'ar' ? 'قيود الحظر الصارمة: Rate Limiting وحظر الـ IP عند تكرار الاستعلام.' : 'Aggressive Portal Defenses: IP banning, rate limiting, and bot fingerprints.' }}</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[#059669] font-bold">✕</span>
                        <span>{{ $locale === 'ar' ? 'اختناق قواعد البيانات: بطء شديد وتوقف كامل عند تصفية الملفات المليونية.' : 'Database Bottlenecks: Relational timeouts during bulk Excel/CSV ingestion.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Distributed Architecture (Master & Worker Bots) -->
    <div class="bg-[#f0fbf7] rounded-3xl border border-[#059669]/15 p-8 sm:p-12 space-y-8">
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#059669]/10 text-[#059669] rounded-full text-[12px] font-bold uppercase tracking-wider">
                Distributed Architecture • Master-Worker
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                {{ $locale === 'ar' ? 'الحل الهندسي: بنية تحتية موزعة تفصل الإدارة عن التنفيذ' : 'The Engineering Solution: Distributed Master-Worker Decoupling' }}
            </h3>
            <p class="text-[14px] sm:text-[15px] text-[#515154] leading-relaxed max-w-3xl">
                {{ $locale === 'ar'
                    ? 'تم تصميم بنية تحتية موزعة ومنفصلة تضمن أقصى سرعة وثبات وأمان، مع الفصل التام بين عقل الإدارة المركزي وروبوتات التنفيذ السحابية والمحلية.'
                    : 'Architected a decoupled, distributed ecosystem separating central orchestration from edge execution to achieve maximum resilience, horizontal scalability, and zero downtime.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Master Console -->
            <div class="bg-white rounded-2xl border border-black/[0.06] p-6 space-y-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-[#059669]/10 text-[#059669] flex items-center justify-center font-bold text-sm">
                        01
                    </span>
                    <h4 class="font-bold text-lg text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'العقل المدبر: لوحة التحكم ومحرك Elasticsearch' : 'Master Orchestrator: Central Dashboard & Elasticsearch' }}
                    </h4>
                </div>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'لوحة تحكم مركزية مبنية بـ Laravel تستقبل ملفات البيانات الضخمة، وتفهرسها لحظياً داخل محرك Elasticsearch المتخصص في البيانات المليونية، مما يتيح البحث والتصفية المتقدمة في أقل من 10ms، وتقسيم الملفات إلى حزم مهام صغيرة معدة للتوزيع.'
                        : 'A centralized orchestration master built with Laravel and powered by an Elasticsearch cluster. Ingests multi-million row datasets instantly, enabling sub-second multi-criteria queries (< 10ms) and slicing massive batches into micro-tasks.' }}
                </p>
                <div class="pt-2 flex flex-wrap gap-2 text-[11px] font-mono">
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">Laravel (PHP)</span>
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">Elasticsearch 8.x</span>
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">MySQL Indexing</span>
                </div>
            </div>

            <!-- Worker Bots -->
            <div class="bg-white rounded-2xl border border-black/[0.06] p-6 space-y-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-[#059669]/10 text-[#059669] flex items-center justify-center font-bold text-sm">
                        02
                    </span>
                    <h4 class="font-bold text-lg text-[#1d1d1f]">
                        {{ $locale === 'ar' ? 'الروبوتات المنفذة: برامج C# .NET و CefSharp' : 'Edge Worker Bots: C# (.NET) & Embedded CefSharp' }}
                    </h4>
                </div>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'تطبيقات سطح مكتب ذكية تعمل في الخلفية بكفاءة عالية، تسحب المهام من اللوحة المركزية وتتخاطب مع الأنظمة المستهدفة بمحاكاة دقيقة للسلوك البشري، مع معالجة ذكية لأي أخطاء أو انقطاعات واستئناف تلقائي للعمل.'
                        : 'Lightweight desktop and background worker clients authored in C# (.NET) with embedded Chromium (CefSharp). Workers poll micro-tasks, emulate human navigational patterns, and execute targeted queries with zero browser footprint.' }}
                </p>
                <div class="pt-2 flex flex-wrap gap-2 text-[11px] font-mono">
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">C# .NET</span>
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">CefSharp Chromium</span>
                    <span class="px-2.5 py-1 bg-[#f5f5f7] rounded-md text-[#1d1d1f]">RESTful Protocol</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Engineering Deep-Dive (4 Core Features) -->
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#059669]"></span>
            <span class="text-[13px] font-bold text-[#059669] uppercase tracking-wider">
                {{ $locale === 'ar' ? 'المميزات والحلول الهندسية المتقدمة' : 'Advanced Engineering Capabilities' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Feature 1: Proxy Rotation -->
            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.06] p-5 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#059669] block">
                    {{ $locale === 'ar' ? 'نظام التخفي وتخطي الحظر' : 'Anti-Ban & Stealth' }}
                </span>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">Smart Proxy Rotation</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'تكتشف الروبوتات فوراً أي علامة من علامات قيود الطلبات، وتقوم بتدوير وتغيير الـ IP تلقائياً وإعادة المحاولة بسلاسة دون أي انقطاع.'
                        : 'Real-time rate-limit heuristics trigger dynamic proxy pool rotation, rerouting queries through clean IPs with zero manual intervention.' }}
                </p>
            </div>

            <!-- Feature 2: Concurrency & Locking -->
            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.06] p-5 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#059669] block">
                    {{ $locale === 'ar' ? 'إدارة التزامن المتوازي' : 'Parallel Concurrency' }}
                </span>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">Task Locking Mechanism</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'عمل عشرات الروبوتات في نفس اللحظة لسحب المهام دون أي تضارب أو تكرار لنفس السجل بفضل آلية قفل المهام الذرية المتقدمة.'
                        : 'Atomic task locking protocol ensures dozens of distributed workers operate in parallel without race conditions or duplicate lookups.' }}
                </p>
            </div>

            <!-- Feature 3: Telegram Alerts -->
            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.06] p-5 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#059669] block">
                    {{ $locale === 'ar' ? 'التنبيهات اللحظية' : 'Live Observability' }}
                </span>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">Telegram Webhooks</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'إرسال إشعارات فورية عبر Telegram لإبلاغ الإدارة باكتمال معالجة الملفات الكبيرة أو رصد أي تنبيهات تشغيلية حرجة في ثوانٍ.'
                        : 'Instant webhook push notifications to Telegram channels on batch completions, milestone metrics, or system anomalies.' }}
                </p>
            </div>

            <!-- Feature 4: Throttle Controls -->
            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.06] p-5 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#059669] block">
                    {{ $locale === 'ar' ? 'مرونة التحكم والإدارة' : 'Adaptive Control' }}
                </span>
                <h4 class="font-bold text-[15px] text-[#1d1d1f]">Granular Throttle Delays</h4>
                <p class="text-[13px] text-[#515154] leading-relaxed">
                    {{ $locale === 'ar'
                        ? 'تحكم كامل من اللوحة المركزية في سرعة الروبوتات، فترات التوقف (Delays)، وعدد العمليات المتزامنة في أي لحظة.'
                        : 'Live centralized knobs to tune worker throttling, randomized inter-request delays, and active concurrency limits on the fly.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Section 4: Measurable Business Outcome -->
    <div class="p-8 sm:p-10 bg-[#f5f5f7] rounded-3xl border border-black/[0.06] space-y-4">
        <span class="text-xs font-bold uppercase tracking-wider text-[#059669] block">
            {{ $locale === 'ar' ? 'النتيجة والأثر الميداني' : 'Measurable Impact & Results' }}
        </span>
        <h3 class="text-xl sm:text-2xl font-bold text-[#1d1d1f] tracking-tight">
            {{ $locale === 'ar'
                ? 'تحويل أيام من العمل اليدوي الشاق إلى دقائق معدودة بدقة 100%'
                : 'From Days of Manual Strain to Sub-Minute Autonomous Precision' }}
        </h3>
        <p class="text-[14px] text-[#515154] leading-relaxed max-w-4xl">
            {{ $locale === 'ar'
                ? 'تحولت العملية التشغيلية التي كانت تستغرق أياماً طويلة من العمل اليدوي الشاق لفريق عمل كامل، إلى عملية مؤتمتة بنسبة 100% تنتهي في دقائق معدودة، مع لوحة تحكم تعرض مؤشرات الأداء، الفلاتر الذكية، والرسوم البيانية اللحظية لمتخذي القرار دون أي هدر في الموارد أو مخاطر حظر.'
                : 'Workflows that historically consumed days of error-prone manual labor across entire departments were transformed into a 100% autonomous pipeline concluding within minutes. Decision-makers gained real-time telemetry, smart faceted filters, and zero IP exposure risk.' }}
        </p>
    </div>

</div>
@endsection
