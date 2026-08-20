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
        <!-- 5. PROVEN SYSTEMS IN PRODUCTION (Carousel) -->
        <!-- ============================================================ -->
        <section class="rounded-[18px] bg-[#f5f5f7] py-14 px-6 overflow-hidden">
            <div class="max-w-[1280px] mx-auto mb-8 text-center sm:text-start rtl:sm:text-right flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-[32px] sm:text-[44px] font-semibold text-[#1d1d1f] tracking-tight">
                        Proven Systems In Production.
                    </h3>
                </div>
                <div class="flex gap-2">
                    <button class="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center text-black/40 hover:bg-black/10">‹</button>
                    <button class="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center text-black/40 hover:bg-black/10">›</button>
                </div>
            </div>

            <!-- Horizontal Scrollable Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 max-w-[1400px] mx-auto">
                
                <div class="bg-white rounded-2xl border border-black/5 p-4 space-y-3 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">W</div>
                    <div>
                        <h4 class="text-base font-bold text-[#1d1d1f]">Trenz CRM</h4>
                        <p class="text-xs text-[#86868b]">Meta Cloud WhatsApp</p>
                    </div>
                    <div class="h-1 bg-black/5 rounded-full overflow-hidden">
                        <div class="h-full w-4/5 bg-black rounded-full"></div>
                    </div>
                    <div class="text-[11px] text-[#86868b]">1.2M chats sent</div>
                    <a href="/portfolio/trenz-whatscrm" class="text-xs text-[#0071e3] font-medium block">Case study &gt;</a>
                </div>

                <div class="bg-white rounded-2xl border border-black/5 p-4 space-y-3 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">$</div>
                    <div>
                        <h4 class="text-base font-bold text-[#1d1d1f]">ChartCash</h4>
                        <p class="text-xs text-[#86868b]">Financial &amp; POS Engine</p>
                    </div>
                    <div class="h-1 bg-black/5 rounded-full overflow-hidden">
                        <div class="h-full w-full bg-[#0071e3] rounded-full"></div>
                    </div>
                    <div class="text-[11px] text-[#86868b]">10ms latency</div>
                    <a href="/portfolio/chartcash" class="text-xs text-[#0071e3] font-medium block">Case study &gt;</a>
                </div>

                <div class="bg-white rounded-2xl border border-black/5 p-4 space-y-3 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">K</div>
                    <div>
                        <h4 class="text-base font-bold text-[#1d1d1f]">Kbdny</h4>
                        <p class="text-xs text-[#86868b]">Multi-Vendor Dropshipping</p>
                    </div>
                    <div class="h-1 bg-black/5 rounded-full overflow-hidden">
                        <div class="h-full w-3/4 bg-amber-500 rounded-full"></div>
                    </div>
                    <div class="text-[11px] text-[#86868b]">5k+ affiliates</div>
                    <a href="/portfolio/kbdny" class="text-xs text-[#0071e3] font-medium block">Case study &gt;</a>
                </div>

                <div class="bg-white rounded-2xl border border-black/5 p-4 space-y-3 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">S</div>
                    <div>
                        <h4 class="text-base font-bold text-[#1d1d1f]">StockManager</h4>
                        <p class="text-xs text-[#86868b]">Retail POS &amp; Inventory</p>
                    </div>
                    <div class="h-1 bg-black/5 rounded-full overflow-hidden">
                        <div class="h-full w-2/3 bg-purple-500 rounded-full"></div>
                    </div>
                    <div class="text-[11px] text-[#86868b]">12 stores</div>
                    <a href="/portfolio/stock-manager" class="text-xs text-[#0071e3] font-medium block">Case study &gt;</a>
                </div>

                <div class="bg-white rounded-2xl border border-black/5 p-4 space-y-3 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">F</div>
                    <div>
                        <h4 class="text-base font-bold text-[#1d1d1f]">Mini Fatora</h4>
                        <p class="text-xs text-[#86868b]">Invoicing &amp; Receipts</p>
                    </div>
                    <div class="h-1 bg-black/5 rounded-full overflow-hidden">
                        <div class="h-full w-4/5 bg-emerald-500 rounded-full"></div>
                    </div>
                    <div class="text-[11px] text-[#86868b]">50k invoices</div>
                    <a href="/portfolio/mini-fatora" class="text-xs text-[#0071e3] font-medium block">Case study &gt;</a>
                </div>

            </div>
        </section>

    </div>

</div>
@endsection
