import React, { useState, useEffect } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import type { DashboardStats } from './types';

interface V8DashboardProps {
    stats?: DashboardStats;
}

// Inline SVG icon helper
function Icon({ d, size = 28 }: { d: string | string[]; size?: number }) {
    const paths = Array.isArray(d) ? d : [d];
    return (
        <svg
            className="item-icon"
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            {paths.map((p, i) => <path key={i} d={p} />)}
        </svg>
    );
}

export default function V8Dashboard({ stats }: V8DashboardProps) {
    const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;
    const user = auth?.user;
    const userName = user?.name ?? 'User';

    // Which wrapper is open per section
    const [open, setOpen] = useState<Record<string, string | null>>({
        academy: 'wrapper',
        notification: 'wrapper',
        site: 'main',
        services: 'main',
    });

    // Preloader state
    const [preloaderDone, setPreloaderDone] = useState(false);
    const [showUsername, setShowUsername] = useState(false);

    useEffect(() => {
        // Mirrors plugins.js: preloader fades at 5s, panels open, username shows at 6.5s
        const t1 = setTimeout(() => setPreloaderDone(true), 5000);
        const t2 = setTimeout(() => setShowUsername(true), 6500);
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, []);

    // toggler: show/hide sub-wrappers (mirrors jQuery slideToggle + .siblings().hide())
    function toggler(section: string, panel: string) {
        setOpen(prev => ({
            ...prev,
            [section]: prev[section] === panel ? null : panel,
        }));
    }

    const isOpen = (section: string, panel: string) => open[section] === panel;

    return (
        <>
            <Head title={`${userName} - MU8`} />

            {/* Load original v8_main CSS from /public/v8main/ */}
            <link rel="stylesheet" href="/v8main/css/bootstrap.min.css" />
            <link rel="stylesheet" href="/v8main/css/style.css" />

            <style>{`
                /* Only override: inline SVG icons replace sprite divs */
                .item-icon {
                    stroke: rgb(41, 186, 189);
                    fill: none;
                    transition: stroke .3s, transform .3s;
                }
                .item:hover .item-icon { stroke: #ff7c20; transform: scale(1.15); }
                /* Smooth show/hide wrapper */
                .v8-wrapper-visible { display: block; }
            `}</style>

            {/* ── PRELOADER ── */}
            {!preloaderDone && (
                <div className="preloader-wrapper">
                    <div className="preloader">
                        <div className="loading-Recovered" />
                        <div className="intro text-light">{`Hello, ${userName}...`}</div>
                        <div className="welcome-wrap">
                            <div className="welcome" />
                        </div>
                        <div className="out text-light mt-3 ml-3">Welcome back!</div>
                    </div>
                </div>
            )}

            {/* ── HEADER ── */}
            <header className="nav">
                <div className="container-fluid">
                    <div className="row">

                        {/* Logo */}
                        <div className="col-lg-5 col-md-3 col-6">
                            <div className="logo-parent d-flex align-items-center">
                                <img
                                    className="logo pointer"
                                    src="/v8main/img/amc8.png"
                                    alt="MU8"
                                    onClick={() => window.location.href = '/dashboard'}
                                    style={{ cursor: 'pointer' }}
                                />
                            </div>
                        </div>

                        {/* Center icons */}
                        <div className="center-icons text-center col-lg-2 col-md-2 text-light col-6">
                            <div className="row d-flex align-items-lg-end align-items-center justify-content-end justify-content-lg-center">
                                <div className="px-2 hover" onClick={() => window.location.href = '/marketplace'}>
                                    <i className="icon-social d-block" />
                                    <h3 className="m-auto">Market</h3>
                                </div>
                                <div className="px-2 hover" onClick={() => window.location.href = '/profile'}>
                                    <i className="icon-user d-block" />
                                    <h3 className="m-auto">Profile</h3>
                                </div>
                            </div>
                        </div>

                        {/* User data */}
                        <div className="user-reference col-lg-5 col-md-7 mt-3 mt-md-0">
                            <div className="text-light flex-row d-flex align-items-center justify-content-between justify-content-md-end text-center">
                                <ul className="d-flex align-items-center mb-0 mr-3 pl-0">
                                    <li className="hover active list-inline-item d-flex align-items-center justify-content-center"
                                        onClick={() => window.location.href = '/notifications'}>
                                        <i className="icon-bell" />
                                    </li>
                                </ul>
                                <div className="user-data d-flex align-items-center px-2 py-1 dropdown pointer"
                                     data-toggle="dropdown">
                                    <div className="profile-pic d-flex mr-1">
                                        <div className="user-img m-auto"
                                             style={{ width: '75%', height: '70%', background: '#29babd', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '16px', fontWeight: 800, color: '#030e11' }}>
                                            {userName.charAt(0).toUpperCase()}
                                        </div>
                                    </div>
                                    <div className="d-none d-md-flex flex-column user-text px-2 mr-1 position-relative text-left font-weight-bold">
                                        <div className="username text-capitalize">{userName}</div>
                                        <div className="user-level text-uppercase">Member</div>
                                    </div>
                                    <div className="dropdown-menu dropdown-menu-right"
                                         style={{ backgroundColor: 'rgb(0,0,0)', color: 'rgb(33,151,154)', borderBottom: 'rgb(33,151,154) 1px solid', borderLeft: '1px solid rgb(33,151,154)', borderRight: '1px solid rgb(33,151,154)' }}>
                                        <Link className="dropdown-item" href="/admin">Admin Panel</Link>
                                        <Link className="dropdown-item" href="/profile">My Profile</Link>
                                        <Link className="dropdown-item" href="/billing">Billing</Link>
                                        <Link className="dropdown-item" href="/wallet">Wallet</Link>
                                        <Link className="dropdown-item" href="/settings">Settings</Link>
                                        <Link className="dropdown-item" href="/logout" method="post" as="button">Logout</Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </header>

            {/* ── CONTENT ── */}
            <section className="content mb-5">
                <div className="container-fluid">
                    <div className="row m-0">

                        {/* ═══════ LEFT ═══════ */}
                        <div className="col-lg-4 col-12 mt-5 p-0 left">
                            <div className="row">

                                {/* MY ISAAS */}
                                <div className="academy col-md-6 col-lg-12">
                                    <h2 className="item-title pointer"
                                        onClick={() => toggler('academy', 'wrapper')}>
                                        MY ISAAS
                                    </h2>
                                    <div className="wrapper pb-2" style={{ display: isOpen('academy', 'wrapper') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">iSaaS Contracts &amp; Projects</span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/contracts', icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2l0 6 6 0 M16 13H8 M16 17H8 M10 9H8', label: 'Contracts' },
                                                { href: '/isaas', icon: 'M2 3h20v14H2z M8 21h8 M12 17v4', label: 'iSaaS' },
                                                { href: '/erp', icon: 'M22 12h-4l-3 9L9 3l-3 9H2', label: 'ERP' },
                                                { href: '/crm', icon: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M9 7a4 4 0 100 8 4 4 0 000-8z M23 21v-2a4 4 0 00-3-3.87 M16 3.13a4 4 0 010 7.75', label: 'CRM' },
                                                { href: '/invoices', icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M12 18v-6 M9 15h6', label: 'Invoices' },
                                                { href: '/tasks', icon: 'M9 11l3 3L22 4 M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11', label: 'Tasks' },
                                            ].map(({ href, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer" onClick={() => window.location.href = href}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => (
                                                                <path key={i} d={i === 0 ? seg : `M${seg}`} />
                                                            ))}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                {/* MY WORKFLOW */}
                                <div className="notification mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                                    <h2 className="item-title pointer"
                                        onClick={() => toggler('notification', 'wrapper')}>
                                        MY WORKFLOW
                                    </h2>
                                    <div className="wrapper pb-2" style={{ display: isOpen('notification', 'wrapper') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Automation &amp; AI Agents</span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/ai-agents',   icon: 'M2 2h20v8H2z M2 14h20v8H2z M6 6v.01 M6 18v.01', label: 'AI Agents' },
                                                { href: '/automation',  icon: 'M13 2L3 14h9l-1 8 10-12h-9l1-8z', label: 'Automation' },
                                                { href: '/scheduling',  icon: 'M3 4h18v18H3z M16 2v4 M8 2v4 M3 10h18', label: 'Schedule' },
                                                { href: '/templates',   icon: 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z', label: 'Templates' },
                                                { href: '/analytics',   icon: 'M18 20V10 M12 20V4 M6 20v-6', label: 'Analytics' },
                                                { href: '/reports',     icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M16 13H8 M16 17H8', label: 'Reports' },
                                            ].map(({ href, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer" onClick={() => window.location.href = href}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => (
                                                                <path key={i} d={i === 0 ? seg : `M${seg}`} />
                                                            ))}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {/* ═══════ CENTER ═══════ */}
                        <div className="col-lg-4 col-12 mt-5 position-relative center-logo">
                            {/* animation-start.png — plays via CSS background-image from /v8main/img/ */}
                            <div
                                id="logo-it"
                                className="animation-start"
                                style={{ display: preloaderDone ? 'block' : 'none' }}
                            />
                            <div className="basic" style={{ display: preloaderDone ? 'block' : 'none' }} />

                            {/* Username area — exact SVGs from v8_main */}
                            <div className="end-icon mt-5" id="incenter_username"
                                 style={{ display: showUsername ? 'block' : 'none' }}>
                                <svg className="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="22px" height="23px">
                                    <path fillRule="evenodd" fill="rgb(41, 186, 189)"
                                          d="M19.263,5.321 C18.960,5.321 18.669,5.267 18.399,5.169 C18.399,5.169 18.399,5.169 17.269,6.329 C18.315,7.690 18.939,9.406 18.939,11.268 C18.939,13.131 18.314,14.847 17.269,16.208 C17.269,16.208 17.269,16.208 18.399,17.367 C18.669,17.270 18.960,17.216 19.263,17.216 C20.697,17.216 21.859,18.408 21.859,19.880 C21.859,21.351 20.697,22.544 19.263,22.544 C17.830,22.544 16.667,21.351 16.667,19.880 C16.667,19.569 16.720,19.271 16.815,18.993 C16.815,18.993 16.815,18.993 15.645,17.792 C14.375,18.717 12.824,19.261 11.152,19.261 C9.479,19.261 7.928,18.717 6.658,17.792 C6.658,17.792 6.658,17.792 5.488,18.993 C5.583,19.270 5.636,19.569 5.636,19.880 C5.636,21.351 4.474,22.544 3.040,22.544 C1.607,22.544 0.445,21.351 0.445,19.880 C0.445,18.408 1.607,17.216 3.040,17.216 C3.343,17.216 3.634,17.270 3.904,17.368 C3.904,17.368 3.904,17.368 5.034,16.208 C3.989,14.847 3.365,13.131 3.365,11.269 C3.365,9.406 3.989,7.690 5.034,6.329 C5.034,6.329 5.034,6.329 3.904,5.170 C3.634,5.267 3.343,5.321 3.040,5.321 C1.607,5.321 0.445,4.129 0.445,2.657 C0.445,1.186 1.607,-0.007 3.040,-0.007 C4.474,-0.007 5.636,1.186 5.636,2.657 C5.636,2.968 5.583,3.267 5.488,3.544 C5.488,3.544 5.488,3.544 6.658,4.745 C7.928,3.820 9.479,3.276 11.152,3.276 C12.824,3.276 14.375,3.820 15.645,4.745 C15.645,4.745 15.645,4.745 16.815,3.544 C16.720,3.267 16.667,2.968 16.667,2.657 C16.667,1.186 17.830,-0.007 19.263,-0.007 C20.697,-0.007 21.859,1.186 21.859,2.657 C21.859,4.129 20.697,5.321 19.263,5.321 Z" />
                                </svg>
                                <svg className="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="169px" height="14px">
                                    <path fillRule="evenodd" fill="rgb(41, 186, 189)"
                                          d="M165.698,5.676 C164.323,5.676 163.184,4.687 162.953,3.386 L115.214,3.386 L108.007,12.328 L108.146,12.328 L107.182,13.259 C107.123,13.235 107.032,13.182 106.920,13.105 L62.564,13.105 C62.531,13.105 62.500,13.098 62.469,13.093 C62.286,13.221 62.146,13.296 62.124,13.270 L61.976,13.086 L61.897,13.086 L60.781,11.712 L60.869,11.712 L54.158,3.386 L6.436,3.386 C6.204,4.687 5.058,5.676 3.677,5.676 C2.129,5.676 0.874,4.433 0.874,2.901 C0.874,1.368 2.129,0.125 3.677,0.125 C4.901,0.125 5.939,0.903 6.321,1.985 L54.193,1.985 C54.375,1.985 54.538,2.055 54.663,2.168 C54.785,2.092 54.872,2.051 54.889,2.071 L62.659,11.712 L106.713,11.712 L114.483,2.071 C114.500,2.050 114.592,2.094 114.719,2.174 C114.844,2.057 115.011,1.985 115.195,1.985 L163.066,1.985 C163.447,0.903 164.480,0.125 165.698,0.125 C167.239,0.125 168.488,1.368 168.488,2.901 C168.488,4.433 167.239,5.676 165.698,5.676 Z" />
                                </svg>
                                <h2 className="item-title text-center d-block pb-0 mt-2 text-uppercase"
                                    style={{ fontSize: '17px' }}>
                                    {userName}
                                </h2>
                            </div>
                        </div>

                        {/* ═══════ RIGHT ═══════ */}
                        <div className="col-lg-4 col-12 mt-5 p-0 right">
                            <div className="row">

                                {/* MY TOOLS */}
                                <div className="site clearfix col-md-6 col-lg-12">
                                    <h2 className="item-title float-right pointer"
                                        onClick={() => toggler('site', 'main')}>
                                        MY TOOLS
                                    </h2>
                                    <div className="clearfix" />
                                    <div className="wrapper main clearfix pb-2 float-right"
                                         style={{ display: isOpen('site', 'main') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Marketplace &amp; Tools</span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/marketplace/services', icon: 'M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z M3 6h18 M16 10a4 4 0 01-8 0', label: 'Marketplace' },
                                                { href: '/tools/scrapers',       icon: 'M21 21l-4.35-4.35 M11 19a8 8 0 100-16 8 8 0 000 16z', label: 'Scrapers' },
                                                { action: 'whatsapp',            icon: 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z', label: 'WhatsApp' },
                                                { href: '/tools/facebook',       icon: 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z', label: 'Facebook' },
                                                { href: '/tools/sms',            icon: 'M5 2h14a2 2 0 012 2v16a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2z M12 18h.01', label: 'SMS' },
                                                { href: '/settings',             icon: 'M12 15a3 3 0 100-6 3 3 0 000 6z M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z', label: 'Settings' },
                                            ].map(({ href, action, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer"
                                                     onClick={() => action === 'whatsapp' ? toggler('site', 'whatsapp') : (href && (window.location.href = href))}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => <path key={i} d={i === 0 ? seg : `M${seg}`} />)}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* WhatsApp sub-panel */}
                                    <div className="wrapper clearfix whatsapp pb-2 float-right"
                                         style={{ display: isOpen('site', 'whatsapp') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">WhatsApp Tools</span>
                                            <span className="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                                  onClick={() => toggler('site', 'main')}>
                                                <span className="d-inline-block px-2">BACK</span>
                                                <span className="back-icon d-inline-block position-absolute">
                                                    <svg className="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                                        <path fillRule="evenodd" fill="rgb(0,0,0)" d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z"/>
                                                    </svg>
                                                </span>
                                            </span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/tools/whatsapp/send',      icon: 'M22 2L11 13 M22 2l-7 20-4-9-9-4 20-7z', label: 'Send' },
                                                { href: '/tools/whatsapp/numbers',   icon: 'M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 01.07 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z', label: 'Numbers' },
                                                { href: '/tools/whatsapp/campaigns', icon: 'M13 2L3 14h9l-1 8 10-12h-9l1-8z', label: 'Campaigns' },
                                            ].map(({ href, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer" onClick={() => window.location.href = href}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => <path key={i} d={i === 0 ? seg : `M${seg}`} />)}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                {/* MY FINANCE */}
                                <div className="services clearfix mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                                    <h2 className="item-title float-right pointer"
                                        onClick={() => toggler('services', 'main')}>
                                        MY FINANCE
                                    </h2>
                                    <div className="clearfix" />
                                    <div className="wrapper main clearfix pb-2 float-right"
                                         style={{ display: isOpen('services', 'main') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Wallet, Billing &amp; Subscriptions</span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/wallet',         icon: 'M21 12V7H5a2 2 0 010-4h14v4 M3 5v14a2 2 0 002 2h16v-5 M18 12a2 2 0 000 4h4v-4z', label: 'Wallet' },
                                                { href: '/billing',        icon: 'M1 4h22v16H1z M1 10h22', label: 'Billing' },
                                                { href: '/subscriptions',  icon: 'M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4', label: 'Subscriptions' },
                                                { action: 'transactions',  icon: 'M17 1l4 4-4 4 M3 11V9a4 4 0 014-4h14 M7 23l-4-4 4-4 M21 13v2a4 4 0 01-4 4H3', label: 'Transactions' },
                                                { href: '/vouchers',       icon: 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z M7 7h.01', label: 'Vouchers' },
                                                { href: '/reports/financial', icon: 'M12 20V10 M18 20V4 M6 20v-4', label: 'Reports' },
                                            ].map(({ href, action, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer"
                                                     onClick={() => action === 'transactions' ? toggler('services', 'transactions') : (href && (window.location.href = href))}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => <path key={i} d={i === 0 ? seg : `M${seg}`} />)}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Transactions sub-panel */}
                                    <div className="wrapper clearfix pb-2 float-right"
                                         style={{ display: isOpen('services', 'transactions') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Transactions</span>
                                            <span className="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                                  onClick={() => toggler('services', 'main')}>
                                                <span className="d-inline-block px-2">BACK</span>
                                                <span className="back-icon d-inline-block position-absolute">
                                                    <svg className="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                                        <path fillRule="evenodd" fill="rgb(0,0,0)" d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z"/>
                                                    </svg>
                                                </span>
                                            </span>
                                        </div>
                                        <div className="row m-0">
                                            {[
                                                { href: '/transactions/deposits',    icon: 'M12 5v14 M19 12l-7 7-7-7', label: 'Deposits' },
                                                { href: '/transactions/withdrawals', icon: 'M12 19V5 M5 12l7-7 7 7', label: 'Withdrawals' },
                                            ].map(({ href, icon, label }) => (
                                                <div key={label} className="col-4 px-0 pointer" onClick={() => window.location.href = href}>
                                                    <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                        <svg className="item-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            {icon.split(' M').map((seg, i) => <path key={i} d={i === 0 ? seg : `M${seg}`} />)}
                                                        </svg>
                                                    </div>
                                                    <div className="item-captian text-light text-center text-capitalize py-2">{label}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </>
    );
}
