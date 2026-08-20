@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ app()->getLocale() === 'ar' ? 'سجل الإنجازات والأنظمة الحية' : 'Case Studies Archive' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'سابقة الأعمال والأنظمة المشحونة' : 'Shipped Systems & Enterprise Solutions' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ app()->getLocale() === 'ar'
                ? 'أكثر من 30 نظام ومنصة سحابية تعمل في السوق الفعلي، تعالج ملايين المعاملات والبيانات الحساسة يومياً.'
                : 'Over 30 production platforms operating in live markets, processing high-throughput financial transactions and data streams.' }}
        </p>
    </div>

    <!-- Portfolio Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            @forelse($dbProjects as $proj)
                <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between">
                    <div>
                        <div class="h-60 bg-zinc-900 overflow-hidden relative">
                            @if(!empty($proj['img']))
                                <img src="{{ $proj['img'] }}" alt="{{ $proj['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-black font-mono text-zinc-600 text-sm">
                                    MUSOFTWARE SYSTEM
                                </div>
                            @endif
                            <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800 uppercase">
                                {{ $proj['cat'] ?? 'Platform' }}
                            </div>
                        </div>
                        <div class="p-6 space-y-3">
                            <h2 class="text-xl font-bold text-white font-sans">{{ $proj['title'] }}</h2>
                            <p class="text-xs text-zinc-400 leading-relaxed font-sans line-clamp-3">
                                {{ $proj['desc'] }}
                            </p>
                        </div>
                    </div>

                    <div class="p-6 pt-0">
                        <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                            <span class="text-zinc-500">
                                @if(!empty($proj['techs']) && is_array($proj['techs']))
                                    {{ implode(' • ', array_slice($proj['techs'], 0, 2)) }}
                                @else
                                    Production System
                                @endif
                            </span>
                            
                            @if(!empty($proj['live_url']))
                                <a href="{{ $proj['live_url'] }}" target="_blank" rel="noopener noreferrer" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                                    <span>Live System</span> ↗
                                </a>
                            @else
                                <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20know%20more%20about%20{{ urlencode($proj['title']) }}." target="_blank" rel="noopener noreferrer" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                                    <span>Inquire</span> ↗
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <!-- Fallback Curated Systems -->
                <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
                    <div class="h-60 bg-zinc-900 overflow-hidden relative">
                        <img src="/images/portfolio/revflow.jpg" alt="RevFlow ERP" class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                            ENTERPRISE ERP
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <h2 class="text-xl font-bold text-white font-sans">RevFlow Double-Entry Kernel</h2>
                        <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                            محرك حسابات مؤسسي معتمد بالقيود المزدوجة، يربط الفواتير والمخازن وتوزيع الأرباح لحظياً.
                        </p>
                    </div>
                </div>

                <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
                    <div class="h-60 bg-zinc-900 overflow-hidden relative">
                        <img src="/images/portfolio/gold-pos.jpg" alt="WeBill FinTech POS" class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                            FINTECH POS
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <h2 class="text-xl font-bold text-white font-sans">Real-Time Gold & POS Terminal</h2>
                        <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                            محطة نقاط بيع وتداول لأسعار الذهب والعملات، مربوطة بالـ WebSockets وطابعات الباركود.
                        </p>
                    </div>
                </div>

                <div class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors">
                    <div class="h-60 bg-zinc-900 overflow-hidden relative">
                        <img src="/images/portfolio/whatsapp-suite.jpg" alt="WhatsApp Cloud Engine" class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4 bg-black/80 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800">
                            META GRAPH API
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <h2 class="text-xl font-bold text-white font-sans">Meta Cloud & Multi-Agent CRM</h2>
                        <p class="text-xs text-zinc-400 leading-relaxed font-sans">
                            محرك معالجة 1M+ رسالة وإشعار يومياً، مع نظام رد آلي وربط فوري بأنظمة الشركات.
                        </p>
                    </div>
                </div>
            @endforelse

        </div>

        <!-- Bottom CTA -->
        <div class="mt-20 p-12 bg-[#161616] border border-[#262626] text-center space-y-6">
            <h3 class="text-2xl font-bold text-white font-sans">
                {{ app()->getLocale() === 'ar' ? 'هل تريد نظاماً مصمماً خصيصاً لاحتياجات مؤسستك؟' : 'Need a custom platform engineered for your business?' }}
            </h3>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ app()->getLocale() === 'ar' ? 'ابدأ معالج تصميم النظام ➔' : 'START SCOPING WIZARD ➔' }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ app()->getLocale() === 'ar' ? 'استشارة واتساب مباشرة' : 'WHATSAPP DIRECT' }}
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
