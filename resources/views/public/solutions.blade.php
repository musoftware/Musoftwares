@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('general.solutions') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_home.services_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_home.hero_subtitle_1') }}
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 items-center justify-center font-mono text-xs pt-4">
            <a href="/start-project" class="w-full sm:w-auto px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                {{ __('general.start_a_project') }} ➔
            </a>
            <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                {{ __('general.whatsapp_direct') }}
            </a>
        </div>
    </div>

    <!-- Solutions Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Solution 1: Healthcare & Clinics -->
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / HEALTHCARE</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_erp_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_erp_desc') }}
                </p>
                <div class="pt-4">
                    <a href="/start-project" class="text-xs font-mono text-[#748660] hover:text-white font-bold">
                        {{ __('general.start_project_wizard') }} ➔
                    </a>
                </div>
            </div>

            <!-- Solution 2: E-Commerce & Marketplaces -->
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / ECOMMERCE</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_web_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_web_desc') }}
                </p>
                <div class="pt-4">
                    <a href="/start-project" class="text-xs font-mono text-[#748660] hover:text-white font-bold">
                        {{ __('general.start_project_wizard') }} ➔
                    </a>
                </div>
            </div>

            <!-- Solution 3: FinTech & POS Ledgers -->
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / FINTECH & POS</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_home.service_pos_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_home.service_pos_desc') }}
                </p>
                <div class="pt-4">
                    <a href="/start-project" class="text-xs font-mono text-[#748660] hover:text-white font-bold">
                        {{ __('general.start_project_wizard') }} ➔
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
