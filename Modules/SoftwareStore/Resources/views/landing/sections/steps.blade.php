@if (! empty($copy['steps']['items']))
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['steps']['kicker'] ?? null, 'title' => $copy['steps']['title']])
        <ol class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($copy['steps']['items'] as $i => $item)
                <li class="border border-black/10 rounded-2xl p-6 space-y-2">
                    <p class="text-sm font-semibold text-[var(--sw-accent)]">{{ __('software.step', ['number' => $i + 1]) }}</p>
                    <h3 class="font-bold text-lg">{{ $item['title'] }}</h3>
                    <p class="text-black/60">{{ $item['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif
