@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_platforms.erp_badge') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_platforms.erp_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_platforms.erp_subtitle') }}
        </p>
    </div>

    <!-- Features 4 Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-16">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
                <span class="text-xs font-semibold text-[#0071e3]">01 / LEDGER</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_platforms.erp_card_1_title') }}
                </h2>
                <p class="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                    {{ __('landing_platforms.erp_card_1_desc') }}
                </p>
            </div>

            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
                <span class="text-xs font-semibold text-[#0071e3]">02 / COMPLIANCE</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_platforms.erp_card_2_title') }}
                </h2>
                <p class="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                    {{ __('landing_platforms.erp_card_2_desc') }}
                </p>
            </div>

            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
                <span class="text-xs font-semibold text-[#0071e3]">03 / MULTI-BRANCH</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_platforms.erp_card_3_title') }}
                </h2>
                <p class="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                    {{ __('landing_platforms.erp_card_3_desc') }}
                </p>
            </div>

            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-md hover:border-black/10 transition-all">
                <span class="text-xs font-semibold text-[#0071e3]">04 / SOVEREIGNTY</span>
                <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">
                    {{ __('landing_platforms.erp_card_4_title') }}
                </h2>
                <p class="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                    {{ __('landing_platforms.erp_card_4_desc') }}
                </p>
            </div>

        </div>

        <!-- Action Box -->
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-10 sm:p-14 text-center space-y-6">
            <h3 class="text-2xl sm:text-3xl font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_platforms.erp_cta_title') }}
            </h3>
            <p class="text-sm text-[#1d1d1f]/60 max-w-xl mx-auto font-sans">
                {{ __('landing_platforms.erp_cta_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-xs pt-2">
                <a href="/start-project" class="px-8 py-3.5 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                    {{ __('landing_platforms.erp_cta_wizard') }}
                </a>
                <a href="https://wa.me/201015218548?text=Hello%20Mahmoud,%20I'd%20like%20to%20discuss%20an%20Enterprise%20ERP." target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-black/10 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] font-semibold uppercase tracking-wider rounded-[980px] transition-all">
                    {{ __('landing_platforms.erp_cta_wa') }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
