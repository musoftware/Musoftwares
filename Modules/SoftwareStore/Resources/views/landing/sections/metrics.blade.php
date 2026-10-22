@if (! empty($copy['metrics']['items']))
<section class="py-16 sm:py-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['metrics']['kicker'] ?? null, 'title' => $copy['metrics']['title']])
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
            @foreach ($copy['metrics']['items'] as $item)
                <div>
                    <p class="text-5xl sm:text-6xl font-extrabold text-[var(--sw-accent)]" dir="ltr">{{ $item['value'] }}</p>
                    <p class="mt-2 text-black/60">{{ $item['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
