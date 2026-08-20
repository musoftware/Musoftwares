<!doctype html>
<html lang="en" style="overflow: overlay;">

<head>
    <script>
        (function() {
            if (window.self !== window.top) {
                var targetUrl = @json(url('/dashboard'));
                var isSandboxed = !window.location.href || window.location.href.startsWith('about:') || window.origin === 'null';
                if (isSandboxed) {
                    try {
                        window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                    } catch(e) {}
                } else {
                    try {
                        window.top.location.href = targetUrl;
                    } catch(e) {
                        try {
                            window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                        } catch(e2) {}
                    }
                }
            }
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Musoftwares Dashboard">
    <link rel="shortcut icon" type="image/svg+xml" href="/favicon.svg"/>

    <!-- Bootstrap & v8_main CSS -->
    <link rel="stylesheet" href="{{ asset('v8main/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('v8main/css/style.css?v=1.1') }}">

    <title>{{ Auth::user()->name ?? 'Dashboard' }} - Musoftwares</title>

    <script>
        var resource_link = "{{ asset('v8main/') }}/";
        var user_name = @json(Auth::user()->name ?? 'User');
    </script>

    <style>
        body {
            zoom: 0.75;
        }

        #logo-it {
            zoom: 1.3;
            margin: 0 auto;
        }

        @media only screen and (max-width: 768px) {
            #logo-it {
                zoom: 0.5;
                margin-top: -80px;
                margin-bottom: -50px;
            }

            #incenter_username {
                display: none;
            }

            body {
                zoom: 0.70;
            }
        }

        /* Red Alert Tactical HUD Icons & Tiles */
        .hud-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: rgba(22, 10, 42, 0.75);
            border: 1px solid rgba(138, 79, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 0 10px rgba(138, 79, 255, 0.1);
        }
        .hud-icon-box svg {
            width: 22px;
            height: 22px;
            stroke: #8A4FFF;
            fill: none;
            transition: all 0.3s ease;
        }
        .hud-item:hover .hud-icon-box {
            border-color: #C77DFF;
            background: rgba(138, 79, 255, 0.3);
            box-shadow: 0 0 15px rgba(199, 125, 255, 0.5), inset 0 0 15px rgba(138, 79, 255, 0.3);
            transform: translateY(-2px);
        }
        .hud-item:hover .hud-icon-box svg {
            stroke: #C77DFF;
            filter: drop-shadow(0 0 6px #C77DFF);
        }
        .hud-item:hover .item-captian {
            color: #E0AAFF !important;
            text-shadow: 0 0 8px rgba(199, 125, 255, 0.6);
        }

        /* ── Override legacy class icons & wrapper styles ──────── */
        /* Remove wrong SVG background icons from all section titles  */
        .content .academy .item-title,
        .content .notification .item-title,
        .content .services .item-title,
        .content .site .item-title,
        .content .academy .item-title.active,
        .content .notification .item-title.active,
        .content .services .item-title.active,
        .content .site .item-title.active,
        .content .academy .item-title:hover,
        .content .notification .item-title:hover,
        .content .services .item-title:hover,
        .content .site .item-title:hover {
            background-image: none !important;
            padding-left: 0 !important;
        }

        /* Kill wrapper ghost bg - no double border */
        .content .wrapper,
        .content .wrapper .head { background: transparent !important; }
        .content .wrapper .item,
        .content .wrapper .item:hover {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            height: auto !important;
        }
        /* Remove bullet dot pseudo-element from .head > span */
        .content .wrapper .head > span:first-child::before {
            display: none !important;
        }
        /* Fix .head subtitle style */
        .content .wrapper .head {
            font-size: 10px !important;
            color: rgba(168, 85, 247, 0.7) !important;
            padding: 0 0 6px 0 !important;
            margin-bottom: 8px !important;
        /* ── Minimalist Holographic Top-HUD Header ─────────── */
        header.nav.sci-fi-hud-header {
            background: rgba(13, 6, 26, 0.45) !important;
            backdrop-filter: blur(14px) !important;
            -webkit-backdrop-filter: blur(14px) !important;
            border: none !important;
            border-bottom: 1px solid rgba(168, 85, 247, 0.3) !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
            padding: 8px 16px !important;
            position: relative;
            z-index: 100;
        }

        /* HUD Ticks / Corner Accents */
        header.nav.sci-fi-hud-header::before {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 30px;
            width: 50px;
            height: 2px;
            background: #00f0ff;
            box-shadow: 0 0 10px #00f0ff;
        }
        header.nav.sci-fi-hud-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            right: 30px;
            width: 50px;
            height: 2px;
            background: #a855f7;
            box-shadow: 0 0 10px #a855f7;
        }
    </style>
</head>

<body>

<!-- Preloader -->
<div class="preloader-wrapper">
    <button class="btn btn-outline-info btn-sm skip-intro-now-btn position-absolute" style="top: 25px; right: 25px; z-index: 99999; border-radius: 20px; font-size: 11px; backdrop-filter: blur(10px); color: #00f0ff; border-color: rgba(0, 240, 255, 0.4);">
        Skip Intro &#9889;
    </button>
    <div class="preloader">
        <div class="loading-Recovered"></div>
        <div class="intro text-light"></div>
        <div class="welcome-wrap">
            <div class="welcome hidden">
                <img src="{{ Auth::user()->avatar_url ?? asset('v8main/img/user.jpg') }}" class="position-relative hidden" alt="">
            </div>
        </div>
        <div class="out text-light mt-3" style="margin-left: 14px;"></div>
    </div>
</div>

<!-- Minimalist Holographic Top-HUD Header -->
<header class="nav sci-fi-hud-header">
    <div class="container-fluid px-3">
        <div class="d-flex align-items-center justify-content-between">
            
            <!-- ◀ LEFT: Brand Logo & System Status Pulse -->
            <div class="d-flex align-items-center">
                <div class="logo-parent d-flex align-items-center mr-3 pointer" data-href="{{ url('/dashboard') }}" onclick="window.location.href='{{ url('/dashboard') }}'" style="cursor: pointer;">
                    <img class="logo pointer" src="{{ asset('favicon.svg') }}"
                         alt="Musoftware"
                         style="height: 28px; width: 28px; filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.8));">
                    <span class="brand-name-text ml-2" style="font-size: 17px; font-weight: 800; color: #f3e8ff; letter-spacing: 0.5px; text-shadow: 0 0 14px rgba(168, 85, 247, 0.7); font-family: system-ui, -apple-system, sans-serif;">Musoftware</span>
                </div>
            </div>

            <!-- ✦ CENTER: Live Holographic Telemetry Badges (Wallet & Points) -->
            <div class="d-none d-lg-flex align-items-center justify-content-center gap-3">
                <a href="{{ url('/financial/add-balance') }}" class="d-flex align-items-center px-3 py-1 text-decoration-none rounded-pill" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.35); backdrop-filter: blur(8px); transition: all 0.3s ease;">
                    <i class="icon-wallet mr-2" style="color: #10b981; font-size: 13px;"></i>
                    <span style="font-size: 11px; font-weight: 600; color: #d1fae5; margin-right: 4px;">{{ __('dashboard.wallet') }}:</span>
                    <span style="font-size: 12px; font-weight: 800; color: #10b981; letter-spacing: 0.5px;">{{ $userBalanceFormatted }}</span>
                </a>

                <div class="d-flex align-items-center px-3 py-1 rounded-pill ml-2" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.35); backdrop-filter: blur(8px);">
                    <i class="icon-star mr-2" style="color: #f59e0b; font-size: 13px;"></i>
                    <span style="font-size: 11px; font-weight: 600; color: #fef3c7; margin-right: 4px;">{{ __('dashboard.points') }}:</span>
                    <span style="font-size: 12px; font-weight: 800; color: #f59e0b; letter-spacing: 0.5px;">{{ number_format($userPoints) }} {{ __('dashboard.pts') }}</span>
                </div>
            </div>

            <!-- ▶ RIGHT: Notifications, Lang & User Capsule -->
            <div class="d-flex align-items-center">

                <!-- Notifications Dropdown -->
                <div class="dropdown mr-2">
                    <div class="d-flex align-items-center justify-content-center pointer position-relative dropdown-toggle"
                         id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                         title="Notifications & Messages" 
                         style="width: 34px; height: 34px; background: rgba(35, 16, 70, 0.6); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 50%; transition: all 0.3s ease;">
                        <i class="icon-bell" style="color: #a855f7; font-size: 13px;"></i>
                        <span class="status-dot-led position-absolute" style="top: 3px; right: 3px; width: 6px; height: 6px; background-color: #00f0ff; box-shadow: 0 0 8px #00f0ff;"></span>
                    </div>

                    <!-- Notification Feed Menu -->
                    <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="notificationDropdown"
                         style="background: #130924; border: 1px solid #8A4FFF; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35); width: 290px;">
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-2" style="border-bottom: 1px solid rgba(138,79,255,0.25);">
                            <div class="font-weight-bold" style="color: #f3e8ff; font-size: 13px;">
                                <i class="icon-bell mr-1" style="color: #8A4FFF;"></i> {{ __('dashboard.notifications') }}
                            </div>
                            <a href="{{ url('/notifications') }}" class="small font-weight-bold" style="color: #a855f7;">{{ __('dashboard.view_all') }}</a>
                        </div>
                        <div class="notification-list text-left" style="max-height: 240px; overflow-y: auto;">
                            @forelse($realNotifications as $notif)
                                <a href="{{ $notif['link'] ?? url('/notifications') }}" class="d-block p-2 mb-2 rounded position-relative text-decoration-none" style="background: rgba(138,79,255,0.08); border-left: 3px solid #8A4FFF; transition: all 0.2s ease;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="small font-weight-bold" style="color: #f3e8ff; font-size: 11px;">{{ $notif['title'] }}</div>
                                        <span style="color: #a855f7; font-size: 8px;">{{ $notif['time'] }}</span>
                                    </div>
                                    <div style="color: #d8b4fe; font-size: 10px; line-height: 1.3;" class="mt-1">{{ $notif['desc'] }}</div>
                                </a>
                            @empty
                                <div class="p-3 text-center text-muted" style="font-size: 11px;">
                                    {{ __('dashboard.no_notifications') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Language Switcher Pill -->
                <div class="dropdown mr-2">
                    <div class="d-flex align-items-center justify-content-center pointer dropdown-toggle"
                         id="langDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                         style="width: 34px; height: 34px; background: rgba(35, 16, 70, 0.6); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 50%; font-size: 11px; font-weight: 800; color: #f3e8ff;">
                        {{ strtoupper(app()->getLocale()) }}
                    </div>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="langDropdown"
                         style="background: #130924; border: 1px solid #8A4FFF; border-radius: 10px; min-width: 130px; box-shadow: 0 10px 30px rgba(0,0,0,0.9);">
                        <a class="dropdown-item py-2 {{ app()->getLocale() === 'en' ? 'font-weight-bold' : '' }}"
                           href="{{ url()->current() }}?lang=en"
                           style="color: {{ app()->getLocale() === 'en' ? '#a855f7' : '#d8b4fe' }}; font-size: 12px;">
                            EN &nbsp; {{ __('dashboard.lang_en') }}
                        </a>
                        <a class="dropdown-item py-2 {{ app()->getLocale() === 'ar' ? 'font-weight-bold' : '' }}"
                           href="{{ url()->current() }}?lang=ar"
                           style="color: {{ app()->getLocale() === 'ar' ? '#a855f7' : '#d8b4fe' }}; font-size: 12px;">
                            AR &nbsp; {{ __('dashboard.lang_ar') }}
                        </a>
                    </div>
                </div>

                <!-- User Profile Hologram Capsule -->
                <div class="dropdown">
                    <div class="user-data d-flex align-items-center px-2 py-1 dropdown-toggle pointer"
                         id="dropdownMenuOffset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                         style="cursor: pointer; background: rgba(35, 16, 70, 0.6); border: 1px solid rgba(168, 85, 247, 0.45); border-radius: 20px; backdrop-filter: blur(8px);">
                        <div class="profile-pic d-flex mr-2">
                            <img src="{{ Auth::user()->avatar_url ?? asset('v8main/img/user.jpg') }}" alt="" class="user-img m-auto" style="width: 26px; height: 26px; border-radius: 50%; object-fit: cover; border: 1.5px solid #a855f7;">
                        </div>
                        <div class="d-none d-md-flex flex-column user-text pr-1 text-left font-weight-bold">
                            <div class="username text-capitalize" style="font-size: 11px; color: #f3e8ff; line-height: 1.2;">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="user-level text-uppercase" style="font-size: 8px; color: #ff7c20; letter-spacing: 1px;">{{ Auth::user()->getRoleNames()->first() ? ucfirst(str_replace('_', ' ', Auth::user()->getRoleNames()->first())) : (Auth::user()->role ?? 'Client') }}</div>
                        </div>
                    </div>
                    
                    <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="dropdownMenuOffset"
                         style="background: #130924; border: 1px solid #8A4FFF; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35); min-width: 250px;">
                        <div class="mb-3 p-2 text-left" style="background: #180c30; border: 1px solid rgba(138,79,255,0.3); border-radius: 8px;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small" style="color: #d8b4fe;">{{ __('dashboard.wallet') }}:</span>
                                <span class="font-weight-bold" style="color: #a855f7;">{{ $userBalanceFormatted }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small" style="color: #d8b4fe;">{{ __('dashboard.points') }}:</span>
                                <span class="font-weight-bold" style="color: #ffb703;">{{ number_format($userPoints) }} {{ __('dashboard.pts') }}</span>
                            </div>
                            <button class="btn btn-pay-due btn-block btn-sm mt-1" data-toggle="modal" data-target="#payDueModal">
                                <i class="icon-basket mr-1"></i> {{ __('dashboard.pay_due_amount') }} ({{ $totalDueFormatted }})
                            </button>
                        </div>
                        @if(Auth::user() && (method_exists(Auth::user(), 'isAdmin') ? Auth::user()->isAdmin() : false) || in_array(strtolower(Auth::user()->role ?? ''), ['admin', 'super_admin', 'superadmin']) || Auth::user()->is_admin)
                            <a class="dropdown-item py-2" href="{{ url('/admin/dashboard') }}" style="color: #00f0ff; font-weight: 600;"><i class="icon-user mr-2" style="color: #00f0ff;"></i>{{ __('dashboard.admin_panel') }}</a>
                        @endif
                        <a class="dropdown-item py-2" href="{{ url('/profile') }}"><i class="icon-user mr-2"></i>{{ __('dashboard.my_profile') }}</a>
                        <a class="dropdown-item py-2" href="{{ url('/dashboard/directory') }}"><i class="icon-grid mr-2" style="color: #a855f7;"></i>{{ __('dashboard.all_apps_dir') }}</a>
                        <div class="dropdown-divider" style="border-color: rgba(138,79,255,0.2);"></div>
                        <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display:none;">@csrf</form>
                        <a class="dropdown-item py-2 text-danger font-weight-bold" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('dashboard.logout') }}</a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</header>
<!-- ════════════════════════════════════════════════════════════
     CAR CONSOLE DASHBOARD — 🔮 Theme-Matching Core Hover Glow (No Button Scale)
     Target: 1366×768  |  Zero-Scroll  |  Dynamic Core Reaction
════════════════════════════════════════════════════════════ -->
<style>
    /* ── Seamless Full Viewport Reset (No Black Bar at Bottom) ── */
    html, body {
        overflow: hidden !important;
        height: 100% !important;
        max-height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #0d061a !important;
    }

    .orbital-viewport {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px);
        overflow: hidden;
        position: relative;
        padding: 0 20px;
        box-sizing: border-box;
        background: radial-gradient(circle at 50% 45%, rgba(138, 79, 255, 0.15) 0%, rgba(13, 6, 26, 0.99) 75%) !important;
        transition: background 0.6s ease;
    }

    /* Subtle digital HUD grid overlay */
    .orbital-viewport::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: 
            linear-gradient(rgba(138, 79, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(138, 79, 255, 0.03) 1px, transparent 1px);
        background-size: 45px 45px;
        pointer-events: none;
        z-index: 1;
    }

    /* ── MAGNIFICENT ENLARGED CENTER HOLOGRAM CORE ──────────────── */
    .orbital-center-core {
        position: absolute;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        pointer-events: none;
        width: 480px;
    }

    .orbital-center-core * {
        pointer-events: auto;
    }

    .orbital-center-core #logo-it {
        zoom: 1.15 !important;
        width: 320px !important;
        height: 260px !important;
        filter: drop-shadow(0 6px 18px rgba(0, 0, 0, 0.9)) drop-shadow(0 0 8px rgba(168, 85, 247, 0.2));
        transition: filter 0.5s ease-out, transform 0.5s ease-out;
        z-index: 5;
    }

    /* ── SUPERCAR LCD GAUGE HUD CLUSTER ── */
    .hud-dashboard-gauge {
        position: absolute;
        top: 42%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        width: 360px;
        height: 360px;
        pointer-events: none;
        z-index: 2;
    }

    .hud-ring-outer {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px dashed rgba(0, 240, 255, 0.2);
        box-shadow: 0 0 25px rgba(0, 240, 255, 0.05);
        animation: spin-clockwise 24s linear infinite;
    }

    .hud-ring-inner {
        position: absolute;
        top: 15px;
        left: 15px;
        width: calc(100% - 30px);
        height: calc(100% - 30px);
        border-radius: 50%;
        border: 1.5px solid rgba(168, 85, 247, 0.15);
        border-left-color: rgba(0, 240, 255, 0.65);
        border-right-color: rgba(244, 63, 94, 0.65);
        box-shadow: inset 0 0 20px rgba(168, 85, 247, 0.1);
        animation: spin-counter-clockwise 18s linear infinite;
    }

    .hud-ticks {
        position: absolute;
        top: 30px;
        left: 30px;
        width: calc(100% - 60px);
        height: calc(100% - 60px);
        border-radius: 50%;
        border: 1px dotted rgba(255, 255, 255, 0.15);
        background: radial-gradient(circle, transparent 60%, rgba(13, 6, 26, 0.7) 100%);
    }

    .dashboard-side-gauges {
        display: flex;
        justify-content: space-between;
        width: 440px;
        margin-top: 15px;
        z-index: 12;
        pointer-events: auto;
    }

    .side-gauge-left, .side-gauge-right {
        display: flex;
        flex-direction: column;
        width: 140px;
        background: rgba(13, 6, 26, 0.9);
        border: 1px solid rgba(168, 85, 247, 0.25);
        border-radius: 10px;
        padding: 8px 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
    }

    .side-gauge-left { border-left: 3px solid #10b981; }
    .side-gauge-right { border-left: 3px solid #f43f5e; }

    .side-gauge-left .label, .side-gauge-right .label {
        font-size: 8px;
        color: rgba(255, 255, 255, 0.5);
        letter-spacing: 0.8px;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .gauge-bar-bg {
        height: 5px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 3px;
        margin: 5px 0;
        overflow: hidden;
    }

    .gauge-bar-fill {
        height: 100%;
        border-radius: 3px;
    }

    .gauge-bar-fill.emerald { background: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6); }
    .gauge-bar-fill.red { background: #f43f5e; box-shadow: 0 0 10px rgba(244, 63, 94, 0.6); }

    .side-gauge-left .value, .side-gauge-right .value {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    
    .text-emerald { color: #10b981 !important; }
    .text-red { color: #f43f5e !important; }

    @keyframes spin-clockwise {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes spin-counter-clockwise {
        0% { transform: rotate(360deg); }
        100% { transform: rotate(0deg); }
    }

    /* 🌟 Dynamic Core Glow Reaction when hovering any 3D button */
    .orbital-viewport[data-core-glow="red"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(244, 63, 94, 0.98)) drop-shadow(0 0 40px rgba(244, 63, 94, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }
    .orbital-viewport[data-core-glow="emerald"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(16, 185, 129, 0.98)) drop-shadow(0 0 40px rgba(16, 185, 129, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }
    .orbital-viewport[data-core-glow="cyan"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(0, 240, 255, 0.98)) drop-shadow(0 0 40px rgba(0, 240, 255, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }
    .orbital-viewport[data-core-glow="pink"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(236, 72, 153, 0.98)) drop-shadow(0 0 40px rgba(236, 72, 153, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }
    .orbital-viewport[data-core-glow="amber"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(245, 158, 11, 0.98)) drop-shadow(0 0 40px rgba(245, 158, 11, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }
    .orbital-viewport[data-core-glow="gold"] .orbital-center-core #logo-it {
        filter: drop-shadow(0 0 95px rgba(251, 191, 36, 0.98)) drop-shadow(0 0 40px rgba(251, 191, 36, 0.8)) drop-shadow(0 0 15px #fff) !important;
        transform: scale(1.05);
    }

    /* User identity below enlarged hologram - Clean Capsule & Zero Overlap */
    .orbital-identity {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 45px !important;
        padding: 5px 22px;
        background: rgba(13, 6, 26, 0.85);
        border: 1.5px solid rgba(168, 85, 247, 0.45);
        border-radius: 20px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.6), 0 0 20px rgba(168, 85, 247, 0.3);
        z-index: 12;
        gap: 2px;
        position: relative;
    }
    .orbital-identity .c-name {
        font-size: 20px;
        font-weight: 800;
        color: #f3e8ff;
        text-transform: uppercase;
        letter-spacing: 3.5px;
        text-shadow: 0 0 20px rgba(168, 85, 247, 0.8), 0 0 8px rgba(255, 255, 255, 0.6);
        transition: text-shadow 0.5s ease;
    }
    .orbital-identity .c-role {
        font-size: 10px;
        font-weight: 700;
        color: #ff7c20;
        text-transform: uppercase;
        letter-spacing: 2.5px;
        text-shadow: 0 0 10px rgba(255, 124, 32, 0.6);
    }

    /* ── Two-Column Dashboard Layout (General Left, Services Right) ──────── */
    .orbital-arc-container {
        position: relative;
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-sizing: border-box;
        padding: 0 20px;
        z-index: 5;
        gap: 60px;
    }

    .orbital-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        max-width: 480px;
        gap: 12px !important;
    }

    .orbital-col.orbital-left {
        display: grid !important;
        grid-template-columns: repeat(2, 85px) !important;
        gap: 25px 45px !important;
        max-width: 240px !important;
        justify-content: center !important;
        justify-items: center !important;
    }

    .orbital-col.orbital-right {
        display: grid !important;
        grid-template-columns: repeat(2, 85px) !important;
        gap: 25px 45px !important;
        max-width: 240px !important;
        justify-content: center !important;
        justify-items: center !important;
    }

    .orbital-col-header {
        grid-column: span 2;
        font-size: 10px;
        font-weight: 700;
        color: rgba(168, 85, 247, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 12px;
        text-align: center;
        width: 100%;
    }

    /* Orbital App Node Item - SCALE UP REMOVED AS REQUESTED */
    .orbital-node {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        cursor: pointer;
        background: transparent;
        border: none;
        padding: 0;
        position: relative;
        z-index: 6;
        transform: none !important;
    }

    /* ── 85px 3D CUBE SCENE ───────────────────────────────────────── */
    .cube-3d-scene {
        width: 85px;
        height: 85px;
        perspective: 900px;
        flex-shrink: 0;
        position: relative;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .orbital-node:hover .cube-3d-scene {
        transform: translateY(-5px);
    }

    /* Ambient Glowing Aura Ring behind each Cube */
    .cube-3d-scene::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 85px;
        height: 85px;
        transform: translate(-50%, -50%) scale(0.85);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.45) 0%, rgba(138, 79, 255, 0) 70%);
        opacity: 0;
        transition: transform 0.55s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.55s ease;
        pointer-events: none;
        z-index: -1;
    }

    .orbital-node:hover .cube-3d-scene::before {
        transform: translate(-50%, -50%) scale(1.45);
        opacity: 0.9;
    }

    .orbital-left .cube-3d, .orbital-right .cube-3d {
        width: 100%;
        height: 100%;
        position: relative;
        transform: none !important;
        transform-style: flat !important;
        animation: none !important;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Staggered Floating Delays */
    .orbital-node[data-node="1"] .cube-3d { animation-delay: 0s; }
    .orbital-node[data-node="2"] .cube-3d { animation-delay: 0.7s; }
    .orbital-node[data-node="3"] .cube-3d { animation-delay: 1.4s; }
    .orbital-node[data-node="4"] .cube-3d { animation-delay: 2.1s; }
    .orbital-node[data-node="5"] .cube-3d { animation-delay: 0.35s; }
    .orbital-node[data-node="6"] .cube-3d { animation-delay: 1.05s; }
    .orbital-node[data-node="7"] .cube-3d { animation-delay: 1.75s; }
    .orbital-node[data-node="8"] .cube-3d { animation-delay: 2.45s; }
    .orbital-node[data-node="9"] .cube-3d { animation-delay: 0.5s; }
    .orbital-node[data-node="10"] .cube-3d { animation-delay: 1.2s; }
    .orbital-node[data-node="11"] .cube-3d { animation-delay: 1.9s; }
    .orbital-node[data-node="12"] .cube-3d { animation-delay: 2.6s; }
    .orbital-node[data-node="13"] .cube-3d { animation-delay: 3.0s; }

    /* Pause animation on active hover */
    .orbital-node.is-hovered .cube-3d {
        animation-play-state: paused !important;
    }

    /* 6 Faces of the 85px 3D Cube */
    .cube-face {
        position: absolute;
        width: 85px;
        height: 85px;
        box-sizing: border-box;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(8px);
        transition: border-color 0.45s ease, box-shadow 0.45s ease, background 0.45s ease;
    }

    .cube-face:not(.cube-face-front) {
        display: none !important;
    }

    /* FRONT FACE: SVG Icon + Text Label INSIDE */
    .cube-face-front {
        transform: none !important;
        position: absolute !important;
        width: 100% !important;
        height: 100% !important;
        top: 0 !important;
        left: 0 !important;
        background: linear-gradient(135deg, rgba(35, 16, 70, 0.96), rgba(16, 7, 34, 0.98)) !important;
        border: 1.5px solid rgba(168, 85, 247, 0.55) !important;
        box-shadow: inset 0 0 16px rgba(168, 85, 247, 0.25) !important;
        padding: 8px 6px !important;
        text-align: center !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 12px !important;
    }
    .cube-face-front svg {
        width: 32px;
        height: 32px;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        filter: drop-shadow(0 0 8px rgba(168, 85, 247, 0.6));
        transition: filter 0.45s ease, transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .orbital-node:hover .cube-face-front svg {
        transform: scale(1.08);
    }
    .cube-face-front .cube-inner-label {
        margin-top: 4px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3px;
        color: #f3e8ff;
        text-transform: uppercase;
        line-height: 1.1;
        white-space: normal;
        word-break: keep-all;
        overflow-wrap: normal;
        hyphens: none;
        transition: color 0.45s ease, text-shadow 0.45s ease;
    }
    .orbital-node:hover .cube-face-front .cube-inner-label {
        color: #fff;
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
    }

    /* BACK FACE */
    .cube-face-back {
        transform: rotateY(180deg) translateZ(42.5px);
        background: rgba(14, 6, 30, 0.95);
        border: 1px solid rgba(138, 79, 255, 0.3);
    }

    /* RIGHT FACE (Shaded Depth for Left Flank) */
    .orbital-left .cube-face-right {
        transform: rotateY(90deg) translateZ(42.5px);
        background: linear-gradient(135deg, rgba(22, 10, 44, 0.95), rgba(10, 4, 20, 0.98));
        border: 1px solid rgba(138, 79, 255, 0.35);
        filter: brightness(0.72);
    }

    .orbital-left .cube-face-left {
        transform: rotateY(-90deg) translateZ(42.5px);
        background: rgba(22, 10, 44, 0.95);
        border: 1px solid rgba(138, 79, 255, 0.35);
    }

    /* LEFT FACE (Shaded Depth for Right Flank - Inward Symmetry) */
    .orbital-right .cube-face-left {
        transform: rotateY(-90deg) translateZ(42.5px);
        background: linear-gradient(135deg, rgba(22, 10, 44, 0.95), rgba(10, 4, 20, 0.98));
        border: 1px solid rgba(138, 79, 255, 0.35);
        filter: brightness(0.72);
    }

    .orbital-right .cube-face-right {
        transform: rotateY(90deg) translateZ(42.5px);
        background: rgba(22, 10, 44, 0.95);
        border: 1px solid rgba(138, 79, 255, 0.35);
    }

    /* TOP FACE (Glossy Specular Light) */
    .cube-face-top {
        transform: rotateX(90deg) translateZ(42.5px);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.28), rgba(168, 85, 247, 0.18));
        border: 1px solid rgba(255, 255, 255, 0.45);
    }

    /* BOTTOM FACE (Seamless Glass Shading - Harsh Black Strip Removed) */
    .cube-face-bottom {
        transform: rotateX(-90deg) translateZ(42.5px);
        background: linear-gradient(180deg, rgba(22, 10, 44, 0.9), rgba(10, 4, 20, 0.95));
        border: 1px solid rgba(138, 79, 255, 0.3);
    }

    /* 🎨 3D CUBE THEME COLOR ACCENTS WITH ULTRA SMOOTH GLOW */
    /* 🔴 Red Due Cube */
    .cube-theme-red .cube-3d-scene::before { background: radial-gradient(circle, rgba(244, 63, 94, 0.5) 0%, rgba(244, 63, 94, 0) 70%); }
    .cube-theme-red .cube-face-front { background: linear-gradient(135deg, rgba(244, 63, 94, 0.38), rgba(30, 10, 20, 0.98)); border-color: #f43f5e; box-shadow: 0 0 20px rgba(244, 63, 94, 0.45), inset 0 0 14px rgba(244, 63, 94, 0.35); }
    .cube-theme-red .cube-face-front svg { stroke: #f43f5e; filter: drop-shadow(0 0 10px #f43f5e); }
    .cube-theme-red .cube-face-top { background: linear-gradient(135deg, rgba(244, 63, 94, 0.65), rgba(255, 255, 255, 0.35)); }
    .cube-theme-red .cube-face-bottom { border-color: rgba(244, 63, 94, 0.4); }
    .orbital-node:hover .cube-theme-red .cube-face-front { box-shadow: 0 0 40px rgba(244, 63, 94, 0.95); }

    /* 🟢 Emerald Add Balance Cube */
    .cube-theme-emerald .cube-3d-scene::before { background: radial-gradient(circle, rgba(16, 185, 129, 0.5) 0%, rgba(16, 185, 129, 0) 70%); }
    .cube-theme-emerald .cube-face-front { background: linear-gradient(135deg, rgba(16, 185, 129, 0.38), rgba(10, 30, 20, 0.98)); border-color: #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.45); }
    .cube-theme-emerald .cube-face-front svg { stroke: #10b981; filter: drop-shadow(0 0 10px #10b981); }
    .cube-theme-emerald .cube-face-top { background: linear-gradient(135deg, rgba(16, 185, 129, 0.65), rgba(255, 255, 255, 0.35)); }
    .cube-theme-emerald .cube-face-bottom { border-color: rgba(16, 185, 129, 0.4); }
    .orbital-node:hover .cube-theme-emerald .cube-face-front { box-shadow: 0 0 40px rgba(16, 185, 129, 0.95); }

    /* 🩵 Cyan ERP Cube */
    .cube-theme-cyan .cube-3d-scene::before { background: radial-gradient(circle, rgba(0, 240, 255, 0.5) 0%, rgba(0, 240, 255, 0) 70%); }
    .cube-theme-cyan .cube-face-front { background: linear-gradient(135deg, rgba(0, 240, 255, 0.38), rgba(10, 25, 40, 0.98)); border-color: #00f0ff; box-shadow: 0 0 20px rgba(0, 240, 255, 0.45); }
    .cube-theme-cyan .cube-face-front svg { stroke: #00f0ff; filter: drop-shadow(0 0 10px #00f0ff); }
    .cube-theme-cyan .cube-face-top { background: linear-gradient(135deg, rgba(0, 240, 255, 0.65), rgba(255, 255, 255, 0.35)); }
    .cube-theme-cyan .cube-face-bottom { border-color: rgba(0, 240, 255, 0.4); }
    .orbital-node:hover .cube-theme-cyan .cube-face-front { box-shadow: 0 0 40px rgba(0, 240, 255, 0.95); }

    /* 🩷 Pink CRM / Projects Cube */
    .cube-theme-pink .cube-3d-scene::before { background: radial-gradient(circle, rgba(236, 72, 153, 0.5) 0%, rgba(236, 72, 153, 0) 70%); }
    .cube-theme-pink .cube-face-front { background: linear-gradient(135deg, rgba(236, 72, 153, 0.38), rgba(35, 10, 30, 0.98)); border-color: #ec4899; box-shadow: 0 0 20px rgba(236, 72, 153, 0.45); }
    .cube-theme-pink .cube-face-front svg { stroke: #ec4899; filter: drop-shadow(0 0 10px #ec4899); }
    .cube-theme-pink .cube-face-top { background: linear-gradient(135deg, rgba(236, 72, 153, 0.65), rgba(255, 255, 255, 0.35)); }
    .cube-theme-pink .cube-face-bottom { border-color: rgba(236, 72, 153, 0.4); }
    .orbital-node:hover .cube-theme-pink .cube-face-front { box-shadow: 0 0 40px rgba(236, 72, 153, 0.95); }

    /* 🟧 Amber Marketplace / SMS Cube */
    .cube-theme-amber .cube-3d-scene::before { background: radial-gradient(circle, rgba(245, 158, 11, 0.5) 0%, rgba(245, 158, 11, 0) 70%); }
    .cube-theme-amber .cube-face-front { background: linear-gradient(135deg, rgba(245, 158, 11, 0.38), rgba(35, 20, 10, 0.98)); border-color: #f59e0b; box-shadow: 0 0 20px rgba(245, 158, 11, 0.45); }
    .cube-theme-amber .cube-face-front svg { stroke: #f59e0b; filter: drop-shadow(0 0 10px #f59e0b); }
    .cube-theme-amber .cube-face-top { background: linear-gradient(135deg, rgba(245, 158, 11, 0.65), rgba(255, 255, 255, 0.35)); }
    .cube-theme-amber .cube-face-bottom { border-color: rgba(245, 158, 11, 0.4); }
    .orbital-node:hover .cube-theme-amber .cube-face-front { box-shadow: 0 0 40px rgba(245, 158, 11, 0.95); }

    /* 🪙 Gold Saver Cube */
    .cube-theme-gold .cube-3d-scene::before { background: radial-gradient(circle, rgba(251, 191, 36, 0.5) 0%, rgba(251, 191, 36, 0) 70%); }
    .cube-theme-gold .cube-face-front { background: linear-gradient(135deg, rgba(251, 191, 36, 0.45), rgba(40, 30, 10, 0.98)); border-color: #fbbf24; box-shadow: 0 0 20px rgba(251, 191, 36, 0.45); }
    .cube-theme-gold .cube-face-front svg { stroke: #fbbf24; filter: drop-shadow(0 0 10px #fbbf24); }
    .cube-theme-gold .cube-face-top { background: linear-gradient(135deg, rgba(251, 191, 36, 0.75), rgba(255, 255, 255, 0.45)); }
    .cube-theme-gold .cube-face-bottom { border-color: rgba(251, 191, 36, 0.4); }
    .orbital-node:hover .cube-theme-gold .cube-face-front { box-shadow: 0 0 40px rgba(251, 191, 36, 0.95); }

    /* Notification / Due Badge on Corner of Cube */
    .c-box-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        font-size: 8px;
        font-weight: 700;
        color: #fff;
        background: #f43f5e;
        padding: 1px 6px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(244, 63, 94, 0.9);
        z-index: 10;
    }

    /* ── MOBILE: Two-column dashboard with stacked cubes ── */
    @media only screen and (max-width: 768px) {
        .orbital-viewport {
            height: auto !important;
            padding: 16px 4px 8px 4px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            position: relative !important;
            overflow: visible !important;
            min-height: 100vh !important;
        }

        .orbital-center-core {
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            transform: none !important;
            z-index: 10 !important;
            width: 100% !important;
            pointer-events: none !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: 16px !important;
        }

        .orbital-center-core #logo-it {
            zoom: 0.95 !important;
            width: 230px !important;
            height: 180px !important;
        }

        .orbital-identity {
            margin-top: 32px !important;
            padding: 3px 16px !important;
        }

        .orbital-identity .c-name {
            font-size: 14px !important;
            letter-spacing: 2px !important;
        }

        .orbital-identity .c-role {
            font-size: 8px !important;
            letter-spacing: 1.5px !important;
        }

        .orbital-arc-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            height: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 4px !important;
            gap: 12px !important;
            z-index: 15 !important;
            position: relative !important;
            box-sizing: border-box !important;
        }

        .orbital-col {
            width: 100% !important;
            height: auto !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .orbital-col-header {
            display: none !important;
        }

        .orbital-col.orbital-left {
            display: flex !important;
            grid-template-columns: none !important;
            max-width: 100% !important;
            align-items: center !important;
            margin: 0 !important;
        }

        .orbital-col.orbital-right {
            display: flex !important;
            grid-template-columns: none !important;
            max-width: 100% !important;
            align-items: center !important;
            margin: 0 !important;
        }

        .cube-3d-scene {
            width: 68px !important;
            height: 68px !important;
            perspective: 500px !important;
        }

        .cube-3d-scene::before {
            width: 68px !important;
            height: 68px !important;
        }

        .cube-face {
            width: 68px !important;
            height: 68px !important;
            border-radius: 10px !important;
        }

        .cube-face-front { transform: translateZ(34px) !important; padding: 4px !important; }
        .cube-face-back { transform: rotateY(180deg) translateZ(34px) !important; }
        .orbital-left .cube-face-right, .orbital-right .cube-face-right { transform: rotateY(90deg) translateZ(34px) !important; }
        .orbital-left .cube-face-left, .orbital-right .cube-face-left { transform: rotateY(-90deg) translateZ(34px) !important; }
        .cube-face-top { transform: rotateX(90deg) translateZ(34px) !important; }
        .cube-face-bottom { transform: rotateX(-90deg) translateZ(34px) !important; }

        .cube-face-front svg {
            width: 24px !important;
            height: 24px !important;
        }

        .cube-face-front .cube-inner-label {
            font-size: 8px !important;
            margin-top: 2px !important;
            line-height: 1.05 !important;
            letter-spacing: 0.1px !important;
        }

        .c-box-badge {
            font-size: 7px !important;
            padding: 1px 4px !important;
            top: -4px !important;
            right: -4px !important;
        }
    }

    @media only screen and (max-width: 380px) {
        .cube-3d-scene {
            width: 60px !important;
            height: 60px !important;
        }
        .cube-3d-scene::before {
            width: 60px !important;
            height: 60px !important;
        }
        .cube-face {
            width: 60px !important;
            height: 60px !important;
        }
        .cube-face-front { transform: translateZ(30px) !important; }
        .cube-face-back { transform: rotateY(180deg) translateZ(30px) !important; }
        .orbital-left .cube-face-right, .orbital-right .cube-face-right { transform: rotateY(90deg) translateZ(30px) !important; }
        .orbital-left .cube-face-left, .orbital-right .cube-face-left { transform: rotateY(-90deg) translateZ(30px) !important; }
        .cube-face-top { transform: rotateX(90deg) translateZ(30px) !important; }
        .cube-face-bottom { transform: rotateX(-90deg) translateZ(30px) !important; }

        .cube-face-front svg {
            width: 20px !important;
            height: 20px !important;
        }
        .cube-face-front .cube-inner-label {
            font-size: 7px !important;
        }
        .orbital-center-core #logo-it {
            zoom: 0.65 !important;
            width: 180px !important;
            height: 150px !important;
        }
    }

    @media only screen and (max-height: 520px) {
        .orbital-viewport {
            overflow-y: auto !important;
        }
        .orbital-center-core #logo-it {
            zoom: 0.55 !important;
            width: 150px !important;
            height: 120px !important;
        }
        .cube-3d-scene {
            width: 55px !important;
            height: 55px !important;
        }
        .cube-3d-scene::before {
            width: 55px !important;
            height: 55px !important;
        }
        .cube-face {
            width: 55px !important;
            height: 55px !important;
        }
        .cube-face-front { transform: translateZ(27.5px) !important; }
        .cube-face-back { transform: rotateY(180deg) translateZ(27.5px) !important; }
        .orbital-left .cube-face-right, .orbital-right .cube-face-right { transform: rotateY(90deg) translateZ(27.5px) !important; }
        .orbital-left .cube-face-left, .orbital-right .cube-face-left { transform: rotateY(-90deg) translateZ(27.5px) !important; }
        .cube-face-top { transform: rotateX(90deg) translateZ(27.5px) !important; }
        .cube-face-bottom { transform: rotateX(-90deg) translateZ(27.5px) !important; }
    }
</style>

<div class="orbital-viewport" id="orbitalViewport">

    <!-- ══ MAGNIFICENT ENLARGED 3D HOLOGRAM CENTER CORE ══ -->
    <div class="orbital-center-core" id="orbitalCenter">
        <!-- Dashboard Gauge HUD Background -->
        <div class="hud-dashboard-gauge">
            <div class="hud-ring-outer"></div>
            <div class="hud-ring-inner"></div>
            <div class="hud-ticks"></div>
        </div>

        <div id="logo-it"></div>

        <div class="orbital-identity">
            <div class="c-name">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="c-role">{{ Auth::user()->getRoleNames()->first() ? ucfirst(str_replace('_', ' ', Auth::user()->getRoleNames()->first())) : (Auth::user()->role ?? 'Client') }}</div>
        </div>

        <!-- Curved Digital Gauges (Speedometer Style) -->
        <div class="dashboard-side-gauges">
            <div class="side-gauge-left">
                <span class="label">WALLET LEVEL</span>
                <div class="gauge-bar-bg">
                    <div class="gauge-bar-fill emerald" data-target-width="85%" style="width: 0%;"></div>
                </div>
                <span class="value text-emerald">{{ $userBalanceFormatted }}</span>
            </div>
            <div class="side-gauge-right">
                <span class="label">DUES STATUS</span>
                <div class="gauge-bar-bg">
                    <div class="gauge-bar-fill red" data-target-width="{{ $unpaidAmount > 0 ? '75%' : '0%' }}" style="width: 0%;"></div>
                </div>
                <span class="value text-red">{{ $totalDueFormatted }}</span>
            </div>
        </div>
    </div>

    <!-- ══ TWO-COLUMN DASHBOARD: GENERAL (LEFT) + SERVICES (RIGHT) ══ -->
    <div class="orbital-arc-container">

        <!-- ◀ LEFT COLUMN: GENERAL LINKS -->
        <div class="orbital-col orbital-left">

            <div class="orbital-col-header">{{ __('dashboard.general_links') }}</div>

            <!-- Cube 1: Wallet Balance -->
            <a href="{{ url('/financial/transactions') }}" class="orbital-node" data-node="1">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="wallet"></i>
                            <span class="cube-inner-label">{{ __('dashboard.wallet') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 2: Add Balance -->
            <a href="{{ url('/financial/add-balance') }}" class="orbital-node" data-node="2">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="plus-circle"></i>
                            <span class="cube-inner-label">{{ __('dashboard.add_balance') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 3: Pay Due Amount -->
            <button class="orbital-node" data-node="3" data-toggle="modal" data-target="#payDueModal">
                <div class="cube-3d-scene cube-theme-red">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="banknote"></i>
                            <span class="cube-inner-label">{{ __('dashboard.pay_due_amount') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                    @if($unpaidAmount > 0)
                        <span class="c-box-badge">{{ $totalDueFormatted }}</span>
                    @endif
                </div>
            </button>

            <!-- Cube 4: My Projects -->
            <a href="{{ url('/projects') }}" class="orbital-node" data-node="4">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="folder"></i>
                            <span class="cube-inner-label">{{ __('dashboard.my_projects_btn') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 14: Invoices -->
            <a href="{{ url('/billing/invoices') }}" class="orbital-node" data-node="14">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="receipt"></i>
                            <span class="cube-inner-label">{{ __('dashboard.invoices') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 15: Transactions -->
            <a href="{{ url('/financial/transactions') }}" class="orbital-node" data-node="15">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="history"></i>
                            <span class="cube-inner-label">{{ __('dashboard.transactions') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 16: Support -->
            <a href="{{ url('/tickets') }}" class="orbital-node" data-node="16">
                <div class="cube-3d-scene cube-theme-amber">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="headset"></i>
                            <span class="cube-inner-label">{{ __('dashboard.support') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 17: Referral -->
            <a href="{{ url('/referrals') }}" class="orbital-node" data-node="17">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="users"></i>
                            <span class="cube-inner-label">{{ __('dashboard.referrals') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 18: Payout Methods -->
            <a href="{{ url('/financial/payout-methods') }}" class="orbital-node" data-node="18">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="landmark"></i>
                            <span class="cube-inner-label">{{ __('dashboard.payout_methods') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 20: Profile -->
            <a href="{{ url('/profile') }}" class="orbital-node" data-node="20">
                <div class="cube-3d-scene cube-theme-gold">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="user"></i>
                            <span class="cube-inner-label">{{ __('dashboard.profile') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

        </div>

        <!-- ▶ RIGHT COLUMN: SERVICES -->
        <div class="orbital-col orbital-right">

            <div class="orbital-col-header">{{ __('dashboard.services') }}</div>

            <!-- Cube 5: Contracts -->
            <a href="{{ url('/isaas/contracts') }}" class="orbital-node" data-node="5">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="file-signature"></i>
                            <span class="cube-inner-label">{{ __('dashboard.contracts') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 6: ERP -->
            <a href="{{ url('/sso/erp') }}" class="orbital-node" data-node="6">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="database"></i>
                            <span class="cube-inner-label">{{ __('dashboard.erp_system') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 7: CRM -->
            <a href="{{ url('/sso/crm') }}" class="orbital-node" data-node="7">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="contact"></i>
                            <span class="cube-inner-label">{{ __('dashboard.crm_system') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 8: Gold Saver System -->
            <a href="{{ url('/sso/goldsaversys') }}" class="orbital-node" data-node="8">
                <div class="cube-3d-scene cube-theme-gold">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="coins"></i>
                            <span class="cube-inner-label">{{ __('dashboard.gold_saver_sys') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 9: SMS Payment Gateway -->
            <a href="{{ url('/sms-payment-gateway') }}" class="orbital-node" data-node="9">
                <div class="cube-3d-scene cube-theme-amber">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="smartphone"></i>
                            <span class="cube-inner-label">{{ __('dashboard.sms_gateway') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 10: Runtime Agent Tools -->
            <a href="{{ url('/sso/toolsys') }}" class="orbital-node" data-node="10">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="terminal"></i>
                            <span class="cube-inner-label">{{ __('dashboard.runtime_tools') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 11: WhatsApp Sender -->
            <a href="{{ url('/whatsapp-sender') }}" class="orbital-node" data-node="11">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="message-square"></i>
                            <span class="cube-inner-label">{{ __('dashboard.whatsapp') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 12: FB Lookup -->
            <a href="{{ url('/fbmb') }}" class="orbital-node" data-node="12">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-custom-fb">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                            <span class="cube-inner-label">{{ __('dashboard.fb_lookup') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 13: Marketplace / Store -->
            <a href="{{ url('/marketplace/services') }}" class="orbital-node" data-node="13">
                <div class="cube-3d-scene cube-theme-amber">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="store"></i>
                            <span class="cube-inner-label">{{ __('dashboard.browse_marketplace') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 19: Subscriptions -->
            <a href="{{ url('/subscriptions/manage') }}" class="orbital-node" data-node="19">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="shield-check"></i>
                            <span class="cube-inner-label">{{ __('dashboard.subscriptions') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

            <!-- Cube 21: Cost Estimator -->
            <a href="{{ url('/estimator') }}" class="orbital-node" data-node="21">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <i data-lucide="calculator"></i>
                            <span class="cube-inner-label">{{ __('dashboard.cost_estimator') }}</span>
                        </div>
                        <div class="cube-face cube-face-back"></div>
                        <div class="cube-face cube-face-right"></div>
                        <div class="cube-face cube-face-left"></div>
                        <div class="cube-face cube-face-top"></div>
                        <div class="cube-face cube-face-bottom"></div>
                    </div>
                </div>
            </a>

        </div>

    </div>

</div>

<!-- Dynamic Theme-Matching Core Glow Reaction + 3D Cursor Look-At JavaScript -->
<script>
    var viewportEl = document.getElementById('orbitalViewport');

    document.querySelectorAll('.orbital-node').forEach(function(node) {
        var cube = node.querySelector('.cube-3d');
        var themeScene = node.querySelector('.cube-3d-scene');
        if (!cube) return;

        // Extract theme color name (e.g. red, emerald, cyan, pink, amber, gold)
        var themeMatch = themeScene ? themeScene.className.match(/cube-theme-(\w+)/) : null;
        var themeName = themeMatch ? themeMatch[1] : 'purple';

        node.addEventListener('mouseenter', function() {
            node.classList.add('is-hovered');
            if (viewportEl) {
                viewportEl.setAttribute('data-core-glow', themeName);
            }
        });

        node.addEventListener('mousemove', function(e) {
            var rect = node.getBoundingClientRect();
            var cx = rect.left + rect.width / 2;
            var cy = rect.top + rect.height / 2;

            // Normalized delta offsets (-1.0 to +1.0)
            var dx = (e.clientX - cx) / (rect.width / 2);
            var dy = (e.clientY - cy) / (rect.height / 2);

            // Subtle 2D parallax offset translation
            cube.style.transform = 'translate(' + (dx * 4).toFixed(1) + 'px, ' + (dy * 4).toFixed(1) + 'px)';
        });

        node.addEventListener('mouseleave', function() {
            node.classList.remove('is-hovered');
            cube.style.transform = ''; // Smoothly resume CSS 3D floating animation
            if (viewportEl) {
                viewportEl.removeAttribute('data-core-glow');
            }
        });
    });
</script>

<!-- Fancy Sci-Fi Glass Payment Modal -->
<div class="modal fade modal-scifi-glass" id="payDueModal" tabindex="-1" role="dialog" aria-labelledby="payDueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="background: rgba(14, 9, 32, 0.96); backdrop-filter: blur(25px); border: 1.5px solid #00f0ff; border-radius: 20px;">
            <div class="modal-header d-flex align-items-center justify-content-between p-3" style="border-bottom: 1px solid rgba(0, 240, 255, 0.2);">
                <h4 class="modal-title font-weight-bold text-cyan" id="payDueModalLabel">
                    <i class="icon-basket mr-2"></i>{{ __('dashboard.modal_title') }}
                </h4>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 28px; color: #00f0ff;">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 text-left">
                <div class="p-3 mb-4 rounded d-flex align-items-center justify-content-between" style="background: rgba(244, 63, 94, 0.12); border: 1px solid #f43f5e; border-radius: 14px;">
                    <div>
                        <span class="text-uppercase text-muted d-block" style="font-size: 11px;">{{ __('dashboard.total_due') }}</span>
                        <h2 class="m-0 font-weight-bold" style="color: #f43f5e;">{{ $totalDueFormatted }}</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-uppercase text-muted d-block" style="font-size: 11px;">{{ __('dashboard.wallet_balance') }}</span>
                        <h4 class="m-0 font-weight-bold text-cyan">{{ $userBalanceFormatted }}</h4>
                    </div>
                </div>

                @if($userBalanceVal >= $totalDueAmount && $totalDueAmount > 0)
                    <div class="alert alert-success d-flex align-items-center mb-4" style="background: rgba(16, 185, 129, 0.15); border-color: #10b981; color: #10b981; border-radius: 12px;">
                        <i class="icon-check mr-2" style="font-size: 20px;"></i>
                        <span>{{ __('dashboard.balance_covers') }}</span>
                    </div>
                @elseif($totalDueAmount > 0)
                    <div class="alert alert-warning d-flex align-items-center mb-4" style="background: rgba(245, 158, 11, 0.15); border-color: #f59e0b; color: #f59e0b; border-radius: 12px;">
                        <i class="icon-attention mr-2" style="font-size: 20px;"></i>
                        <span>{{ __('dashboard.balance_short') }}</span>
                    </div>
                @endif

                <h5 class="font-weight-bold text-light mb-3">{{ __('dashboard.outstanding_inv') }}</h5>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover table-dark mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-cyan">
                                <th>{{ __('dashboard.invoice_num') }}</th>
                                <th>{{ __('dashboard.description') }}</th>
                                <th>{{ __('dashboard.status') }}</th>
                                <th>{{ __('dashboard.amount_due') }}</th>
                                <th>{{ __('dashboard.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unpaidInvoices as $invoice)
                                <tr>
                                    <td>#{{ $invoice->id }}</td>
                                    <td>{{ $invoice->title ?? $invoice->description ?? __('dashboard.service_invoice') }}</td>
                                    <td><span class="badge badge-warning">{{ __('dashboard.unpaid_badge') }}</span></td>
                                    <td class="font-weight-bold text-danger">{{ number_format($invoice->unpaid, 2) }} {{ $currencySymbol }}</td>
                                    <td>
                                        <a href="{{ url('/billing/invoices/' . $invoice->id) }}" class="btn btn-outline-info btn-sm" style="border-radius: 8px;">{{ __('dashboard.pay_invoice') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="icon-check d-block mb-2" style="font-size: 30px; color: #10b981;"></i>
                                        {{ __('dashboard.no_invoices') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex align-items-center justify-content-between p-3" style="border-top: 1px solid rgba(0, 240, 255, 0.2);">
                <a href="{{ url('/financial/add-balance') }}" class="btn btn-outline-light btn-sm" style="border-radius: 10px;">
                    <i class="icon-plus mr-1"></i>{{ __('dashboard.add_balance') }}
                </a>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal" style="border-radius: 10px;">{{ __('dashboard.cancel') }}</button>
                    <a href="{{ url('/billing/invoices') }}" class="btn btn-primary btn-sm px-4" style="border-radius: 10px; background: linear-gradient(135deg, #00f0ff, #d946ef); border: none; font-weight: bold;">
                        {{ __('dashboard.view_all_invoices') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/SVGLoader.js"></script>
<script src="{{ asset('v8main/js/jquery-3.4.1.min.js') }}"></script>
<script src="{{ asset('v8main/js/popper.min.js') }}"></script>
<script src="{{ asset('v8main/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('v8main/js/typed.js') }}"></script>
<script src="{{ asset('v8main/js/plugins.js') }}"></script>

<script>
    $('a[href]').each(function (i, l) {
        var h = l.getAttribute('href');
        if (h && h !== '#') {
            l.setAttribute('data-href', h);
        }
    });

    $(document).on('click', '*[data-href]', function (e) {
        var href = $(this).data('href');
        if (href && href !== '#') {
            location.assign(href);
        }
    });

    $('#notificationSearchInput').on('input', function () {
        var query = $(this).val().toLowerCase().trim();
        $('.notification-feed-item').each(function () {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    /* ==========================================================================
       100% AUTOMATIC FLUID VIEWPORT SCALER FOR LARGE & ULTRA-WIDE SCREENS
       ========================================================================== */
    (function () {
        function applySmartViewportScaling() {
            var w = window.innerWidth;
            var h = window.innerHeight;

            if (w <= 768) {
                document.body.style.zoom = '0.70';
                return;
            }

            if (w <= 1365) {
                document.body.style.zoom = '0.80';
                return;
            }

            var scaleX = w / 1366;
            var scaleY = h / 850;
            var computedScale = Math.min(scaleX, scaleY) * 0.90;

            var finalZoom = Math.min(1.45, Math.max(0.90, computedScale)).toFixed(3);
            document.body.style.zoom = finalZoom;
        }

        var resizeTimer;
        $(window).on('resize orientationchange load', function () {
            cancelAnimationFrame(resizeTimer);
            resizeTimer = requestAnimationFrame(applySmartViewportScaling);
        });
    })();

    /* ==========================================================================
       THREE.JS WEBGL 3D CYBERPUNK ARC REACTOR CORE (VIOLET HUD EDITION)
       ========================================================================== */
    (function () {
        var container = document.getElementById('logo-it');
        if (!container || typeof THREE === 'undefined') return;

        // Clean any pre-existing canvas
        var oldCanvas = document.getElementById('three-hologram-canvas');
        if (oldCanvas) oldCanvas.remove();

        var canvas = document.createElement('canvas');
        canvas.id = 'three-hologram-canvas';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '5';
        container.style.position = 'relative';
        container.appendChild(canvas);

        var width = container.clientWidth || 286;
        var height = container.clientHeight || 285;

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        camera.position.z = 210;

        var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        var coreGroup = new THREE.Group();
        scene.add(coreGroup);

        // 1. STANDALONE 3D EXTRUDED LOGO (MU - Titanium Chrome Finish, Zero Disc)
        var logo3DGroup = new THREE.Group();
        coreGroup.add(logo3DGroup);

        var chromeMaterial = new THREE.MeshPhongMaterial({
            color: 0x3a3f4d,       // Polished Dark Gunmetal Metallic Base (Car Emblem Finish)
            emissive: 0x090814,    // Subtle dark purple shadow reflection
            specular: 0xffffff,    // Pure Mirror Chrome Specular Highlight
            shininess: 240,        // Luxury High Gloss Metallic Reflections
            side: THREE.DoubleSide
        });

        // Fallback 3D Extruded Connected MU Shape (Zero Disc)
        function createFallback3DMULogo() {
            var mShape = new THREE.Shape();
            mShape.moveTo(-36, 30);
            mShape.lineTo(-36, -30);
            mShape.lineTo(-24, -30);
            mShape.lineTo(-24, 8);
            mShape.lineTo(-12, -18);
            mShape.lineTo(0, 8);
            mShape.lineTo(0, -30);
            mShape.lineTo(12, -30);
            mShape.lineTo(12, 30);
            mShape.lineTo(0, 30);
            mShape.lineTo(-12, 4);
            mShape.lineTo(-24, 30);
            mShape.closePath();

            // Stylized V-like U Shape connected right next to M
            var uShape = new THREE.Shape();
            uShape.moveTo(14, 30);
            uShape.lineTo(24, -22);
            uShape.lineTo(34, -30); // V-style bottom
            uShape.lineTo(44, -22);
            uShape.lineTo(54, 30);
            uShape.lineTo(42, 30);
            uShape.lineTo(34, -10);
            uShape.lineTo(26, 30);
            uShape.closePath();

            var settings = { depth: 14, bevelEnabled: true, bevelThickness: 3, bevelSize: 1.5, bevelSegments: 4 };
            var mGeo = new THREE.ExtrudeGeometry(mShape, settings);
            var uGeo = new THREE.ExtrudeGeometry(uShape, settings);
            mGeo.center();
            uGeo.center();

            var mMesh = new THREE.Mesh(mGeo, chromeMaterial);
            var uMesh = new THREE.Mesh(uGeo, chromeMaterial);
            mMesh.position.x = -16;
            uMesh.position.x = 22;

            logo3DGroup.add(mMesh);
            logo3DGroup.add(uMesh);
        }

        // SVGLoader: Filter out background circle disc so ONLY standalone 3D MU logo renders
        if (typeof THREE.SVGLoader !== 'undefined') {
            var svgLoader = new THREE.SVGLoader();
            svgLoader.load("{{ asset('favicon.svg') }}", function(data) {
                var paths = data.paths;
                var svgGroup = new THREE.Group();

                for (var i = 0; i < paths.length; i++) {
                    var path = paths[i];

                    // SKIP background circle disc element completely
                    if (path.userData && path.userData.node && path.userData.node.tagName === 'circle') {
                        continue;
                    }
                    if (i === 0 && paths.length > 1) {
                        continue; // Skip 1st background circle path in favicon.svg
                    }

                    var shapes = THREE.SVGLoader.createShapes(path);
                    for (var j = 0; j < shapes.length; j++) {
                        var shape = shapes[j];
                        var extrudeGeo = new THREE.ExtrudeGeometry(shape, {
                            depth: 16, // Solid standalone 3D depth
                            bevelEnabled: true,
                            bevelThickness: 3.5,
                            bevelSize: 1.5,
                            bevelSegments: 4
                        });
                        extrudeGeo.center();

                        var mesh = new THREE.Mesh(extrudeGeo, chromeMaterial);
                        svgGroup.add(mesh);
                    }
                }

                svgGroup.scale.set(0.32, -0.32, 0.32);
                logo3DGroup.add(svgGroup);
            }, undefined, function(err) {
                createFallback3DMULogo();
            });
        } else {
            createFallback3DMULogo();
        }

        var corePointLight = new THREE.PointLight(0xa855f7, 1.2, 300);
        coreGroup.add(corePointLight);

        // 2. Geodesic Vertices Glowing Nodes (Floating Stars)
        var wireGeo = new THREE.IcosahedronGeometry(45, 2);
        var wirePositions = wireGeo.attributes.position.array;
        var vertexCount = wirePositions.length / 3;
        var vertexGeo = new THREE.BufferGeometry();
        vertexGeo.setAttribute('position', new THREE.BufferAttribute(wirePositions, 3));
        var vertexMat = new THREE.PointsMaterial({
            color: 0xc084fc,
            size: 3.2,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending
        });
        var vertexNodes = new THREE.Points(vertexGeo, vertexMat);
        coreGroup.add(vertexNodes);

        // 4. Concentric Mechanical Outer Armor Rings (Segmented Metallic Shells)
        var metallicMat = new THREE.MeshPhongMaterial({
            color: 0x160c29,
            specular: 0x8a4fff,
            shininess: 80,
            emissive: 0x1a0c33,
            side: THREE.DoubleSide
        });

        var neonAccentMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            transparent: true,
            opacity: 0.85,
            blending: THREE.AdditiveBlending
        });

        // Mechanical Segmented Ring 1 (Inner Shell)
        var ring1Group = new THREE.Group();
        var torusGeo1 = new THREE.TorusGeometry(54, 2.2, 16, 48);
        var torusMesh1 = new THREE.Mesh(torusGeo1, metallicMat);
        ring1Group.add(torusMesh1);

        // Add 6 Segmented Notch Clamps around Ring 1
        for (var i = 0; i < 6; i++) {
            var clampGeo = new THREE.BoxGeometry(4, 7, 7);
            var clampMesh = new THREE.Mesh(clampGeo, metallicMat);
            var angle = (i / 6) * Math.PI * 2;
            clampMesh.position.set(Math.cos(angle) * 54, Math.sin(angle) * 54, 0);
            clampMesh.rotation.z = angle;
            ring1Group.add(clampMesh);

            // Neon Light Slot on Clamps
            var slotGeo = new THREE.BoxGeometry(2, 5, 7.5);
            var slotMesh = new THREE.Mesh(slotGeo, neonAccentMat);
            slotMesh.position.copy(clampMesh.position);
            slotMesh.rotation.z = angle;
            ring1Group.add(slotMesh);
        }
        coreGroup.add(ring1Group);

        // Mechanical Segmented Ring 2 (Outer Heavy Armor Shell - Counter Rotating)
        var ring2Group = new THREE.Group();
        var torusGeo2 = new THREE.TorusGeometry(63, 3.5, 16, 36);
        var torusMesh2 = new THREE.Mesh(torusGeo2, metallicMat);
        ring2Group.add(torusMesh2);

        // Add 8 Heavy Armor Segment Brackets around Ring 2
        for (var j = 0; j < 8; j++) {
            var armorGeo = new THREE.BoxGeometry(6, 9, 9);
            var armorMesh = new THREE.Mesh(armorGeo, metallicMat);
            var angle2 = (j / 8) * Math.PI * 2;
            armorMesh.position.set(Math.cos(angle2) * 63, Math.sin(angle2) * 63, 0);
            armorMesh.rotation.z = angle2;
            ring2Group.add(armorMesh);

            // Glowing Notch Strip
            var stripGeo = new THREE.BoxGeometry(3, 7, 9.6);
            var stripMesh = new THREE.Mesh(stripGeo, neonAccentMat);
            stripMesh.position.copy(armorMesh.position);
            stripMesh.rotation.z = angle2;
            ring2Group.add(stripMesh);
        }
        ring2Group.rotation.x = Math.PI / 3;
        ring2Group.rotation.y = Math.PI / 6;
        coreGroup.add(ring2Group);

        // Orbital Precision HUD Ring 3
        var ring3Geo = new THREE.TorusGeometry(72, 1.0, 16, 64);
        var ring3Mat = new THREE.MeshBasicMaterial({ color: 0xc084fc, transparent: true, opacity: 0.5 });
        var ring3Mesh = new THREE.Mesh(ring3Geo, ring3Mat);
        ring3Mesh.rotation.x = -Math.PI / 4;
        coreGroup.add(ring3Mesh);

        // 5. Floating Levitation Underglow Shadow Disc
        var underglowGeo = new THREE.RingGeometry(0, 42, 32);
        var underglowMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            transparent: true,
            opacity: 0.35,
            blending: THREE.AdditiveBlending,
            side: THREE.DoubleSide
        });
        var underglowMesh = new THREE.Mesh(underglowGeo, underglowMat);
        underglowMesh.position.y = -68;
        underglowMesh.rotation.x = Math.PI / 2;
        scene.add(underglowMesh);

        // Lighting Configuration
        var ambientLight = new THREE.AmbientLight(0x1a0c33, 2.0);
        scene.add(ambientLight);

        var dirLight1 = new THREE.DirectionalLight(0x8a4fff, 2.5);
        dirLight1.position.set(120, 120, 150);
        scene.add(dirLight1);

        var dirLight2 = new THREE.DirectionalLight(0xc084fc, 1.5);
        dirLight2.position.set(-120, -80, -100);
        scene.add(dirLight2);

        // Mouse Parallax Interaction
        var mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', function (e) {
            var windowHalfX = window.innerWidth / 2;
            var windowHalfY = window.innerHeight / 2;
            mouseX = (e.clientX - windowHalfX) * 0.0007;
            mouseY = (e.clientY - windowHalfY) * 0.0007;
        });

        // ══ INTERACTIVE 3D MOUSE DRAG & SPIN ENGINE WITH PHYSICS MOMENTUM ══
        canvas.style.pointerEvents = 'auto';
        canvas.style.cursor = 'grab';

        var isDragging = false;
        var previousMousePosition = { x: 0, y: 0 };
        var spinVelocity = { x: 0, y: 0 };

        function onPointerDown(e) {
            isDragging = true;
            canvas.style.cursor = 'grabbing';
            var pageX = e.touches ? e.touches[0].clientX : e.clientX;
            var pageY = e.touches ? e.touches[0].clientY : e.clientY;
            previousMousePosition = { x: pageX, y: pageY };
            spinVelocity = { x: 0, y: 0 };
        }

        function onPointerMove(e) {
            if (!isDragging) return;

            var pageX = e.touches ? e.touches[0].clientX : e.clientX;
            var pageY = e.touches ? e.touches[0].clientY : e.clientY;

            var deltaX = pageX - previousMousePosition.x;
            var deltaY = pageY - previousMousePosition.y;

            // Free 3D Drag Rotation
            coreGroup.rotation.y += deltaX * 0.015;
            coreGroup.rotation.x += deltaY * 0.015;

            // Throw velocity for physics momentum inertia
            spinVelocity.y = deltaX * 0.008;
            spinVelocity.x = deltaY * 0.008;

            previousMousePosition = { x: pageX, y: pageY };
        }

        function onPointerUp() {
            if (isDragging) {
                isDragging = false;
                canvas.style.cursor = 'grab';
            }
        }

        canvas.addEventListener('mousedown', onPointerDown);
        canvas.addEventListener('touchstart', onPointerDown, { passive: true });

        window.addEventListener('mousemove', onPointerMove);
        window.addEventListener('touchmove', onPointerMove, { passive: true });

        window.addEventListener('mouseup', onPointerUp);
        window.addEventListener('touchend', onPointerUp);

        // Hover Scaling Interaction
        $('.item, .btn-pay-due, header .user-reference ul li').hover(function () {
            neonAccentMat.opacity = 1.0;
            coreGroup.scale.set(1.08, 1.08, 1.08);
        }, function () {
            neonAccentMat.opacity = 0.85;
            coreGroup.scale.set(1.0, 1.0, 1.0);
        });

        // Main Animation Loop
        var clock = new THREE.Clock();

        function animate() {
            requestAnimationFrame(animate);

            var elapsedTime = clock.getElapsedTime();

            // Inner Core Toned Down Idle Pulse (Zero Overexposure)
            var pulse = 1.0 + Math.sin(elapsedTime * 2.0) * 0.25;
            corePointLight.intensity = pulse;

            // Rotations
            if (typeof logo3DGroup !== 'undefined') logo3DGroup.rotation.y += 0.008;

            if (typeof vertexNodes !== 'undefined') {
                vertexNodes.rotation.y += 0.005;
                vertexNodes.rotation.z += 0.002;
            }

            ring1Group.rotation.z += 0.009;
            ring2Group.rotation.z -= 0.012;
            ring3Mesh.rotation.z += 0.006;

            // 3D Drag Physics Momentum & Friction Damping
            if (!isDragging) {
                coreGroup.rotation.y += spinVelocity.y;
                coreGroup.rotation.x += spinVelocity.x;

                // Friction decay towards 0
                spinVelocity.y *= 0.95;
                spinVelocity.x *= 0.95;

                // Baseline parallax mouse response when idle
                coreGroup.rotation.y += (mouseX - coreGroup.rotation.y) * 0.03;
                coreGroup.rotation.x += (mouseY - coreGroup.rotation.x) * 0.03;
            }

            // Floating bobbing effect
            coreGroup.position.y = Math.sin(elapsedTime * 1.8) * 3;

            renderer.render(scene, camera);
        }
        animate();
    })();
</script>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Render Lucide icons
        lucide.createIcons();

        // Supercar LCD dashboard system start choreography
        var tl = gsap.timeline();

        // 1. Initial states to avoid layout flash
        gsap.set(".hud-dashboard-gauge", { opacity: 0, scale: 0.1, rotation: -180 });
        gsap.set(".dashboard-side-gauges", { opacity: 0, y: 20 });
        gsap.set(".orbital-left .orbital-node", { opacity: 0, x: -60 });
        gsap.set(".orbital-right .orbital-node", { opacity: 0, x: 60 });
        gsap.set(".orbital-identity", { opacity: 0, scale: 0.8 });

        // 2. Play timeline
        tl.to(".hud-dashboard-gauge", { opacity: 1, scale: 0.9, rotation: 0, duration: 1.0, ease: "back.out(1.7)" })
          .to(".orbital-identity", { opacity: 1, scale: 1, duration: 0.6, ease: "power2.out" }, "-=0.6")
          .to(".dashboard-side-gauges", { opacity: 1, y: 0, duration: 0.6, ease: "power2.out" }, "-=0.4")
          // Animate left/right level bars swipe from 0 to target width
          .to(".gauge-bar-fill", {
              width: function(index, target) {
                  return target.getAttribute('data-target-width') || "0%";
              },
              duration: 1.0,
              ease: "power2.out"
          }, "-=0.3")
          // Stagger show left flank buttons
          .to(".orbital-left .orbital-node", { opacity: 1, x: 0, duration: 0.5, stagger: 0.06, ease: "power2.out" }, "-=0.8")
          // Stagger show right flank buttons
          .to(".orbital-right .orbital-node", { opacity: 1, x: 0, duration: 0.5, stagger: 0.06, ease: "power2.out" }, "-=0.8");
    });
</script>

</body>
</html>
