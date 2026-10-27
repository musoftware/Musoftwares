@php $seo = app(\Modules\SoftwareStore\Services\StoreSeo::class); @endphp
@push('head')
    {{-- a private link sent to one customer stays out of search results --}}
    <meta name="robots" content="{{ ($shared ?? false) ? 'noindex,nofollow' : 'index,follow,max-image-preview:large' }}">
    @foreach ($seo->alternates($product->purchaseUrl()) as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" type="text/markdown" href="{{ route('software.markdown', $product->slug) }}">
    <script type="application/ld+json">{!! $seo->toJson($seo->productGraph($product)) !!}</script>
@endpush
