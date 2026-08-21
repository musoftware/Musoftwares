@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_company.about_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_company.about_engineering_first_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.about_subtitle') }}
        </p>
    </div>

    <!-- 3 Pillars Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-16">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4 hover:shadow-md transition-shadow">
                <span class="text-xs font-semibold text-[#0071e3]">01 / ARCHITECTURE</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_company.about_data_sovereignty_title') }}
                </h2>
                <p class="text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                    {{ __('landing_company.about_data_sovereignty_body') }}
                </p>
            </div>

            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4 hover:shadow-md transition-shadow">
                <span class="text-xs font-semibold text-[#0071e3]">02 / RELIABILITY</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_company.about_engineering_first_title') }}
                </h2>
                <p class="text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                    {{ __('landing_company.about_engineering_first_body') }}
                </p>
            </div>

            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4 hover:shadow-md transition-shadow">
                <span class="text-xs font-semibold text-[#0071e3]">03 / CONTINUITY</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_company.about_long_term_title') }}
                </h2>
                <p class="text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                    {{ __('landing_company.about_long_term_body') }}
                </p>
            </div>
        </div>

        <!-- Leadership Highlight Banner -->
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="space-y-3">
                <span class="text-xs font-semibold text-[#0071e3] uppercase tracking-wider">Chief Software Architect</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_bio.title') }}
                </h2>
                <p class="text-sm text-[#1d1d1f]/60 max-w-2xl font-sans">
                    {{ __('landing_bio.subtitle') }}
                </p>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <a href="/about/mahmoud-amin" class="px-6 py-3 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                    {{ __('landing_bio.badge') }} ➔
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
