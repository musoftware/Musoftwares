@if (! empty($copy['problems']['items']))
<section class="py-16 sm:py-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['problems']['kicker'] ?? null, 'title' => $copy['problems']['title']])
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($copy['problems']['items'] as $item)
                <div class="bg-white border border-black/10 rounded-2xl p-6 space-y-3">
                    <span class="w-12 h-12 rounded-xl bg-[var(--sw-accent)]/10 text-[var(--sw-accent)] flex items-center justify-center">
                        @include('softwarestore::landing.icon', ['name' => $item['icon'] ?? 'check'])
                    </span>
                    <h3 class="text-lg font-bold">{{ $item['title'] }}</h3>
                    <p class="text-black/60 leading-relaxed">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
