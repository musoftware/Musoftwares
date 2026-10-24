@extends('layouts.public', ['title' => __($succeeded ? 'software.success_title' : 'software.failed_title').' | Musoftwares'])

@push('head')
    <meta name="robots" content="noindex">
    @if ($succeeded && ! $order->isPaid())
        <meta http-equiv="refresh" content="15">
    @endif
@endpush

@section('content')
@include('softwarestore::landing.language')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-16 sm:pt-24 pb-24">
    <div class="max-w-xl mx-auto px-4 sm:px-6 text-center space-y-6">
        @if ($succeeded)
            <h1 class="text-3xl font-bold tracking-tight">{{ __('software.success_title') }}</h1>
            <p class="text-[#1d1d1f]/60">{{ $order->isPaid() ? __('software.success_paid') : __('software.success_pending') }}</p>
            @if ($order->activated_at)
                <p class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
                    {{ $order->device_id ? __('software.activated_device') : __('software.activated_account', ['email' => $order->client_email]) }}
                    @if ($order->license_expires_at)
                        <br>{{ __('software.valid_until', ['date' => $order->license_expires_at->format('Y-m-d')]) }}
                    @endif
                </p>
            @endif
            <p class="text-sm text-[#1d1d1f]/50">{{ __('software.order_number') }}: <span class="font-semibold text-[#1d1d1f]">{{ $order->reference() }}</span>@if ($order->plan_name) &middot; {{ $order->product->planByKey($order->plan_key)['name'] ?? $order->plan_name }}@endif</p>

            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center w-full h-14 rounded-[980px] bg-[#25d366] hover:bg-[#1fb957] text-white font-semibold text-lg transition-colors">
                {{ __('software.whatsapp_button') }}
            </a>

            @if ($downloadLink)
                <a href="{{ $downloadLink }}"
                   class="flex items-center justify-center w-full h-12 rounded-[980px] border border-black/15 bg-white font-semibold hover:bg-[#f5f5f7] transition-colors">
                    {{ __('software.download') }}
                </a>
            @endif
        @else
            <h1 class="text-3xl font-bold tracking-tight">{{ __('software.failed_title') }}</h1>
            <p class="text-[#1d1d1f]/60">{{ __('software.failed_body') }}</p>
            <a href="{{ route('website-services.show', $order->product->slug) }}"
               class="flex items-center justify-center w-full h-12 rounded-[980px] bg-[#0071e3] text-white font-semibold">
                {{ __('software.try_again') }}
            </a>
        @endif

        <a href="{{ route('services.index') }}" class="inline-block text-sm text-[#0071e3] font-semibold">{{ __('software.back_to_programs') }}</a>
    </div>
</div>
@endsection
