@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'الذكاء الاصطناعي وخدمة العملاء الآلية' : 'Conversational AI & ERP RAG Integration' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'ربط الذكاء الاصطناعي بقاعدة بيانات المخازن الحية عبر واتساب' : 'Retrieval-Augmented AI Support Agent Grounded in Live Stock Records' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'روبوت ذكاء اصطناعي تفاعلي مربوط بأنظمة الـ ERP للرد الفوري على استفسارات العملاء على مدار الساعة، يفهم اللهجات العربية والإنجليزية، يفحص توافر الكميات والأسعار الحية في المخازن، ويقوم بإنشاء الفاتورة وتوليد رابط السداد مباشرة داخل الشات.'
                    : 'StockTalk AI is a grounded conversational support agent connected directly to enterprise inventory databases. It understands natural Arabic dialects and English, performs live SKU stock lookups, calculates bulk discounts, and generates checkout payment links directly inside WhatsApp.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات الذكاء الاصطناعي' : 'AI RAG SPECS' }}</span>
                <span class="text-zinc-500">GROUNDED ERP DATA</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'استعلام مباشر من قاعدة بيانات المخزون لمنع الإجابات المغلوطة (Zero Hallucination)' : 'Direct ERP query grounding preventing hallucinated inventory quantities.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'فهم فائق للغة العربية العامية والإنجليزية والمصطلحات التجارية' : 'Natural Arabic dialect & English commercial query understanding.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'توليد روابط الدفع الإلكتروني والفواتير وتأكيد الطلب داخل المحادثة' : 'Instant in-chat payment link generation and order confirmation.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تحويل تلقائي سلس للموظف البشري في الحالات الخاصة والمعقدة' : 'Seamless human agent handover trigger for complex client inquiries.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
