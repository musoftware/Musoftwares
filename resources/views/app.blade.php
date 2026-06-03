<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0f0f11">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Musoftware">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/pwa-192.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @php
            $runtimeHost = '127.0.0.1';
            $user = auth()->user();
            if (!$user && auth('erp_team')->check()) {
                $user = auth('erp_team')->user()?->tenant?->user;
            }
            if (!$user && auth('crm_team')->check()) {
                $crmMember = auth('crm_team')->user();
                $user = $crmMember?->workspace?->owner;
            }
            if ($user && is_array($user->workspace_settings) && isset($user->workspace_settings['runtimeHost'])) {
                $runtimeHost = $user->workspace_settings['runtimeHost'];
            }
        @endphp
        <script>
            window.MUSOFTWARE_RUNTIME_HOST = @json($runtimeHost);
        </script>

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx'])
        @inertiaHead

        <!-- Service Worker registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' })
                        .then(reg => console.debug('[SW] registered', reg.scope))
                        .catch(err => console.warn('[SW] registration failed', err));
                });
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
