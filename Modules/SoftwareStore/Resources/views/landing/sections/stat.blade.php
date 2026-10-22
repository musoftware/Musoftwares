@if (! empty($copy['stat']['value']))
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-[800px] mx-auto px-4 sm:px-6 text-center space-y-3">
        @if (! empty($copy['stat']['kicker']))
            <p class="text-sm font-semibold text-[var(--sw-accent)]">{{ $copy['stat']['kicker'] }}</p>
        @endif
        <p class="text-7xl sm:text-8xl font-extrabold text-[var(--sw-accent)]" dir="ltr">{{ $copy['stat']['value'] }}</p>
        <h2 class="text-2xl sm:text-3xl font-bold text-[var(--sw-ink)]">{{ $copy['stat']['title'] }}</h2>
        <p class="text-black/60">{{ $copy['stat']['body'] ?? '' }}</p>
    </div>
</section>
@endif
