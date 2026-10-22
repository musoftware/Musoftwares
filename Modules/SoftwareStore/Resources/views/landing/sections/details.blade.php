@php $d = $copy['details'] ?? []; @endphp
@if (! empty($d['title']))
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-[900px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $d['kicker'] ?? null, 'title' => $d['title']])
        <div class="space-y-4 text-base leading-relaxed text-black/75">
            @foreach ($d['paragraphs'] ?? [] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
        @if (! empty($d['list']))
            <h3 class="mt-8 mb-3 text-lg font-bold">{{ $d['list_title'] ?? '' }}</h3>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                @foreach ($d['list'] as $line)
                    <li class="flex gap-3 text-black/75">
                        <span class="mt-1 shrink-0 text-[var(--sw-accent)]">@include('softwarestore::landing.icon', ['name' => 'check'])</span>
                        <span>{{ $line }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
