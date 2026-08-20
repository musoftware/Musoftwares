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
    class="w-full bg-[#ffffff] text-[#1d1d1f] pt-16 sm:pt-24 pb-28"
>
    
    <!-- Page Header -->
    <div class="max-w-[1024px] mx-auto px-6 text-center space-y-4 mb-16 apple-reveal">
        <span class="text-[14px] font-semibold text-[#86868b] tracking-tight block">
            {{ $locale === 'ar' ? 'سجل الأعمال والأنظمة المنفذة' : 'PROVEN PRODUCTION ARCHIVE' }}
        </span>
        <h1 class="text-4xl sm:text-6xl font-bold text-[#1d1d1f] apple-headline tracking-tight">
            {{ $locale === 'ar' ? 'معرض الأنظمة والمشاريع البرمجية' : 'Case Studies & Production Platforms' }}
        </h1>
        <p class="text-lg sm:text-xl text-[#86868b] max-w-2xl mx-auto apple-subhead">
            {{ $locale === 'ar' 
                ? 'استعرض أكثر من 30 تطبيق ويب، تطبيق موبايل، ومحركاً برمجياً تم تصميمه وهندسته بالكامل بمعايير مؤسسية وأداء فائق السرعة.'
                : 'Explore over 30 production platforms, mobile apps, desktop automation suites, and custom cloud systems shipped by Mahmoud Amin.' }}
        </p>
    </div>

    <!-- Category Filter Tabs & Search Bar -->
    <div class="max-w-[1140px] mx-auto px-4 mb-12 space-y-6">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4 border-b border-black/[0.08] pb-6">
            
            <!-- Category Pills -->
            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                @foreach($categories as $catKey => $catLabels)
                    <button 
                        @click="activeTab = '{{ $catKey }}'"
                        :class="activeTab === '{{ $catKey }}' 
                            ? 'bg-[#1d1d1f] text-white font-semibold' 
                            : 'bg-[#f5f5f7] text-[#86868b] border border-black/[0.06] hover:text-[#1d1d1f]'"
                        class="px-4 py-1.5 rounded-full text-[13px] transition-all"
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
                    class="w-full bg-[#f5f5f7] border border-black/[0.08] text-[#1d1d1f] rounded-full px-4 py-2 text-xs focus:border-[#0071e3] focus:outline-none transition-colors"
                />
            </div>

        </div>
    </div>

    <!-- Portfolio Bento Grid -->
    <div class="max-w-[1140px] mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
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
                    class="bg-[#f5f5f7] rounded-3xl border border-black/[0.05] overflow-hidden group flex flex-col justify-between apple-bento-card"
                >
                    <div>
                        <!-- Thumbnail Image (Proportionally Scaled & Contained) -->
                        <div class="h-56 bg-[#fbfbfd] flex items-center justify-center p-3 overflow-hidden relative border-b border-black/[0.06]">
                            @if(!empty($proj['img']))
                                <img src="{{ $proj['img'] }}" alt="{{ $projTitle }}" class="max-h-[190px] w-auto max-w-[95%] object-contain rounded-xl shadow-sm group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-[#f5f5f7] text-[#86868b] text-xs">
                                    [PRODUCTION SYSTEM]
                                </div>
                            @endif
                            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-md px-2.5 py-1 text-[11px] text-[#0071e3] font-semibold rounded-full border border-black/[0.06] shadow-sm">
                                {{ $projCatLocalized }}
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-2">
                            <h2 class="text-xl font-bold text-[#1d1d1f] tracking-tight group-hover:text-[#0071e3] transition-colors">
                                <a href="/portfolio/{{ $slug }}">
                                    {{ $projTitle }}
                                </a>
                            </h2>
                            <p class="text-xs text-[#86868b] leading-relaxed line-clamp-3">
                                {{ $projDesc }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer & Actions -->
                    <div class="p-6 pt-0 space-y-4">
                        <!-- Tech Chips -->
                        @if(!empty($proj['techs']))
                            <div class="flex flex-wrap gap-1.5 pt-4 border-t border-black/[0.06]">
                                @foreach(array_slice($proj['techs'], 0, 3) as $tech)
                                    <span class="text-[11px] bg-white border border-black/[0.06] text-[#86868b] px-2.5 py-0.5 rounded-full">
                                        {{ $tech }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Direct Link to Dedicated Page -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <a href="/portfolio/{{ $slug }}" class="text-[#0066cc] hover:underline font-semibold flex items-center gap-1">
                                <span>{{ $locale === 'ar' ? 'دراسة المشروع' : 'Case Study' }}</span>
                                <span>›</span>
                            </a>

                            <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I would like to inquire about ' . $proj['title_en']) }}" target="_blank" rel="noopener noreferrer" class="text-[#86868b] hover:text-[#1d1d1f] transition-colors">
                                {{ $locale === 'ar' ? 'استفسار ↗' : 'Inquire ↗' }}
                            </a>
                        </div>
                    </div>
                </div>

            @endforeach

        </div>

        <!-- Bottom CTA -->
        <div class="mt-20 p-10 sm:p-14 bg-[#f5f5f7] rounded-3xl border border-black/[0.05] text-center space-y-4 apple-reveal">
            <span class="text-[13px] font-semibold text-[#86868b] uppercase tracking-wider block">
                {{ $locale === 'ar' ? 'هل لديك فكرة أو متطلب برمجي خاص؟' : 'NEED A BESPOKE ARCHITECTURE?' }}
            </span>
            <h3 class="text-2xl sm:text-4xl font-bold text-[#1d1d1f] tracking-tight apple-headline">
                {{ $locale === 'ar' ? 'دعنا نبني تطبيقك القادم بأعلى معايير الجودة والسرعة' : 'Let\'s engineer your next production platform.' }}
            </h3>
            <p class="text-sm text-[#86868b] max-w-xl mx-auto">
                {{ $locale === 'ar' 
                    ? 'من تطبيقات الويب والموبايل المخصصة إلى محركات محادثات واتساب وأنظمة المدفوعات، نضمن لك ملكية الكود 100% بدون أي قيود.'
                    : 'From custom web and mobile apps to multi-agent WhatsApp automation and FinTech systems, get 100% full source code ownership.' }}
            </p>
            <div class="flex items-center justify-center gap-4 pt-2">
                <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[14px] px-6 py-2.5 shadow-sm">
                    {{ $locale === 'ar' ? 'بدء معالج المواصفات' : 'Start Project Wizard' }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[14px] px-6 py-2.5">
                    {{ $locale === 'ar' ? 'تواصل عبر واتساب' : 'WhatsApp Consultation' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
