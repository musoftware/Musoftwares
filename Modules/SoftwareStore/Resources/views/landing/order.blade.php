@php
    $cta = $copy['cta'] ?? [];
    $fields = [
        ['client_name', 'name', 'text', 'name'],
        ['client_email', 'email', 'email', 'email'],
        ['client_whatsapp', 'whatsapp', 'tel', 'tel'],
    ];
@endphp
<section id="order" class="bg-[var(--sw-ink)] text-white scroll-mt-4">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-16 sm:py-24 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div class="space-y-4 min-w-0">
            <p class="text-sm font-semibold text-[var(--sw-accent)]">{{ $cta['kicker'] ?? '' }}</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold leading-tight">{{ $cta['title'] ?? __('software.order_title') }}</h2>
            <p class="text-white/70 leading-relaxed">{{ $cta['body'] ?? '' }}</p>
            @if ($latest)
                <p class="text-sm text-white/50">{{ __('software.latest_version', ['version' => $latest->version]) }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('software.orders.store', $product->slug) }}"
              class="bg-white text-[#111827] rounded-2xl p-6 space-y-4 shadow-xl">
            @csrf
            @if (preg_match('/^[A-Za-z0-9_\- ]{8,128}$/', (string) request('device', old('device_id'))))
                <input type="hidden" name="device_id" value="{{ request('device', old('device_id')) }}">
                <p class="text-xs text-black/60">{{ __('software.device_attached') }}</p>
            @endif
            <h3 class="text-xl font-bold">{{ __('software.order_title') }}</h3>

            @if ($product->hasPlans())
                <fieldset class="space-y-2">
                    <legend class="block text-sm font-medium mb-1">{{ __('software.choose_plan') }}</legend>
                    @foreach ($product->planList() as $plan)
                        <label class="flex items-center justify-between gap-3 rounded-xl border border-black/15 p-3 cursor-pointer has-[:checked]:border-[var(--sw-accent)] has-[:checked]:bg-[var(--sw-accent)]/5">
                            <span class="flex items-center gap-3 min-w-0">
                                <input type="radio" name="plan_key" value="{{ $plan['key'] }}" required class="accent-[var(--sw-accent)]"
                                       @checked(old('plan_key', $product->planList()[0]['key']) === $plan['key'])>
                                <span class="min-w-0">
                                    <span class="font-semibold block">{{ $plan['name'] }}</span>
                                    @if (! empty($plan['description']))
                                        <span class="block text-xs text-black/60">{{ $plan['description'] }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="font-bold shrink-0" dir="ltr">{{ $product->money((float) $plan['price']) }} {{ $product->saleCurrency() }}</span>
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
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                           value="{{ old($field) }}" required dir="ltr"
                           class="w-full h-12 px-4 rounded-xl border border-black/15 text-base focus:outline-none focus:ring-2 focus:ring-[var(--sw-accent)]">
                    @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach

            @if (! empty($copy['terms']))
                <p class="text-xs text-black/60 leading-relaxed">{{ $copy['terms'] }}</p>
            @endif

            <button type="submit" class="w-full h-12 rounded-xl bg-[var(--sw-accent)] text-white font-semibold">
                @if ($product->hasPlans())
                    {{ $cta['button'] ?? __('software.continue_to_payment') }}
                @else
                    {{ $cta['button'] ?? __('software.pay', ['price' => $product->money($product->salePrice()).' '.$product->saleCurrency()]) }}
                    &middot; {{ $product->money($product->salePrice()) }} {{ $product->saleCurrency() }} {{ $product->periodLabel() }}
                @endif
            </button>
            <p class="text-xs text-black/50 text-center">{{ __('software.no_account') }}</p>
        </form>
    </div>
</section>
