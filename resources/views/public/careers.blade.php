@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_company.careers_meta_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_company.careers_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.careers_subtitle') }}
        </p>
    </div>

    <!-- Job Positions -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-6">
        
        <!-- Job 1 -->
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_company.careers_job_1_title') }}
                </h2>
                <span class="text-xs font-semibold text-[#0071e3] px-2.5 py-1 bg-[#0071e3]/10 rounded-full">
                    {{ __('landing_company.careers_job_1_type') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                {{ __('landing_company.careers_job_1_desc') }}
            </p>
            <div class="pt-2">
                <a href="mailto:careers@musoftwares.com?subject=Application:%20Senior%20Backend%20Engineer" class="inline-block px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                    {{ __('landing_company.careers_job_1_apply') }} ➔
                </a>
            </div>
        </div>

        <!-- Job 2 -->
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_company.careers_job_2_title') }}
                </h2>
                <span class="text-xs font-semibold text-[#0071e3] px-2.5 py-1 bg-[#0071e3]/10 rounded-full">
                    {{ __('landing_company.careers_job_2_type') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                {{ __('landing_company.careers_job_2_desc') }}
            </p>
            <div class="pt-2">
                <a href="mailto:careers@musoftwares.com?subject=Application:%20Frontend%20Architect" class="inline-block px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                    {{ __('landing_company.careers_job_2_apply') }} ➔
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
