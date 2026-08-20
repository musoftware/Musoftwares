@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'معمارية الـ ERP المؤسسي' : 'Enterprise ERP Architecture' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'أنظمة ERP ودفاتر حسابات القيود المزدوجة' : 'Mission-Critical Cloud ERP & Double-Entry Ledgers' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar'
                ? 'أنظمة محاسبية وإدارية مخصصة، مصممة لمعالجة ملايين المعاملات بدقة متناهية وسرعة قياسية، مع الامتثال التام للفاتورة الإلكترونية.'
                : 'Custom accounting and enterprise resource platforms engineered for sub-millisecond database queries, multi-warehouse sync, and full tax compliance.' }}
        </p>
    </div>

    <!-- Features 4 Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / LEDGER</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'دفاتر قيود مزدوجة غير قابلة للتلاعب' : 'Immutable Double-Entry Ledger' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'محرك مالي صارم يضمن توازن الأصول والخصوم وسجلات تدقيق كاملة (Audit Trails) لكل قرش يدخل أو يخرج.'
                        : 'Rigorous balance validation ensuring exact zero-sum debits and credits with cryptographic audit logging.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / COMPLIANCE</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'الفاتورة الإلكترونية ETA و ZATCA' : 'E-Invoicing & Tax Compliance' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'تكامل مباشر عبر الـ APIs مع مصلحة الضرائب المصرية ومنظومة الزكاة والضريبة والجمارك بالمملكة العربية السعودية.'
                        : 'Native API integrations with Egyptian Tax Authority (ETA) and Saudi ZATCA phase 2 e-invoicing.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / MULTI-BRANCH</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'إدارة الفروع والمخازن المتعددة' : 'Multi-Warehouse & Stock Control' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'تحويلات لحظية بين الفروع، جرد دوري بالباركود، وتنبيهات فورية عند وصول المنتجات للحد الأدنى للطلب.'
                        : 'Real-time inter-branch stock transfers, barcode auditing, and automated reorder threshold triggers.' }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">04 / SOVEREIGNTY</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ app()->getLocale() === 'ar' ? 'استضافة على خوادمك الخاصة' : 'On-Premise or Private Cloud' }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ app()->getLocale() === 'ar'
                        ? 'نشر النظام على خوادمك الداخلية أو سحابتك الخاصة المشفرة مع دعم العمل بدون إنترنت (Offline Sync).'
                        : 'Deploy on dedicated enterprise bare-metal servers or private VPC with seamless edge/offline replication.' }}
                </p>
            </div>

        </div>

        <!-- Action Box -->
        <div class="bg-[#161616] border border-[#262626] p-10 sm:p-14 text-center space-y-6">
            <h3 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ app()->getLocale() === 'ar' ? 'هل تريد برنامج ERP مفصل على مقاس شركتك؟' : 'Ready to Engineer a Tailored ERP for Your Operations?' }}
            </h3>
            <p class="text-sm text-zinc-400 max-w-xl mx-auto font-sans">
                {{ app()->getLocale() === 'ar'
                    ? 'حدد الموديولات التي تحتاجها في دقيقة واحصل على كود المشروع وتواصل مع المهندس محمود أمين مباشرة.'
                    : 'Configure your modules in our interactive wizard and get instant quotation and engineering dispatch.' }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs pt-2">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ app()->getLocale() === 'ar' ? 'ابدأ تصميم نظام شركتك ➔' : 'START SCOPING WIZARD ➔' }}
                </a>
                <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20an%20Enterprise%20ERP." target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ app()->getLocale() === 'ar' ? 'استشارة واتساب فورية' : 'DISCUSS ON WHATSAPP' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
