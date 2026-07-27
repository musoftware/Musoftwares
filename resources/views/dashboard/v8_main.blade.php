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
            <div class="col-lg-2 col-md-2 col-4">
                <div class="logo-parent d-flex align-items-center">
                    <img class="logo pointer" src="{{ asset('v8main/img/amc8.png') }}"
                         data-href="{{ url('/dashboard') }}"
                         alt="Musoftwares">
                </div>
            </div>

            <!-- Center icons -->
            <div class="center-icons text-center col-lg-2 col-md-2 text-light col-4">
                <div class="row d-flex align-items-center justify-content-center">
                    <div class="px-2 hover" data-href="{{ url('/marketplace/services') }}">
                        <i class="icon-social d-block"></i>
                        <h3 class="m-auto">Market</h3>
                    </div>
                    <div class="px-2 hover" data-href="{{ url('/profile') }}">
                        <i class="icon-user d-block"></i>
                        <h3 class="m-auto">Profile</h3>
                    </div>
                </div>
            </div>

            <!-- User Data & Financial Widgets -->
            <div class="user-reference col-lg-8 col-md-8 col-12 mt-2 mt-md-0">
                <div class="text-light flex-row d-flex align-items-center justify-content-end text-center">
                    <ul class="d-flex align-items-center mb-0 mr-4 pl-0">
                        <li class="hover active list-inline-item d-flex align-items-center justify-content-center"
                            data-href="{{ url('/referrals') }}">
                            <i class="icon-users"></i>
                        </li>
                        <li class="hover active list-inline-item d-flex align-items-center justify-content-center"
                            data-href="{{ url('/messages') }}">
                            <i class="icon-message"></i>
                        </li>
                        <li class="hover active list-inline-item d-flex align-items-center justify-content-center"
                            data-href="{{ url('/notifications') }}">
                            <i class="icon-bell"></i>
                        </li>
                    </ul>

                    <!-- Financial Widgets & Pay Due Button -->
                    <div class="d-flex align-items-center mr-4 header-financial-widgets">
                        <div class="financial-pill wallet-pill mr-3 d-flex align-items-center px-3 py-1" title="Wallet Balance">
                            <i class="icon-credit-card text-cyan mr-2" style="font-size: 16px;"></i>
                            <div class="text-left">
                                <span class="d-block pill-label">Wallet</span>
                                <strong class="pill-value text-cyan">{{ $userBalanceFormatted }}</strong>
                            </div>
                        </div>

                        <div class="financial-pill points-pill mr-3 d-flex align-items-center px-3 py-1" title="Reward Points">
                            <i class="icon-star text-amber mr-2" style="font-size: 16px;"></i>
                            <div class="text-left">
                                <span class="d-block pill-label">Points</span>
                                <strong class="pill-value text-amber">{{ number_format($userPoints) }} Pts</strong>
                            </div>
                        </div>

                        <button class="btn btn-pay-due btn-sm d-flex align-items-center px-3 py-2" data-toggle="modal" data-target="#payDueModal">
                            <i class="icon-basket mr-1" style="font-size: 14px;"></i>
                            <span>دفع الفلوس</span>
                            @if($totalDueAmount > 0)
                                <span class="badge badge-danger ml-2 px-2 py-1 pulse-badge">{{ $totalDueFormatted }}</span>
                            @else
                                <span class="badge badge-success ml-2 px-2 py-1" style="font-size: 10px;">0 مستحقات</span>
                            @endif
                        </button>
                    </div>

                    <div class="user-data d-flex align-items-center px-2 py-1 dropdown-toggle dropdown pointer"
                         id="dropdownMenuOffset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                        <div class="profile-pic d-flex mr-1">
                            <img src="{{ Auth::user()->avatar_url ?? asset('v8main/img/user.jpg') }}" alt="" class="user-img m-auto" style="border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="d-none d-md-flex flex-column user-text px-2 mr-1 position-relative text-left font-weight-bold">
                            <div class="username text-capitalize">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="user-level text-uppercase">{{ Auth::user()->role ?? 'Admin' }}</div>
                        </div>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuOffset"
                             style="background-color: rgb(0, 0, 0);color: rgb(33, 151, 154);border-bottom: rgb(33, 151, 154) 1px solid;border-left: 1px solid rgb(33, 151, 154);border-right: 1px solid rgb(33, 151, 154);position: absolute;will-change: transform;top: 0px;left: 0px;transform: translate3d(1px, 56px, 0px);">
                            <a class="dropdown-item" href="{{ url('/admin/dashboard') }}">Admin Panel</a>
                            <a class="dropdown-item" href="{{ url('/profile') }}">My Profile</a>
                            <a class="dropdown-item" href="{{ url('/billing/invoices') }}">Billing</a>
                            <a class="dropdown-item" href="{{ url('/financial/transactions') }}">Wallet</a>
                            <a class="dropdown-item" href="{{ url('/settings/backup') }}">Settings</a>
                            <a class="dropdown-item" href="#" id="resetWelcomeIntroBtn">Reset Welcome Intro</a>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display:none;">
                                @csrf
                            </form>
                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
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
                                <div class="col-4 px-0 pointer" data-href="{{ url('/settings/backup') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="add"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Backup</div>
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

            <!-- CENTER COLUMN -->
            <div class="col-lg-4 col-12 mt-5 position-relative center-logo">
                <div class="animation-start hidden" id="logo-it"></div>
                <div class="basic hidden"></div>

                <!-- Username SVG & Title -->
                <div class="end-icon mt-5" id="incenter_username" style="display: none;">
                    <svg class="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="22px" height="23px">
                        <path fill-rule="evenodd" fill="rgb(41, 186, 189)"
                              d="M19.263,5.321 C18.960,5.321 18.669,5.267 18.399,5.169 C18.399,5.169 18.399,5.169 17.269,6.329 C18.315,7.690 18.939,9.406 18.939,11.268 C18.939,13.131 18.314,14.847 17.269,16.208 C17.269,16.208 17.269,16.208 18.399,17.367 C18.669,17.270 18.960,17.216 19.263,17.216 C20.697,17.216 21.859,18.408 21.859,19.880 C21.859,21.351 20.697,22.544 19.263,22.544 C17.830,22.544 16.667,21.351 16.667,19.880 C16.667,19.569 16.720,19.271 16.815,19.993 C16.815,18.993 16.815,18.993 15.645,17.792 C14.375,18.717 12.824,19.261 11.152,19.261 C9.479,19.261 7.928,18.717 6.658,17.792 C6.658,17.792 6.658,17.792 5.488,18.993 C5.583,19.270 5.636,19.569 5.636,19.880 C5.636,21.351 4.474,22.544 3.040,22.544 C1.607,22.544 0.445,21.351 0.445,19.880 C0.445,18.408 1.607,17.216 3.040,17.216 C3.343,17.216 3.634,17.270 3.904,17.368 C3.904,17.368 3.904,17.368 5.034,16.208 C3.989,14.847 3.365,13.131 3.365,11.269 C3.365,9.406 3.989,7.690 5.034,6.329 C5.034,6.329 5.034,6.329 3.904,5.170 C3.634,5.267 3.343,5.321 3.040,5.321 C1.607,5.321 0.445,4.129 0.445,2.657 C0.445,1.186 1.607,-0.007 3.040,-0.007 C4.474,-0.007 5.636,1.186 5.636,2.657 C5.636,2.968 5.583,3.267 5.488,3.544 C5.488,3.544 5.488,3.544 6.658,4.745 C7.928,3.820 9.479,3.276 11.152,3.276 C12.824,3.276 14.375,3.820 15.645,4.745 C15.645,4.745 15.645,4.745 16.815,3.544 C16.720,3.267 16.667,2.968 16.667,2.657 C16.667,1.186 17.830,-0.007 19.263,-0.007 C20.697,-0.007 21.859,1.186 21.859,2.657 C21.859,4.129 20.697,5.321 19.263,5.321 ZM11.152,4.813 C7.678,4.813 4.862,7.704 4.862,11.269 C4.862,12.834 5.405,14.270 6.309,15.387 C6.396,14.523 6.577,13.485 7.023,13.303 L9.354,12.349 C9.354,12.349 9.354,12.349 9.900,11.876 C10.044,11.752 10.257,11.760 10.391,11.897 L11.152,12.672 C11.152,12.672 11.152,12.672 11.911,11.897 C12.045,11.760 12.258,11.751 12.402,11.876 L12.948,12.349 C12.948,12.349 12.948,12.349 15.280,13.303 C15.726,13.485 15.907,14.523 15.994,15.388 C16.898,14.270 17.441,12.834 17.441,11.269 C17.441,7.704 14.625,4.813 11.152,4.813 ZM11.152,11.691 C9.719,11.714 8.840,10.426 8.818,8.303 C8.803,6.753 9.640,5.948 11.152,5.948 C12.697,5.948 13.486,6.753 13.486,8.303 C13.486,11.788 11.152,11.691 11.152,11.691 Z"/>
                    </svg>
                    <svg class="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="169px" height="14px">
                        <path fill-rule="evenodd" fill="rgb(41, 186, 189)"
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
                                <div class="col-4 px-0 pointer" onclick="toggler('.site>.popup-wrap')">
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
                                <div class="col-4 px-0 pointer" data-href="{{ url('/settings/backup') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="shorten"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Settings</div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp sub-panel -->
                        <div class="wrapper clearfix popup-wrap pb-2 float-right">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">WhatsApp Tools</span>
                                <span class="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                      onclick="toggler('.site>.main')">
                                    <span class="d-inline-block px-2">BACK</span>
                                    <span class="back-icon d-inline-block position-absolute">
                                        <svg class="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                            <path fill-rule="evenodd" fill="rgb(0, 0, 0)"
                                                  d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z"/>
                                        </svg>
                                    </span>
                                </span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/whatsapp-sender') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="add-popup"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Sender</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/whatsapp-sender') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="mange-pop"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Accounts</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/whatsapp-sender') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="pop-balance"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Messages</div>
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

                        <!-- Transactions sub-panel -->
                        <div class="wrapper clearfix pb-2 campaings-wrap float-right">
                            <div class="mb-3 head pl-4 d-flex align-items-center">
                                <span class="position-relative">Transactions</span>
                                <span class="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                      onclick="toggler('.services>.main')">
                                    <span class="d-inline-block px-2">BACK</span>
                                    <span class="back-icon d-inline-block position-absolute">
                                        <svg class="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                            <path fill-rule="evenodd" fill="rgb(0, 0, 0)"
                                                  d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z"/>
                                        </svg>
                                    </span>
                                </span>
                            </div>
                            <div class="row m-0">
                                <div class="col-4 px-0 pointer" data-href="{{ url('/financial/add-balance') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="add-camp"></div>
                                    </div>
                                    <div class="item-captian text-light text-center text-capitalize py-2">Add Balance</div>
                                </div>
                                <div class="col-4 px-0 pointer" data-href="{{ url('/financial/withdrawals') }}">
                                    <div class="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                        <div class="mange-camp"></div>
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

<!-- JS files -->
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
       THREE.JS WEBGL LIVING REACTIVE 3D HOLOGRAM CORE
       ========================================================================== */
    (function () {
        var container = document.getElementById('logo-it');
        if (!container || typeof THREE === 'undefined') return;

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

        var globeGroup = new THREE.Group();
        scene.add(globeGroup);

        var sphereGeo = new THREE.SphereGeometry(52, 22, 22);
        var sphereMat = new THREE.MeshBasicMaterial({
            color: 0x00f0ff,
            wireframe: true,
            transparent: true,
            opacity: 0.35
        });
        var sphereMesh = new THREE.Mesh(sphereGeo, sphereMat);
        globeGroup.add(sphereMesh);

        var ringGeo1 = new THREE.TorusGeometry(70, 1.2, 16, 64);
        var ringMat1 = new THREE.MeshBasicMaterial({ color: 0xd946ef, transparent: true, opacity: 0.65 });
        var ringMesh1 = new THREE.Mesh(ringGeo1, ringMat1);
        ringMesh1.rotation.x = Math.PI / 3;
        globeGroup.add(ringMesh1);

        var ringGeo2 = new THREE.TorusGeometry(82, 1.0, 16, 48);
        var ringMat2 = new THREE.MeshBasicMaterial({ color: 0x00f0ff, transparent: true, opacity: 0.5 });
        var ringMesh2 = new THREE.Mesh(ringGeo2, ringMat2);
        ringMesh2.rotation.y = Math.PI / 4;
        globeGroup.add(ringMesh2);

        var particleCount = 500;
        var particleGeo = new THREE.BufferGeometry();
        var positions = new Float32Array(particleCount * 3);
        var colors = new Float32Array(particleCount * 3);

        var colorCyan = new THREE.Color(0x00f0ff);
        var colorMagenta = new THREE.Color(0xd946ef);

        for (var i = 0; i < particleCount; i++) {
            var u = Math.random();
            var v = Math.random();
            var theta = u * 2.0 * Math.PI;
            var phi = Math.acos(2.0 * v - 1.0);
            var r = 56 + Math.random() * 26;

            positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
            positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
            positions[i * 3 + 2] = r * Math.cos(phi);

            var mixColor = Math.random() > 0.5 ? colorCyan : colorMagenta;
            colors[i * 3] = mixColor.r;
            colors[i * 3 + 1] = mixColor.g;
            colors[i * 3 + 2] = mixColor.b;
        }

        particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        particleGeo.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        var particleMat = new THREE.PointsMaterial({
            size: 2.2,
            vertexColors: true,
            transparent: true,
            opacity: 0.85
        });
        var particleSystem = new THREE.Points(particleGeo, particleMat);
        globeGroup.add(particleSystem);

        var mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', function (e) {
            var windowHalfX = window.innerWidth / 2;
            var windowHalfY = window.innerHeight / 2;
            mouseX = (e.clientX - windowHalfX) * 0.0008;
            mouseY = (e.clientY - windowHalfY) * 0.0008;
        });

        $('.item, .btn-pay-due, header .user-reference ul li').hover(function () {
            var targetColorHex = 0x00f0ff;
            if ($(this).hasClass('btn-pay-due')) {
                targetColorHex = 0xf43f5e;
            } else if ($(this).parents('.left').length) {
                targetColorHex = 0x00f0ff;
            } else if ($(this).parents('.right').length) {
                targetColorHex = 0xd946ef;
            } else {
                targetColorHex = 0x8b5cf6;
            }
            sphereMat.color.setHex(targetColorHex);
            sphereMat.opacity = 0.75;
            ringMat1.opacity = 0.95;
            globeGroup.scale.set(1.1, 1.1, 1.1);
        }, function () {
            sphereMat.color.setHex(0x00f0ff);
            sphereMat.opacity = 0.35;
            ringMat1.opacity = 0.65;
            globeGroup.scale.set(1.0, 1.0, 1.0);
        });

        function animate() {
            requestAnimationFrame(animate);

            globeGroup.rotation.y += 0.008 + mouseX * 0.1;
            globeGroup.rotation.x += (mouseY - globeGroup.rotation.x) * 0.05;

            ringMesh1.rotation.z += 0.012;
            ringMesh2.rotation.z -= 0.015;
            particleSystem.rotation.y -= 0.004;

            renderer.render(scene, camera);
        }
        animate();
    })();
</script>

</body>
</html>
