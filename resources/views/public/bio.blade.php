@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_bio.badge') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_bio.title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_bio.subtitle') }}
        </p>
    </div>

    <!-- Main Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- Left Profile Card (4 cols) -->
            <div class="lg:col-span-4 bg-[#161616] border border-[#2B2B2B] p-8 space-y-6">
                <div class="w-24 h-24 bg-[#1E2619] border-2 border-[#748660] flex items-center justify-center font-mono text-3xl font-black text-[#748660]">
                    MA
                </div>
                
                <div class="space-y-1">
                    <h2 class="text-2xl font-bold text-white font-sans">Mahmoud Amin</h2>
                    <p class="text-xs font-mono text-[#748660] uppercase tracking-wider">{{ __('landing_bio.role') }}</p>
                    <p class="text-xs text-zinc-400 font-sans">{{ __('landing_bio.location') }}</p>
                </div>

                <div class="pt-4 border-t border-[#222222] space-y-3 text-xs font-mono text-zinc-300">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">{{ __('landing_bio.experience_label') }}</span>
                        <span>{{ __('landing_bio.experience_value') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">{{ __('landing_bio.platforms_label') }}</span>
                        <span>{{ __('landing_bio.platforms_value') }}</span>
                    </div>
                    
                    <div class="space-y-1.5 pt-2">
                        <span class="text-zinc-500 block">{{ __('landing_bio.core_domains_label') }}</span>
                        <div class="flex flex-wrap gap-1.5 pt-1 text-[10px]">
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_saas') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_desktop') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_whatsapp') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_meta') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_erp') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_pos') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_fintech') }}</span>
                            <span class="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">{{ __('landing_bio.domain_rpa') }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#222222] space-y-2">
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="w-full flex items-center justify-center gap-2 py-3 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider transition-colors">
                        <span>{{ __('landing_bio.msg_whatsapp') }}</span>
                    </a>

                    <div class="grid grid-cols-2 gap-2 pt-2 text-[11px] font-mono text-center">
                        <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">LinkedIn ↗</a>
                        <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">GitHub ↗</a>
                        <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">X (Twitter) ↗</a>
                        <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" class="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors">Facebook ↗</a>
                    </div>
                </div>
            </div>

            <!-- Right Narrative (8 cols) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Statement -->
                <div class="bg-[#161616] border border-[#2B2B2B] p-8 sm:p-10 space-y-6">
                    <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                        {{ __('landing_bio.statement_badge') }}
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                        {{ __('landing_bio.statement_title') }}
                    </h3>
                    <p class="text-sm text-zinc-300 font-sans leading-relaxed">
                        {{ __('landing_bio.statement_body') }}
                    </p>
                </div>

                <!-- Career Milestones -->
                <div class="space-y-4">
                    <h4 class="text-xs font-mono uppercase tracking-wider text-zinc-400 font-bold">
                        {{ __('landing_bio.milestones_title') }}
                    </h4>

                    <div class="space-y-3">
                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">{{ __('landing_bio.milestone_1_role') }}</span>
                                <span class="text-[#748660]">2026</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                {{ __('landing_bio.milestone_1_desc') }}
                            </p>
                        </div>

                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">{{ __('landing_bio.milestone_2_role') }}</span>
                                <span class="text-[#748660]">2023 - 2025</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                {{ __('landing_bio.milestone_2_desc') }}
                            </p>
                        </div>

                        <div class="bg-[#161616] border border-[#262626] p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-mono text-xs">
                                <span class="text-white font-bold">{{ __('landing_bio.milestone_3_role') }}</span>
                                <span class="text-[#748660]">2019 - 2022</span>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans">
                                {{ __('landing_bio.milestone_3_desc') }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection
