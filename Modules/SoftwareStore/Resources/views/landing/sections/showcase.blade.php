@if (! empty($copy['showcase']['items']))
<section class="py-16 sm:py-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['showcase']['kicker'] ?? null, 'title' => $copy['showcase']['title']])
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-center">
            <div class="lg:col-span-3 min-w-0">
                @if ($gallery)
                    <img src="{{ $gallery[1] ?? $gallery[0] }}" alt="{{ $product->localized('name') }}" loading="lazy"
                         class="w-full rounded-2xl border border-black/10 shadow-lg">
                @endif
            </div>
            <div class="lg:col-span-2 space-y-4">
                @foreach ($copy['showcase']['items'] as $item)
                    <div class="bg-white border border-black/10 rounded-2xl p-5">
                        <h3 class="font-bold text-lg">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-black/60">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
