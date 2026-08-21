@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_bio.badge') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_bio.title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_bio.subtitle') }}
        </p>
    </div>

    <!-- Main Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- Left Profile Card (4 cols) -->
            <div class="lg:col-span-4 bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-6">
                <div class="w-20 h-20 bg-black rounded-2xl flex items-center justify-center font-sans text-2xl font-bold text-white shadow-md">
                    MA
                </div>
                
                <div class="space-y-1">
                    <h2 class="text-2xl font-bold text-[#1d1d1f] font-sans">Mahmoud Amin</h2>
                    <p class="text-xs font-semibold text-[#0071e3] uppercase tracking-wider">{{ __('landing_bio.role') }}</p>
                    <p class="text-xs text-[#1d1d1f]/60 font-sans">{{ __('landing_bio.location') }}</p>
                </div>

                <div class="pt-4 border-t border-black/5 space-y-3 text-xs font-sans text-[#1d1d1f]">
                    <div class="flex justify-between">
                        <span class="text-[#1d1d1f]/60">{{ __('landing_bio.experience_label') }}</span>
                        <span class="font-semibold">{{ __('landing_bio.experience_value') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#1d1d1f]/60">{{ __('landing_bio.platforms_label') }}</span>
                        <span class="font-semibold">{{ __('landing_bio.platforms_value') }}</span>
                    </div>
                    
                    <div class="space-y-1.5 pt-2">
                        <span class="text-[#1d1d1f]/60 block">{{ __('landing_bio.core_domains_label') }}</span>
                        <div class="flex flex-wrap gap-1.5 pt-1 text-[11px]">
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_saas') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_desktop') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_whatsapp') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_meta') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_erp') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_pos') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_fintech') }}</span>
                            <span class="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] font-medium rounded-full">{{ __('landing_bio.domain_rpa') }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-black/5 space-y-2">
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="w-full flex items-center justify-center gap-2 py-3 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                        <span>{{ __('landing_bio.msg_whatsapp') }}</span>
                    </a>

                    <div class="grid grid-cols-2 gap-2 pt-2 text-[11px] font-sans text-center">
                        <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" class="p-2.5 border border-black/5 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] rounded-xl font-medium transition-colors">LinkedIn ↗</a>
                        <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" class="p-2.5 border border-black/5 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] rounded-xl font-medium transition-colors">GitHub ↗</a>
                        <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" class="p-2.5 border border-black/5 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] rounded-xl font-medium transition-colors">X (Twitter) ↗</a>
                        <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" class="p-2.5 border border-black/5 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] rounded-xl font-medium transition-colors">Facebook ↗</a>
                    </div>
                </div>
            </div>

            <!-- Right Narrative (8 cols) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Statement -->
                <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#0071e3]">
                        {{ __('landing_bio.statement_badge') }}
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] tracking-tight font-sans">
                        {{ __('landing_bio.statement_title') }}
                    </h3>
                    <p class="text-sm sm:text-base text-[#1d1d1f]/70 font-sans leading-relaxed">
                        {{ __('landing_bio.statement_body') }}
                    </p>
                </div>

                <!-- Career Milestones -->
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-[#1d1d1f]/60">
                        {{ __('landing_bio.milestones_title') }}
                    </h4>

                    <div class="space-y-3">
                        <div class="bg-white border border-black/5 rounded-[20px] shadow-sm p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-sans text-xs">
                                <span class="text-[#1d1d1f] font-bold text-sm">{{ __('landing_bio.milestone_1_role') }}</span>
                                <span class="text-[#0071e3] font-semibold">2026</span>
                            </div>
                            <p class="text-xs text-[#1d1d1f]/60 font-sans">
                                {{ __('landing_bio.milestone_1_desc') }}
                            </p>
                        </div>

                        <div class="bg-white border border-black/5 rounded-[20px] shadow-sm p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-sans text-xs">
                                <span class="text-[#1d1d1f] font-bold text-sm">{{ __('landing_bio.milestone_2_role') }}</span>
                                <span class="text-[#0071e3] font-semibold">2023 - 2025</span>
                            </div>
                            <p class="text-xs text-[#1d1d1f]/60 font-sans">
                                {{ __('landing_bio.milestone_2_desc') }}
                            </p>
                        </div>

                        <div class="bg-white border border-black/5 rounded-[20px] shadow-sm p-6 space-y-2">
                            <div class="flex justify-between items-baseline font-sans text-xs">
                                <span class="text-[#1d1d1f] font-bold text-sm">{{ __('landing_bio.milestone_3_role') }}</span>
                                <span class="text-[#0071e3] font-semibold">2019 - 2022</span>
                            </div>
                            <p class="text-xs text-[#1d1d1f]/60 font-sans">
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
