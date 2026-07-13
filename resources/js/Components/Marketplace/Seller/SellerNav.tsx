import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Package, Wallet } from 'lucide-react';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export function SellerNav() {
    const { url } = usePage();

    const tabs = [
        { name: __('general.dashboard'), href: '/seller/dashboard', icon: LayoutDashboard },
        { name: __('general.my_products'), href: '/seller/products', icon: Package },
        { name: __('general.my_payouts'), href: '/seller/payouts', icon: Wallet },
    ];

    return (
        <div className="mb-6 border-b border-slate-200">
            <nav className="-mb-px flex space-x-6" aria-label="Tabs">
                {tabs.map((tab) => {
                    const isActive = url.startsWith(tab.href);
                    return (
                        <Link
                            key={tab.name}
                            href={tab.href}
                            className={cn(
                                isActive
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium flex items-center gap-2 transition-colors'
                            )}
                            aria-current={isActive ? 'page' : undefined}
                        >
                            <tab.icon className={cn('h-4 w-4', isActive ? 'text-indigo-500' : 'text-slate-400')} />
                            {tab.name}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}
