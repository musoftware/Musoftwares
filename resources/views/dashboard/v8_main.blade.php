@php
    $authUser = Auth::user();
    $userBalanceVal = $authUser->user_balance ?? 0;
    $currencySymbol = optional($authUser->currencyRelation)->symbol ?? 'EGP';
    $userBalanceFormatted = number_format($userBalanceVal, 2) . ' ' . $currencySymbol;
    $userPoints = $authUser->points ?? $authUser->reward_points ?? 0;

    $unpaidInvoices = collect();
    $totalDueAmount = 0;

    if ($authUser && method_exists($authUser, 'invoices')) {
        $unpaidInvoices = $authUser->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();
        $totalDueAmount = $unpaidInvoices->sum('unpaid');
    }
    $totalDueFormatted = number_format($totalDueAmount, 2) . ' ' . $currencySymbol;
@endphp
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
            margin-top: 45px;
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
    </style>
</head>

<body>

<!-- Preloader -->
<div class="preloader-wrapper">
    <button class="btn btn-outline-info btn-sm skip-intro-now-btn position-absolute" style="top: 25px; right: 25px; z-index: 99999; border-radius: 20px; font-size: 11px; backdrop-filter: blur(10px); color: #00f0ff; border-color: rgba(0, 240, 255, 0.4);">
        Skip Intro ⚡
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
                        <span class="mr-2 d-none d-sm-inline" style="color: #f3e8ff;">Search...</span>
                        <kbd class="command-bar-kbd">Ctrl K</kbd>
                    </div>

                    <!-- Streamlined Notification Hub Dropdown -->
                    <div class="dropdown mr-2">
                        <div class="command-bar-trigger d-flex align-items-center justify-content-center pointer position-relative dropdown-toggle"
                             id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                             title="Notifications & Messages" style="width: 36px; padding: 0;">
                            <i class="icon-bell" style="color: #8A4FFF; font-size: 14px;"></i>
                            <span class="status-dot-led position-absolute" style="top: 4px; right: 4px; width: 6px; height: 6px; background-color: #8A4FFF; box-shadow: 0 0 6px #8A4FFF;"></span>
                        </div>

                        <!-- Notification Dropdown Menu -->
                        <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="notificationDropdown"
                             style="background: #130924; border: 1px solid #8A4FFF; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35); width: 280px;">
                            
                            <!-- Header -->
                            <div class="d-flex align-items-center justify-content-between pb-2 mb-2" style="border-bottom: 1px solid rgba(138,79,255,0.25);">
                                <div class="font-weight-bold" style="color: #f3e8ff; font-size: 13px;">
                                    <i class="icon-bell mr-1" style="color: #8A4FFF;"></i> Notifications
                                </div>
                                <a href="{{ url('/notifications') }}" class="small" style="color: #a855f7;">View All</a>
                            </div>

                            <!-- Quick Action Hub Tiles -->
                            <div class="row m-0 text-center mb-2">
                                <div class="col-4 p-1">
                                    <a href="{{ url('/notifications') }}" class="d-block p-2 text-decoration-none rounded" style="background: #180c30; border: 1px solid rgba(138,79,255,0.25); color: #f3e8ff;">
                                        <i class="icon-bell d-block mb-1" style="color: #8A4FFF; font-size: 14px;"></i>
                                        <span style="font-size: 9px;">Alerts</span>
                                    </a>
                                </div>
                                <div class="col-4 p-1">
                                    <a href="{{ url('/messages') }}" class="d-block p-2 text-decoration-none rounded" style="background: #180c30; border: 1px solid rgba(138,79,255,0.25); color: #f3e8ff;">
                                        <i class="icon-message d-block mb-1" style="color: #a855f7; font-size: 14px;"></i>
                                        <span style="font-size: 9px;">Inbox</span>
                                    </a>
                                </div>
                                <div class="col-4 p-1">
                                    <a href="{{ url('/referrals') }}" class="d-block p-2 text-decoration-none rounded" style="background: #180c30; border: 1px solid rgba(138,79,255,0.25); color: #f3e8ff;">
                                        <i class="icon-users d-block mb-1" style="color: #c084fc; font-size: 14px;"></i>
                                        <span style="font-size: 9px;">Team</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Recent Notification Feed -->
                            <div class="notification-list text-left" style="max-height: 160px; overflow-y: auto;">
                                <div class="p-2 mb-1 rounded position-relative" style="background: rgba(138,79,255,0.08); border-left: 2px solid #8A4FFF;">
                                    <div class="small font-weight-bold" style="color: #f3e8ff; font-size: 11px;">Runtime Agent Connected</div>
                                    <div style="color: #d8b4fe; font-size: 9px;">Local automation engine running</div>
                                </div>
                                <div class="p-2 rounded" style="background: rgba(255,255,255,0.02);">
                                    <div class="small font-weight-bold" style="color: #f3e8ff; font-size: 11px;">System Operational</div>
                                    <div style="color: #94a3b8; font-size: 9px;">All services active</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Streamlined User Profile Capsule with Dropdown Financial Summary -->
                    <div class="user-data d-flex align-items-center px-2 py-1 dropdown-toggle dropdown pointer"
                         id="dropdownMenuOffset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                        <div class="profile-pic d-flex mr-1">
                            <img src="{{ Auth::user()->avatar_url ?? asset('v8main/img/user.jpg') }}" alt="" class="user-img m-auto" style="border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="d-none d-md-flex flex-column user-text px-2 mr-1 position-relative text-left font-weight-bold">
                            <div class="username text-capitalize">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="user-level text-uppercase">{{ Auth::user()->role ?? 'Admin' }}</div>
                        </div>

                        <!-- Dropdown Menu with Integrated Financial Summary -->
                        <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="dropdownMenuOffset"
                             style="background: #130924; border: 1px solid #8A4FFF; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35); min-width: 250px;">
                            
                            <!-- Financial Summary Section -->
                            <div class="mb-3 p-2 text-left" style="background: #180c30; border: 1px solid rgba(138,79,255,0.3); border-radius: 8px;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small" style="color: #d8b4fe;">Wallet:</span>
                                    <span class="font-weight-bold" style="color: #a855f7;">{{ $userBalanceFormatted }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small" style="color: #d8b4fe;">Points:</span>
                                    <span class="font-weight-bold" style="color: #ffb703;">{{ number_format($userPoints) }} Pts</span>
                                </div>
                                <button class="btn btn-pay-due btn-block btn-sm mt-1" data-toggle="modal" data-target="#payDueModal">
                                    <i class="icon-basket mr-1"></i> دفع الفلوس ({{ $totalDueFormatted }})
                                </button>
                            </div>

                            <a class="dropdown-item py-2" href="{{ url('/admin/dashboard') }}"><i class="icon-user mr-2"></i>Admin Panel</a>
                            <a class="dropdown-item py-2" href="{{ url('/profile') }}"><i class="icon-user mr-2"></i>My Profile</a>
                            <a class="dropdown-item py-2" href="{{ url('/billing/invoices') }}"><i class="icon-doc mr-2"></i>Billing &amp; Invoices</a>
                            <a class="dropdown-item py-2" href="{{ url('/financial/transactions') }}"><i class="icon-credit-card mr-2"></i>Wallet &amp; Transactions</a>
                            <a class="dropdown-item py-2" href="{{ url('/settings/automations') }}"><i class="icon-cog mr-2"></i>Settings</a>
                            <a class="dropdown-item py-2" href="#" id="resetWelcomeIntroBtn"><i class="icon-bell mr-2"></i>Reset Welcome Intro</a>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display:none;">
                                @csrf
                            </form>
                            <a class="dropdown-item py-2 text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<section class="content mb-5">
    <div class="container-fluid">
        <div class="row m-0">

            <!-- LEFT COLUMN -->
            <div class="col-lg-4 col-12 mt-5 p-0 left">
                <div class="row">

                    <!-- MY ISAAS -->
                    <div class="academy col-md-6 col-lg-12">
                        <h2 class="item-title pointer" onclick="toggler('.academy>.wrapper')">
                            MY ISAAS
                        </h2>
                        <div class="wrapper pb-2">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">iSaaS Platforms &amp; SSO Systems</span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sso/erp') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="dowloads"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">ERP System</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sso/crm') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="ad"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">CRM System</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sso/goldsaversys') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="courses"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Gold POS</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sso/affsys') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="monster"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Affiliate POS</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sso/bookingsys') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="events"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Booking System</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/isaas/contracts') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="books"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Contracts</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MY WORKFLOW -->
                    <div class="notification mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                        <h2 class="item-title pointer" onclick="toggler('.notification>.wrapper')">
                            MY WORKFLOW
                        </h2>
                        <div class="wrapper pb-2">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">Automation &amp; System Tools</span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/runtime/download') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="add-browser"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Runtime Agent</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/settings/automations') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="mange-browser"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Automations</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/settings/automations') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="add"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Automations</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/points') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="mange-noti"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Points</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/notifications') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="search"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Notifications</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/messages') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="all"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Messages</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- CENTER COLUMN (PURE THREE.JS 3D HOLOGRAM) -->
            <div class="col-lg-4 col-12 mt-5 position-relative center-logo">
                <div id="logo-it" style="width: 286px; height: 285px; margin: 0 auto; background: none !important;"></div>

                <!-- Username SVG & Title -->
                <div class="end-icon mt-4" id="incenter_username" style="display: block;">
                    <svg class="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="22px" height="23px">
                        <path fill-rule="evenodd" fill="#8A4FFF"
                              d="M19.263,5.321 C18.960,5.321 18.669,5.267 18.399,5.169 C18.399,5.169 18.399,5.169 17.269,6.329 C18.315,7.690 18.939,9.406 18.939,11.268 C18.939,13.131 18.314,14.847 17.269,16.208 C17.269,16.208 17.269,16.208 18.399,17.367 C18.669,17.270 18.960,17.216 19.263,17.216 C20.697,17.216 21.859,18.408 21.859,19.880 C21.859,21.351 20.697,22.544 19.263,22.544 C17.830,22.544 16.667,21.351 16.667,19.880 C16.667,19.569 16.720,19.271 16.815,19.993 C16.815,18.993 16.815,18.993 15.645,17.792 C14.375,18.717 12.824,19.261 11.152,19.261 C9.479,19.261 7.928,18.717 6.658,17.792 C6.658,17.792 6.658,17.792 5.488,18.993 C5.583,19.270 5.636,19.569 5.636,19.880 C5.636,21.351 4.474,22.544 3.040,22.544 C1.607,22.544 0.445,21.351 0.445,19.880 C0.445,18.408 1.607,17.216 3.040,17.216 C3.343,17.216 3.634,17.270 3.904,17.368 C3.904,17.368 3.904,17.368 5.034,16.208 C3.989,14.847 3.365,13.131 3.365,11.269 C3.365,9.406 3.989,7.690 5.034,6.329 C5.034,6.329 5.034,6.329 3.904,5.170 C3.634,5.267 3.343,5.321 3.040,5.321 C1.607,5.321 0.445,4.129 0.445,2.657 C0.445,1.186 1.607,-0.007 3.040,-0.007 C4.474,-0.007 5.636,1.186 5.636,2.657 C5.636,2.968 5.583,3.267 5.488,3.544 C5.488,3.544 5.488,3.544 6.658,4.745 C7.928,3.820 9.479,3.276 11.152,3.276 C12.824,3.276 14.375,3.820 15.645,4.745 C15.645,4.745 15.645,4.745 16.815,3.544 C16.720,3.267 16.667,2.968 16.667,2.657 C16.667,1.186 17.830,-0.007 19.263,-0.007 C20.697,-0.007 21.859,1.186 21.859,2.657 C21.859,4.129 20.697,5.321 19.263,5.321 ZM11.152,4.813 C7.678,4.813 4.862,7.704 4.862,11.269 C4.862,12.834 5.405,14.270 6.309,15.387 C6.396,14.523 6.577,13.485 7.023,13.303 L9.354,12.349 C9.354,12.349 9.354,12.349 9.900,11.876 C10.044,11.752 10.257,11.760 10.391,11.897 L11.152,12.672 C11.152,12.672 11.152,12.672 11.911,11.897 C12.045,11.760 12.258,11.751 12.402,11.876 L12.948,12.349 C12.948,12.349 12.948,12.349 15.280,13.303 C15.726,13.485 15.907,14.523 15.994,15.388 C16.898,14.270 17.441,12.834 17.441,11.269 C17.441,7.704 14.625,4.813 11.152,4.813 ZM11.152,11.691 C9.719,11.714 8.840,10.426 8.818,8.303 C8.803,6.753 9.640,5.948 11.152,5.948 C12.697,5.948 13.486,6.753 13.486,8.303 C13.486,11.788 11.152,11.691 11.152,11.691 Z"/>
                    </svg>
                    <svg class="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="169px" height="14px">
                        <path fill-rule="evenodd" fill="#8A4FFF"
                              d="M165.698,5.676 C164.323,5.676 163.184,4.687 162.953,3.386 L115.214,3.386 L108.007,12.328 L108.146,12.328 L107.182,13.259 C107.123,13.235 107.032,13.182 106.920,13.105 L62.564,13.105 C62.531,13.105 62.500,13.098 62.469,13.093 C62.286,13.221 62.146,13.296 62.124,13.270 L61.976,13.086 L61.897,13.086 L60.781,11.712 L60.869,11.712 L54.158,3.386 L6.436,3.386 C6.204,4.687 5.058,5.676 3.677,5.676 C2.129,5.676 0.874,4.433 0.874,2.901 C0.874,1.368 2.129,0.125 3.677,0.125 C4.901,0.125 5.939,0.903 6.321,1.985 L54.193,1.985 C54.375,1.985 54.538,2.055 54.663,2.168 C54.785,2.092 54.872,2.051 54.889,2.071 L62.659,11.712 L106.713,11.712 L114.483,2.071 C114.500,2.050 114.592,2.094 114.719,2.174 C114.844,2.057 115.011,1.985 115.195,1.985 L163.066,1.985 C163.447,0.903 164.480,0.125 165.698,0.125 C167.239,0.125 168.488,1.368 168.488,2.901 C168.488,4.433 167.239,5.676 165.698,5.676 Z"/>
                    </svg>
                    <h2 class="item-title text-center d-block pb-0 mt-2 text-uppercase" style="font-size: 17px;">
                        {{ Auth::user()->name ?? 'User' }}
                    </h2>
                    <h6 class="text-center d-block pb-0 mt-2 text-uppercase" style="color: #ff7c20; font-size: 9px;">
                        {{ Auth::user()->role ?? 'Admin' }}
                    </h6>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-4 col-12 mt-5 p-0 right">
                <div class="row">

                    <!-- MY TOOLS -->
                    <div class="site clearfix col-md-6 col-lg-12">
                        <h2 class="item-title float-right pointer" onclick="toggler('.site>.main')">
                            MY TOOLS
                        </h2>
                        <div class="clearfix"></div>
                        <div class="wrapper main clearfix pb-2 float-right">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">Marketplace &amp; Tools</span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/marketplace/services') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="sites"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Marketplace</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/marketplace/dashboard') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="sales"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Seller Portal</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/whatsapp-sender') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="popup"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">WhatsApp</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/fbmb') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="post"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">FB Marketing</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/sms-payment-gateway') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="gl"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">SMS Gateway</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/settings/automations') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="shorten"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Settings</div>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!-- MY FINANCE -->
                    <div class="services clearfix mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                        <h2 class="item-title float-right pointer" onclick="toggler('.services>.main')">
                            MY FINANCE
                        </h2>
                        <div class="clearfix"></div>
                        <div class="wrapper main clearfix pb-2 float-right">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">Wallet, Billing &amp; Subscriptions</span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/financial/add-balance') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="camp"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Add Balance</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/billing/invoices') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="sales"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Invoices</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/subscriptions/plans') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="reports"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Plans</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/financial/transactions') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="full"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Transactions</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/vouchers') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="upload"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Vouchers</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/financial/withdrawals') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="setting"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Withdrawals</div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Pause Button -->
<svg class="col-xs-12 ml-3 mb-3 pause-btn active pointer hidden" version="1.1" xmlns="http://www.w3.org/2000/svg"
     xmlns:xlink="http://www.w3.org/1999/xlink" width="34" height="34" viewBox="0 0 34 34"
     style="bottom: 5px;display: inline;position: absolute;">
    <g id="Group-1">
        <path id="fill"
              d="M16.588,22.274 C16.489,22.322 16.383,22.345 16.279,22.345 C16.112,22.345 15.948,22.287 15.815,22.174 C11.842,18.807 11.842,18.807 11.842,18.807 C11.842,18.807 9.727,18.807 9.727,18.807 C9.325,18.808 9.000,18.475 9.000,18.064 C9.000,15.103 9.000,15.103 9.000,15.103 C9.000,14.693 9.325,14.360 9.727,14.360 C11.843,14.360 11.843,14.360 11.843,14.360 C11.843,14.360 15.815,10.993 15.815,10.993 C16.032,10.809 16.333,10.770 16.588,10.893 C16.843,11.016 17.006,11.278 17.006,11.566 C17.005,21.602 17.005,21.602 17.005,21.602 C17.005,21.602 17.005,21.602 17.005,21.602 C17.005,21.890 16.843,22.152 16.588,22.274 zM19.746,20.443 C19.554,20.443 19.369,20.365 19.232,20.225 C19.135,20.125 19.135,20.125 19.135,20.125 C18.880,19.865 18.850,19.453 19.065,19.157 C19.609,18.407 19.896,17.517 19.896,16.584 C19.896,15.580 19.570,14.639 18.953,13.862 C18.718,13.566 18.740,13.137 19.003,12.868 C19.100,12.768 19.100,12.768 19.100,12.768 C19.245,12.620 19.440,12.539 19.650,12.552 C19.855,12.562 20.046,12.661 20.176,12.824 C21.032,13.895 21.485,15.195 21.485,16.584 C21.485,17.878 21.085,19.109 20.328,20.145 C20.202,20.317 20.008,20.425 19.798,20.441 C19.780,20.442 19.763,20.443 19.746,20.443 zM22.277,23.000 C22.267,23.001 22.257,23.001 22.247,23.001 C22.054,23.001 21.870,22.923 21.733,22.784 C21.637,22.686 21.637,22.686 21.637,22.686 C21.371,22.413 21.353,21.977 21.595,21.682 C22.767,20.257 23.413,18.446 23.413,16.584 C23.413,14.647 22.721,12.783 21.467,11.334 C21.212,11.040 21.225,10.595 21.496,10.317 C21.591,10.219 21.591,10.219 21.591,10.219 C21.733,10.074 21.916,9.994 22.127,10.001 C22.327,10.007 22.516,10.097 22.649,10.250 C24.166,11.996 25.001,14.246 25.001,16.584 C25.002,18.834 24.221,21.019 22.802,22.737 C22.671,22.896 22.481,22.992 22.277,23.000 z"
              fill="rgb(33, 151, 154)"/>
        <g id="Ellipse-1">
            <path id="stroke"
                  d="M17.000,1.192 C25.730,1.192 32.808,8.270 32.808,17.000 C32.808,25.730 25.730,32.808 17.000,32.808 C8.270,32.808 1.192,25.730 1.192,17.000 C1.192,8.270 8.270,1.192 17.000,1.192 z"
                  fill="none" stroke="rgb(33, 151, 154)" stroke-width="2.51"/>
        </g>
    </g>
</svg>

<!-- Fancy Sci-Fi Glass Payment Modal -->
<div class="modal fade modal-scifi-glass" id="payDueModal" tabindex="-1" role="dialog" aria-labelledby="payDueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="background: rgba(14, 9, 32, 0.96); backdrop-filter: blur(25px); border: 1.5px solid #00f0ff; border-radius: 20px;">
            <div class="modal-header d-flex align-items-center justify-content-between p-3" style="border-bottom: 1px solid rgba(0, 240, 255, 0.2);">
                <h4 class="modal-title font-weight-bold text-cyan" id="payDueModalLabel">
                    <i class="icon-basket mr-2"></i>دفع الفلوس والمستحقات المترتبة
                </h4>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 28px; color: #00f0ff;">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 text-left">
                <!-- Summary Header Card -->
                <div class="p-3 mb-4 rounded d-flex align-items-center justify-content-between" style="background: rgba(244, 63, 94, 0.12); border: 1px solid #f43f5e; border-radius: 14px;">
                    <div>
                        <span class="text-uppercase text-muted d-block" style="font-size: 11px;">إجمالي المبالغ المستحقة</span>
                        <h2 class="m-0 font-weight-bold" style="color: #f43f5e;">{{ $totalDueFormatted }}</h2>
                    </div>
                    <div class="text-right">
                        <span class="text-uppercase text-muted d-block" style="font-size: 11px;">الرصيد المتاح بالمحفظة</span>
                        <h4 class="m-0 font-weight-bold text-cyan">{{ $userBalanceFormatted }}</h4>
                    </div>
                </div>

                @if($userBalanceVal >= $totalDueAmount && $totalDueAmount > 0)
                    <div class="alert alert-success d-flex align-items-center mb-4" style="background: rgba(16, 185, 129, 0.15); border-color: #10b981; color: #10b981; border-radius: 12px;">
                        <i class="icon-check mr-2" style="font-size: 20px;"></i>
                        <span>رصيد المحفظة يغطي المستحقات بالكامل. يمكنك الدفع مباشرة من الرصيد.</span>
                    </div>
                @elseif($totalDueAmount > 0)
                    <div class="alert alert-warning d-flex align-items-center mb-4" style="background: rgba(245, 158, 11, 0.15); border-color: #f59e0b; color: #f59e0b; border-radius: 12px;">
                        <i class="icon-attention mr-2" style="font-size: 20px;"></i>
                        <span>رصيد المحفظة الحالي غير كافٍ لتغطية كل المستحقات. يرجى شحن الرصيد أو الدفع عبر بوابة الدفع الإلكتروني.</span>
                    </div>
                @endif

                <!-- Unpaid Invoices Table -->
                <h5 class="font-weight-bold text-light mb-3">فواتير المستحقات المترتبة:</h5>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover table-dark mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-cyan">
                                <th>رقم الفاتورة</th>
                                <th>البيان</th>
                                <th>الحالة</th>
                                <th>المبلغ المستحق</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unpaidInvoices as $invoice)
                                <tr>
                                    <td>#{{ $invoice->id }}</td>
                                    <td>{{ $invoice->title ?? $invoice->description ?? 'فاتورة خدمات' }}</td>
                                    <td><span class="badge badge-warning">غير مدفوعة</span></td>
                                    <td class="font-weight-bold text-danger">{{ number_format($invoice->unpaid, 2) }} {{ $currencySymbol }}</td>
                                    <td>
                                        <a href="{{ url('/billing/invoices/' . $invoice->id) }}" class="btn btn-outline-info btn-sm" style="border-radius: 8px;">دفع الفاتورة</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="icon-check d-block mb-2" style="font-size: 30px; color: #10b981;"></i>
                                        لا توجد أي فواتير مستحقة الدفع حالياً. حسابك في حالة ممتازة!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex align-items-center justify-content-between p-3" style="border-top: 1px solid rgba(0, 240, 255, 0.2);">
                <a href="{{ url('/financial/transactions') }}" class="btn btn-outline-light btn-sm" style="border-radius: 10px;">
                    <i class="icon-plus mr-1"></i>شحن المحفظة
                </a>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm mr-2" data-dismiss="modal" style="border-radius: 10px;">إغلاق</button>
                    <a href="{{ url('/billing/invoices') }}" class="btn btn-primary btn-sm px-4" style="border-radius: 10px; background: linear-gradient(135deg, #00f0ff, #d946ef); border: none; font-weight: bold;">
                        الانتقال لصفحة الفواتير
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- UX FEATURE 1: Universal Command Bar (Ctrl + K) Modal -->
<div class="modal fade modal-command-bar" id="commandBarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body p-4 text-left">
                <div class="d-flex align-items-center mb-3">
                    <input type="text" id="commandSearchInput" class="command-search-input" placeholder="Search systems, tools, invoices, actions... (e.g. ERP, CRM, Gold, Wallet)" autofocus autocomplete="off">
                </div>
                <div id="commandResultsList" class="command-results-container" style="max-height: 320px; overflow-y: auto;">
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
                        <div><i class="icon-basket text-amber mr-2"></i><strong>Pay Due Amount (دفع الفلوس)</strong> <span class="text-muted small ml-2">- Quick Settlement Modal</span></div>
                        <span class="badge badge-outline-warning">Action</span>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted small mt-3 pt-2 border-top border-secondary">
                    <span>Navigation: <kbd class="command-bar-kbd">↑</kbd> <kbd class="command-bar-kbd">↓</kbd> to select, <kbd class="command-bar-kbd">Enter</kbd> to open</span>
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
        l.setAttribute('data-href', l.getAttribute('href'));
    });

    $('*[data-href]').click(function () {
        location.assign($(this).data('href'));
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
