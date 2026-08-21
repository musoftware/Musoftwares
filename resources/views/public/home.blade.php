@extends('layouts.public')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
<style>
  .apple-blur { backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); }
  @keyframes pulse { 50% { opacity: .5; } }
  .animate-pulse { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
</style>

<div class="min-h-screen bg-white text-[#1d1d1f] selection:bg-[#0071e3]/10 antialiased" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', Inter, Helvetica, Arial, sans-serif;">

    <div class="flex flex-col gap-3 p-3 bg-white max-w-[1920px] mx-auto">
        
        <!-- ============================================================ -->
        <!-- 1. HERO SECTION (Code. Ship. Scale.) -->
        <!-- ============================================================ -->
        <section class="rounded-[18px] bg-[#f5f5f7] pt-14 md:pt-[72px] pb-10 md:pb-14 overflow-hidden">
            <div class="max-w-[980px] mx-auto px-6 text-center">
                <h1 class="text-[40px] md:text-[56px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    Code. Ship. Scale.
                </h1>
                <p class="mt-4 text-[19px] md:text-[21px] leading-[1.25] tracking-[-0.01em] text-[#1d1d1f]/80 max-w-[620px] mx-auto">
                    Web Apps. Mobile Apps. Desktop Systems. Built to Perfection.
                </p>
                <div class="mt-7 flex items-center justify-center gap-3">
                    <a href="/start-project" class="inline-flex items-center justify-center rounded-[980px] bg-[#0071e3] text-white text-[17px] font-normal px-[22px] h-[36px] hover:bg-[#0077ed] active:bg-[#006edb] transition shadow-sm">
                        View Capabilities
                    </a>
                </div>

                <!-- 3 Floating 3D Vector Mockups (Web, Mobile, Desktop) -->
                <div class="mt-12 md:mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-7 max-w-[980px] mx-auto items-end">
                    
                    <!-- Web App UI Window Mockup (rotate -2deg) -->
                    <div class="relative mx-auto w-full max-w-[300px] bg-white rounded-[18px] shadow-[0_12px_40px_rgba(0,0,0,0.08),0_1px_3px_rgba(0,0,0,0.06)] border-[8px] border-white p-3 rotate-[-2deg] origin-bottom hover:rotate-0 transition-transform duration-500">
                        <div class="rounded-[12px] bg-[#f5f5f7] overflow-hidden border border-black/5">
                            <div class="h-[28px] flex items-center gap-1.5 px-3 border-b border-black/5 bg-white">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#ff5f57]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#ffbd2e]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#28c940]"></div>
                                <div class="ml-3 h-3 w-24 bg-black/5 rounded-full"></div>
                            </div>
                            <div class="p-3 grid grid-cols-3 gap-2">
                                <div class="col-span-2 h-16 rounded-lg bg-white border border-black/5 p-2">
                                    <div class="h-2 w-12 bg-black/10 rounded mb-2"></div>
                                    <div class="flex items-end gap-1 h-8">
                                        <div class="w-1/5 h-3 bg-[#0071e3]/30 rounded-t"></div>
                                        <div class="w-1/5 h-6 bg-[#0071e3]/60 rounded-t"></div>
                                        <div class="w-1/5 h-4 bg-[#0071e3]/40 rounded-t"></div>
                                        <div class="w-1/5 h-8 bg-[#0071e3] rounded-t"></div>
                                    </div>
                                </div>
                                <div class="h-16 rounded-lg bg-white border border-black/5 p-2">
                                    <div class="h-2 w-8 bg-black/10 rounded mb-2"></div>
                                    <div class="w-8 h-8 rounded-full border-[3px] border-[#0071e3] border-t-transparent mx-auto mt-1 animate-spin"></div>
                                </div>
                                <div class="col-span-3 h-10 rounded-lg bg-white border border-black/5 flex items-center px-2 gap-2">
                                    <div class="w-6 h-6 rounded-full bg-black/5"></div>
                                    <div class="flex-1 h-2 bg-black/10 rounded"></div>
                                    <div class="w-12 h-5 rounded-full bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-medium">Save</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile App iPhone Mockup (rotate 1deg) -->
                    <div class="relative mx-auto w-full max-w-[220px] bg-white rounded-[28px] shadow-[0_20px_50px_rgba(0,0,0,0.12),0_1px_3px_rgba(0,0,0,0.06)] border-[8px] border-white p-2 rotate-[1deg] hover:rotate-0 transition-transform duration-500">
                        <div class="rounded-[20px] bg-[#101010] overflow-hidden aspect-[9/19] relative border border-black">
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-16 h-5 bg-black rounded-b-[10px] z-10"></div>
                            <div class="h-full bg-[#f5f5f7] p-3 pt-8 flex flex-col">
                                <div class="h-3 w-20 bg-black/10 rounded mb-3"></div>
                                <div class="space-y-2">
                                    <div class="h-12 rounded-xl bg-white border border-black/5 flex items-center px-3 gap-2">
                                        <div class="w-8 h-8 rounded-full bg-[#0071e3]"></div>
                                        <div>
                                            <div class="w-16 h-2 bg-black/10 rounded mb-1"></div>
                                            <div class="w-10 h-1.5 bg-black/5 rounded"></div>
                                        </div>
                                    </div>
                                    <div class="h-12 rounded-xl bg-white border border-black/5 flex items-center px-3 gap-2">
                                        <div class="w-8 h-8 rounded-full bg-black/10"></div>
                                        <div>
                                            <div class="w-20 h-2 bg-black/10 rounded mb-1"></div>
                                            <div class="w-12 h-1.5 bg-black/5 rounded"></div>
                                        </div>
                                    </div>
                                    <div class="h-20 rounded-xl bg-[#0071e3] p-3 text-white">
                                        <div class="w-10 h-2 bg-white/60 rounded mb-2"></div>
                                        <div class="w-full h-1.5 bg-white/30 rounded mb-1"></div>
                                        <div class="w-2/3 h-1.5 bg-white/30 rounded"></div>
                                    </div>
                                </div>
                                <div class="mt-auto flex justify-around pt-3 border-t border-black/5">
                                    <div class="w-5 h-5 rounded-full bg-black/10"></div>
                                    <div class="w-5 h-5 rounded-full bg-[#0071e3]"></div>
                                    <div class="w-5 h-5 rounded-full bg-black/10"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop POS Terminal Mockup (rotate 2deg) -->
                    <div class="relative mx-auto w-full max-w-[300px] bg-white rounded-[18px] shadow-[0_12px_40px_rgba(0,0,0,0.08),0_1px_3px_rgba(0,0,0,0.06)] border-[8px] border-white p-3 rotate-[2deg] origin-bottom hover:rotate-0 transition-transform duration-500">
                        <div class="rounded-[12px] bg-[#111] overflow-hidden border border-black">
                            <div class="h-8 bg-[#1e1e1e] flex items-center px-3 gap-2">
                                <div class="w-8 h-1.5 bg-white/10 rounded"></div>
                                <div class="ml-auto flex gap-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white/20"></div>
                                    <div class="w-1.5 h-1.5 rounded-full bg-white/20"></div>
                                    <div class="w-1.5 h-1.5 rounded-full bg-white/20"></div>
                                </div>
                            </div>
                            <div class="p-3 grid grid-cols-5 gap-2 bg-[#f5f5f7]">
                                <div class="col-span-3 space-y-2">
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach(range(1,6) as $item)
                                        <div class="aspect-square rounded-lg bg-white border border-black/5 flex flex-col items-center justify-center p-1">
                                            <div class="w-6 h-6 rounded bg-black/5 mb-1"></div>
                                            <div class="w-8 h-1 bg-black/10 rounded"></div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-span-2 rounded-lg bg-white border border-black/5 p-2">
                                    <div class="h-2 w-12 bg-black/10 rounded mb-2"></div>
                                    <div class="space-y-1.5">
                                        <div class="flex justify-between">
                                            <div class="w-12 h-1.5 bg-black/5 rounded"></div>
                                            <div class="w-6 h-1.5 bg-black/10 rounded"></div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="w-10 h-1.5 bg-black/5 rounded"></div>
                                            <div class="w-8 h-1.5 bg-black/10 rounded"></div>
                                        </div>
                                        <div class="h-px bg-black/5 my-2"></div>
                                        <div class="flex justify-between font-bold">
                                            <div class="w-8 h-2 bg-black rounded"></div>
                                            <div class="w-10 h-2 bg-black rounded"></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 h-7 rounded-full bg-[#1d1d1f] flex items-center justify-center text-[9px] text-white font-medium">
                                        Pay • 1,240 EGP
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 2. FULL-STACK SAAS & WEB DEVELOPMENT (Full-Width Light) -->
        <!-- ============================================================ -->
        <section id="web" class="rounded-[18px] bg-[#f5f5f7] pt-12 md:pt-[84px] overflow-hidden">
            <div class="text-center px-6 max-w-[720px] mx-auto">
                <div class="text-[12px] font-semibold tracking-[0.08em] text-[#6e6e73] uppercase">
                    Web Development
                </div>
                <h2 class="mt-2 text-[32px] md:text-[48px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    Full-Stack SaaS.
                </h2>
                <p class="mt-3 text-[17px] md:text-[19px] leading-[1.3] text-[#1d1d1f]/70">
                    React, Next.js, dashboards that scale to millions.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <a href="/start-project" class="rounded-[980px] bg-[#0071e3] text-white text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0077ed] transition">
                        Learn more
                    </a>
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] border border-[#0071e3] text-[#0071e3] text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0071e3]/5 transition">
                        View Stack &gt;
                    </a>
                </div>
            </div>

            <!-- Full-Width Web Dashboard UI Window -->
            <div class="mt-10 md:mt-14 mx-auto max-w-[1100px] px-4 md:px-8">
                <div class="rounded-t-[18px] bg-white shadow-[0_20px_80px_rgba(0,0,0,0.12)] border border-black/5 border-b-0 overflow-hidden">
                    <div class="h-[44px] flex items-center px-4 gap-3 border-b border-black/5 bg-[#fbfbfd]">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-[#ff5f57]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#ffbd2e]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#28c940]"></div>
                        </div>
                        <div class="ml-4 flex-1 max-w-[420px] h-7 rounded-full bg-[#f5f5f7] border border-black/5 flex items-center px-3 gap-2 text-[12px] text-black/40">
                            <div class="w-3 h-3 rounded-full bg-black/10"></div>
                            <span>musoftwares.app/dashboard</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-12 min-h-[320px] md:min-h-[380px]">
                        <div class="col-span-2 hidden md:block border-r border-black/5 p-4 space-y-3 bg-[#fbfbfd]">
                            <div class="h-2 w-16 bg-black/10 rounded"></div>
                            <div class="space-y-2 mt-4">
                                @foreach([1,2,3,4] as $item)
                                <div class="h-8 rounded-lg flex items-center px-2 gap-2 {{ $item === 1 ? 'bg-[#1d1d1f] text-white' : '' }}">
                                    <div class="w-4 h-4 rounded {{ $item === 1 ? 'bg-white/20' : 'bg-black/10' }}"></div>
                                    <div class="w-12 h-1.5 rounded {{ $item === 1 ? 'bg-white/40' : 'bg-black/10' }}"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-10 p-4 md:p-6 bg-white">
                            <div class="flex flex-wrap gap-4 mb-6">
                                <div class="flex-1 min-w-[160px] rounded-[12px] border border-black/5 p-4">
                                    <div class="text-[11px] text-black/40 uppercase tracking-widest">Revenue</div>
                                    <div class="mt-1 text-[22px] font-semibold tracking-tight">$284,420</div>
                                    <div class="mt-2 flex gap-1 items-end h-8">
                                        @foreach([4,8,5,10,7,12,9] as $h)
                                        <div style="height: {{ $h * 3 }}px;" class="w-2 rounded-full bg-[#0071e3] opacity-80"></div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex-1 min-w-[160px] rounded-[12px] border border-black/5 p-4">
                                    <div class="text-[11px] text-black/40 uppercase tracking-widest">Active Users</div>
                                    <div class="mt-1 text-[22px] font-semibold tracking-tight">12,402</div>
                                    <div class="mt-3 h-2 w-full bg-black/5 rounded-full overflow-hidden">
                                        <div class="h-full w-[72%] bg-black rounded-full"></div>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-[160px] rounded-[12px] border border-black/5 p-4">
                                    <div class="text-[11px] text-black/40 uppercase tracking-widest">API Latency</div>
                                    <div class="mt-1 text-[22px] font-semibold tracking-tight">
                                        10ms <span class="text-[12px] font-normal text-green-600">• Live</span>
                                    </div>
                                    <div class="mt-3 flex gap-1">
                                        @foreach(range(1,5) as $bar)
                                        <div class="h-1.5 flex-1 rounded-full bg-green-500/20">
                                            <div class="h-full w-full bg-green-500 rounded-full" style="opacity: {{ 0.3 + $bar * 0.15 }};"></div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-[12px] border border-black/5 overflow-hidden">
                                <div class="h-10 border-b border-black/5 flex items-center px-4 gap-4 text-[12px] text-black/40">
                                    <span class="text-black font-medium">Transactions</span>
                                    <span>Customers</span>
                                    <span>Logs</span>
                                </div>
                                <div class="divide-y divide-black/5">
                                    @foreach(range(1,3) as $row)
                                    <div class="h-12 flex items-center px-4 gap-4 text-[13px]">
                                        <div class="w-7 h-7 rounded-full bg-black/5"></div>
                                        <div class="w-20 h-2 bg-black/10 rounded"></div>
                                        <div class="ml-auto w-16 h-2 bg-black/5 rounded"></div>
                                        <div class="w-12 h-5 rounded-full bg-black/5"></div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 3. NATIVE MOBILE APPS (Full-Width Pastel Gradient) -->
        <!-- ============================================================ -->
        <section id="mobile" class="rounded-[18px] pt-12 md:pt-[84px] overflow-hidden" style="background: linear-gradient(180deg, #e6f0ff 0%, #f5f5f7 70%);">
            <div class="text-center px-6 max-w-[720px] mx-auto">
                <div class="text-[12px] font-semibold tracking-[0.08em] text-[#6e6e73] uppercase">
                    Mobile Apps
                </div>
                <h2 class="mt-2 text-[32px] md:text-[48px] font-semibold tracking-[-0.03em] leading-[0.95] text-[#1d1d1f]">
                    iOS &amp; Android.<br>Native Performance.
                </h2>
                <p class="mt-3 text-[17px] md:text-[19px] leading-[1.3] text-[#1d1d1f]/70">
                    Flutter, React Native, Swift. One codebase, two stores.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <a href="/start-project" class="rounded-[980px] bg-[#0071e3] text-white text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-[#0077ed] transition">
                        Learn more
                    </a>
                    <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] border border-[#0071e3] text-[#0071e3] text-[17px] h-[36px] px-[22px] inline-flex items-center hover:bg-white/60 transition">
                        View Stack &gt;
                    </a>
                </div>
            </div>

            <!-- Overlapping Native Smartphone Mockups -->
            <div class="mt-12 flex justify-center items-end pb-0 relative h-[420px] md:h-[520px]">
                
                <!-- Phone 1 (Dark Theme - rotate -4deg) -->
                <div class="relative z-10 w-[210px] md:w-[260px] bg-white rounded-[36px] shadow-[0_30px_80px_rgba(0,0,0,0.15)] border-[8px] border-white p-2 translate-x-[18px] rotate-[-4deg] hover:rotate-0 transition-transform duration-500">
                    <div class="rounded-[28px] bg-black overflow-hidden aspect-[9/19] relative border border-black">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-20 h-6 bg-black rounded-b-[12px] z-10"></div>
                        <div class="h-full bg-[#f5f5f7] p-3 pt-10">
                            <div class="w-12 h-12 rounded-2xl bg-[#1d1d1f] mb-3"></div>
                            <div class="h-3 w-24 bg-black/10 rounded mb-2"></div>
                            <div class="h-2 w-32 bg-black/5 rounded mb-6"></div>
                            <div class="space-y-2">
                                @foreach(range(1,3) as $card)
                                <div class="h-14 rounded-xl bg-white border border-black/5 p-3 flex gap-2">
                                    <div class="w-8 h-8 rounded-full bg-black/5"></div>
                                    <div class="flex-1">
                                        <div class="w-16 h-2 bg-black/10 rounded mb-1"></div>
                                        <div class="w-10 h-1.5 bg-black/5 rounded"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phone 2 (Light Theme - rotate 4deg) -->
                <div class="relative z-20 w-[210px] md:w-[260px] bg-white rounded-[36px] shadow-[0_30px_80px_rgba(0,0,0,0.18)] border-[8px] border-white p-2 -translate-x-[18px] rotate-[4deg] hover:rotate-0 transition-transform duration-500">
                    <div class="rounded-[28px] bg-black overflow-hidden aspect-[9/19] relative border border-black">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-20 h-6 bg-black rounded-b-[12px] z-10"></div>
                        <div class="h-full bg-white p-3 pt-10 flex flex-col">
                            <div class="flex justify-between items-center mb-4">
                                <div class="w-6 h-6 rounded-full bg-black/10"></div>
                                <div class="w-6 h-6 rounded-full bg-black/10"></div>
                            </div>
                            <div class="text-[11px] text-black/40 uppercase tracking-widest mb-2">Order #8421</div>
                            <div class="h-28 rounded-2xl bg-[#f5f5f7] border border-black/5 p-3 mb-3">
                                <div class="flex gap-2 mb-2">
                                    <div class="w-12 h-12 rounded-xl bg-white border border-black/5"></div>
                                    <div>
                                        <div class="w-20 h-2 bg-black/10 rounded mb-1"></div>
                                        <div class="w-12 h-1.5 bg-black/5 rounded"></div>
                                    </div>
                                </div>
                                <div class="h-1.5 w-full bg-black/5 rounded-full overflow-hidden">
                                    <div class="h-full w-[65%] bg-[#0071e3] rounded-full"></div>
                                </div>
                            </div>
                            <div class="mt-auto h-12 rounded-full bg-[#1d1d1f] text-white flex items-center justify-center text-[13px] font-medium">
                                Confirm Delivery
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- ============================================================ -->
        <!-- 4. 2-COLUMN BENTO GRID (Cards) -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Bento 1 (Light): Desktop Applications -->
            <div id="desktop" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="flex justify-center gap-2 mb-3 text-[12px]">
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">Windows</span>
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">macOS</span>
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5">Linux</span>
                    </div>
                    <h3 class="text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Desktop Applications
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Native desktop software, offline-first databases, and hardware control.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/stock-manager" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">View demos &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[360px] bg-white rounded-t-[16px] shadow-[0_10px_40px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 p-3 pb-0">
                    <div class="rounded-t-[10px] bg-[#1d1d1f] h-[200px] p-3 grid grid-cols-4 gap-2">
                        <div class="col-span-1 space-y-2">
                            <div class="h-6 rounded bg-white/10"></div>
                            <div class="space-y-1">
                                @foreach([1,2,3] as $item)
                                <div class="h-6 rounded bg-white/5"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-span-3 grid grid-cols-3 gap-2">
                            @foreach(range(1,6) as $item)
                            <div class="rounded-lg bg-white/10 border border-white/5 flex flex-col items-center justify-center p-2">
                                <div class="w-6 h-6 rounded bg-white/10 mb-1"></div>
                                <div class="w-10 h-1 bg-white/20 rounded"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento 2 (Light): Cloud Architecture & High Performance Systems -->
            <div id="enterprise" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#30d158] animate-pulse"></span> LIVE TELEMETRY
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Cloud Infrastructure
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        High-throughput APIs, sub-10ms telemetry, and real-time sync.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/chartcash" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Architecture &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[380px] rounded-t-[16px] bg-white border border-black/5 border-b-0 p-4 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="flex justify-between items-center mb-4">
                        <div class="h-2 w-16 bg-black/10 rounded"></div>
                        <div class="flex gap-1">
                            <div class="w-8 h-5 rounded bg-black/5"></div>
                            <div class="w-8 h-5 rounded bg-[#30d158]/20 border border-[#30d158]/30 flex items-center justify-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#30d158]"></span>
                            </div>
                        </div>
                    </div>
                    <div class="h-[120px] relative">
                        <svg viewBox="0 0 200 80" class="w-full h-full">
                            <path d="M0 60 Q20 55 40 30 T80 40 T120 20 T160 35 T200 10" fill="none" stroke="#0071e3" stroke-width="2.5"/>
                            <path d="M0 60 Q20 55 40 30 T80 40 T120 20 T160 35 T200 10 L200 80 L0 80 Z" fill="url(#g1_light)" opacity="0.15"/>
                            <defs>
                                <linearGradient id="g1_light" x1="0" x2="0" y1="0" y2="1">
                                    <stop stop-color="#0071e3"/>
                                    <stop offset="1" stop-color="#0071e3" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute top-[8px] right-4 text-[10px] px-1.5 py-0.5 rounded bg-[#0071e3] text-white font-medium shadow-sm">+12.4%</div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-3">
                        <div class="rounded-lg bg-[#f5f5f7] border border-black/5 p-2">
                            <div class="text-[9px] text-[#86868b] uppercase font-medium">THROUGHPUT</div>
                            <div class="text-[12px] font-semibold text-[#1d1d1f] mt-1">4.2M req</div>
                        </div>
                        <div class="rounded-lg bg-[#f5f5f7] border border-black/5 p-2">
                            <div class="text-[9px] text-[#86868b] uppercase font-medium">UPTIME</div>
                            <div class="text-[12px] font-semibold text-[#1d1d1f] mt-1">99.99%</div>
                        </div>
                        <div class="rounded-lg bg-[#f5f5f7] border border-black/5 p-2">
                            <div class="text-[9px] text-[#86868b] uppercase font-medium">LATENCY</div>
                            <div class="text-[12px] font-semibold text-green-600 mt-1">10ms</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento 3 (Pastel Blue): WhatsApp Cloud CRM -->
            <div id="cloud" class="rounded-[18px] bg-[#e8f0fb] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#1d1d1f]">
                        <span class="w-5 h-5 rounded-full bg-[#25D366] flex items-center justify-center text-white text-[10px]">W</span> Official Meta API
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        WhatsApp Cloud CRM
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Official Meta Graph API. Multi-agent inbox.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/trenz-whatscrm" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">See inbox &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[340px] bg-white rounded-t-[16px] shadow-[0_10px_30px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 overflow-hidden">
                    <div class="h-10 flex items-center px-3 gap-2 border-b border-black/5">
                        <div class="w-7 h-7 rounded-full bg-[#25D366]"></div>
                        <div>
                            <div class="w-16 h-2 bg-black/10 rounded mb-1"></div>
                            <div class="w-10 h-1.5 bg-green-500/30 rounded"></div>
                        </div>
                        <div class="ml-auto w-5 h-5 rounded-full bg-black/5"></div>
                    </div>
                    <div class="p-3 space-y-2 bg-[#efeae2]">
                        <div class="max-w-[75%] p-2 rounded-lg bg-white shadow-sm text-[11px]">
                            Hello, I'd like to book an appointment.
                        </div>
                        <div class="max-w-[75%] ml-auto p-2 rounded-lg bg-[#d9fdd3] shadow-sm text-[11px]">
                            Sure! Calendar synced automatically.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento 4 (Light): AI & Automation -->
            <div id="ai" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3]"></span> GPT-4o &amp; LLAMA 3
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        AI &amp; Automation
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Arabic dialects understanding. Grounded database queries.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/stocktalk-ai" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Try prompt &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[340px] bg-white rounded-t-[16px] border border-black/5 border-b-0 p-4 space-y-3 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] text-[11px] font-bold">AI</div>
                        <div class="text-[12px] text-[#1d1d1f] font-medium">StockTalk AI Assistant</div>
                    </div>
                    <div class="p-3 rounded-xl bg-[#f5f5f7] border border-black/5 text-[12px] text-[#1d1d1f] space-y-1">
                        <div class="text-[#0071e3] text-[10px] font-semibold">User Query (Egyptian Arabic)</div>
                        <div>"عندك كام قطعة من مقاس XL في المخزن الرئيسي؟"</div>
                    </div>
                    <div class="p-3 rounded-xl bg-[#e8f0fb] border border-[#0071e3]/15 text-[12px] text-[#1d1d1f] space-y-1">
                        <div class="text-[#0071e3] text-[10px] font-semibold">Grounded DB Response</div>
                        <div>"متاح حالياً 48 قطعة في المخزن الرئيسي جاهزة للشحن."</div>
                    </div>
                </div>
            </div>

            <!-- Bento 5 (Light): E-Commerce & Supply -->
            <div id="ecommerce" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="text-[12px] font-semibold text-[#0071e3] uppercase">5,000+ AFFILIATES</div>
                    <h3 class="mt-2 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        E-commerce &amp; Supply
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Multi-vendor catalogs, manifests, commission wallets.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/kbdny" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Case study &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[360px] bg-white rounded-t-[16px] shadow-[0_10px_30px_rgba(0,0,0,0.08)] border border-black/5 border-b-0 p-4">
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-[12px] font-semibold">Courier Manifests</div>
                        <div class="text-[11px] text-green-600 font-medium">Bosta &amp; Mylerz Synced</div>
                    </div>
                    <div class="space-y-2">
                        @foreach(range(1,3) as $manifest)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-[#f5f5f7] text-[11px]">
                            <span>Batch #{{ 1040 + $manifest }} (45 Orders)</span>
                            <span class="font-semibold text-[#0071e3]">Print Manifest &gt;</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Bento 6 (Light): Telecom & SMS -->
            <div id="telecom" class="rounded-[18px] bg-[#f5f5f7] pt-10 pb-0 px-6 md:px-10 overflow-hidden flex flex-col min-h-[560px]">
                <div class="text-center max-w-[380px] mx-auto">
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#30d158]"></span> ZERO SMS COST
                    </div>
                    <h3 class="mt-3 text-[28px] md:text-[32px] font-semibold tracking-[-0.02em] leading-[1.05] text-[#1d1d1f]">
                        Telecom &amp; SMS
                    </h3>
                    <p class="mt-2 text-[17px] leading-[1.3] text-[#1d1d1f]/70">
                        Turn local Android SIM cards into automated SMS gateways.
                    </p>
                    <div class="mt-4 flex justify-center gap-3">
                        <a href="/portfolio/am-sms-gateway" class="rounded-[980px] bg-[#0071e3] text-white text-[12px] h-[28px] px-4 inline-flex items-center">Learn more</a>
                        <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="rounded-[980px] text-[#06c] text-[12px] h-[28px] px-2 inline-flex items-center">Hardware setup &gt;</a>
                    </div>
                </div>
                <div class="mt-8 mx-auto w-full max-w-[340px] bg-white rounded-t-[16px] border border-black/5 border-b-0 p-4 space-y-2 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="text-[11px] text-[#86868b] uppercase font-medium">Active SIM Channels</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-3 rounded-lg bg-[#f5f5f7] border border-black/5">
                            <div class="text-[10px] text-green-700 font-semibold">SIM 1 • Vodafone</div>
                            <div class="text-[13px] font-semibold text-[#1d1d1f] mt-1">99.8% Sent</div>
                        </div>
                        <div class="p-3 rounded-lg bg-[#f5f5f7] border border-black/5">
                            <div class="text-[10px] text-green-700 font-semibold">SIM 2 • Orange</div>
                            <div class="text-[13px] font-semibold text-[#1d1d1f] mt-1">99.4% Sent</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- ============================================================ -->
        <!-- 5. CENTERED SHOWCASE GALLERY (Manual Navigation) -->
        <!-- ============================================================ -->
        <section id="portfolio-showcase" class="rounded-[28px] bg-[#f5f5f7] py-16 md:py-24 px-4 sm:px-6 md:px-12 overflow-hidden text-center">
            
            <!-- Gallery Title -->
            <div class="max-w-[800px] mx-auto mb-6">
                <h3 class="text-[36px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'معرض الأنظمة والمشاريع' : 'Gallery' }}
                </h3>
                <p class="mt-2 text-[16px] text-[#1d1d1f]/60 font-normal">
                    {{ $locale === 'ar' ? 'استعراض المنصات والأنظمة البرمجية المنفذة في بيئات الإنتاج الحية.' : 'High-impact platforms engineered for web, mobile, and desktop.' }}
                </p>
            </div>

            <!-- Top Filter Pill Container -->
            <div class="inline-flex p-1.5 rounded-full bg-[#e8e8ed] border border-black/5 mb-10 shadow-inner">
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 bg-white text-[#1d1d1f] shadow-sm" data-category="web">
                    {{ $locale === 'ar' ? 'تطبيقات الويب' : 'Web Apps' }}
                </button>
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 text-[#1d1d1f]/70 hover:text-[#1d1d1f]" data-category="mobile">
                    {{ $locale === 'ar' ? 'تطبيقات الموبايل' : 'Mobile Apps' }}
                </button>
                <button class="gallery-category-pill px-6 py-2 rounded-full text-[13px] font-semibold transition duration-200 text-[#1d1d1f]/70 hover:text-[#1d1d1f]" data-category="desktop">
                    {{ $locale === 'ar' ? 'برامج الديسك توب' : 'Desktop Apps' }}
                </button>
            </div>

            <!-- Featured Stage with Side Floating Buttons -->
            <div class="max-w-[1024px] mx-auto relative flex items-center justify-center">
                
                <!-- Left Nav Arrow Button -->
                <button id="gallery-stage-prev" aria-label="Previous Project" class="absolute -left-2 sm:-left-6 md:-left-8 z-20 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/90 backdrop-blur-md border border-black/10 shadow-[0_4px_20px_rgba(0,0,0,0.08)] flex items-center justify-center text-[#1d1d1f] hover:bg-[#1d1d1f] hover:text-white transition duration-200">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Center Showcase Card Container -->
                <div class="w-full max-w-[880px] bg-white rounded-[24px] sm:rounded-[36px] border border-black/5 shadow-[0_20px_60px_rgba(0,0,0,0.08)] overflow-hidden transition-all duration-300 p-3 sm:p-5">
                    <div class="relative w-full aspect-[16/10] sm:aspect-[16/9] rounded-[18px] sm:rounded-[28px] overflow-hidden bg-[#f0f0f2] flex items-center justify-center">
                        <img id="gallery-stage-img" src="/images/portfolio/kbdny.png" alt="Kbdny Affiliate" class="w-full h-full object-cover object-top transition duration-500 transform hover:scale-[1.02]">
                        
                        <!-- Floating Category Badge -->
                        <div class="absolute top-4 start-4 px-3.5 py-1.5 rounded-full bg-white/90 backdrop-blur-md shadow-sm border border-black/5 text-[11px] font-semibold text-[#1d1d1f] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#30d158]"></span>
                            <span id="gallery-stage-badge">Web &amp; E-Commerce</span>
                        </div>
                    </div>
                </div>

                <!-- Right Nav Arrow Button -->
                <button id="gallery-stage-next" aria-label="Next Project" class="absolute -right-2 sm:-right-6 md:-right-8 z-20 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/90 backdrop-blur-md border border-black/10 shadow-[0_4px_20px_rgba(0,0,0,0.08)] flex items-center justify-center text-[#1d1d1f] hover:bg-[#1d1d1f] hover:text-white transition duration-200">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

            </div>

            <!-- Below Showcase Meta & Caption -->
            <div class="max-w-[680px] mx-auto mt-6 space-y-2">
                <h4 id="gallery-stage-title" class="text-[20px] sm:text-[24px] font-semibold text-[#1d1d1f] tracking-tight">
                    {{ $locale === 'ar' ? 'منصة كبدني للتجارة والتسويق بالعمولة' : 'Kbdny Affiliate' }}
                </h4>
                <p id="gallery-stage-desc" class="text-[14px] text-[#1d1d1f]/70 leading-relaxed">
                    {{ $locale === 'ar' ? 'نظام متكامل واحترافي للتجارة الإلكترونية والدروبشيبينغ مع تتبع العمولات اللحظي.' : 'Multi-vendor affiliate platform with real-time commission tracking and payouts.' }}
                </p>
                <div class="pt-1 flex items-center justify-center gap-4 text-[13px]">
                    <span id="gallery-stage-metric" class="font-medium text-[#1d1d1f]/80">5,000+ Affiliates</span>
                    <span class="text-black/20">•</span>
                    <a id="gallery-stage-link" href="/portfolio/kbdny" class="font-semibold text-[#0071e3] hover:underline inline-flex items-center gap-1">
                        {{ $locale === 'ar' ? 'عرض تفاصيل النظام >' : 'View case study >' }}
                    </a>
                </div>
            </div>

            <!-- Pagination Dots / Active Bar -->
            <div id="gallery-pagination" class="flex items-center justify-center gap-2 mt-6">
                <!-- Injected via JavaScript -->
            </div>

        </section>

    </div>

</div>

<!-- Centered Gallery Navigation Logic (NO AUTO-MOVE - 100% REAL DATA) -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isArabic = '{{ $locale }}' === 'ar';

        const galleryData = {
            web: [
                {
                    title_en: "Kbdny Affiliate",
                    title_ar: "منصة كبدني للتجارة والتسويق بالعمولة",
                    badge: "Web & E-Commerce",
                    desc_en: "Multi-vendor affiliate platform with real-time commission tracking and payouts.",
                    desc_ar: "نظام متكامل واحترافي للتجارة الإلكترونية والدروبشيبينغ مع تتبع العمولات اللحظي.",
                    img: "/images/portfolio/kbdny.png",
                    slug: "kbdny",
                    metric: "5,000+ Affiliates"
                },
                {
                    title_en: "Stock Manager",
                    title_ar: "نظام Stock Manager لإدارة المخزون ونقاط البيع",
                    badge: "Web & POS",
                    desc_en: "Inventory and POS system with multi-location support and KPI reporting.",
                    desc_ar: "نظام متكامل لإدارة المخازن ونقاط البيع للمحلات وسلاسل التوزيع.",
                    img: "/images/portfolio/stockmanager.png",
                    slug: "stock-manager",
                    metric: "Multi-Location Support"
                },
                {
                    title_en: "Mini Fatora",
                    title_ar: "منصة Mini Fatora للفوترة السريعة وإدارة الفواتير",
                    badge: "SaaS Billing",
                    desc_en: "Online invoicing and billing SaaS for freelancers and small businesses.",
                    desc_ar: "منصة فوترة سحابية خفيفة وسريعة للمستقلين والشركات الناشئة.",
                    img: "/images/portfolio/minifatora.png",
                    slug: "mini-fatora",
                    metric: "Instant PDF Invoicing"
                },
                {
                    title_en: "Vodafone CRM",
                    title_ar: "نظام إدارة الموزعين والعمليات الميدانية لـ Vodafone",
                    badge: "Enterprise CRM",
                    desc_en: "Custom CRM and operations management system for Vodafone Egypt distributor.",
                    desc_ar: "نظام مؤسسي متقدم لإدارة شبكة الموزعين والمناديب الميدانيين لخدمات الاتصالات.",
                    img: "/images/portfolio/vodafone-crm.jpg",
                    slug: "vodafone-crm",
                    metric: "50k+ SIM Serial Records"
                },
                {
                    title_en: "AMC Academy",
                    title_ar: "منصة AMC Academy للتعليم والتدريب الرقمي المشفر",
                    badge: "E-Learning & DRM",
                    desc_en: "Full e-learning platform with student portals, scheduling, and assessments.",
                    desc_ar: "منصة تعليمية متكاملة تدعم حماية الفيديو من التسجيل والعلامات المائية.",
                    img: "/images/portfolio/amcacademy.jpg",
                    slug: "amc-academy",
                    metric: "DRM Video Protection"
                },
                {
                    title_en: "Telecom System",
                    title_ar: "بوابة شحن وإدارة خدمات الاتصالات والإنترنت B2B",
                    badge: "B2B Telecom",
                    desc_en: "B2B recharge and ISP management platform with automated billing.",
                    desc_ar: "منصة خدمات الاتصالات والشحن الفوري للشركات وإدارة الاشتراكات.",
                    img: "/images/portfolio/telecom-system.png",
                    slug: "telecom-system",
                    metric: "< 1.2s API Response"
                },
                {
                    title_en: "Altayaraa",
                    title_ar: "منصة التجارة الإلكترونية السريعة الطيارة (Altayaraa)",
                    badge: "E-Commerce",
                    desc_en: "Arabic e-commerce and product listing platform with vendor management.",
                    desc_ar: "متجر إلكتروني فائق السرعة مصمم للشراء الفوري ومزامنة المخازن.",
                    img: "/images/portfolio/altayaraa.png",
                    slug: "altayaraa",
                    metric: "Single-Page Checkout"
                },
                {
                    title_en: "Trenz whatsCRM",
                    title_ar: "منصة Trenz whatsCRM لإدارة المحادثات والحجوزات",
                    badge: "WhatsApp CRM",
                    desc_en: "WhatsApp CRM and appointment scheduling platform designed for agencies.",
                    desc_ar: "منصة سحابية متكاملة لخدمة العملاء عبر واتساب وجدولة المواعيد.",
                    img: "/images/portfolio/trenz-whatscrm.png",
                    slug: "trenz-whatscrm",
                    metric: "Meta Cloud API"
                }
            ],
            mobile: [
                {
                    title_en: "Nokhpa",
                    title_ar: "تطبيق النخبة للتجارة الإلكترونية والتسوق الفاخر",
                    badge: "Mobile App",
                    desc_en: "E-commerce mobile app with native checkout, order tracking, and product filtering.",
                    desc_ar: "تطبيق تسوق إلكتروني متطور للمنتجات الفاخرة يدعم تتبع مسار المندوب بالـ GPS.",
                    img: "/images/portfolio/nokhpa.png",
                    slug: "nokhpa",
                    metric: "Native Checkout & GPS"
                },
                {
                    title_en: "Forex App",
                    title_ar: "تطبيق إشارات التداول وتحليل أسواق العملات Forex App",
                    badge: "Fintech Mobile",
                    desc_en: "Mobile companion for algorithmic trading with market signals and alerts.",
                    desc_ar: "تطبيق موبايل مالي متخصص في إرسال إشارات التداول اللحظية وتنبيهات السوق.",
                    img: "/images/portfolio/forex-app.png",
                    slug: "forex-app",
                    metric: "< 150ms Push Alerts"
                },
                {
                    title_en: "AMC Social",
                    title_ar: "منصة النشر والتواصل الاجتماعي AMC Social",
                    badge: "Social Network",
                    desc_en: "Internal social platform for AMC Academy students with posts and events.",
                    desc_ar: "منصة سحابية لإدارة وجدولة المنشورات والتفاعل على شبكات التواصل الاجتماعي.",
                    img: "/images/portfolio/amcsocial.png",
                    slug: "amc-social",
                    metric: "Cross-Network Sync"
                },
                {
                    title_en: "Wallet App",
                    title_ar: "تطبيق المحفظة الرقمية وتحويل الأموال Wallet App",
                    badge: "Digital Wallet",
                    desc_en: "Digital currency wallet with real-time exchange, recharge, and transfer capabilities.",
                    desc_ar: "محفظة مالية رقمية مع أسعار صرف فورية وتحويلات وإعادة شحن.",
                    img: "/images/portfolio/wallet-app.png",
                    slug: "portfolio",
                    metric: "Real-Time Exchange"
                },
                {
                    title_en: "QCoin App",
                    title_ar: "تطبيق QCoin لمتابعة وإدارة الاستثمارات الرقمية",
                    badge: "Crypto Tracking",
                    desc_en: "Crypto investment and tracking mobile app with portfolio management.",
                    desc_ar: "تطبيق جوال لمتابعة الأصول الرقمية وإدارة المحافظ الاستثمارية.",
                    img: "/images/portfolio/qcoin-app.jpg",
                    slug: "portfolio",
                    metric: "Portfolio Tracker"
                }
            ],
            desktop: [
                {
                    title_en: "WhatsApp Sender",
                    title_ar: "برنامج إرسال رسائل الواتساب المخصصة WhatsApp Sender",
                    badge: "Desktop Automation",
                    desc_en: "Bulk WhatsApp messaging tool with scheduling, templates, and contact lists.",
                    desc_ar: "برنامج سطح مكتب لأتمتة إرسال رسائل الفواتير والتنبيهات المخصصة عبر واتساب.",
                    img: "/images/portfolio/whatsapp-sender.png",
                    slug: "whatsapp-sender",
                    metric: "Random Delay Engine"
                },
                {
                    title_en: "Telegram Sender",
                    title_ar: "برنامج البث والنشر الفوري على تيليجرام Telegram Sender",
                    badge: "MTProto Broadcaster",
                    desc_en: "Automated Telegram broadcast tool with group/channel targeting and scheduling.",
                    desc_ar: "برنامج بث ونشر فوري عبر بروتوكول MTProto لآلاف القنوات والمجموعات.",
                    img: "/images/portfolio/telegram-sender.png",
                    slug: "telegram-sender",
                    metric: "Multi-Account Session"
                },
                {
                    title_en: "Inbox Sender",
                    title_ar: "برنامج إرسال البريد الإلكتروني وتدوير الـ SMTP",
                    badge: "Email Delivery",
                    desc_en: "Email bulk sending system with SMTP rotation and delivery rate optimization.",
                    desc_ar: "منصة إرسال رسائل بريدية مع تدوير ذكي لعناوين الـ IP وخوادم الـ SMTP.",
                    img: "/images/portfolio/inbox-sender.png",
                    slug: "email-sender",
                    metric: "SMTP Pool Rotation"
                },
                {
                    title_en: "StockTalk AI",
                    title_ar: "محرك الذكاء الاصطناعي وخدمة العملاء StockTalk AI",
                    badge: "AI Assistant",
                    desc_en: "WhatsApp-based AI customer support agent for automated stock and order queries.",
                    desc_ar: "ربط الذكاء الاصطناعي بقاعدة بيانات المخازن الحية للرد الفوري على العملاء.",
                    img: "/images/portfolio/stocktalk.png",
                    slug: "stocktalk-ai",
                    metric: "ERP RAG Grounding"
                },
                {
                    title_en: "ChartCash",
                    title_ar: "منصة التحليلات المالية ولوحات القيادة ChartCash",
                    badge: "Financial Analytics",
                    desc_en: "Financial analytics dashboard with real-time charts, KPIs, and P&L tracking.",
                    desc_ar: "منصة ذكاء أعمال وتحليلات مالية متقدمة توفر مؤشرات التدفق النقدي وهوامش الربحية.",
                    img: "/images/portfolio/chartcash.png",
                    slug: "chartcash",
                    metric: "Real-Time Aggregations"
                },
                {
                    title_en: "Forex Bot",
                    title_ar: "روبوت التداول الخوارزمي الآلي Forex Bot",
                    badge: "Algorithmic Trading",
                    desc_en: "Algorithmic trading bot with adaptive strategy, signal processing, and execution.",
                    desc_ar: "محرك تداول آلي خوارزمي متصل بمنصات التداول لتنفيذ الصفقات وإدارة المخاطر.",
                    img: "/images/portfolio/forex.png",
                    slug: "forex-bot",
                    metric: "Adaptive Execution"
                },
                {
                    title_en: "Duplicate Finder",
                    title_ar: "برنامج البحث عن الملفات المكررة Duplicate Finder",
                    badge: "Windows Utility",
                    desc_en: "File indexing and duplicate detection for Windows. Fast scan, SHA comparison.",
                    desc_ar: "فهرسة الملفات واكتشاف الملفات المكررة للويندوز مع فحص سريع ومقارنة SHA.",
                    img: "/images/portfolio/duplicate-finder.jpg",
                    slug: "portfolio",
                    metric: "Fast SHA Scan"
                },
                {
                    title_en: "Map Extractor",
                    title_ar: "أداة استخراج بيانات الشركات والعملاء Map Extractor",
                    badge: "Data Extraction",
                    desc_en: "Business leads extraction tool with filtering and export capabilities.",
                    desc_ar: "أداة استخراج العملاء المحتملين والشركات مع الفلترة والتصدير لإكسيل.",
                    img: "/images/portfolio/map-extractor.jpg",
                    slug: "portfolio",
                    metric: "Direct Excel Export"
                },
                {
                    title_en: "Instagram Manager",
                    title_ar: "برنامج إدارة وجدولة حسابات انستجرام Instagram Manager",
                    badge: "Desktop Automation",
                    desc_en: "Desktop automation suite for account management and content scheduling.",
                    desc_ar: "أتمتة إدارة الحسابات وجدولة المحتوى ونشر المنشورات على انستجرام.",
                    img: "/images/portfolio/instagram-manager.png",
                    slug: "portfolio",
                    metric: "Automated Scheduling"
                },
                {
                    title_en: "HEIC Converter",
                    title_ar: "أداة تحويل صور HEIC لـ JPG/PNG للويندوز",
                    badge: "Windows Utility",
                    desc_en: "Batch HEIC to JPG/PNG converter for Windows with drag-and-drop interface.",
                    desc_ar: "تحويل مجمع لصور الآيفون بصيغة HEIC إلى JPG/PNG بالسحب والإفلات.",
                    img: "/images/portfolio/heic-converter.png",
                    slug: "portfolio",
                    metric: "Batch Conversion"
                }
            ]
        };

        let currentCategory = 'web';
        let currentIndex = 0;

        const imgEl = document.getElementById('gallery-stage-img');
        const badgeEl = document.getElementById('gallery-stage-badge');
        const titleEl = document.getElementById('gallery-stage-title');
        const descEl = document.getElementById('gallery-stage-desc');
        const metricEl = document.getElementById('gallery-stage-metric');
        const linkEl = document.getElementById('gallery-stage-link');
        const paginationEl = document.getElementById('gallery-pagination');
        const prevBtn = document.getElementById('gallery-stage-prev');
        const nextBtn = document.getElementById('gallery-stage-next');
        const categoryPills = document.querySelectorAll('.gallery-category-pill');

        function renderSlide(index) {
            const list = galleryData[currentCategory];
            if (!list || list.length === 0) return;

            currentIndex = (index + list.length) % list.length;
            const item = list[currentIndex];

            // Smooth crossfade effect
            imgEl.style.opacity = '0';
            setTimeout(() => {
                imgEl.src = item.img;
                imgEl.alt = item.title_en;
                imgEl.style.opacity = '1';
            }, 150);

            badgeEl.textContent = item.badge;
            titleEl.textContent = isArabic ? item.title_ar : item.title_en;
            descEl.textContent = isArabic ? item.desc_ar : item.desc_en;
            metricEl.textContent = item.metric;
            linkEl.href = item.slug === 'portfolio' ? '/portfolio' : '/portfolio/' + item.slug;

            renderPagination(list.length, currentIndex);
        }

        function renderPagination(total, current) {
            paginationEl.innerHTML = '';
            for (let i = 0; i < total; i++) {
                const dot = document.createElement('button');
                dot.setAttribute('aria-label', `Slide ${i + 1}`);
                if (i === current) {
                    dot.className = 'h-2 w-8 rounded-full bg-[#1d1d1f] transition-all duration-300';
                } else {
                    dot.className = 'h-2 w-2 rounded-full bg-[#1d1d1f]/25 hover:bg-[#1d1d1f]/50 transition-all duration-200';
                }
                dot.addEventListener('click', () => {
                    renderSlide(i);
                });
                paginationEl.appendChild(dot);
            }
        }

        // Arrow Controls
        prevBtn?.addEventListener('click', () => {
            renderSlide(currentIndex - 1);
        });

        nextBtn?.addEventListener('click', () => {
            renderSlide(currentIndex + 1);
        });

        // Category Switcher
        categoryPills.forEach(pill => {
            pill.addEventListener('click', () => {
                const cat = pill.getAttribute('data-category');
                if (cat === currentCategory) return;

                categoryPills.forEach(p => {
                    p.classList.remove('bg-white', 'text-[#1d1d1f]', 'shadow-sm');
                    p.classList.add('text-[#1d1d1f]/70');
                });

                pill.classList.remove('text-[#1d1d1f]/70');
                pill.classList.add('bg-white', 'text-[#1d1d1f]', 'shadow-sm');

                currentCategory = cat;
                currentIndex = 0;
                renderSlide(0);
            });
        });

        // Initial Render
        renderSlide(0);
    });
</script>
@endsection
