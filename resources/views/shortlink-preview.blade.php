<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="noindex, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $destination_url }}">

    <!-- OpenGraph Meta Tags (WhatsApp, Facebook, LinkedIn, Discord) -->
    <meta property="og:site_name" content="Musoftware">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@musoftwares">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    <!-- Structured Data -->
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <!-- Instant Redirect for Browsers -->
    <meta http-equiv="refresh" content="0;url={{ $destination_url }}">
    <script>
        window.location.replace({!! json_encode($destination_url) !!});
    </script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b0c10;
            color: #c5c6c7;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
        }
        .container {
            max-width: 480px;
            padding: 32px;
            background: #1f2833;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 20px;
            border: 3px solid rgba(102, 252, 241, 0.2);
            border-radius: 50%;
            border-top-color: #66fcf1;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin: 0 0 8px;
        }
        p {
            font-size: 14px;
            color: #8892b0;
            margin: 0 0 20px;
            line-height: 1.5;
        }
        a.btn {
            display: inline-block;
            background: #45a29e;
            color: #0b0c10;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        a.btn:hover {
            background: #66fcf1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <a class="btn" href="{{ $destination_url }}">الانتقال إلى الرابط &rarr;</a>
    </div>
</body>
</html>
