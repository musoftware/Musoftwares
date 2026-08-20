@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_company.contact_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_company.contact_meta_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.contact_subtitle') }}
        </p>
    </div>

    <!-- Quick Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-center font-mono text-xs mb-20 px-6">
        <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20a%20project." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto text-center bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest transition-all">
            {{ __('general.whatsapp_direct') }} ➔
        </a>
        <a href="mailto:admin@musoftwares.com" class="w-full sm:w-auto text-center border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest transition-all">
            admin@musoftwares.com
        </a>
    </div>

    <!-- 3 Channel Cards -->
    <div class="px-6 max-w-[1400px] mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Channel 1: WhatsApp -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        WA
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ __('landing_company.contact_support_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_company.contact_support_desc') }}
                    </p>
                </div>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="mt-8 text-xs font-mono font-bold text-[#748660] hover:text-white flex items-center gap-1">
                    <span>{{ __('general.chat_whatsapp') }} ↗</span>
                </a>
            </div>

            <!-- Channel 2: Email Proposal -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        @
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ __('landing_company.contact_sales_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_company.contact_sales_desc') }}
                    </p>
                </div>
                <a href="mailto:admin@musoftwares.com" class="mt-8 text-xs font-mono font-bold text-[#748660] hover:text-white flex items-center gap-1">
                    <span>{{ __('landing_company.contact_sales_title') }} ↗</span>
                </a>
            </div>

            <!-- Channel 3: Headquarters -->
            <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660] font-mono font-bold text-lg">
                        EG
                    </div>
                    <h2 class="text-xl font-bold text-white font-sans">
                        {{ __('landing_company.contact_hq_title') }}
                    </h2>
                    <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                        {{ __('landing_company.contact_hq_desc') }}
                    </p>
                </div>
                <div class="mt-8 text-xs font-mono text-zinc-500">
                    {{ __('landing_company.contact_hq_address') }} &bull; (UTC+2 / UTC+3)
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
