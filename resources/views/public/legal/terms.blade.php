@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            {{ __('landing_legal.legal_center') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            {{ __('landing_legal.terms_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_legal.terms_subtitle') }}
        </p>
        <div class="text-xs text-[#1d1d1f]/40 pt-2 font-medium">
            {{ __('landing_legal.last_updated') }}
        </div>
    </div>

    <!-- Terms Document Content -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-8 text-sm leading-relaxed text-[#1d1d1f]/80">
        
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_legal.terms_agreement_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                {{ __('landing_legal.terms_agreement_body') }}
            </p>
        </div>

        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_legal.terms_user_accounts_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                {{ __('landing_legal.terms_user_accounts_body') }}
            </p>
        </div>

        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_legal.terms_prohibited_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                {{ __('landing_legal.terms_prohibited_body') }}
            </p>
        </div>

        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_legal.terms_ip_rights_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                {{ __('landing_legal.terms_ip_rights_body') }}
            </p>
        </div>

        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_legal.terms_payment_billing_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                {{ __('landing_legal.terms_payment_billing_body') }}
            </p>
        </div>

    </div>

</div>
@endsection
