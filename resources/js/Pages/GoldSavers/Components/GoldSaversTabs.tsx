import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { LayoutDashboard, Wallet, TrendingUp, PieChart, FileText } from 'lucide-react';

export default function GoldSaversTabs() {
    const { url } = usePage();

    const tabs = [
        {
            name: __('general.dashboard'),
            href: route('isaas.gold-savers.index'),
            active: url === '/isaas/gold-savers' || url.startsWith('/isaas/gold-savers?'),
            icon: LayoutDashboard
        },
        {
            name: __('erp.my_wallets'),
            href: route('isaas.gold-savers.wallets.index'),
            active: url.startsWith('/isaas/gold-savers/wallets'),
            icon: Wallet
        },
        {
            name: __('general.market_prices'),
            href: route('isaas.gold-savers.market.index'),
            active: url.startsWith('/isaas/gold-savers/market'),
            icon: TrendingUp
        },
        {
            name: __('general.analytics'),
            href: route('isaas.gold-savers.analytics.index'),
            active: url.startsWith('/isaas/gold-savers/analytics'),
            icon: PieChart
        },
        {
            name: __('general.reports'),
            href: route('isaas.gold-savers.reports.index'),
            active: url.startsWith('/isaas/gold-savers/reports'),
            icon: FileText
        }
    ];

    return (
        <div className="flex space-x-1 sm:space-x-4 overflow-x-auto pb-2 -mb-2 scrollbar-hide">
            {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                    <Link
                        key={tab.name}
                        href={tab.href}
                        className={`flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors ${
                            tab.active
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                        }`}
                    >
                        <Icon className="w-4 h-4" />
                        {tab.name}
                    </Link>
                );
            })}
        </div>
    );
}
