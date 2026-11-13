<!DOCTYPE html>
@php($rtl = app()->getLocale() === 'ar')
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="index,follow">
    <title>{{ __('builder.page_title') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600&display=swap">
    <style>
        :root{--ink:#111;--muted:#6b6b6b;--line:#e6e6e6}
        *{box-sizing:border-box}
        html,body{margin:0;background:#fff;color:var(--ink);font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;font-size:16px;line-height:1.7;overflow-x:clip}
        main{min-height:100vh;max-width:440px;margin:0 auto;padding:24px 16px;display:flex;flex-direction:column;justify-content:center;text-align:center}
        h1{margin:0 0 8px;font-size:28px;font-weight:600;line-height:1.3}
        p{margin:0 0 24px;color:var(--muted)}
        .google{display:flex;align-items:center;justify-content:center;gap:12px;min-height:48px;padding:8px 20px;border:1px solid var(--ink);border-radius:12px;background:var(--ink);color:#fff;font-weight:600;text-decoration:none}
        .google:focus-visible{outline:3px solid #4285F4;outline-offset:3px}
        .google svg{background:#fff;border-radius:50%;padding:2px}
        .why{margin:16px 0 0;font-size:14px}
    </style>
</head>
<body>
<main>
    <h1>{{ __('builder.google_gate.title') }}</h1>
    <p>{{ __('builder.google_gate.text') }}</p>
    <a class="google" href="{{ $googleUrl }}">
        <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        {{ __('builder.google_gate.button') }}
    </a>
    <p class="why">{{ __('builder.google_gate.why') }}</p>
</main>
</body>
</html>
