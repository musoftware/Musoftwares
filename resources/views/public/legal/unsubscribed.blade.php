@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans pt-24 pb-32 px-6">
    <div class="max-w-xl mx-auto bg-white border border-black/10 rounded-[24px] shadow-sm p-8 sm:p-10 text-center space-y-4">
        <h1 class="text-2xl font-bold text-[#1d1d1f]">
            {{ __('privacy_compliance.unsubscribe_success_title') }}
        </h1>
        <p class="text-sm text-[#1d1d1f]/70 leading-relaxed">
            {{ __('privacy_compliance.unsubscribe_success_body', ['email' => $email]) }}
        </p>
        <div class="pt-4">
            <a href="/privacy-policy" class="btn-studio-secondary">
                {{ __('privacy_compliance.read_privacy_policy') }}
            </a>
        </div>
    </div>
</div>
@endsection
