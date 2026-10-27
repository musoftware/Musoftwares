@extends('softwarestore::white-label.layout', ['pageTitle' => $product->localized('name').' | '.$partner->brand_name])

@php
    $fields = [
        ['client_name', 'name', 'text', 'name'],
        ['client_email', 'email', 'email', 'email'],
        ['client_whatsapp', 'whatsapp', 'tel', 'tel'],
    ];
    $device = (string) request('device', old('device_id'));
@endphp

@section('content')
<div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-10 sm:py-14 grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
    <div class="min-w-0">
        <a href="{{ route('white-label.store', $partner) }}" class="text-sm font-semibold text-[var(--wl-accent)]">&larr; {{ __('white_label.back_to_store') }}</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ $product->localized('name') }}</h1>
        @if ($product->localized('tagline'))
            <p class="mt-3 text-[#1d1d1f]/70 leading-relaxed">{{ $product->localized('tagline') }}</p>
        @endif
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="" class="mt-6 w-full rounded-2xl border border-black/5 shadow-sm">
        @endif
    </div>

    <form method="POST" action="{{ route('white-label.order', [$partner, $product->slug]) }}" class="bg-white rounded-2xl border border-black/5 p-6 space-y-4 shadow-sm">
        @csrf
        @if (preg_match('/^[A-Za-z0-9_\- ]{8,128}$/', $device))
            <input type="hidden" name="device_id" value="{{ $device }}">
            <p class="text-xs text-black/60">{{ __('software.device_attached') }}</p>
        @endif
        <h2 class="text-xl font-bold">{{ __('software.order_title') }}</h2>

        @if ($plans)
            <fieldset class="space-y-2">
                <legend class="block text-sm font-medium mb-1">{{ __('software.choose_plan') }}</legend>
                @foreach ($plans as $plan)
                    <label class="flex items-center justify-between gap-3 rounded-xl border border-black/15 p-3 cursor-pointer has-[:checked]:border-[var(--wl-accent)]">
                        <span class="flex items-center gap-3 min-w-0">
                            <input type="radio" name="plan_key" value="{{ $plan['key'] }}" required class="accent-[var(--wl-accent)]" @checked(old('plan_key', $plans[0]['key']) === $plan['key'])>
                            <span class="min-w-0">
                                <span class="font-semibold block">{{ $plan['name'] }}</span>
                                @if (! empty($plan['description']))
                                    <span class="block text-xs text-black/60">{{ $plan['description'] }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="font-bold shrink-0" dir="ltr">{{ $product->money($plan['partner_price']) }} {{ $product->saleCurrency() }}</span>
                    </label>
                @endforeach
                @error('plan_key')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
        @endif

        @if (session('error'))
            <p class="text-sm text-red-600" role="alert">{{ session('error') }}</p>
        @endif

        @foreach ($fields as [$field, $label, $type, $autocomplete])
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium mb-1">{{ __('software.'.$label) }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($field) }}" required dir="ltr"
                       class="w-full h-12 px-4 rounded-xl border border-black/15 text-base focus:outline-none focus:ring-2 focus:ring-[var(--wl-accent)]">
                @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <button type="submit" class="w-full h-12 rounded-xl bg-[var(--wl-accent)] text-white font-semibold">
            {{ $plans ? __('software.continue_to_payment') : __('software.pay', ['price' => $product->money($price).' '.$product->saleCurrency()]) }}
        </button>
        <p class="text-xs text-black/50 text-center">{{ __('white_label.activates_itself') }}</p>
    </form>
</div>
@endsection
