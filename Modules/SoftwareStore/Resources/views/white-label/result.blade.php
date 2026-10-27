@extends('softwarestore::white-label.layout', ['partner' => $order->partner, 'pageTitle' => __($succeeded ? 'software.success_title' : 'software.failed_title').' | '.$order->partner->brand_name])

@if ($succeeded && ! $order->isPaid())
    @push('head')<meta http-equiv="refresh" content="15">@endpush
@endif

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center space-y-6">
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
        <p class="text-sm text-[#1d1d1f]/50">{{ __('software.order_number') }}: <span class="font-semibold text-[#1d1d1f]">{{ $order->reference() }}</span></p>

        @if ($order->partner->whatsappNumber())
            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center w-full h-14 rounded-[980px] bg-[#25d366] text-white font-semibold text-lg">
                {{ __('software.whatsapp_button') }}
            </a>
        @endif
        @if ($downloadLink)
            <a href="{{ $downloadLink }}" class="flex items-center justify-center w-full h-12 rounded-[980px] border border-black/15 bg-white font-semibold hover:bg-[#f5f5f7]">
                {{ __('software.download') }}
            </a>
        @endif
    @else
        <h1 class="text-3xl font-bold tracking-tight">{{ __('software.failed_title') }}</h1>
        <p class="text-[#1d1d1f]/60">{{ __('software.failed_body') }}</p>
        <a href="{{ route('white-label.product', [$order->partner, $order->product->slug]) }}"
           class="flex items-center justify-center w-full h-12 rounded-[980px] bg-[var(--wl-accent)] text-white font-semibold">
            {{ __('software.try_again') }}
        </a>
    @endif

    <a href="{{ route('white-label.store', $order->partner) }}" class="inline-block text-sm font-semibold text-[var(--wl-accent)]">{{ __('white_label.back_to_store') }}</a>
</div>
@endsection
