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

        @php
            $ga_id = \App\Models\AdminSettings::GetValue('google_analytics_id');
            $gtm_id = \App\Models\AdminSettings::GetValue('google_tag_manager_id');
            $pixel_id = \App\Models\AdminSettings::GetValue('meta_pixel_id');
            $custom_head = \App\Models\AdminSettings::GetValue('custom_head_scripts');
            $custom_body = \App\Models\AdminSettings::GetValue('custom_body_scripts');
        @endphp

        <!-- Google Tag Manager -->
        @if($gtm_id)
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm_id }}');</script>
        @endif
        <!-- End Google Tag Manager -->

        <!-- Google Analytics -->
        @if($ga_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga_id }}"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '{{ $ga_id }}');
        </script>
        @endif
        <!-- End Google Analytics -->

        <!-- Meta Pixel Code -->
        @if($pixel_id)
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $pixel_id }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixel_id }}&ev=PageView&noscript=1"/></noscript>
        @endif
        <!-- End Meta Pixel Code -->

        <!-- Custom Head Scripts -->
        @if($custom_head)
        {!! $custom_head !!}
        @endif
        <!-- End Custom Head Scripts -->

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
        <!-- Google Tag Manager (noscript) -->
        @if($gtm_id)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_id }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif
        <!-- End Google Tag Manager (noscript) -->

        <!-- Custom Body Scripts -->
        @if($custom_body)
        {!! $custom_body !!}
        @endif
        <!-- End Custom Body Scripts -->

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
