@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_company.about_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_company.about_engineering_first_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.about_subtitle') }}
        </p>
    </div>

    <!-- 3 Pillars Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / ARCHITECTURE</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_company.about_data_sovereignty_title') }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_company.about_data_sovereignty_body') }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / RELIABILITY</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_company.about_engineering_first_title') }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_company.about_engineering_first_body') }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / CONTINUITY</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_company.about_long_term_title') }}
                </h2>
                <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_company.about_long_term_body') }}
                </p>
            </div>
        </div>

        <!-- Leadership Highlight Banner -->
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="space-y-3">
                <span class="text-xs font-mono text-[#748660] uppercase tracking-wider font-bold">Chief Software Architect</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                    {{ __('landing_bio.title') }}
                </h2>
                <p class="text-sm text-zinc-400 max-w-2xl font-sans">
                    {{ __('landing_bio.subtitle') }}
                </p>
            </div>

            <div class="flex items-center gap-4 shrink-0 font-mono text-xs">
                <a href="/about/mahmoud-amin" class="px-6 py-3 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-wider transition-colors">
                    {{ __('landing_bio.badge') }} ➔
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
