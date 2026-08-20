@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'القيادة الهندسية وتصميم الأنظمة' : 'Leadership & Engineering Direction' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'السيرة المهنية للمهندس محمود أمين' : 'Mahmoud Amin — Chief Software Architect' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar'
                ? 'مؤسس وكبير مهندسي استوديو ميوسوفت ويرز. أكثر من 10 سنوات في هندسة أنظمة الـ ERP، المنصات السحابية، وتطبيقات سطح المكتب.'
                : 'Founder and Chief Software Architect at Musoftwares Studio. 10+ years engineering high-throughput SaaS, ERP ledgers, and desktop solutions.' }}
        </p>
    </div>

    <!-- Main Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- Left Profile Card (4 cols) -->
            <div class="lg:col-span-4 bg-[#161616] border border-[#2B2B2B] p-8 space-y-6">
                <div class="w-24 h-24 bg-[#1E2619] border-2 border-[#748660] flex items-center justify-center font-mono text-3xl font-black text-[#748660]">
                    MA
                </div>
                
                <div class="space-y-1">
                    <h2 class="text-2xl font-bold text-white font-sans">Mahmoud Amin</h2>
                    <p class="text-xs font-mono text-[#748660] uppercase tracking-wider">Founder & Chief Architect</p>
                    <p class="text-xs text-zinc-400 font-sans">Suez, Egypt &bull; Worldwide Delivery</p>
                </div>

                <div class="pt-4 border-t border-[#222222] space-y-3 text-xs font-mono text-zinc-300">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">{{ app()->getLocale() === 'ar' ? 'سنوات الخبرة:' : 'Experience:' }}</span>
                        <span>10+ Years</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">{{ app()->getLocale() === 'ar' ? 'الأنظمة المشحونة:' : 'Platforms Shipped:' }}</span>
                        <span>30+ Production Systems</span>
                    </div>
                    
                    <div class="space-y-1.5 pt-2">
                        <span class="text-zinc-500 block">{{ app()->getLocale() === 'ar' ? 'المجالات الهندسية الأساسية:' : 'Core Architectural Domains:' }}</span>
                        <div class="flex flex-wrap gap-1.5 pt-1 text-[10px]">
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Cloud SaaS Platforms</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Desktop Applications (.NET)</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">WhatsApp Business Solutions</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Meta Graph Cloud APIs</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Enterprise ERP & Ledgers</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Point of Sale (POS)</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">FinTech & Exchanges</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">RPA & Data Automation</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#222222] space-y-2">
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="w-full flex items-center justify-center gap-2 py-3 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider transition-colors">
                        <span>{{ app()->getLocale() === 'ar' ? 'تواصل واتساب مباشر' : 'Message on WhatsApp' }}</span>
                    </a>

                    <div class="grid grid-cols-2 gap-2 pt-2 text-[11px] font-mono text-center">
                        <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">LinkedIn ↗</a>
                        <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">GitHub ↗</a>
                        <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">X (Twitter) ↗</a>
                        <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">Facebook ↗</a>
                    </div>
                </div>
            </div>

            <!-- Right Narrative (8 cols) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Statement -->
                <div class="bg-[#161616] border border-[#2B2B2B] p-8 sm:p-10 space-y-6">
                    <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                        {{ app()->getLocale() === 'ar' ? 'البيان المعماري' : 'Architectural Statement' }}
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                        {{ app()->getLocale() === 'ar' 
                            ? '"نبني البرمجيات كبنية تحتية متينة ودائمة تعيش لسنوات، وليست مجرد نماذج استهلاكية عابرة."' 
                            : '"We engineer software as durable infrastructure, not disposable consumer prototypes."' }}
                    </h3>
                    <p class="text-sm text-zinc-300 font-sans leading-relaxed">
                        {{ app()->getLocale() === 'ar' 
                            ? 'محمود أمين هو المؤسس والمهندس الرئيسي وراء استوديو ميوسوفت ويرز. على مدار السنوات العشر الماضية، قاد هندسة وتسليم أنظمة مؤسسية معقدة تشمل دفاتر حسابات القيود المزدوجة، منصات تداول وبورصات الذهب اللحظية، وتطبيقات WhatsApp Cloud API المعتمدة التي تعالج ملايين الرسائل شهرياً.' 
                            : 'Over the past decade, Mahmoud Amin has engineered enterprise-grade solutions ranging from multi-branch double-entry ledgers to real-time gold exchange terminals and verified Meta Cloud API engines.' }}
                    </p>
                </div>

                <!-- Career Milestones -->
                <div class="space-y-4">
                    <h4 class="text-xs font-mono uppercase tracking-wider text-zinc-400 font-bold">
                        {{ app()->getLocale() === 'ar' ? 'المسيرة الهندسية والمحطات الرئيسية' : 'Engineering Timeline & Milestones' }}
                    </h4>

                    <div class="space-y-3">
                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">Founder & Chief Software Architect</span>
                                <span class="text-[#748660]">2026</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                قيادة الاستوديو في بناء محركات الـ ERP السحابية، وربط منصات الـ Meta API، وبرمجيات التشغيل الذاتية.
                            </p>
                        </div>

                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">Enterprise Systems & FinTech Architect</span>
                                <span class="text-[#748660]">2023 - 2025</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                بناء دفاتر القيود المزدوجة فائقة السرعة، محطات تداول أسعار الذهب اللحظية، وموزعات شات الواتساب.
                            </p>
                        </div>

                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">Senior Full-Stack & Desktop Engineer</span>
                                <span class="text-[#748660]">2019 - 2022</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                تطوير أكثر من 20 نظام نقاط بيع (POS) متخصص، ومزامنة طابعات الباركود والإيصالات الحرارية في C# و Laravel.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection
