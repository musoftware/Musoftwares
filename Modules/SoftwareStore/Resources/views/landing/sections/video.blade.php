@php
    $embed = $product->embedUrl();
    $videoFile = $product->videoFileUrl();
@endphp
@if ($embed || $videoFile)
<section class="py-16 sm:py-20">
    <div class="max-w-[900px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['video']['kicker'] ?? null, 'title' => $copy['video']['title'] ?? __('software.video')])
        @if ($embed)
            <div class="aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-lg">
                <iframe src="{{ $embed }}" title="{{ __('software.video') }}" class="w-full h-full" allowfullscreen loading="lazy"></iframe>
            </div>
        @else
            <video src="{{ $videoFile }}" controls preload="metadata" @if ($product->posterUrl()) poster="{{ $product->posterUrl() }}" @endif
                   class="w-full rounded-2xl bg-black aspect-video shadow-lg"></video>
        @endif
    </div>
</section>
@endif
