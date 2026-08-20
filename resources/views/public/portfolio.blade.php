@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $categories = \App\Services\PortfolioData::categories();
@endphp

@section('content')
<div 
    x-data="{ 
        activeTab: 'All',
        searchQuery: '',
        matches(cat, title, desc) {
            const matchesCat = this.activeTab === 'All' || cat === this.activeTab;
            const query = this.searchQuery.toLowerCase().trim();
            const matchesSearch = !query || title.toLowerCase().includes(query) || desc.toLowerCase().includes(query);
            return matchesCat && matchesSearch;
        }
    }" 
    class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36"
>
    
    <!-- Page Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ $locale === 'ar' ? 'سجل الأعمال والأنظمة المنفذة' : 'PROVEN PRODUCTION ARCHIVE' }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ $locale === 'ar' ? 'معرض الأنظمة والمشاريع البرمجية' : 'Case Studies & Production Platforms' }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ $locale === 'ar' 
                ? 'استعرض أكثر من 30 نظاماً ومحركاً برمجياً تم تصميمه وهندسته بالكامل بمعايير مؤسسية وأداء فائق السرعة.'
                : 'Explore over 30 production platforms, enterprise ERP engines, desktop automation suites, and custom cloud systems shipped by Musoftware.' }}
        </p>
    </div>

    <!-- Category Filter Tabs & Search Bar -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 mb-12 space-y-6">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6 border-b border-[#222222] pb-6">
            
            <!-- Tabs -->
            <div class="flex flex-wrap items-center gap-2 font-mono text-xs w-full lg:w-auto">
                @foreach($categories as $catKey => $catLabels)
                    <button 
                        @click="activeTab = '{{ $catKey }}'"
                        :class="activeTab === '{{ $catKey }}' 
                            ? 'bg-white text-black font-bold border-white' 
                            : 'bg-[#161616] text-zinc-400 border-[#262626] hover:text-white hover:border-zinc-500'"
                        class="px-4 py-2 border transition-all uppercase tracking-wider text-center"
                    >
                        {{ $catLabels[$locale] ?? $catLabels['en'] }}
                    </button>
                @endforeach
            </div>

            <!-- Search Filter -->
            <div class="w-full lg:w-72">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="{{ $locale === 'ar' ? 'بحث في المشاريع...' : 'Search case studies...' }}" 
                    class="w-full bg-[#161616] border border-[#2B2B2B] text-white px-4 py-2 text-xs font-mono focus:border-[#748660] focus:outline-none transition-colors"
                />
            </div>

        </div>
    </div>

    <!-- Portfolio Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            @foreach($projects as $slug => $proj)
                @php
                    $projTitle = $proj['title_' . $locale] ?? $proj['title_en'];
                    $projDesc = $proj['desc_' . $locale] ?? $proj['desc_en'];
                    $projCat = $proj['category'];
                    $projCatLocalized = $proj['category_' . $locale] ?? $proj['category'];
                @endphp

                <div 
                    x-show="matches('{{ $projCat }}', {{ json_encode($projTitle) }}, {{ json_encode($projDesc) }})"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between"
                >
                    <div>
                        <!-- Thumbnail Image -->
                        <div class="h-60 bg-[#0E0E0E] overflow-hidden relative border-b border-[#222222]">
                            @if(!empty($proj['img']))
                                <img src="{{ $proj['img'] }}" alt="{{ $projTitle }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-black font-mono text-zinc-600 text-xs">
                                    [MUSOFTWARE PRODUCTION SYSTEM]
                                </div>
                            @endif
                            <div class="absolute top-4 right-4 bg-black/90 px-2.5 py-1 text-[10px] font-mono text-[#748660] font-bold border border-zinc-800 uppercase tracking-wider">
                                {{ $projCatLocalized }}
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-3">
                            <h2 class="text-xl font-bold text-white font-sans group-hover:text-[#748660] transition-colors">
                                <a href="/portfolio/{{ $slug }}">
                                    {{ $projTitle }}
                                </a>
                            </h2>
                            <p class="text-xs text-zinc-400 leading-relaxed font-sans line-clamp-3">
                                {{ $projDesc }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer & Actions -->
                    <div class="p-6 pt-0 space-y-4">
                        <!-- Tech Chips -->
                        @if(!empty($proj['techs']))
                            <div class="flex flex-wrap gap-1.5 pt-4 border-t border-[#222222]">
                                @foreach(array_slice($proj['techs'], 0, 3) as $tech)
                                    <span class="text-[10px] font-mono bg-black border border-[#262626] text-zinc-400 px-2 py-0.5">
                                        {{ $tech }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Direct Link to Dedicated Page -->
                        <div class="flex items-center justify-between font-mono text-xs pt-2">
                            <a href="/portfolio/{{ $slug }}" class="text-white hover:text-[#748660] font-bold flex items-center gap-1.5 transition-colors">
                                <span>{{ $locale === 'ar' ? 'عرض دراسة المشروع' : 'Case Study' }}</span>
                                <span class="transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1">➔</span>
                            </a>

                            <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I would like to inquire about ' . $proj['title_en']) }}" target="_blank" rel="noopener noreferrer" class="text-zinc-500 hover:text-white transition-colors" title="{{ $locale === 'ar' ? 'استفسار عبر واتساب' : 'Inquire on WhatsApp' }}">
                                {{ $locale === 'ar' ? 'استفسار ↗' : 'Inquire ↗' }}
                            </a>
                        </div>
                    </div>
                </div>

            @endforeach

        </div>

        <!-- Bottom CTA -->
        <div class="mt-24 p-10 sm:p-14 bg-[#161616] border border-[#262626] text-center space-y-6">
            <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
                {{ $locale === 'ar' ? 'هل لديك فكرة أو متطلب برمجي خاص؟' : 'NEED A BESPOKE ARCHITECTURE?' }}
            </span>
            <h3 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'دعنا نبني نظامك البرمجي القادم بأعلى معايير الجودة' : 'Let\'s engineer your next production platform.' }}
            </h3>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans max-w-2xl mx-auto">
                {{ $locale === 'ar' 
                    ? 'من أنظمة الـ ERP المؤسسية إلى المنصات السحابية وأدوات الأتمتة المتقدمة، نضمن لك ملكية الكود 100% بدون أي قيود.'
                    : 'From enterprise ERP engines to multi-agent WhatsApp automation and cloud SaaS, get 100% full source code ownership.' }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs pt-2">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ $locale === 'ar' ? 'بدء معالج تحديد المواصفات' : 'Start Project Wizard' }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ $locale === 'ar' ? 'تواصل عبر واتساب' : 'WhatsApp Consultation' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
