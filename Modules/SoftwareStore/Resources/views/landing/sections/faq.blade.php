@php $faq = $copy['faq'] ?? []; @endphp
@if (! empty($faq['items']))
<section class="py-16 sm:py-20">
    <div class="max-w-[900px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $faq['kicker'] ?? null, 'title' => $faq['title'] ?? __('software.faq')])
        <div class="divide-y divide-black/10 rounded-2xl border border-black/10 bg-white">
            @foreach ($faq['items'] as $item)
                <details class="group p-5" @if ($loop->first) open @endif>
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold">
                        <span>{{ $item['q'] }}</span>
                        <span class="shrink-0 text-[var(--sw-accent)] transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-black/70 leading-relaxed">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
