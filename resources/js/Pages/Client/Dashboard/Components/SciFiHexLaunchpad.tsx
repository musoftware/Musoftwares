import React from 'react';
import { Link } from '@inertiajs/react';
import { 
    Layers, 
    Users, 
    ShoppingBag, 
    Kanban, 
    Zap, 
    Cpu, 
    ExternalLink,
    ChevronRight
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface LaunchpadItem {
    id: string;
    title: string;
    description: string;
    href: string;
    icon: React.ReactNode;
    badge?: string;
    isExternal?: boolean;
}

export default function SciFiHexLaunchpad() {
    const launchpadItems: LaunchpadItem[] = [
        {
            id: 'erp',
            title: 'ERP Platform',
            description: __('general.a_complete_suite_to_manage_financials_op'),
            href: '/dashboard',
            icon: <Layers className="h-6 w-6" />,
            badge: 'SYS_CORE',
        },
        {
            id: 'crm',
            title: 'CRM Systems',
            description: __('general.accurate_realtime_tracking_of_income_exp'),
            href: '/platforms/crm',
            icon: <Users className="h-6 w-6" />,
            badge: 'LIVE',
        },
        {
            id: 'marketplace',
            title: 'Marketplace Tools',
            description: __('general.scifi_active_scrapers'),
            href: '/marketplace',
            icon: <ShoppingBag className="h-6 w-6" />,
            badge: 'STORE',
        },
        {
            id: 'projects',
            title: 'Projects Board',
            description: __('general.scifi_radar_projects'),
            href: '/projects',
            icon: <Kanban className="h-6 w-6" />,
            badge: 'WORKFLOW',
        },
        {
            id: 'automation',
            title: 'Automation Engine',
            description: __('general.a_sequence_is_a_series_of_automated_emails_sent_over_time'),
            href: '/automation-rules',
            icon: <Zap className="h-6 w-6" />,
            badge: 'AUTO',
        },
        {
            id: 'runtime',
            title: 'Runtime Agent',
            description: __('general.a_lightweight_execution_layer_that_runs_tools_securely_inside_your_browser_used_for_social_media_and_web_based_tools'),
            href: '/runtime',
            icon: <Cpu className="h-6 w-6" />,
            badge: 'AGENT_NODE',
        },
    ];

    return (
        <div className="scifi-panel p-6 rounded-2xl relative overflow-hidden">
            <div className="scifi-corner-tl" />
            <div className="scifi-corner-tr" />
            <div className="scifi-corner-bl" />
            <div className="scifi-corner-br" />

            {/* Section Header */}
            <div className="flex items-center justify-between border-b border-[var(--scifi-panel-border)] pb-4 mb-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-3 w-3 items-center justify-center">
                        <span className="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-[var(--scifi-primary)] opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-[var(--scifi-primary-light)]"></span>
                    </div>
                    <h2 className="font-mono text-lg font-bold tracking-wider text-slate-100 uppercase flex items-center gap-2">
                        [ {__('general.scifi_operations_launchpad')} ]
                    </h2>
                </div>
                <div className="font-mono text-xs text-[var(--scifi-primary-light)] tracking-widest">
                    SEC_LEVEL_01 :: ONLINE
                </div>
            </div>

            {/* Grid Launchpad Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {launchpadItems.map((item) => (
                    <Link
                        key={item.id}
                        href={item.href}
                        className="group relative flex flex-col justify-between p-5 rounded-xl border border-[var(--scifi-panel-border)] bg-[rgba(15,23,42,0.6)] hover:bg-[rgba(245,158,11,0.06)] hover:border-[var(--scifi-primary)] transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg"
                    >
                        {/* Card Corners */}
                        <div className="scifi-corner-tl opacity-50 group-hover:opacity-100" />
                        <div className="scifi-corner-br opacity-50 group-hover:opacity-100" />

                        <div>
                            {/* Card Top Metadata */}
                            <div className="flex items-center justify-between mb-3">
                                <div className="p-2.5 rounded-lg bg-[rgba(255,255,255,0.04)] text-[var(--scifi-primary-light)] border border-[var(--scifi-panel-border)] group-hover:border-[var(--scifi-primary)] group-hover:scale-110 transition-transform">
                                    {item.icon}
                                </div>
                                {item.badge && (
                                    <span className="font-mono text-[9px] px-2 py-0.5 rounded bg-[rgba(245,158,11,0.12)] text-[var(--scifi-primary-light)] border border-[var(--scifi-panel-border)] font-bold tracking-widest uppercase">
                                        {item.badge}
                                    </span>
                                )}
                            </div>

                            {/* Card Title & Desc */}
                            <h3 className="font-mono text-base font-bold text-slate-100 group-hover:text-[var(--scifi-primary-light)] transition-colors">
                                {item.title}
                            </h3>
                            <p className="mt-1 text-xs text-slate-400 line-clamp-2 font-sans">
                                {item.description}
                            </p>
                        </div>

                        {/* Card Bottom CTA */}
                        <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono text-slate-400 group-hover:text-[var(--scifi-primary-light)]">
                            <span>{__('general.scifi_quick_launch')}</span>
                            <ChevronRight className="h-4 w-4 transform group-hover:translate-x-1 transition-transform" />
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
}
