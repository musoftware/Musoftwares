@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $title = $project['title_' . $locale] ?? $project['title_en'];
    $desc = $project['desc_' . $locale] ?? $project['desc_en'];
    $category = $project['category_' . $locale] ?? $project['category'];
    $highlights = $project['highlights_' . $locale] ?? $project['highlights_en'];
@endphp

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-12 sm:pt-20 pb-28 sm:pb-36">
    
    <!-- Top Navigation Breadcrumb -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 mb-12">
        <a href="/portfolio" class="inline-flex items-center text-xs font-mono font-bold uppercase tracking-widest text-zinc-400 hover:text-white transition-colors group">
            <span class="inline-block transition-transform group-hover:-translate-x-1 rtl:group-hover:translate-x-1 me-2 rtl:me-0 rtl:ms-2">←</span>
            {{ $locale === 'ar' ? 'العودة إلى أرشيف المشاريع' : 'Back to Studio Archive' }}
        </a>
    </div>

    <!-- Main Project Hero & Showcase -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
            
            <!-- Left Meta Column (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="space-y-3">
                    <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
                        {{ $category }}
                    </span>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white font-sans tracking-tight leading-tight">
                        {{ $title }}
                    </h1>
                </div>

                <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed">
                    {{ $desc }}
                </p>

                <!-- Technical Specs Chips -->
                @if(!empty($project['techs']))
                    <div class="pt-6 border-t border-[#222222] space-y-3">
                        <span class="text-[11px] font-mono uppercase tracking-[0.2em] text-zinc-500 block">
                            {{ $locale === 'ar' ? 'البنية والتقنيات المستخدمة:' : 'Engineered Tech Stack:' }}
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($project['techs'] as $tech)
                                <span class="px-3 py-1 bg-black border border-[#2B2B2B] text-zinc-300 font-mono text-xs">
                                    {{ $tech }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Operational Metrics Grid -->
                @if(!empty($project['metrics']))
                    <div class="pt-6 border-t border-[#222222] grid grid-cols-2 gap-4">
                        @foreach($project['metrics'] as $key => $val)
                            <div class="bg-[#161616] border border-[#262626] p-3.5 space-y-1">
                                <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-wider block">{{ $key }}</span>
                                <span class="text-xs font-mono font-bold text-white block">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Action CTAs -->
                <div class="pt-6 flex flex-wrap gap-4 font-mono text-xs">
                    @if(!empty($project['live_url']))
                        <a href="{{ $project['live_url'] }}" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 bg-white text-black font-bold uppercase tracking-widest hover:bg-zinc-200 transition-all flex items-center gap-2">
                            <span>{{ $locale === 'ar' ? 'زيارة المنصة الحية' : 'Launch Platform' }}</span>
                            <span>↗</span>
                        </a>
                    @endif

                    <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I am interested in discussing a project similar to ' . $project['title_en']) }}" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all flex items-center gap-2">
                        <span>{{ $locale === 'ar' ? 'طلب نظام مماثل عبر واتساب' : 'Inquire Similar System' }}</span>
                        <span>➔</span>
                    </a>

                    <a href="/start-project" class="px-6 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                        {{ $locale === 'ar' ? 'بدء مشروع جديد' : 'Start New Project' }}
                    </a>
                </div>

            </div>

            <!-- Right Visual Mockup (7 cols) -->
            <div class="lg:col-span-7 bg-[#161616] border border-[#2B2B2B] overflow-hidden shadow-2xl flex flex-col">
                <!-- Browser Title Bar -->
                <div class="bg-black border-b border-[#2B2B2B] px-4 py-3 flex items-center gap-3 shrink-0">
                    <div class="flex gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                    </div>
                    <div class="flex-1 bg-[#141414] border border-[#262626] px-3 py-1 text-[11px] text-zinc-400 font-mono text-center truncate">
                        {{ $project['live_url'] ?? ('musoftwares.com/portfolio/' . $project['slug']) }}
                    </div>
                </div>

                <!-- Media Preview -->
                <div class="p-6 bg-[#0E0E0E] flex items-center justify-center min-h-[420px] max-h-[620px] overflow-hidden">
                    @if(!empty($project['img']))
                        <img src="{{ $project['img'] }}" alt="{{ $title }}" class="w-full h-auto object-contain max-h-[580px] shadow-lg">
                    @else
                        <div class="py-24 text-center font-mono text-zinc-600 text-xs">
                            [MUSOFTWARE PRODUCTION ARCHIVE]
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Architectural Highlights Section -->
        @if(!empty($highlights))
            <div class="mt-24 pt-16 border-t border-[#222222] space-y-8">
                <div class="space-y-2">
                    <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                        {{ $locale === 'ar' ? 'القدرات الهندسية والمعمارية' : 'Architectural & Engineering Capabilities' }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                        {{ $locale === 'ar' ? 'أبرز مميزات ومواصفات النظام' : 'Key Technical Capabilities' }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($highlights as $idx => $highlight)
                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-3 hover:border-[#748660] transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs font-bold text-[#748660] px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40">
                                    0{{ $idx + 1 }}
                                </span>
                                <span class="font-mono text-xs text-zinc-500 uppercase tracking-wider">
                                    {{ $locale === 'ar' ? 'مواصفة هندسية' : 'Specification' }}
                                </span>
                            </div>
                            <p class="text-sm text-zinc-300 font-sans leading-relaxed">
                                {{ $highlight }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Bottom Studio Consultation CTA Strip -->
        <div class="mt-24 p-10 sm:p-14 bg-[#161616] border border-[#262626] flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-start rtl:md:text-right">
                <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                    {{ $locale === 'ar' ? 'استشارة معمارية متخصصة' : 'Direct Architectural Consultation' }}
                </span>
                <h3 class="text-2xl font-bold text-white font-sans">
                    {{ $locale === 'ar' ? 'هل تخطط لبناء منصة برمجية مخصصة لشركتك؟' : 'Planning a custom enterprise platform for your business?' }}
                </h3>
                <p class="text-xs text-zinc-400 font-sans max-w-xl">
                    {{ $locale === 'ar' ? 'تواصل مباشرة مع محمود أمين لمناقشة المتطلبات الفنية، بنية قواعد البيانات، والتكلفة التقديرية.' : 'Talk directly with Mahmoud Amin to review architectural scope, database schemas, and delivery roadmaps.' }}
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 font-mono text-xs shrink-0">
                <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I would like to consult on building a custom system.') }}" target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all text-center">
                    {{ $locale === 'ar' ? 'استشارة فورية عبر واتساب' : 'WhatsApp Consultation' }}
                </a>
                <a href="/portfolio" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all text-center">
                    {{ $locale === 'ar' ? 'استعراض باقي المشاريع' : 'All Case Studies' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
