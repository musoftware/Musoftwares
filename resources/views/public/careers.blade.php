@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_company.careers_meta_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_company.careers_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.careers_subtitle') }}
        </p>
    </div>

    <!-- Job Positions -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-8">
        
        <!-- Job 1 -->
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4 hover:border-[#748660] transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_company.careers_job_1_title') }}
                </h2>
                <span class="text-xs font-mono text-[#748660] font-bold">
                    {{ __('landing_company.careers_job_1_type') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_company.careers_job_1_desc') }}
            </p>
            <div class="pt-4">
                <a href="mailto:careers@musoftwares.com?subject=Application:%20Senior%20Backend%20Engineer" class="inline-block px-6 py-2.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider transition-colors">
                    {{ __('landing_company.careers_job_1_apply') }} ➔
                </a>
            </div>
        </div>

        <!-- Job 2 -->
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4 hover:border-[#748660] transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_company.careers_job_2_title') }}
                </h2>
                <span class="text-xs font-mono text-[#748660] font-bold">
                    {{ __('landing_company.careers_job_2_type') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_company.careers_job_2_desc') }}
            </p>
            <div class="pt-4">
                <a href="mailto:careers@musoftwares.com?subject=Application:%20Frontend%20Architect" class="inline-block px-6 py-2.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider transition-colors">
                    {{ __('landing_company.careers_job_2_apply') }} ➔
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
