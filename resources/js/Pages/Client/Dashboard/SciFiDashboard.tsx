import React, { useState, useEffect } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import { 
    Clock, 
    Globe, 
    Palette, 
    User as UserIcon, 
    LogOut, 
    PlusCircle, 
    ShieldCheck, 
    Radio, 
    ChevronRight,
    Sparkles
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import './scifi.css';

import SciFiTelemetryCards from './Components/SciFiTelemetryCards';
import SciFiHexLaunchpad from './Components/SciFiHexLaunchpad';
import SciFiTelemetryGauge from './Components/SciFiTelemetryGauge';
import SciFiFinancialStream from './Components/SciFiFinancialStream';
import type { DashboardStats, RecentTransaction, ChartData, UserProject, ActiveTool } from './types';

interface SciFiDashboardProps {
    stats?: DashboardStats;
    recentTransactions?: RecentTransaction[];
    chartData?: ChartData[];
    userProjects?: UserProject[];
    activeToolLicenses?: ActiveTool[];
}

export default function SciFiDashboard({
    stats,
    recentTransactions = [],
    chartData = [],
    userProjects = [],
    activeToolLicenses = [],
}: SciFiDashboardProps) {
    const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;
    const user = auth?.user;

    // Dual theme mode: 'amber' (default) vs 'cyan'
    const [themeMode, setThemeMode] = useState<'amber' | 'cyan'>(() => {
        return (localStorage.getItem('scifi_theme') as 'amber' | 'cyan') || 'amber';
    });

    // Cairo Time state
    const [cairoTime, setCairoTime] = useState<string>('');

    useEffect(() => {
        const updateCairoTime = () => {
            try {
                const now = new Date();
                const formatted = new Intl.DateTimeFormat('en-US', {
                    timeZone: 'Africa/Cairo',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                }).format(now);
                setCairoTime(formatted);
            } catch (e) {
                setCairoTime(new Date().toLocaleTimeString());
            }
        };

        updateCairoTime();
        const interval = setInterval(updateCairoTime, 1000);
        return () => clearInterval(interval);
    }, []);

    const toggleTheme = () => {
        const nextTheme = themeMode === 'amber' ? 'cyan' : 'amber';
        setThemeMode(nextTheme);
        localStorage.setItem('scifi_theme', nextTheme);
    };

    const activityFeedItems = React.useMemo(() => {
        return recentTransactions.map((txn) => {
            const isDeposit = txn.type === 'deposit';
            return {
                id: txn.id,
                description: isDeposit 
                    ? __('general.transaction_deposit_desc', { amount: formatMoney(txn.amount, txn.currency), method: txn.method })
                    : __('general.transaction_withdrawal_desc', { amount: formatMoney(txn.amount, txn.currency), method: txn.method }),
                created_at: txn.date,
                icon: isDeposit ? 'wallet' : 'receipt',
                isDeposit,
            };
        });
    }, [recentTransactions]);

    // Fallback gauges if user has no projects yet
    const displayGauges = userProjects.length > 0 ? userProjects.slice(0, 4) : [
        { id: 1, name: 'System Security Node', progress: 99, status: 'operational' },
        { id: 2, name: 'ERP Core Engine', progress: 75, status: 'in_progress' },
        { id: 3, name: 'Marketplace Automation', progress: 50, status: 'active' },
        { id: 4, name: 'Runtime Bridge Agent', progress: 25, status: 'queued' },
    ];

    if (!stats) {
        return (
            <div className="min-h-screen bg-[#050811] flex items-center justify-center text-amber-500 font-mono">
                <div className="flex items-center gap-3">
                    <div className="h-6 w-6 animate-spin rounded-full border-2 border-amber-500 border-t-transparent" />
                    <span>INITIALIZING_HUD_TELEMETRY...</span>
                </div>
            </div>
        );
    }

    return (
        <div className={`min-h-screen scifi-root-${themeMode} bg-[var(--scifi-bg-dark)] text-slate-100 font-sans selection:bg-[var(--scifi-primary)] selection:text-black relative overflow-x-hidden scifi-bg-grid pb-16`}>
            <Head title={__('general.scifi_command_center')} />

            {/* Ambient Background Scan Glow */}
            <div className="fixed inset-0 pointer-events-none z-0">
                <div className="absolute -top-40 left-1/2 -translate-x-1/2 w-[1000px] h-[400px] bg-[var(--scifi-primary-glow)] rounded-full blur-[140px] opacity-25" />
            </div>

            {/* TOP COMMAND HUD HEADER */}
            <header className="relative z-10 border-b border-[var(--scifi-panel-border)] bg-[rgba(6,10,20,0.85)] backdrop-blur-md sticky top-0">
                <div className="mx-auto max-w-7xl px-4 py-3.5 sm:px-6 lg:px-8 flex items-center justify-between">
                    
                    {/* Brand & Connection Pulse */}
                    <div className="flex items-center gap-4">
                        <Link href="/" className="flex items-center gap-2.5 group">
                            <div className="relative flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--scifi-panel-bg)] border border-[var(--scifi-primary)] shadow-[0_0_12px_var(--scifi-primary-glow)]">
                                <Sparkles className="h-5 w-5 text-[var(--scifi-primary-light)] group-hover:rotate-12 transition-transform" />
                                <div className="scifi-corner-tl" />
                                <div className="scifi-corner-br" />
                            </div>
                            <div className="flex flex-col">
                                <span className="font-mono text-base font-extrabold tracking-wider text-slate-100 uppercase">
                                    MUSOFT<span style={{ color: 'var(--scifi-primary-light)' }}>OS</span>
                                </span>
                                <span className="text-[9px] font-mono text-[var(--scifi-text-muted)] tracking-widest">
                                    HUD_v3.6 :: CAIRO_TZ
                                </span>
                            </div>
                        </Link>

                        <div className="hidden md:flex items-center gap-2 px-3 py-1 rounded-full border border-[var(--scifi-panel-border)] bg-[rgba(15,23,42,0.6)] text-[10px] font-mono">
                            <Radio className="h-3 w-3 text-emerald-400 animate-pulse" />
                            <span className="text-emerald-400 font-bold">{__('general.scifi_system_online')}</span>
                        </div>
                    </div>

                    {/* Telemetry Clock & Theme Switcher & User Actions */}
                    <div className="flex items-center gap-3">
                        {/* Live Cairo Clock */}
                        <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-[var(--scifi-panel-border)] bg-[rgba(15,23,42,0.6)] text-xs font-mono text-[var(--scifi-primary-light)]">
                            <Clock className="h-3.5 w-3.5" />
                            <span>{cairoTime || '00:00:00 AM'}</span>
                            <span className="text-[9px] text-slate-500">CLT</span>
                        </div>

                        {/* Dual Theme Switcher Button */}
                        <button
                            onClick={toggleTheme}
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[var(--scifi-panel-border)] bg-[rgba(15,23,42,0.6)] text-xs font-mono text-slate-200 hover:border-[var(--scifi-primary)] transition-all"
                            title="Toggle Theme (Amber / Cyan)"
                        >
                            <Palette className="h-3.5 w-3.5 text-[var(--scifi-primary-light)]" />
                            <span className="hidden md:inline uppercase text-[10px]">
                                {themeMode === 'amber' ? __('general.scifi_amber_gold') : __('general.scifi_neon_cyan')}
                            </span>
                        </button>

                        {/* Top-up Wallet Fast CTA */}
                        <Link
                            href="/billing"
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[var(--scifi-primary)] text-slate-950 font-mono text-xs font-bold hover:brightness-110 shadow-[0_0_12px_var(--scifi-primary-glow)] transition-all"
                        >
                            <PlusCircle className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">{__('general.add_funds')}</span>
                        </Link>
                    </div>
                </div>
            </header>

            {/* MAIN DASHBOARD CONTENT AREA */}
            <main className="relative z-10 mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

                {/* USER GREETING & COMMAND BANNER */}
                <div className="scifi-panel p-6 rounded-2xl relative flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="scifi-corner-tl" />
                    <div className="scifi-corner-tr" />
                    <div className="scifi-corner-bl" />
                    <div className="scifi-corner-br" />

                    <div>
                        <div className="flex items-center gap-2 text-xs font-mono text-[var(--scifi-primary-light)] uppercase tracking-widest mb-1">
                            <ShieldCheck className="h-4 w-4" />
                            <span>AUTHENTICATED_COMMANDER :: {user?.name}</span>
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-100 font-mono">
                            {__('general.scifi_command_center')}
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-slate-400">
                            {__('general.scifi_all_systems_operational')}
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="px-4 py-2 rounded-xl bg-[rgba(255,255,255,0.03)] border border-[var(--scifi-panel-border)] text-right font-mono">
                            <span className="text-[10px] text-slate-400 block uppercase">{__('general.account_balance')}</span>
                            <span className="text-lg font-bold text-[var(--scifi-primary-light)]">{formatMoney(stats.walletBalance, stats.currency)}</span>
                        </div>
                    </div>
                </div>

                {/* MODULE 1: TELEMETRY METRICS CARDS */}
                <SciFiTelemetryCards stats={stats} />

                {/* MODULE 2: CORE OPERATIONS LAUNCHPAD */}
                <SciFiHexLaunchpad />

                {/* MODULE 3: RADAR & PROJECT PROGRESS METERS (GAUGES 25%, 50%, 75%, 99%) */}
                <div className="scifi-panel p-6 rounded-2xl relative">
                    <div className="scifi-corner-tl" />
                    <div className="scifi-corner-tr" />
                    <div className="scifi-corner-bl" />
                    <div className="scifi-corner-br" />

                    <div className="flex items-center justify-between border-b border-[var(--scifi-panel-border)] pb-4 mb-6">
                        <h2 className="font-mono text-lg font-bold tracking-wider text-slate-100 uppercase flex items-center gap-2">
                            [ {__('general.scifi_radar_projects')} ]
                        </h2>
                        <Link 
                            href="/projects" 
                            className="font-mono text-xs text-[var(--scifi-primary-light)] hover:underline flex items-center gap-1"
                        >
                            <span>{__('general.view_all')}</span>
                            <ChevronRight className="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        {displayGauges.map((g: any) => (
                            <SciFiTelemetryGauge
                                key={g.id}
                                percentage={g.progress ?? 75}
                                title={g.name || 'Active Workflow'}
                                subtitle={g.updated_at ? `Updated ${g.updated_at}` : 'Operational'}
                            />
                        ))}
                    </div>
                </div>

                {/* MODULE 4: FINANCIAL TELEMETRY STREAM & NEON CHART */}
                <SciFiFinancialStream
                    chartData={chartData}
                    activityFeedItems={activityFeedItems}
                />

            </main>
        </div>
    );
}
