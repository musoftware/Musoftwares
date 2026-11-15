{{-- The title, search snippet and share card of /ai, the same on the chat page and the sign-in page Facebook sees. --}}
@php($shareImage = app(\Modules\AppBuilder\Services\AiPageImage::class)->url(app()->getLocale()))
<title>{{ __('builder.page_title') }} | Musoftware</title>
<meta name="description" content="{{ __('builder.og_description') }}">
<link rel="canonical" href="{{ route('builder.index') }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Musoftware">
<meta property="og:title" content="{{ __('builder.og_headline') }}">
<meta property="og:description" content="{{ __('builder.og_description') }}">
<meta property="og:url" content="{{ route('builder.index') }}">
<meta property="og:image" content="{{ $shareImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ __('builder.og_headline') }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ __('builder.og_headline') }}">
<meta name="twitter:description" content="{{ __('builder.og_description') }}">
<meta name="twitter:image" content="{{ $shareImage }}">
