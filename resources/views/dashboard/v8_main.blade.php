<!doctype html>
<html lang="en" style="overflow: overlay;">

<head>
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
            letter-spacing: 0.5px !important;
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
        <div class="audio-test text-light hidden">
            <h3>Play site with sound effects?</h3>
            <div class="buttons mt-4 text-center">
                <button class="btn btn-primary btn-sm mr-4 yes">Yes</button>
                <button class="btn btn-danger btn-sm no">No</button>
            </div>
            <div class="mt-4 text-center">
                <label class="pointer text-light" style="font-size: 11px; cursor: pointer; user-select: none; color: #a0aec0 !important;">
                    <input type="checkbox" id="dontShowWelcome30Days" style="cursor: pointer; vertical-align: middle;" class="mr-1">
                    Don't show welcome intro for 30 days
                </label>
            </div>
        </div>
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

<!-- Header -->
<header class="nav">
    <div class="container-fluid">
        <div class="row">
            <!-- Logo -->
            <div class="col-lg-3 col-md-3 col-6">
                <div class="logo-parent d-flex align-items-center">
                    <img class="logo pointer" src="{{ asset('v8main/img/amc8.png') }}"
                         data-href="{{ url('/dashboard') }}"
                         alt="Musoftwares">
                </div>
            </div>

            <!-- User Data & Streamlined Financial Controls -->
            <div class="user-reference col-lg-9 col-md-9 col-6 mt-0">
                <div class="text-light flex-row d-flex align-items-center justify-content-end text-center">

                    <!-- Search (Ctrl + K) & Embedded Live Operational LED -->
                    <div class="command-bar-trigger mr-2 d-flex align-items-center pointer" data-toggle="modal" data-target="#commandBarModal" title="Universal Search & Actions (Ctrl + K)">
                        <span class="status-dot-led mr-2" title="System Operational"></span>
                        <i class="icon-search mr-1" style="font-size: 12px; color: #8A4FFF;"></i>
                        <span class="mr-2 d-none d-sm-inline" style="color: #f3e8ff;">{{ __('dashboard.search') }}</span>
                        <kbd class="command-bar-kbd">Ctrl K</kbd>
                    </div>

                    <!-- Streamlined Notification Hub Dropdown -->
                    <div class="dropdown mr-2">
                        <div class="d-flex align-items-center justify-content-center pointer position-relative dropdown-toggle"
                             id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                             title="Notifications & Messages" style="width: 36px; height: 32px; padding: 0; background: rgba(22, 10, 42, 0.6); border: 1px solid rgba(138, 79, 255, 0.4); border-radius: 8px;">
                            <i class="icon-bell" style="color: #8A4FFF; font-size: 14px;"></i>
                            <span class="status-dot-led position-absolute" style="top: 4px; right: 4px; width: 6px; height: 6px; background-color: #8A4FFF; box-shadow: 0 0 6px #8A4FFF;"></span>
                        </div>

                        <!-- Notification Dropdown Menu -->
                        <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="notificationDropdown"
                             style="background: #130924; border: 1px solid #8A4FFF; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35); width: 290px;">
                            
                            <!-- Header -->
                            <div class="d-flex align-items-center justify-content-between pb-2 mb-2" style="border-bottom: 1px solid rgba(138,79,255,0.25);">
                                <div class="font-weight-bold" style="color: #f3e8ff; font-size: 13px;">
                                    <i class="icon-bell mr-1" style="color: #8A4FFF;"></i> {{ __('dashboard.notifications') }}
                                </div>
                                <a href="{{ url('/notifications') }}" class="small font-weight-bold" style="color: #a855f7;">{{ __('dashboard.view_all') }}</a>
                            </div>

                            <!-- Real Notification Feed -->
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

                    <!-- Language Switcher -->
                    <div class="dropdown mr-2">
                        <div class="d-flex align-items-center justify-content-center pointer dropdown-toggle"
                             id="langDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                             style="width: 36px; height: 32px; padding: 0; background: rgba(22,10,42,0.6); border: 1px solid rgba(138,79,255,0.4); border-radius: 8px; font-size: 13px;">
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

                    <!-- User Profile Capsule -->
                    <div class="dropdown">
                        <div class="user-data d-flex align-items-center px-2 py-1 dropdown-toggle pointer"
                             id="dropdownMenuOffset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                            <div class="profile-pic d-flex mr-1">
                                <img src="{{ Auth::user()->avatar_url ?? asset('v8main/img/user.jpg') }}" alt="" class="user-img m-auto" style="border-radius: 50%; object-fit: cover;">
                            </div>
                            <div class="d-none d-md-flex flex-column user-text px-2 mr-1 position-relative text-left font-weight-bold">
                                <div class="username text-capitalize">{{ Auth::user()->name ?? 'User' }}</div>
                                <div class="user-level text-uppercase">{{ Auth::user()->role ?? 'Client' }}</div>
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
                            @if(Auth::user() && (Auth::user()->role === 'Admin' || Auth::user()->is_admin))
                                <a class="dropdown-item py-2" href="{{ url('/admin/dashboard') }}"><i class="icon-user mr-2"></i>{{ __('dashboard.admin_panel') }}</a>
                            @endif
                            <a class="dropdown-item py-2" href="{{ url('/profile') }}"><i class="icon-user mr-2"></i>{{ __('dashboard.my_profile') }}</a>
                            <div class="dropdown-divider" style="border-color: rgba(138,79,255,0.2);"></div>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display:none;">@csrf</form>
                            <a class="dropdown-item py-2 text-danger font-weight-bold" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('dashboard.logout') }}</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</header>
<!-- ════════════════════════════════════════════════════════════
     CAR CONSOLE DASHBOARD — Android Square App Icon Style
     Target: 1366×768  |  Zero-Scroll Layout  |  8 Android App Tiles
════════════════════════════════════════════════════════════ -->
<style>
    /* ── No-Scroll Car Console Reset ───────────────────────────── */
    html, body {
        overflow: hidden !important;
        height: 100% !important;
        max-height: 100% !important;
    }
    .console-viewport {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px); /* subtract header */
        overflow: hidden;
        padding: 8px 12px 6px;
        gap: 8px;
        box-sizing: border-box;
    }

    /* ── Hologram Strip (top row) ───────────────────────────────── */
    .console-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        flex: 0 0 auto;
        height: 170px;
    }
    .console-top #logo-it {
        zoom: 0.75 !important;
        width: 200px !important;
        height: 170px !important;
        flex-shrink: 0;
    }
    .console-identity {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    .console-identity .c-name {
        font-size: 15px;
        font-weight: 700;
        color: #a855f7;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .console-identity .c-role {
        font-size: 9px;
        color: #ff7c20;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .console-wallet-pill {
        display: flex;
        gap: 10px;
        margin-top: 4px;
    }
    .console-wallet-pill .cpill {
        background: rgba(22,10,42,0.85);
        border: 1px solid rgba(138,79,255,0.3);
        border-radius: 8px;
        padding: 5px 14px;
        font-size: 11px;
        font-weight: 600;
    }
    .console-wallet-pill .cpill span { color: #a78bfa; display: block; font-size: 8px; font-weight: 400; }
    .console-wallet-pill .cpill strong { color: #c084fc; }
    .console-wallet-pill .cpill.cpill-due { border-color: rgba(244,63,94,0.4); }
    .console-wallet-pill .cpill.cpill-due strong { color: #f43f5e; }
    .console-wallet-pill .cpill.cpill-pts { border-color: rgba(251,191,36,0.35); }
    .console-wallet-pill .cpill.cpill-pts strong { color: #fbbf24; }
    .c-dir-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        padding: 6px 14px;
        border: 1px solid rgba(138,79,255,0.5);
        background: rgba(138,79,255,0.18);
        border-radius: 8px;
        color: #d8b4fe;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none !important;
        letter-spacing: 0.4px;
        transition: all 0.2s;
    }
    .c-dir-btn:hover { background: rgba(138,79,255,0.35); color: #fff; text-decoration: none !important; box-shadow: 0 0 14px rgba(138,79,255,0.4); }

    /* ── 2×2 Panel Grid (bottom fill) ──────────────────────────── */
    .console-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 8px;
        flex: 1 1 auto;
        overflow: hidden;
        min-height: 0;
    }
    .c-panel {
        background: rgba(14, 6, 30, 0.88);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    /* Panel Headers */
    .c-panel-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        background: rgba(138, 79, 255, 0.12);
        border-bottom: 1px solid rgba(138, 79, 255, 0.18);
        flex-shrink: 0;
    }
    .c-panel-head-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .c-panel-head-icon svg {
        width: 14px;
        height: 14px;
        fill: none;
    }

    /* Distinct Accents per Panel */
    .c-panel-finances .c-panel-head-icon { background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); }
    .c-panel-finances .c-panel-head-icon svg { stroke: #10b981; }

    .c-panel-market .c-panel-head-icon { background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); }
    .c-panel-market .c-panel-head-icon svg { stroke: #f59e0b; }

    .c-panel-services .c-panel-head-icon { background: rgba(0, 240, 255, 0.2); border: 1px solid rgba(0, 240, 255, 0.4); }
    .c-panel-services .c-panel-head-icon svg { stroke: #00f0ff; }

    .c-panel-projects .c-panel-head-icon { background: rgba(236, 72, 153, 0.2); border: 1px solid rgba(236, 72, 153, 0.4); }
    .c-panel-projects .c-panel-head-icon svg { stroke: #ec4899; }

    .c-panel-head .c-panel-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.8px;
        color: #e2d4f7;
        text-transform: uppercase;
    }
    .c-panel-head .c-panel-sub {
        margin-left: auto;
        font-size: 9px;
        color: rgba(168,85,247,0.6);
        letter-spacing: 0.5px;
    }

    .c-panel-body {
        flex: 1 1 auto;
        overflow: hidden;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }

    /* ── Android Square App Icon Grid System ──────────────────────── */
    .c-app-grid {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 24px;
        width: 100%;
    }

    .c-app-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        cursor: pointer;
        transition: transform 0.2s ease;
        background: transparent;
        border: none;
        padding: 0;
    }

    .c-app-item:hover {
        transform: translateY(-3px) scale(1.05);
    }

    /* Square App Box with Centered Icon */
    .c-app-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(22, 10, 42, 0.85);
        border: 1.5px solid rgba(138, 79, 255, 0.4);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), inset 0 0 10px rgba(138, 79, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.2s ease;
    }

    .c-app-box svg {
        width: 24px;
        height: 24px;
        stroke: #c084fc;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .c-app-item:hover .c-app-box {
        border-color: #a855f7;
        background: rgba(138, 79, 255, 0.25);
        box-shadow: 0 0 18px rgba(168, 85, 247, 0.5), inset 0 0 12px rgba(138, 79, 255, 0.3);
    }

    /* Text Outside (Underneath Icon) */
    .c-app-label {
        margin-top: 6px;
        font-size: 11px;
        font-weight: 600;
        color: #e2d4f7;
        text-align: center;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }
    .c-app-item:hover .c-app-label {
        color: #fff;
    }

    /* Color Accents per App Box */
    .c-app-box-red { background: rgba(244, 63, 94, 0.15); border-color: rgba(244, 63, 94, 0.5); }
    .c-app-box-red svg { stroke: #f43f5e; }
    .c-app-item:hover .c-app-box-red { background: rgba(244, 63, 94, 0.3); border-color: #f43f5e; box-shadow: 0 0 18px rgba(244, 63, 94, 0.6); }

    .c-app-box-emerald { background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.4); }
    .c-app-box-emerald svg { stroke: #10b981; }

    .c-app-box-amber { background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.4); }
    .c-app-box-amber svg { stroke: #f59e0b; }

    .c-app-box-cyan { background: rgba(0, 240, 255, 0.15); border-color: rgba(0, 240, 255, 0.4); }
    .c-app-box-cyan svg { stroke: #00f0ff; }

    .c-app-box-pink { background: rgba(236, 72, 153, 0.15); border-color: rgba(236, 72, 153, 0.4); }
    .c-app-box-pink svg { stroke: #ec4899; }

    /* Notification / Due Badge on Corner of Box */
    .c-box-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        font-size: 8px;
        font-weight: 700;
        color: #fff;
        background: #f43f5e;
        padding: 1px 5px;
        border-radius: 10px;
        box-shadow: 0 0 8px rgba(244, 63, 94, 0.8);
    }
</style>

<div class="console-viewport">

    <!-- ══ TOP: Hologram + Identity ══ -->
    <div class="console-top">

        <!-- Left identity text -->
        <div class="console-identity text-right" style="align-items: flex-end;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" style="margin-bottom:3px;">
                <path fill-rule="evenodd" fill="#8A4FFF" d="M15.842,4.424 C15.594,4.424 15.361,4.379 15.145,4.304 L14.212,5.261 C15.088,6.359 15.604,7.753 15.604,9.268 C15.604,10.783 15.088,12.177 14.212,13.275 L15.145,14.232 C15.361,14.157 15.594,14.112 15.842,14.112 C17.010,14.112 17.957,15.082 17.957,16.278 C17.957,17.474 17.010,18.444 15.842,18.444 C14.674,18.444 13.727,17.474 13.727,16.278 C13.727,16.026 13.769,15.785 13.845,15.562 L12.906,14.600 C11.880,15.346 10.619,15.790 9.253,15.790 C7.887,15.790 6.626,15.346 5.600,14.600 L4.661,15.562 C4.737,15.785 4.779,16.026 4.779,16.278 C4.779,17.474 3.832,18.444 2.664,18.444 C1.496,18.444 0.549,17.474 0.549,16.278 C0.549,15.082 1.496,14.112 2.664,14.112 C2.912,14.112 3.145,14.157 3.361,14.232 L4.294,13.275 C3.418,12.177 2.902,10.783 2.902,9.268 C2.902,7.753 3.418,6.359 4.294,5.261 L3.361,4.304 C3.145,4.379 2.912,4.424 2.664,4.424 C1.496,4.424 0.549,3.454 0.549,2.258 C0.549,1.062 1.496,0.092 2.664,0.092 C3.832,0.092 4.779,1.062 4.779,2.258 C4.779,2.510 4.737,2.751 4.661,2.974 L5.600,3.936 C6.626,3.190 7.887,2.746 9.253,2.746 C10.619,2.746 11.880,3.190 12.906,3.936 L13.845,2.974 C13.769,2.751 13.727,2.510 13.727,2.258 C13.727,1.062 14.674,0.092 15.842,0.092 C17.010,0.092 17.957,1.062 17.957,2.258 C17.957,3.454 17.010,4.424 15.842,4.424 Z"/>
            </svg>
            <div class="c-name">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="c-role">{{ Auth::user()->role ?? 'Client' }}</div>
        </div>

        <!-- Hologram orb -->
        <div id="logo-it" style="flex-shrink:0;"></div>

        <!-- Right wallet + directory -->
        <div class="console-identity" style="align-items: flex-start;">
            <div class="console-wallet-pill">
                <div class="cpill">
                    <span>{{ __('dashboard.wallet') }}</span>
                    <strong>{{ $userBalanceFormatted }}</strong>
                </div>
                @if($unpaidCount > 0)
                <div class="cpill cpill-due">
                    <span>{{ __('dashboard.due') }}</span>
                    <strong>{{ $totalDueFormatted }}</strong>
                </div>
                @endif
                <div class="cpill cpill-pts">
                    <span>{{ __('dashboard.points') }}</span>
                    <strong>{{ number_format($userPoints) }}</strong>
                </div>
            </div>
            <a href="{{ url('/dashboard/directory') }}" class="c-dir-btn">
                <div class="c-app-box c-app-box-cyan" style="width:18px; height:18px; border-radius:4px; border:none; background:transparent;">
                    <svg viewBox="0 0 24 24" style="width:14px; height:14px;"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                </div>
                {{ __('dashboard.all_apps_dir') }} &rsaquo;
            </a>
        </div>

    </div>

    <!-- ══ BOTTOM: 2×2 Panel Grid (Android App Icons Style) ══ -->
    <div class="console-grid">

        <!-- ① MY FINANCES ─────────────────────────────────────── -->
        <div class="c-panel c-panel-finances">
            <div class="c-panel-head">
                <div class="c-panel-head-icon">
                    <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M16 14h.01"/></svg>
                </div>
                <span class="c-panel-title">{{ __('dashboard.my_finances') }}</span>
                <span class="c-panel-sub">{{ __('dashboard.dues_payments') }}</span>
            </div>
            <div class="c-panel-body">
                <div class="c-app-grid">
                    <!-- Pay Due App Button -->
                    <button class="c-app-item" data-toggle="modal" data-target="#payDueModal">
                        <div class="c-app-box c-app-box-red">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            @if($unpaidAmount > 0)
                                <span class="c-box-badge">{{ $totalDueFormatted }}</span>
                            @endif
                        </div>
                        <span class="c-app-label">{{ __('dashboard.pay_due_amount') }}</span>
                    </button>

                    <!-- Add Balance App Button -->
                    <a href="{{ url('/financial/add-balance') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-emerald">
                            <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.add_balance') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ② MARKETPLACE ─────────────────────────────────────── -->
        <div class="c-panel c-panel-market">
            <div class="c-panel-head">
                <div class="c-panel-head-icon">
                    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <span class="c-panel-title">{{ __('dashboard.marketplace') }}</span>
                <span class="c-panel-sub">{{ __('dashboard.browse_sell') }}</span>
            </div>
            <div class="c-panel-body">
                <div class="c-app-grid">
                    <a href="{{ url('/marketplace/services') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-amber">
                            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.browse_marketplace') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ③ MY SERVICES ─────────────────────────────────────── -->
        <div class="c-panel c-panel-services">
            <div class="c-panel-head">
                <div class="c-panel-head-icon">
                    <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                </div>
                <span class="c-panel-title">{{ __('dashboard.my_services') }}</span>
                <span class="c-panel-sub">{{ __('dashboard.platforms_subs') }}</span>
            </div>
            <div class="c-panel-body">
                <div class="c-app-grid">
                    <a href="{{ url('/sso/erp') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-cyan">
                            <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.erp_system') }}</span>
                    </a>
                    <a href="{{ url('/sso/crm') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-pink">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.crm_system') }}</span>
                    </a>
                    <a href="{{ url('/sms-payment-gateway') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-amber">
                            <svg viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/><path d="M9 7h6M9 11h4"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.sms_gateway') }}</span>
                    </a>
                    <a href="{{ url('/sso/goldsaversys') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-amber">
                            <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.gold_saver_sys') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ④ MY PROJECTS ─────────────────────────────────────── -->
        <div class="c-panel c-panel-projects">
            <div class="c-panel-head">
                <div class="c-panel-head-icon">
                    <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <span class="c-panel-title">{{ __('dashboard.my_projects') }}</span>
                <span class="c-panel-sub">{{ __('dashboard.work_collab') }}</span>
            </div>
            <div class="c-panel-body">
                <div class="c-app-grid">
                    <a href="{{ url('/projects') }}" class="c-app-item">
                        <div class="c-app-box c-app-box-pink">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 7v7m4-7v4m4-4v9"/></svg>
                        </div>
                        <span class="c-app-label">{{ __('dashboard.my_projects_btn') }}</span>
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /console-grid -->

</div><!-- /console-viewport -->

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

<!-- UX FEATURE 1: Universal Command Bar (Ctrl + K) Modal -->
<div class="modal fade modal-command-bar" id="commandBarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 920px;">
        <div class="modal-content">
            <div class="modal-body p-4 text-left">
                <div class="d-flex align-items-center mb-3">
                    <input type="text" id="commandSearchInput" class="command-search-input" placeholder="Search systems, tools, invoices, actions... (e.g. ERP, CRM, Gold, Wallet)" autofocus autocomplete="off">
                </div>
                <div id="commandResultsList" class="command-results-container" style="max-height: 360px; overflow-y: auto; overflow-x: hidden; padding-right: 6px;">
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/erp') }}">
                        <div><i class="icon-calendar text-cyan mr-2"></i><strong>ERP Enterprise System</strong> <span class="text-muted small ml-2">- Financial & Accounting Operations</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/crm') }}">
                        <div><i class="icon-users text-cyan mr-2"></i><strong>CRM Customer Management</strong> <span class="text-muted small ml-2">- Leads & Client Interactions</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/goldsaversys') }}">
                        <div><i class="icon-star text-amber mr-2"></i><strong>Gold Saver System</strong> <span class="text-muted small ml-2">- Precious Metal Investment</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/affsys') }}">
                        <div><i class="icon-chart-bar text-cyan mr-2"></i><strong>Affiliate POS System</strong> <span class="text-muted small ml-2">- Partner Sales & Commissions</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/bookingsys') }}">
                        <div><i class="icon-clock text-cyan mr-2"></i><strong>Booking System</strong> <span class="text-muted small ml-2">- Reservations & Appointments</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/sso/toolsys') }}">
                        <div><i class="icon-cog text-cyan mr-2"></i><strong>Runtime Agent Tools</strong> <span class="text-muted small ml-2">- AI & Automation Engines</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/marketplace/services') }}">
                        <div><i class="icon-social text-cyan mr-2"></i><strong>Marketplace Services</strong> <span class="text-muted small ml-2">- Addons & Plugins Catalog</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-href="{{ url('/billing/invoices') }}">
                        <div><i class="icon-credit-card text-cyan mr-2"></i><strong>Billing & Invoices</strong> <span class="text-muted small ml-2">- Manage Payments</span></div>
                        <span class="badge badge-outline-info">Open</span>
                    </div>
                    <div class="command-result-item d-flex align-items-center justify-content-between" data-target="#payDueModal" data-toggle="modal" data-dismiss="modal">
                        <div><i class="icon-basket text-amber mr-2"></i><strong>{{ __('dashboard.pay_due_amount') }}</strong></div>
                        <span class="badge badge-outline-warning">Action</span>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted small mt-3 pt-2 border-top border-secondary">
                    <span>Navigation: <kbd class="command-bar-kbd">â†‘</kbd> <kbd class="command-bar-kbd">â†“</kbd> to select, <kbd class="command-bar-kbd">Enter</kbd> to open</span>
                    <span>Close: <kbd class="command-bar-kbd">Esc</kbd></span>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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

    /* ==========================================================================
       UX FEATURE 1: UNIVERSAL COMMAND BAR (CTRL + K) SPOTLIGHT ENGINE
       ========================================================================== */
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#commandBarModal').modal('show');
        }
    });

    $(document).on('click', '.command-bar-trigger', function (e) {
        e.preventDefault();
        $('#commandBarModal').modal('show');
    });

    $('#commandBarModal').on('shown.bs.modal', function () {
        $('#commandSearchInput').focus().val('');
        $('.command-result-item').show();
    });

    $('#commandSearchInput').on('input', function () {
        var query = $(this).val().toLowerCase().trim();
        $('.command-result-item').each(function () {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
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

        // 1. Central Intense Reactor Light & Inner Bright Sphere Core
        var innerCoreGeo = new THREE.SphereGeometry(18, 32, 32);
        var innerCoreMat = new THREE.MeshBasicMaterial({
            color: 0xffffff,
            transparent: true,
            opacity: 0.95
        });
        var innerCoreMesh = new THREE.Mesh(innerCoreGeo, innerCoreMat);
        coreGroup.add(innerCoreMesh);

        var corePointLight = new THREE.PointLight(0xa855f7, 4.5, 300);
        coreGroup.add(corePointLight);

        // 2. Inner Glowing Energy Aura
        var auraGeo = new THREE.SphereGeometry(25, 32, 32);
        var auraMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            transparent: true,
            opacity: 0.45,
            blending: THREE.AdditiveBlending,
            side: THREE.BackSide
        });
        var auraMesh = new THREE.Mesh(auraGeo, auraMat);
        coreGroup.add(auraMesh);

        // 3. Geodesic Triangular Wireframe Mesh (Matching Image Inner Sphere)
        var wireGeo = new THREE.IcosahedronGeometry(45, 2);
        var wireMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            wireframe: true,
            transparent: true,
            opacity: 0.85
        });
        var wireMesh = new THREE.Mesh(wireGeo, wireMat);
        coreGroup.add(wireMesh);

        // Geodesic Vertices Glowing Nodes
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
        wireMesh.add(vertexNodes);

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

            // Inner Core Pulse
            var pulse = 3.8 + Math.sin(elapsedTime * 3.5) * 0.8;
            corePointLight.intensity = pulse;
            auraMesh.scale.setScalar(1.0 + Math.sin(elapsedTime * 2.5) * 0.05);

            // Rotations
            wireMesh.rotation.y += 0.005;
            wireMesh.rotation.z += 0.002;

            ring1Group.rotation.z += 0.009;
            ring2Group.rotation.z -= 0.012;
            ring3Mesh.rotation.z += 0.006;

            // Parallax Smooth Tilt
            coreGroup.rotation.y += (mouseX - coreGroup.rotation.y) * 0.05;
            coreGroup.rotation.x += (mouseY - coreGroup.rotation.x) * 0.05;

            // Floating bobbing effect
            coreGroup.position.y = Math.sin(elapsedTime * 1.8) * 3;

            renderer.render(scene, camera);
        }
        animate();
    })();
</script>

</body>
</html>
