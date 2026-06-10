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
        
        @if(isset($meta))
            <meta name="description" content="{{ $meta['description'] ?? '' }}">
            <meta property="og:title" content="{{ $meta['title'] ?? '' }}">
            <meta property="og:description" content="{{ $meta['description'] ?? '' }}">
            <meta property="og:image" content="{{ $meta['image'] ?? '' }}">
            <meta property="og:url" content="{{ $meta['url'] ?? '' }}">
            <meta property="og:type" content="website">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $meta['title'] ?? '' }}">
            <meta name="twitter:description" content="{{ $meta['description'] ?? '' }}">
            <meta name="twitter:image" content="{{ $meta['image'] ?? '' }}">
        @endif

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
        <div id="initial-global-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; display: flex; justify-content: center; align-items: center; background-color: #0f0f11; transition: opacity 0.5s ease-out, visibility 0.5s ease-out;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(255, 255, 255, 0.1); border-radius: 50%; border-top-color: #ffffff; animation: initial-spin 1s ease-in-out infinite;"></div>
        </div>
        <style>
            @keyframes initial-spin {
                to { transform: rotate(360deg); }
            }
        </style>
        @inertia

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var loader = document.getElementById('initial-global-loader');
                if (loader) {
                    var checkInterval = setInterval(function() {
                        var app = document.getElementById('app');
                        if (app && app.children.length > 0) {
                            clearInterval(checkInterval);
                            loader.style.opacity = '0';
                            loader.style.visibility = 'hidden';
                            setTimeout(function() {
                                if (loader.parentNode) {
                                    loader.parentNode.removeChild(loader);
                                }
                            }, 500);
                        }
                    }, 50);
                    // Fallback just in case
                    setTimeout(function() {
                        clearInterval(checkInterval);
                        if (loader && loader.parentNode) {
                            loader.style.opacity = '0';
                            loader.style.visibility = 'hidden';
                            setTimeout(function() {
                                if (loader.parentNode) {
                                    loader.parentNode.removeChild(loader);
                                }
                            }, 500);
                        }
                    }, 10000);
                }
            });
        </script>
    </body>
</html>
