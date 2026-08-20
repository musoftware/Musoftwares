@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            {{ __('landing_legal.legal_center') }}
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            {{ __('landing_legal.cookie_title') }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            {{ __('landing_legal.cookie_subtitle') }}
        </p>
        <div class="text-xs font-mono text-zinc-500 pt-2">
            {{ __('landing_legal.last_updated') }}
        </div>
    </div>

    <!-- Cookies Document Content -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-12 text-sm leading-relaxed text-zinc-300">
        
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_intro_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_intro_body') }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_use_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_use_body') }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_types_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_types_body') }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_third_party_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_third_party_body') }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_control_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_control_body') }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-lg font-bold text-white font-sans">
                {{ __('landing_legal.cookie_updates_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-zinc-400 font-sans leading-relaxed">
                {{ __('landing_legal.cookie_updates_body') }}
            </p>
        </div>

    </div>

</div>
@endsection
