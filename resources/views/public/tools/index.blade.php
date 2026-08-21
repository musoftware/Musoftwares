@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            Free Engineering & Business Utilities
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            Developer & Business Tools
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            Free production utilities, budget calculators, security checkers, and performance auditors engineered by Musoftwares.
        </p>
    </div>

    <!-- Tools Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Tool 1: Project Estimator -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">CALCULATOR</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">Project Cost & Budget Estimator</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Transparent instant software and web application budget estimator with real-time currency exchange.
                    </p>
                </div>
                <a href="/estimator" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Launch Estimator</span> ➔
                </a>
            </div>

            <!-- Tool 2: System Architecture Wizard -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">WIZARD</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">System Architecture Builder</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Design your custom enterprise system or mobile app step-by-step and dispatch project code.
                    </p>
                </div>
                <a href="/start-project" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Open Wizard</span> ➔
                </a>
            </div>

            <!-- Tool 3: Website Audit Checker -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">AUDITOR</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">Website & SSL Security Inspector</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Inspect header security, SSL certificates, response times, and DNS latency.
                    </p>
                </div>
                <a href="/tools/website-checker" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Inspect Website</span> ➔
                </a>
            </div>

            <!-- Tool 4: Payment Gateway Auditor -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">FINTECH</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">Payment Gateway Fee Auditor</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Compare processing fees across Visa, Mastercard, Fawry, Vodafone Cash, and Stripe.
                    </p>
                </div>
                <a href="/tools/payment-gateway-auditor" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Audit Gateways</span> ➔
                </a>
            </div>

            <!-- Tool 5: Image Cropper -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">GRAPHICS</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">High-DPI Image & Asset Cropper</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Client-side instant lossless cropping and aspect ratio formatting for social banners and logos.
                    </p>
                </div>
                <a href="/tools/image-cropper" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Launch Cropper</span> ➔
                </a>
            </div>

            <!-- Tool 6: Invoice Generator -->
            <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 space-y-4 hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                <div class="space-y-3">
                    <span class="text-xs font-semibold text-[#0071e3]">UTILITY</span>
                    <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">Quick PDF Invoice Generator</h2>
                    <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                        Generate professional tax-compliant PDF invoices instantly with dual currency support.
                    </p>
                </div>
                <a href="/tools/invoice-generator" class="text-xs font-semibold text-[#0071e3] hover:underline flex items-center gap-1">
                    <span>Generate Invoice</span> ➔
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
