@php
    $cta = $copy['cta'] ?? [];
    $fields = [
        ['client_name', 'name', 'text', 'name'],
        ['client_email', 'email', 'email', 'email'],
        ['client_whatsapp', 'whatsapp', 'tel', 'tel'],
    ];
    $authUser = auth()->user();
    $fieldDefaults = [
        'client_name' => $authUser?->name,
        'client_email' => $authUser?->email,
        'client_whatsapp' => $authUser?->whatsapp_number,
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
                            <span class="font-bold shrink-0 text-end" dir="ltr">{{ $product->money((float) $plan['price']) }} {{ $product->saleCurrency() }}@if ($usd = $product->approxUsdLabel((float) $plan['price']))<span class="block text-xs font-medium text-black/50">{{ $usd }}</span>@endif</span>
                        </label>
                    @endforeach
                    @error('plan_key')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </fieldset>
            @endif

            @if (session('error'))
                <p class="text-sm text-red-600" role="alert">{{ session('error') }}</p>
            @endif

            @auth
                <input type="hidden" name="client_name" value="{{ $authUser->name }}">
                <input type="hidden" name="client_email" value="{{ $authUser->email }}">
                <input type="hidden" name="client_whatsapp" value="{{ $fieldDefaults['client_whatsapp'] ?? '00000000000' }}">

                <div class="rounded-xl border border-black/10 bg-black/[0.03] p-4 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <span class="text-xs font-semibold uppercase tracking-wider text-black/50 block">{{ __('software.logged_in_account') }}</span>
                        <span class="font-bold text-base text-black truncate block">{{ $authUser->name }}</span>
                        <span class="text-xs text-black/60 truncate block" dir="ltr">{{ $authUser->email }}</span>
                    </div>
                    <div class="shrink-0 text-xs text-emerald-700 font-semibold bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1">
                        {{ __('software.account_verified') }}
                    </div>
                </div>
            @else
                @foreach ($fields as [$field, $label, $type, $autocomplete])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium mb-1">{{ __('software.'.$label) }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                               value="{{ old($field, $fieldDefaults[$field] ?? null) }}" required dir="ltr"
                               class="w-full h-12 px-4 rounded-xl border border-black/15 text-base focus:outline-none focus:ring-2 focus:ring-[var(--sw-accent)]">
                        @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            @endauth

            <div>
                <label for="coupon_code" class="block text-sm font-medium mb-1">{{ __('software.coupon_code') }}</label>
                <input id="coupon_code" name="coupon_code" type="text" autocomplete="off" dir="ltr"
                       value="{{ old('coupon_code') }}"
                       placeholder="{{ __('software.coupon_placeholder') }}"
                       class="w-full h-12 px-4 rounded-xl border border-black/15 text-base uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-[var(--sw-accent)]">
                @error('coupon_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @auth
                    <p class="mt-1.5 text-xs text-black/60 font-medium">
                        {{ __('software.logged_in_as_client_ready', ['name' => $authUser->name]) }}
                    </p>
                @else
                    <p class="mt-1.5 text-xs text-black/60">
                        {{ __('software.coupon_note_for_guests') }}
                        <a href="{{ route('login', ['redirect' => request()->fullUrl().'#order']) }}" class="text-[var(--sw-accent)] font-semibold underline">{{ __('software.login_here') }}</a>
                    </p>
                @endauth
            </div>

            @if (! empty($copy['terms']))
                <p class="text-xs text-black/60 leading-relaxed">{{ $copy['terms'] }}</p>
            @endif

            <button id="order-submit-btn" type="submit" class="w-full h-12 rounded-xl bg-[var(--sw-accent)] text-white font-semibold transition hover:brightness-105 active:scale-[0.99] flex items-center justify-center gap-2">
                @if ($product->hasPlans())
                    {{ $cta['button'] ?? __('software.continue_to_payment') }}
                @else
                    {{ $cta['button'] ?? __('software.pay', ['price' => $product->money($product->salePrice()).' '.$product->saleCurrency()]) }}
                    &middot; {{ $product->money($product->salePrice()) }} {{ $product->saleCurrency() }} {{ $product->approxUsdLabel($product->salePrice()) }} {{ $product->periodLabel() }}
                @endif
            </button>
            <script>
                (function() {
                    var input = document.getElementById('coupon_code');
                    var btn = document.getElementById('order-submit-btn');
                    if (!input || !btn) return;
                    var originalHtml = btn.innerHTML;
                    var applyText = @json(__('software.apply_coupon_and_proceed'));
                    function updateBtn() {
                        if (input.value.trim().length > 0) {
                            btn.textContent = applyText;
                        } else {
                            btn.innerHTML = originalHtml;
                        }
                    }
                    input.addEventListener('input', updateBtn);
                    updateBtn();
                })();
            </script>
            @if ($usdNote = $product->approxUsdNote())
                <p class="text-xs text-black/50 text-center">{{ $usdNote }}</p>
            @endif
            <p class="text-xs text-black/50 text-center">{{ __('software.no_account') }}</p>
        </form>
    </div>
</section>
