@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_company.contact_title') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_company.contact_meta_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_company.contact_subtitle') }}
        </p>
    </div>

    <!-- Quick Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-center text-xs mb-20 px-6">
        <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20a%20project." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto text-center bg-[#0071e3] text-white hover:bg-[#0077ed] px-8 py-3.5 font-semibold uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
            {{ __('general.whatsapp_direct') }} ➔
        </a>
        <a href="mailto:admin@musoftwares.com" class="w-full sm:w-auto text-center border border-black/10 bg-white hover:bg-[#f5f5f7] text-[#1d1d1f] px-8 py-3.5 font-semibold uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
            admin@musoftwares.com
        </a>
    </div>

    <!-- 3 Channel Cards -->
    <div class="px-6 max-w-[1400px] mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Channel 1: WhatsApp -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 flex flex-col justify-between group hover:shadow-md hover:border-black/10 transition-all">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-emerald-50 border border-emerald-200/60 rounded-2xl flex items-center justify-center text-emerald-600 font-sans font-bold text-lg">
                        WA
                    </div>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                        {{ __('landing_company.contact_support_title') }}
                    </h2>
                    <p class="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                        {{ __('landing_company.contact_support_desc') }}
                    </p>
                </div>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="mt-8 text-xs font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                    <span>{{ __('general.chat_whatsapp') }} ↗</span>
                </a>
            </div>

            <!-- Channel 2: Email Proposal -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 flex flex-col justify-between group hover:shadow-md hover:border-black/10 transition-all">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-[#0071e3]/10 border border-[#0071e3]/20 rounded-2xl flex items-center justify-center text-[#0071e3] font-sans font-bold text-lg">
                        @
                    </div>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                        {{ __('landing_company.contact_sales_title') }}
                    </h2>
                    <p class="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                        {{ __('landing_company.contact_sales_desc') }}
                    </p>
                </div>
                <a href="mailto:admin@musoftwares.com" class="mt-8 text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>{{ __('landing_company.contact_sales_title') }} ↗</span>
                </a>
            </div>

            <!-- Channel 3: Headquarters -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 flex flex-col justify-between group hover:shadow-md hover:border-black/10 transition-all">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-[#f5f5f7] border border-black/5 rounded-2xl flex items-center justify-center text-[#1d1d1f] font-sans font-bold text-lg">
                        EG
                    </div>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                        {{ __('landing_company.contact_hq_title') }}
                    </h2>
                    <p class="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                        {{ __('landing_company.contact_hq_desc') }}
                    </p>
                </div>
                <div class="mt-8 text-xs text-[#1d1d1f]/50 font-medium">
                    {{ __('landing_company.contact_hq_address') }} &bull; (UTC+2 / UTC+3)
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
