@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('general.platforms') }}
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

    <!-- 4 Platform Architectures Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Platform 1: ERP -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-base">
                        ERP
                    </div>
                    <h2 class="text-2xl font-bold text-white font-sans">
                        {{ __('landing_platforms.erp_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_platforms.erp_subtitle') }}
                    </p>
                </div>
                <div class="pt-8 flex justify-between items-center">
                    <a href="/platforms/erp" class="text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('landing_home.service_erp_action') }}</span>
                    </a>
                </div>
            </div>

            <!-- Platform 2: WhatsApp & CRM -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-base">
                        WA
                    </div>
                    <h2 class="text-2xl font-bold text-white font-sans">
                        {{ __('landing_platforms.crm_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_platforms.crm_subtitle') }}
                    </p>
                </div>
                <div class="pt-8 flex justify-between items-center">
                    <a href="/platforms/crm" class="text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('landing_home.service_wa_action') }}</span>
                    </a>
                </div>
            </div>

            <!-- Platform 3: Web & SaaS -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-base">
                        WEB
                    </div>
                    <h2 class="text-2xl font-bold text-white font-sans">
                        {{ __('landing_home.service_web_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_home.service_web_desc') }}
                    </p>
                </div>
                <div class="pt-8 flex justify-between items-center">
                    <a href="/start-project" class="text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('landing_home.service_web_action') }}</span>
                    </a>
                </div>
            </div>

            <!-- Platform 4: POS & Desktop .NET -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-base">
                        POS
                    </div>
                    <h2 class="text-2xl font-bold text-white font-sans">
                        {{ __('landing_home.service_pos_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_home.service_pos_desc') }}
                    </p>
                </div>
                <div class="pt-8 flex justify-between items-center">
                    <a href="/start-project" class="text-xs font-mono text-[#748660] hover:text-white font-bold flex items-center gap-1">
                        <span>{{ __('landing_home.service_pos_action') }}</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
