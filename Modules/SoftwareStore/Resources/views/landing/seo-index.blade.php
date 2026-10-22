@php $seo = app(\Modules\SoftwareStore\Services\StoreSeo::class); @endphp
@push('head')
    <meta name="robots" content="index,follow,max-image-preview:large">
    @foreach ($seo->alternates(route('services.index')) as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
    @endforeach
    <script type="application/ld+json">{!! $seo->toJson($seo->indexGraph($products)) !!}</script>
@endpush
