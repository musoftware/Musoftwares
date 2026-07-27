import React, { useState, useEffect } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import type { DashboardStats } from './types';

interface V8DashboardProps {
    stats?: DashboardStats;
}

export default function V8Dashboard({ stats }: V8DashboardProps) {
    const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;
    const user = auth?.user;
    const userName = user?.name ?? 'Mahmoud';

    const [openSub, setOpenSub] = useState<Record<string, string | null>>({
        academy: 'wrapper',
        notification: 'wrapper',
        site: 'main',
        services: 'main',
    });

    const [preloaderDone, setPreloaderDone] = useState(false);
    const [showUsername, setShowUsername] = useState(false);

    useEffect(() => {
        const t1 = setTimeout(() => setPreloaderDone(true), 4000);
        const t2 = setTimeout(() => setShowUsername(true), 5500);
        return () => {
            clearTimeout(t1);
            clearTimeout(t2);
        };
    }, []);

    const togglePanel = (section: string, panel: string) => {
        setOpenSub(prev => ({
            ...prev,
            [section]: prev[section] === panel ? null : panel,
        }));
    };

    const isVisible = (section: string, panel: string) => openSub[section] === panel;

    const navigate = (href: string) => {
        window.location.href = href;
    };

    return (
        <div className="v8-dashboard-scope" style={{ zoom: 0.75, backgroundColor: '#030e11', minHeight: '100vh', color: '#fff' }}>
            <Head title={`${userName} - Musoftwares`} />

            {/* Load v8main CSS files without polluting global React apps */}
            <link rel="stylesheet" href="/v8main/css/bootstrap.min.css" />
            <link rel="stylesheet" href="/v8main/css/style.css?v=1.1" />

            {/* Scoped CSS Overrides */}
            <style>{`
                .v8-dashboard-scope body {
                    zoom: 0.75;
                    background-color: #030e11;
                }
                .v8-dashboard-scope .item-title {
                    cursor: pointer;
                }
                .v8-dashboard-scope .wrapper {
                    transition: all 0.3s ease;
                }
            `}</style>

            {/* ══ PRELOADER (exact HTML from v8_main.tpl.php) ══ */}
            {!preloaderDone && (
                <div className="preloader-wrapper">
                    <div className="preloader">
                        <div className="audio-test text-light hidden">
                            <h3>Play site with sound effects?</h3>
                            <div className="buttons mt-5 text-center">
                                <button className="btn btn-primary btn-sm mr-5 yes">Yes</button>
                                <button className="btn btn-danger btn-sm no">No</button>
                            </div>
                        </div>
                        <div className="loading-Recovered" />
                        <div className="intro text-light">{`Hello, ${userName}...`}</div>
                        <div className="welcome-wrap">
                            <div className="welcome hidden">
                                <img src="/v8main/img/user.jpg" className="position-relative hidden" alt="" />
                            </div>
                        </div>
                        <div className="out text-light mt-3" style={{ marginLeft: '14px' }}>Welcome back!</div>
                    </div>
                </div>
            )}

            {/* ══ HEADER (exact HTML from v8_main.tpl.php) ══ */}
            <header className="nav">
                <div className="container-fluid">
                    <div className="row">

                        {/* logo */}
                        <div className="col-lg-5 col-md-3 col-6">
                            <div className="logo-parent d-flex align-items-center">
                                <img
                                    className="logo pointer"
                                    src="/v8main/img/amc8.png"
                                    onClick={() => navigate('/dashboard')}
                                    alt="Musoftwares"
                                />
                            </div>
                        </div>

                        {/* center two icons */}
                        <div className="center-icons text-center col-lg-2 col-md-2 text-light col-6">
                            <div className="row d-flex align-items-lg-end align-items-center justify-content-end justify-content-lg-center">
                                <div className="px-2 hover" onClick={() => navigate('/marketplace')}>
                                    <i className="icon-social d-block" />
                                    <h3 className="m-auto">Market</h3>
                                </div>
                                <div className="px-2 hover" onClick={() => navigate('/profile')}>
                                    <i className="icon-user d-block" />
                                    <h3 className="m-auto">Profile</h3>
                                </div>
                            </div>
                        </div>

                        {/* user data */}
                        <div className="user-reference col-lg-5 col-md-7 mt-3 mt-md-0">
                            <div className="text-light flex-row d-flex align-items-center justify-content-between justify-content-md-end text-center">

                                <ul className="d-flex align-items-center mb-0 mr-3 pl-0">
                                    <li className="hover active list-inline-item d-flex align-items-center justify-content-center" onClick={() => navigate('/friends')}>
                                        <i className="icon-users" />
                                    </li>
                                    <li className="hover active list-inline-item d-flex align-items-center justify-content-center" onClick={() => navigate('/messages')}>
                                        <i className="icon-message" />
                                    </li>
                                    <li className="hover active list-inline-item d-flex align-items-center justify-content-center" onClick={() => navigate('/notifications')}>
                                        <i className="icon-bell" />
                                    </li>
                                </ul>

                                <div
                                    className="user-data d-flex align-items-center px-2 py-1 dropdown-toggle dropdown pointer"
                                    id="dropdownMenuOffset"
                                    data-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                    <div className="profile-pic d-flex mr-1">
                                        <img src="/v8main/img/user.jpg" alt="" className="user-img m-auto" />
                                    </div>
                                    <div className="d-none d-md-flex flex-column user-text px-2 mr-1 position-relative text-left font-weight-bold">
                                        <div className="username text-capitalize">{userName}</div>
                                        <div className="user-level text-uppercase">Admin</div>
                                    </div>
                                    <div
                                        className="dropdown-menu dropdown-menu-right"
                                        aria-labelledby="dropdownMenuOffset"
                                        style={{
                                            backgroundColor: 'rgb(0, 0, 0)',
                                            color: 'rgb(33, 151, 154)',
                                            borderBottom: 'rgb(33, 151, 154) 1px solid',
                                            borderLeft: '1px solid rgb(33, 151, 154)',
                                            borderRight: '1px solid rgb(33, 151, 154)',
                                            position: 'absolute',
                                            willChange: 'transform',
                                            top: '0px',
                                            left: '0px',
                                            transform: 'translate3d(1px, 56px, 0px)',
                                        }}
                                    >
                                        <a className="dropdown-item" onClick={() => navigate('/admin')}>Admin Panel</a>
                                        <a className="dropdown-item" onClick={() => navigate('/profile')}>My Profile</a>
                                        <a className="dropdown-item" onClick={() => navigate('/billing')}>Billing</a>
                                        <a className="dropdown-item" onClick={() => navigate('/wallet')}>Wallet</a>
                                        <a className="dropdown-item" onClick={() => navigate('/settings')}>Settings</a>
                                        <Link className="dropdown-item" href="/logout" method="post" as="button">Logout</Link>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </header>

            {/* ══ MAIN CONTENT (exact 3-column layout from v8_main.tpl.php) ══ */}
            <section className="content mb-5">
                <div className="container-fluid">
                    <div className="row m-0">

                        {/* LEFT COLUMN */}
                        <div className="col-lg-4 col-12 mt-5 p-0 left">
                            <div className="row">

                                {/* MY ISAAS (was: MY ACADEMY) */}
                                <div className="academy col-md-6 col-lg-12">
                                    <h2
                                        className={`item-title pointer ${isVisible('academy', 'wrapper') ? 'active' : ''}`}
                                        onClick={() => togglePanel('academy', 'wrapper')}
                                    >
                                        MY ISAAS
                                    </h2>
                                    <div className="wrapper pb-2" style={{ display: isVisible('academy', 'wrapper') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">iSaaS Contracts &amp; Projects</span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/contracts')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="monster" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Contracts</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/isaas')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="courses" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">iSaaS</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/erp')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="dowloads" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">ERP</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/crm')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="ad" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">CRM</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/invoices')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="events" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Invoices</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tasks')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="books" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Tasks</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* MY WORKFLOW (was: MY NOTIFICATION) */}
                                <div className="notification mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                                    <h2
                                        className={`item-title pointer ${isVisible('notification', 'wrapper') ? 'active' : ''}`}
                                        onClick={() => togglePanel('notification', 'wrapper')}
                                    >
                                        MY WORKFLOW
                                    </h2>
                                    <div className="wrapper pb-2" style={{ display: isVisible('notification', 'wrapper') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Automation &amp; AI Agents</span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/ai-agents')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="add-browser" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">AI Agents</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/automation')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="mange-browser" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Automation</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/scheduling')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="add" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Schedule</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/templates')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="mange-noti" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Templates</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/analytics')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="search" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Analytics</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/reports')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="all" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Reports</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {/* CENTER COLUMN */}
                        <div className="col-lg-4 col-12 mt-5 position-relative center-logo">
                            <div className="animation-start basic" id="logo-it" />
                            <div className="basic hidden" />

                            {/* end icon */}
                            <div className="end-icon mt-5" id="incenter_username" style={{ display: showUsername ? 'block' : 'none' }}>
                                <svg className="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="22px" height="23px">
                                    <path fillRule="evenodd" fill="rgb(41, 186, 189)" d="M19.263,5.321 C18.960,5.321 18.669,5.267 18.399,5.169 C18.399,5.169 18.399,5.169 17.269,6.329 C18.315,7.690 18.939,9.406 18.939,11.268 C18.939,13.131 18.314,14.847 17.269,16.208 C17.269,16.208 17.269,16.208 18.399,17.367 C18.669,17.270 18.960,17.216 19.263,17.216 C20.697,17.216 21.859,18.408 21.859,19.880 C21.859,21.351 20.697,22.544 19.263,22.544 C17.830,22.544 16.667,21.351 16.667,19.880 C16.667,19.569 16.720,19.271 16.815,19.993 C16.815,18.993 16.815,18.993 15.645,17.792 C14.375,18.717 12.824,19.261 11.152,19.261 C9.479,19.261 7.928,18.717 6.658,17.792 C6.658,17.792 6.658,17.792 5.488,18.993 C5.583,19.270 5.636,19.569 5.636,19.880 C5.636,21.351 4.474,22.544 3.040,22.544 C1.607,22.544 0.445,21.351 0.445,19.880 C0.445,18.408 1.607,17.216 3.040,17.216 C3.343,17.216 3.634,17.270 3.904,17.368 C3.904,17.368 3.904,17.368 5.034,16.208 C3.989,14.847 3.365,13.131 3.365,11.269 C3.365,9.406 3.989,7.690 5.034,6.329 C5.034,6.329 5.034,6.329 3.904,5.170 C3.634,5.267 3.343,5.321 3.040,5.321 C1.607,5.321 0.445,4.129 0.445,2.657 C0.445,1.186 1.607,-0.007 3.040,-0.007 C4.474,-0.007 5.636,1.186 5.636,2.657 C5.636,2.968 5.583,3.267 5.488,3.544 C5.488,3.544 5.488,3.544 6.658,4.745 C7.928,3.820 9.479,3.276 11.152,3.276 C12.824,3.276 14.375,3.820 15.645,4.745 C15.645,4.745 15.645,4.745 16.815,3.544 C16.720,3.267 16.667,2.968 16.667,2.657 C16.667,1.186 17.830,-0.007 19.263,-0.007 C20.697,-0.007 21.859,1.186 21.859,2.657 C21.859,4.129 20.697,5.321 19.263,5.321 ZM11.152,4.813 C7.678,4.813 4.862,7.704 4.862,11.269 C4.862,12.834 5.405,14.270 6.309,15.387 C6.396,14.523 6.577,13.485 7.023,13.303 L9.354,12.349 C9.354,12.349 9.354,12.349 9.900,11.876 C10.044,11.752 10.257,11.760 10.391,11.897 L11.152,12.672 C11.152,12.672 11.152,12.672 11.911,11.897 C12.045,11.760 12.258,11.751 12.402,11.876 L12.948,12.349 C12.948,12.349 12.948,12.349 15.280,13.303 C15.726,13.485 15.907,14.523 15.994,15.388 C16.898,14.270 17.441,12.834 17.441,11.269 C17.441,7.704 14.625,4.813 11.152,4.813 ZM11.152,11.691 C9.719,11.714 8.840,10.426 8.818,8.303 C8.803,6.753 9.640,5.948 11.152,5.948 C12.697,5.948 13.486,6.753 13.486,8.303 C13.486,11.788 11.152,11.691 11.152,11.691 Z" />
                                </svg>
                                <svg className="mx-auto d-block" xmlns="http://www.w3.org/2000/svg" width="169px" height="14px">
                                    <path fillRule="evenodd" fill="rgb(41, 186, 189)" d="M165.698,5.676 C164.323,5.676 163.184,4.687 162.953,3.386 L115.214,3.386 L108.007,12.328 L108.146,12.328 L107.182,13.259 C107.123,13.235 107.032,13.182 106.920,13.105 L62.564,13.105 C62.531,13.105 62.500,13.098 62.469,13.093 C62.286,13.221 62.146,13.296 62.124,13.270 L61.976,13.086 L61.897,13.086 L60.781,11.712 L60.869,11.712 L54.158,3.386 L6.436,3.386 C6.204,4.687 5.058,5.676 3.677,5.676 C2.129,5.676 0.874,4.433 0.874,2.901 C0.874,1.368 2.129,0.125 3.677,0.125 C4.901,0.125 5.939,0.903 6.321,1.985 L54.193,1.985 C54.375,1.985 54.538,2.055 54.663,2.168 C54.785,2.092 54.872,2.051 54.889,2.071 L62.659,11.712 L106.713,11.712 L114.483,2.071 C114.500,2.050 114.592,2.094 114.719,2.174 C114.844,2.057 115.011,1.985 115.195,1.985 L163.066,1.985 C163.447,0.903 164.480,0.125 165.698,0.125 C167.239,0.125 168.488,1.368 168.488,2.901 C168.488,4.433 167.239,5.676 165.698,5.676 Z" />
                                </svg>
                                <h2 className="item-title text-center d-block pb-0 mt-2 text-uppercase" style={{ fontSize: '17px' }}>
                                    {userName}
                                </h2>
                                <h6 className="text-center d-block pb-0 mt-2 text-uppercase" style={{ color: '#ff7c20', fontSize: '9px' }}>
                                    Admin
                                </h6>
                            </div>
                        </div>

                        {/* RIGHT COLUMN */}
                        <div className="col-lg-4 col-12 mt-5 p-0 right">
                            <div className="row">

                                {/* MY TOOLS (was: MY SITES) */}
                                <div className="site clearfix col-md-6 col-lg-12">
                                    <h2
                                        className={`item-title float-right pointer ${isVisible('site', 'main') ? 'active' : ''}`}
                                        onClick={() => togglePanel('site', 'main')}
                                    >
                                        MY TOOLS
                                    </h2>
                                    <div className="clearfix" />
                                    <div className="wrapper main clearfix pb-2 float-right" style={{ display: isVisible('site', 'main') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Marketplace &amp; Tools</span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/marketplace/services')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="sites" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Marketplace</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/scrapers')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="sales" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Scrapers</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => togglePanel('site', 'whatsapp')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="popup" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">WhatsApp</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/facebook')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="post" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Facebook</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/sms')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="gl" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">SMS</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/settings')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="shorten" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Settings</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* WhatsApp sub-panel (like popup-wrap) */}
                                    <div className="wrapper clearfix popup-wrap pb-2 float-right" style={{ display: isVisible('site', 'whatsapp') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">WhatsApp Tools</span>
                                            <span
                                                className="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                                onClick={() => togglePanel('site', 'main')}
                                            >
                                                <span className="d-inline-block px-2">BACK</span>
                                                <span className="back-icon d-inline-block position-absolute">
                                                    <svg className="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                                        <path fillRule="evenodd" fill="rgb(0, 0, 0)" d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z" />
                                                    </svg>
                                                </span>
                                            </span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/whatsapp/send')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="add-popup" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Send</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/whatsapp/numbers')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="mange-pop" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Numbers</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/tools/whatsapp/campaigns')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="pop-balance" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Campaigns</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* MY FINANCE (was: MY SERVICES) */}
                                <div className="services clearfix mt-5 mt-md-0 mt-lg-5 col-md-6 col-lg-12">
                                    <h2
                                        className={`item-title float-right pointer ${isVisible('services', 'main') ? 'active' : ''}`}
                                        onClick={() => togglePanel('services', 'main')}
                                    >
                                        MY FINANCE
                                    </h2>
                                    <div className="clearfix" />
                                    <div className="wrapper main clearfix pb-2 float-right" style={{ display: isVisible('services', 'main') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Wallet, Billing &amp; Subscriptions</span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/wallet')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="camp" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Wallet</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/billing')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="sales" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Billing</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/subscriptions')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="reports" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Subscriptions</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => togglePanel('services', 'transactions')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="full" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Transactions</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/vouchers')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="upload" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Vouchers</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/reports/financial')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="setting" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Reports</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Transactions sub-panel (like campaings-wrap) */}
                                    <div className="wrapper clearfix campaings-wrap pb-2 float-right" style={{ display: isVisible('services', 'transactions') ? 'block' : 'none' }}>
                                        <div className="mb-3 head pl-4 d-flex align-items-center">
                                            <span className="position-relative">Transactions</span>
                                            <span
                                                className="back-btn ml-auto d-inline-block position-relative pr-2 pointer"
                                                onClick={() => togglePanel('services', 'main')}
                                            >
                                                <span className="d-inline-block px-2">BACK</span>
                                                <span className="back-icon d-inline-block position-absolute">
                                                    <svg className="position-absolute" xmlns="http://www.w3.org/2000/svg" width="5px" height="9px">
                                                        <path fillRule="evenodd" fill="rgb(0, 0, 0)" d="M4.216,8.866 C4.395,9.043 4.685,9.043 4.865,8.866 C5.044,8.689 5.044,8.402 4.865,8.224 C1.105,4.500 1.105,4.500 1.105,4.500 L4.865,0.775 C5.044,0.598 5.044,0.310 4.865,0.133 C4.685,-0.044 4.395,-0.044 4.215,0.133 C0.132,4.178 0.132,4.178 0.132,4.178 C-0.045,4.354 -0.045,4.646 0.132,4.821 L4.216,8.866 Z" />
                                                    </svg>
                                                </span>
                                            </span>
                                        </div>
                                        <div className="row m-0">
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/transactions/deposits')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="add-camp" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Deposits</div>
                                            </div>
                                            <div className="col-4 px-0 pointer" onClick={() => navigate('/transactions/withdrawals')}>
                                                <div className="py-2 mx-2 d-flex flex-fill item align-items-center justify-content-center">
                                                    <div className="mange-camp" />
                                                </div>
                                                <div className="item-captian text-light text-center text-capitalize py-2">Withdrawals</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </div>
    );
}
