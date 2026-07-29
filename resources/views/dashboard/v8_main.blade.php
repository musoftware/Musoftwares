<!doctype html>
<html lang="en" style="overflow: overlay;">

<head>
    <script>
        try {
            if (window.self !== window.top) {
                window.top.location.href = window.location.href;
            }
        } catch(e) { /* sandboxed iframe: can't break out, page stays hidden */ }
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

            <!-- ▶ RIGHT: Command Bar, Notifications, Lang & User Capsule -->
            <div class="d-flex align-items-center">

                <!-- Search (Ctrl + K) Trigger -->
                <div class="command-bar-trigger mr-2 d-flex align-items-center pointer px-2 py-1 rounded-pill" 
                     data-toggle="modal" data-target="#commandBarModal" title="Universal Search & Actions (Ctrl + K)"
                     style="background: rgba(35, 16, 70, 0.6); border: 1px solid rgba(168, 85, 247, 0.4); backdrop-filter: blur(8px); transition: all 0.3s ease;">
                    <i class="icon-search mr-1" style="font-size: 12px; color: #00f0ff;"></i>
                    <span class="mr-2 d-none d-sm-inline" style="color: #f3e8ff; font-size: 11px; font-weight: 600;">{{ __('dashboard.search') }}</span>
                    <kbd class="command-bar-kbd" style="background: rgba(0, 240, 255, 0.15); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.4); font-size: 9px; padding: 1px 5px; border-radius: 4px;">Ctrl K</kbd>
                </div>

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
                            <div class="user-level text-uppercase" style="font-size: 8px; color: #ff7c20; letter-spacing: 1px;">{{ Auth::user()->role ?? 'Client' }}</div>
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
        background: radial-gradient(circle at 50% 45%, rgba(138, 79, 255, 0.18) 0%, rgba(13, 6, 26, 0.98) 75%) !important;
        transition: background 0.6s ease;
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
    }

    .orbital-center-core * {
        pointer-events: auto;
    }

    .orbital-center-core #logo-it {
        zoom: 1.45 !important;
        width: 320px !important;
        height: 260px !important;
        filter: drop-shadow(0 6px 18px rgba(0, 0, 0, 0.9)) drop-shadow(0 0 8px rgba(168, 85, 247, 0.2));
        transition: filter 0.5s ease-out, transform 0.5s ease-out;
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

    /* ── Floating Orbital Arc Nodes Layout (NO SCALE UP ON HOVER) ──────── */
    .orbital-arc-container {
        position: relative;
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-sizing: border-box;
        padding: 0 20px;
        z-index: 5;
    }

    /* Left & Right Node Columns */
    .orbital-col {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        height: 84%;
        width: 110px;
    }

    .orbital-col.orbital-left { align-items: flex-start; }
    .orbital-col.orbital-right { align-items: flex-end; }

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

    /* ── LEFT FLANK 3D CUBES ── */
    @keyframes orbitHoverWaveLeft {
        0%   { transform: rotateX(-16deg) rotateY(20deg) rotateZ(-5deg) translateY(0px); }
        50%  { transform: rotateX(-12deg) rotateY(28deg) rotateZ(5deg) translateY(-10px); }
        100% { transform: rotateX(-16deg) rotateY(20deg) rotateZ(-5deg) translateY(0px); }
    }

    .orbital-left .cube-3d {
        width: 100%;
        height: 100%;
        position: relative;
        transform-style: preserve-3d;
        transform: rotateX(-16deg) rotateY(20deg);
        animation: orbitHoverWaveLeft 5.5s ease-in-out infinite;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* ── RIGHT FLANK 3D CUBES ── */
    @keyframes orbitHoverWaveRight {
        0%   { transform: rotateX(-16deg) rotateY(-20deg) rotateZ(5deg) translateY(0px); }
        50%  { transform: rotateX(-12deg) rotateY(-28deg) rotateZ(-5deg) translateY(-10px); }
        100% { transform: rotateX(-16deg) rotateY(-20deg) rotateZ(5deg) translateY(0px); }
    }

    .orbital-right .cube-3d {
        width: 100%;
        height: 100%;
        position: relative;
        transform-style: preserve-3d;
        transform: rotateX(-16deg) rotateY(-20deg);
        animation: orbitHoverWaveRight 5.5s ease-in-out infinite;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
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

    /* FRONT FACE: SVG Icon + Text Label INSIDE */
    .cube-face-front {
        transform: translateZ(42.5px);
        background: linear-gradient(135deg, rgba(35, 16, 70, 0.96), rgba(16, 7, 34, 0.98));
        border: 1.5px solid rgba(168, 85, 247, 0.55);
        box-shadow: inset 0 0 16px rgba(168, 85, 247, 0.25);
        padding: 6px;
        text-align: center;
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
        word-break: break-word;
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

    /* ── MOBILE / SPACE-DEPENDENT RESPONSIVE TOP & BOTTOM BUTTON LAYOUT ── */
    @media only screen and (max-width: 768px) {
        .orbital-viewport {
            height: calc(100vh - 56px) !important;
            padding: 14px 4px 8px 4px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .orbital-center-core {
            position: absolute !important;
            top: 48% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            z-index: 10 !important;
            width: 100% !important;
            pointer-events: none !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
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
            justify-content: space-between !important;
            align-items: center !important;
            height: 100% !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 6px 2px !important;
            z-index: 15 !important;
            position: relative !important;
            box-sizing: border-box !important;
        }

        .orbital-col {
            width: 100% !important;
            height: auto !important;
            flex-direction: row !important;
            justify-content: space-around !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            padding: 0 4px !important;
            margin: 0 !important;
        }

        .orbital-col.orbital-left {
            align-items: center !important;
            margin-top: 18px !important;
            margin-bottom: 10px !important;
        }

        .orbital-col.orbital-right {
            align-items: center !important;
            margin-bottom: 12px !important;
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
        <div id="logo-it"></div>

        <div class="orbital-identity">
            <div class="c-name">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="c-role">{{ Auth::user()->role ?? 'Client' }}</div>
        </div>
    </div>

    <!-- ══ FLOATING 85px 3D CUBES WITH DYNAMIC MOUSE CURSOR LOOK-AT ══ -->
    <div class="orbital-arc-container">

        <!-- ◀ LEFT ORBITAL FLANK -->
        <div class="orbital-col orbital-left">

            <!-- 3D Cube Node 1: Pay Due Amount -->
            <button class="orbital-node" data-node="1" data-toggle="modal" data-target="#payDueModal">
                <div class="cube-3d-scene cube-theme-red">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
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

            <!-- 3D Cube Node 2: Add Balance -->
            <a href="{{ url('/financial/add-balance') }}" class="orbital-node" data-node="2">
                <div class="cube-3d-scene cube-theme-emerald">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
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

            <!-- 3D Cube Node 3: ERP System -->
            <a href="{{ url('/sso/erp') }}" class="orbital-node" data-node="3">
                <div class="cube-3d-scene cube-theme-cyan">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
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

            <!-- 3D Cube Node 4: CRM System -->
            <a href="{{ url('/sso/crm') }}" class="orbital-node" data-node="4">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
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

        </div>

        <!-- ▶ RIGHT ORBITAL FLANK -->
        <div class="orbital-col orbital-right">

            <!-- 3D Cube Node 5: Store -->
            <a href="{{ url('/marketplace/services') }}" class="orbital-node" data-node="5">
                <div class="cube-3d-scene cube-theme-amber">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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

            <!-- 3D Cube Node 6: My Projects -->
            <a href="{{ url('/projects') }}" class="orbital-node" data-node="6">
                <div class="cube-3d-scene cube-theme-pink">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 7v7m4-7v4m4-4v9"/></svg>
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

            <!-- 3D Cube Node 7: SMS Gateway -->
            <a href="{{ url('/sms-payment-gateway') }}" class="orbital-node" data-node="7">
                <div class="cube-3d-scene cube-theme-amber">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/><path d="M9 7h6M9 11h4"/></svg>
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

            <!-- 3D Cube Node 8: Gold Saver System -->
            <a href="{{ url('/sso/goldsaversys') }}" class="orbital-node" data-node="8">
                <div class="cube-3d-scene cube-theme-gold">
                    <div class="cube-3d">
                        <div class="cube-face cube-face-front">
                            <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
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

            // Smooth pitch (rotateX) & yaw (rotateY) pointing towards cursor
            var rotX = -dy * 24; // max ±24deg pitch
            var rotY = dx * 28;  // max ±28deg yaw

            cube.style.transform = 'rotateX(' + rotX.toFixed(1) + 'deg) rotateY(' + rotY.toFixed(1) + 'deg) translateZ(22px)';
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

        // 2. Geodesic Triangular Wireframe Ring Mesh
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
            wireMesh.rotation.y += 0.005;
            wireMesh.rotation.z += 0.002;

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

</body>
</html>
