@if (! empty($copy['pillars']['items']))
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['pillars']['kicker'] ?? null, 'title' => $copy['pillars']['title']])
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($copy['pillars']['items'] as $item)
                <div class="bg-[#fafbfc] border border-black/10 rounded-2xl p-6 space-y-3">
                    <span class="w-11 h-11 rounded-xl bg-[var(--sw-accent)]/10 text-[var(--sw-accent)] flex items-center justify-center">
                        @include('softwarestore::landing.icon', ['name' => $item['icon'] ?? 'check'])
                    </span>
                    <h3 class="font-bold text-lg">{{ $item['title'] }}</h3>
                    <p class="text-black/60 leading-relaxed">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
