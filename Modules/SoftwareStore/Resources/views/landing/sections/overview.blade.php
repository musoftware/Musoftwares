@php $o = $copy['overview'] ?? []; @endphp
@if (! empty($o['items']))
<section class="py-12 sm:py-16 bg-white border-b border-black/5">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[var(--sw-ink)] mb-8">{{ $o['title'] ?? '' }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($o['items'] as $item)
                <div class="space-y-2">
                    <h3 class="font-bold text-[var(--sw-accent)]">{{ $item['title'] }}</h3>
                    <p class="text-black/70 leading-relaxed">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
