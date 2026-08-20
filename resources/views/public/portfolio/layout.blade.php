@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $title = $project['title_' . $locale] ?? $project['title_en'] ?? 'Musoftware System';
    $desc = $project['desc_' . $locale] ?? $project['desc_en'] ?? '';
    $category = $project['category_' . $locale] ?? $project['category'] ?? 'Platform';
    $highlights = $project['highlights_' . $locale] ?? $project['highlights_en'] ?? [];
@endphp

@section('title', $title . ' | Musoftwares')
@section('description', $desc)

@section('content')
<div class="w-full bg-[#ffffff] text-[#1d1d1f] pt-12 sm:pt-16 pb-28">
    
    <!-- Top Navigation Breadcrumb -->
    <div class="max-w-[1140px] mx-auto px-6 mb-8">
        <a href="/portfolio" class="inline-flex items-center text-[13px] text-[#0066cc] hover:underline transition-colors group">
            <span class="inline-block transition-transform group-hover:-translate-x-1 rtl:group-hover:translate-x-1 me-2 rtl:me-0 rtl:ms-2">‹</span>
            {{ $locale === 'ar' ? 'العودة إلى أرشيف المشاريع' : 'Back to Studio Archive' }}
        </a>
    </div>

    <!-- Main Project Hero & Showcase -->
    <div class="max-w-[1140px] mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- Left Meta Column (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="space-y-2">
                    <span class="text-[13px] font-semibold text-[#0071e3] uppercase tracking-wider block">
                        {{ $category }}
                    </span>
                    <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] tracking-tight apple-headline leading-tight">
                        {{ $title }}
                    </h1>
                </div>

                <p class="text-base text-[#86868b] leading-relaxed apple-subhead">
                    {{ $desc }}
                </p>

                <!-- Technical Specs Chips -->
                @if(!empty($project['techs']))
                    <div class="pt-6 border-t border-black/[0.08] space-y-3">
                        <span class="text-[12px] uppercase tracking-wider text-[#86868b] font-medium block">
                            {{ $locale === 'ar' ? 'البنية والتقنيات المستخدمة:' : 'Engineered Tech Stack:' }}
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($project['techs'] as $tech)
                                <span class="px-3 py-1 bg-[#f5f5f7] border border-black/[0.06] text-[#1d1d1f] text-[12px] rounded-full">
                                    {{ $tech }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Operational Metrics Grid -->
                @if(!empty($project['metrics']))
                    <div class="pt-6 border-t border-black/[0.08] grid grid-cols-2 gap-3">
                        @foreach($project['metrics'] as $key => $val)
                            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.05] p-4 space-y-1">
                                <span class="text-[10px] text-[#86868b] uppercase tracking-wider block font-semibold">{{ $key }}</span>
                                <span class="text-[14px] font-semibold text-[#1d1d1f] block">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Action CTAs -->
                <div class="pt-6 flex flex-wrap gap-3">
                    @if(!empty($project['live_url']))
                        <a href="{{ $project['live_url'] }}" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-primary text-[13px] px-5 py-2 flex items-center gap-1.5 shadow-sm">
                            <span>{{ $locale === 'ar' ? 'زيارة المنصة الحية' : 'Launch Platform' }}</span>
                            <span>↗</span>
                        </a>
                    @endif

                    <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I am interested in discussing a project similar to ' . ($project['title_en'] ?? $title)) }}" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-5 py-2 flex items-center gap-1.5">
                        <span>{{ $locale === 'ar' ? 'استشارة واتساب' : 'WhatsApp Consultation' }}</span>
                        <span>➔</span>
                    </a>

                    <a href="/start-project" class="apple-pill-btn text-[13px] px-5 py-2 border border-black/[0.12] text-[#86868b] hover:text-[#1d1d1f] hover:border-black transition-all">
                        {{ $locale === 'ar' ? 'بدء مشروع جديد' : 'Start New Project' }}
                    </a>
                </div>

            </div>

            <!-- Right Visual Mockup (7 cols) -->
            <div class="lg:col-span-7 bg-[#f5f5f7] rounded-3xl border border-black/[0.06] overflow-hidden shadow-xl flex flex-col">
                <!-- Browser Title Bar -->
                <div class="bg-[#e8e8ed] border-b border-black/[0.06] px-4 py-3 flex items-center gap-3 shrink-0">
                    <div class="flex gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-[#ffbd2e]"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-[#27c93f]"></span>
                    </div>
                    <div class="flex-1 bg-white rounded-md px-3 py-1 text-[11px] text-[#86868b] text-center truncate shadow-sm">
                        {{ $project['live_url'] ?? ('musoftwares.com/portfolio/' . ($project['slug'] ?? 'system')) }}
                    </div>
                </div>

                <!-- Media Preview -->
                <div class="p-6 bg-white flex items-center justify-center min-h-[420px] max-h-[620px] overflow-hidden">
                    @if(!empty($project['img']))
                        <img src="{{ $project['img'] }}" alt="{{ $title }}" class="w-full h-auto object-contain max-h-[580px] shadow-md rounded-xl">
                    @else
                        <div class="py-24 text-center text-[#86868b] text-xs">
                            [MUSOFTWARE PRODUCTION ARCHIVE]
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Custom Case Study Deep Dive (Extended by dedicated blade files) -->
        @yield('case_study')

        <!-- Architectural Highlights Section -->
        @sectionMissing('case_study')
            @if(!empty($highlights))
                <div class="mt-20 pt-12 border-t border-black/[0.08] space-y-8">
                    <div class="space-y-2">
                        <span class="text-[13px] font-semibold text-[#0071e3] uppercase tracking-wider block">
                            {{ $locale === 'ar' ? 'القدرات الهندسية والمعمارية' : 'Architectural & Engineering Capabilities' }}
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                            {{ $locale === 'ar' ? 'أبرز مواصفات وقدرات النظام' : 'Key Technical Capabilities' }}
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($highlights as $idx => $highlight)
                            <div class="bg-[#f5f5f7] rounded-2xl border border-black/[0.05] p-6 space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-[#0071e3] px-2 py-0.5 bg-[#0071e3]/10 rounded-md">
                                        0{{ $idx + 1 }}
                                    </span>
                                    <span class="text-xs text-[#86868b] uppercase tracking-wider">
                                        {{ $locale === 'ar' ? 'مواصفة معمارية' : 'Specification' }}
                                    </span>
                                </div>
                                <p class="text-sm text-[#1d1d1f] leading-relaxed">
                                    {{ $highlight }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <!-- Bottom Studio Consultation CTA Strip -->
        <div class="mt-20 p-8 sm:p-12 bg-[#f5f5f7] rounded-3xl border border-black/[0.05] flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-start rtl:md:text-right">
                <span class="text-[12px] font-semibold text-[#0071e3] uppercase tracking-wider block">
                    {{ $locale === 'ar' ? 'استشارة معمارية مباشرة' : 'Direct Architectural Consultation' }}
                </span>
                <h3 class="text-2xl font-bold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'هل تخطط لبناء منصة برمجية مخصصة لشركتك؟' : 'Planning a custom web or mobile platform for your business?' }}
                </h3>
                <p class="text-xs text-[#86868b] max-w-xl">
                    {{ $locale === 'ar' ? 'تواصل مباشرة مع محمود أمين لمناقشة المتطلبات الفنية، بنية قواعد البيانات، والتكلفة التقديرية.' : 'Talk directly with Mahmoud Amin to review architectural scope, database schemas, and delivery roadmaps.' }}
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                <a href="https://wa.me/201015218548?text={{ urlencode('Hello Mahmoud, I would like to consult on building a custom system.') }}" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-primary text-[13px] px-6 py-2.5 text-center shadow-sm">
                    {{ $locale === 'ar' ? 'استشارة واتساب' : 'WhatsApp Consultation' }}
                </a>
                <a href="/portfolio" class="apple-pill-btn apple-pill-secondary text-[13px] px-6 py-2.5 text-center">
                    {{ $locale === 'ar' ? 'كافة المشاريع' : 'All Case Studies' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
