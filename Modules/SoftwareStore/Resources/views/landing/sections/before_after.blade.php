@php $ba = $copy['before_after'] ?? []; @endphp
@if (! empty($ba['before']))
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $ba['kicker'] ?? null, 'title' => $ba['title']])
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded-2xl border border-red-200 p-6 space-y-4">
                <h3 class="text-xl font-bold text-red-600">{{ $ba['before_title'] }}</h3>
                @foreach ($ba['before'] as $row)
                    <p class="text-black/60"><strong class="text-black/80">{{ $row['b'] }}:</strong> {{ $row['t'] }}</p>
                @endforeach
            </div>
            <div class="rounded-2xl border-2 border-[var(--sw-accent)]/40 p-6 space-y-4">
                <h3 class="text-xl font-bold text-[var(--sw-accent)]">{{ $ba['after_title'] }}</h3>
                @foreach ($ba['after'] as $row)
                    <p class="text-black/60"><strong class="text-black/80">{{ $row['b'] }}:</strong> {{ $row['t'] }}</p>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
