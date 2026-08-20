@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_platforms.crm_badge') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_platforms.crm_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_platforms.crm_subtitle') }}
        </p>
    </div>

    <!-- 4 Key Advantages -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">01 / OFFICIAL API</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_platforms.crm_card_1_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_platforms.crm_card_1_desc') }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">02 / NOTIFICATIONS</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_platforms.crm_card_2_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_platforms.crm_card_2_desc') }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">03 / MULTI-AGENT</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_platforms.crm_card_3_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_platforms.crm_card_3_desc') }}
                </p>
            </div>

            <div class="bg-[#161616] border border-[#262626] p-8 space-y-4 hover:border-[#748660] transition-colors">
                <span class="text-xs font-mono text-[#748660] font-bold">04 / BOTS & AI</span>
                <h2 class="text-xl font-bold text-white font-sans">
                    {{ __('landing_platforms.crm_card_4_title') }}
                </h2>
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ __('landing_platforms.crm_card_4_desc') }}
                </p>
            </div>

        </div>

        <!-- Action Box -->
        <div class="bg-[#161616] border border-[#262626] p-10 sm:p-14 text-center space-y-6">
            <h3 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ __('landing_platforms.crm_cta_title') }}
            </h3>
            <p class="text-sm text-zinc-400 max-w-xl mx-auto font-sans">
                {{ __('landing_platforms.crm_cta_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs pt-2">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ __('landing_platforms.crm_cta_req') }}
                </a>
                <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20integrate%20WhatsApp%20Cloud%20API." target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ __('landing_platforms.crm_cta_wa') }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
