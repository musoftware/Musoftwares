@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $projectTitle = $project['title_' . $locale] ?? $project['title_en'] ?? 'Musoftware System';
    $desc = $project['desc_' . $locale] ?? $project['desc_en'] ?? '';
    $category = $project['category_' . $locale] ?? $project['category'] ?? 'Platform';
    $highlights = $project['highlights_' . $locale] ?? $project['highlights_en'] ?? [];
    $metrics = $project['metrics_' . $locale] ?? $project['metrics'] ?? [];
    $isAr = $locale === 'ar';
    // Ready messages are written as the visitor, in the page language.
    $similarMessage = $isAr
        ? 'أهلًا، أنا شفت شغلكم في "'.$projectTitle.'" وعايز أتكلم معاكم عن مشروع شبهه.'
        : 'Hi, I saw your work on "'.$projectTitle.'" and would like to discuss a similar project.';
@endphp


@section('content')
<div class="w-full bg-[#ffffff] text-[#1d1d1f] pt-12 sm:pt-16 pb-28">
    
    <!-- Top Navigation Breadcrumb -->
    <div class="max-w-[1140px] mx-auto px-6 mb-8">
        <a href="/portfolio" class="inline-flex items-center text-[13px] text-[#0066cc] hover:underline transition-colors group">
            <svg class="w-4 h-4 me-1.5 rtl:rotate-180 transition-transform group-hover:-translate-x-1 rtl:group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 5l-5 5 5 5"/></svg>
            {{ $isAr ? 'الرجوع لأعمالنا' : 'Back to our work' }}
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
                        {{ $projectTitle }}
                    </h1>
                </div>

                <p class="text-base text-[#86868b] leading-relaxed apple-subhead">
                    {{ $desc }}
                </p>

                {{-- Short facts about the project, in the page language when the data has them --}}
                @if(!empty($metrics))
                    <div class="pt-6 border-t border-black/[0.08] grid grid-cols-2 gap-3">
                        @foreach($metrics as $key => $val)
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
                            <span>{{ $isAr ? 'زيارة الموقع' : 'Visit the live site' }}</span>
                            <svg class="w-3.5 h-3.5 rtl:-scale-x-100" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 13l6-6M8 7h5v5"/></svg>
                        </a>
                    @endif

                    <a href="/start-project" class="apple-pill-btn apple-pill-secondary text-[13px] px-5 py-2 flex items-center">
                        {{ $isAr ? 'عايز حاجة زي دي؟ ابدأ مشروعك' : 'Want one like this? Start your project' }}
                    </a>

                    <a href="{{ \App\Support\SiteNav::whatsAppUrl($similarMessage) }}" target="_blank" rel="noopener noreferrer" class="apple-pill-btn text-[13px] px-5 py-2 border border-black/[0.12] text-[#515154] hover:text-[#1d1d1f] hover:border-black transition-all">
                        {{ $isAr ? 'كلمنا على واتساب' : 'Message us on WhatsApp' }}
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
                    <div dir="ltr" class="flex-1 bg-white rounded-md px-3 py-1 text-[11px] text-[#86868b] text-center truncate shadow-sm">
                        {{ $project['live_url'] ?? ('musoftwares.com/portfolio/' . ($project['slug'] ?? 'system')) }}
                    </div>
                </div>

                <!-- Media Preview -->
                <div class="p-6 bg-white flex items-center justify-center min-h-[420px] max-h-[620px] overflow-hidden">
                    @if(!empty($project['img']))
                        <img src="{{ $project['img'] }}" alt="{{ $projectTitle }}" class="w-full h-auto object-contain max-h-[580px] shadow-md rounded-xl">
                    @else
                        <div class="py-24 text-center text-[#86868b] text-xs">
                            [MUSOFTWARE PRODUCTION ARCHIVE]
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- More screens of the same project (real screenshots taken on demo data) --}}
        @if(count($project['gallery'] ?? []) > 1)
            <div class="mt-16 pt-12 border-t border-black/[0.08]">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'شاشات من النظام' : 'Screens from the system' }}
                </h2>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach(array_slice($project['gallery'], 1) as $shot)
                        <a href="{{ $shot }}" target="_blank" rel="noopener" class="block bg-[#f5f5f7] rounded-2xl border border-black/[0.06] p-3 hover:shadow-md transition-shadow">
                            <img src="{{ $shot }}" alt="{{ $projectTitle }}" loading="lazy" class="w-full h-auto rounded-xl">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Custom Case Study Deep Dive (Extended by dedicated blade files) -->
        @yield('case_study')

        {{-- What was built: the features in plain words --}}
        @sectionMissing('case_study')
            @if(!empty($highlights))
                <div class="mt-20 pt-12 border-t border-black/[0.08] space-y-8">
                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight">
                            {{ $isAr ? 'اللي عملناه في المشروع ده' : 'What we built in this project' }}
                        </h2>
                    </div>

                    <ol class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($highlights as $idx => $highlight)
                            <li class="flex items-start gap-3 bg-[#f5f5f7] rounded-2xl border border-black/[0.05] p-5">
                                <span class="shrink-0 w-7 h-7 rounded-full bg-[#0071e3]/10 text-[#0071e3] text-[13px] font-semibold flex items-center justify-center">{{ $idx + 1 }}</span>
                                <p class="text-[15px] text-[#1d1d1f] leading-relaxed">{{ $highlight }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        @endif

        {{-- Technical details come last: most visitors care about what the project does, not how --}}
        @if(!empty($project['techs']))
            <div class="mt-16 pt-10 border-t border-black/[0.08] space-y-3">
                <h2 class="text-xl font-bold text-[#1d1d1f] tracking-tight">{{ $isAr ? 'تفاصيل تقنية' : 'Technical details' }}</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach($project['techs'] as $tech)
                        <li class="px-3 py-1 bg-[#f5f5f7] border border-black/[0.06] text-[#1d1d1f] text-[13px] rounded-full" dir="ltr">{{ $tech }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Bottom Studio Consultation CTA Strip -->
        <div class="mt-20 p-8 sm:p-12 bg-[#f5f5f7] rounded-3xl border border-black/[0.05] flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-start rtl:md:text-right">
                <h3 class="text-2xl font-bold text-[#1d1d1f] tracking-tight">
                    {{ $isAr ? 'عايز موقع أو برنامج لشغلك؟' : 'Want a website or program for your business?' }}
                </h3>
                <p class="text-[15px] text-[#515154] max-w-xl">
                    {{ $isAr ? 'احكيلنا عن شغلك، ونقولك نقدر نعمل إيه وهيتكلف كام.' : 'Tell us about your business, and we will tell you what we can build and what it costs.' }}
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                <a href="/start-project" class="apple-pill-btn apple-pill-primary text-[13px] px-6 py-2.5 text-center shadow-sm">
                    {{ $isAr ? 'ابدأ مشروعك' : 'Start your project' }}
                </a>
                <a href="{{ \App\Support\SiteNav::whatsAppChatUrl() }}" target="_blank" rel="noopener noreferrer" class="apple-pill-btn apple-pill-secondary text-[13px] px-6 py-2.5 text-center">
                    {{ $isAr ? 'كلمنا على واتساب' : 'Message us on WhatsApp' }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
