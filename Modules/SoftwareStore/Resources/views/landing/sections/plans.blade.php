@if ($product->hasPlans())
<section class="py-16 sm:py-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['plans']['kicker'] ?? null, 'title' => $copy['plans']['title'] ?? __('software.choose_plan')])
        <div class="grid grid-cols-1 sm:grid-cols-2 {{ count($product->planList()) === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4' }} gap-6">
            @foreach ($product->planList() as $plan)
                <div class="bg-white border border-black/10 rounded-2xl p-6 flex flex-col gap-4">
                    <h3 class="text-lg font-bold">{{ $plan['name'] }}</h3>
                    <p class="text-3xl font-extrabold text-[var(--sw-accent)]" dir="ltr">
                        {{ $product->money((float) $plan['price']) }}
                        <span class="text-sm font-semibold text-black/60">{{ $product->saleCurrency() }} {{ $product->periodLabel() }}</span>
                        @if ($usd = $product->approxUsdLabel((float) $plan['price']))
                            <span class="block text-xs font-medium text-black/50">{{ $usd }}</span>
                        @endif
                    </p>
                    @if (! empty($plan['description']))
                        <p class="text-sm text-black/70">{{ $plan['description'] }}</p>
                    @endif
                    <ul class="space-y-2 text-sm text-black/70 flex-1">
                        @isset($plan['points'])
                            <li>{{ __('software.plan_points', ['count' => number_format((int) $plan['points'])]) }}</li>
                        @endisset
                        @isset($plan['bots'])
                            <li>{{ __('software.plan_bots', ['count' => $plan['bots']]) }}</li>
                        @endisset
                        @foreach ($plan['features'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                        @foreach ($copy['plans']['included'] ?? [] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                    <a href="#order" class="h-11 inline-flex items-center justify-center rounded-xl border border-[var(--sw-accent)] text-[var(--sw-accent)] font-semibold hover:bg-[var(--sw-accent)]/5">
                        {{ __('software.buy') }}
                    </a>
                </div>
            @endforeach
        </div>
        @if (! empty($copy['plans']['note']))
            <p class="mt-6 text-sm text-black/60 leading-relaxed">{{ $copy['plans']['note'] }}</p>
        @endif
        @if ($usdNote = $product->approxUsdNote())
            <p class="mt-2 text-sm text-black/60 leading-relaxed">{{ $usdNote }}</p>
        @endif
    </div>
</section>
@endif
