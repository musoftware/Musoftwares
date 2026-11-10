@inject('shopSeo', 'Modules\Shop\Services\ShopSeo')
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <meta property="og:site_name" content="{{ config('shop.name') }}">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if ($seo['image'])
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:secure_url" content="{{ $seo['image'] }}">
        @foreach ($seo['image_meta'] as $key => $value)
            <meta property="og:image:{{ $key }}" content="{{ $value }}">
        @endforeach
        <meta property="og:image:alt" content="{{ $seo['image_alt'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['image_alt'] }}">
    @endif
    @foreach ($seo['og_tags'] as $property => $value)
        <meta property="{{ $property }}" content="{{ $value }}">
    @endforeach
    <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:site" content="{{ config('shop.twitter_site') }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    @foreach (array_keys($seo['twitter_labels']) as $index => $label)
        <meta name="twitter:label{{ $index + 1 }}" content="{{ $label }}">
        <meta name="twitter:data{{ $index + 1 }}" content="{{ $seo['twitter_labels'][$label] }}">
    @endforeach
    <meta name="theme-color" content="#1c1917">
    @foreach ($seo['schemas'] as $schema)
        <script type="application/ld+json">{!! $shopSeo->toJson($schema) !!}</script>
    @endforeach
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap">
    @include('shop::partials.styles')
</head>
<body>
    @include('shop::partials.header')
    <main id="main" class="page">
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    @include('shop::partials.footer')
    @stack('scripts')
</body>
</html>
