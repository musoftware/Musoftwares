import React from 'react';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

interface Tab {
    key: string;
    labelKey: string;
    url: string;
}

const SECTIONS: Record<string, Tab[]> = {
    recurring: [
        { key: 'costs', labelKey: 'admin.sidebar_recurring_costs', url: '/admin/business/recurring/costs' },
        { key: 'income', labelKey: 'admin.sidebar_recurring_income', url: '/admin/business/recurring/income' },
        { key: 'salaries', labelKey: 'admin.sidebar_recurring_salaries', url: '/admin/business/recurring/salaries' },
        { key: 'invoices', labelKey: 'admin.sidebar_recurring_invoices', url: '/admin/business/recurring/invoices' },
    ],
    reports: [
        { key: 'reports', labelKey: 'admin.sidebar_reports', url: '/admin/business/reports' },
        { key: 'balance', labelKey: 'admin.sidebar_balance', url: '/admin/business/balance-report' },
    ],
    currencies: [
        { key: 'currencies', labelKey: 'admin.sidebar_currencies', url: '/admin/currencies' },
        { key: 'exchanges', labelKey: 'admin.sidebar_currency_exchanges', url: '/admin/currency-exchanges' },
    ],
};

/** The tabs that join related finance pages, so the sidebar needs only one link for each group. */
export default function FinanceSectionTabs({ section, active }: { section: keyof typeof SECTIONS; active: string }) {
    return (
        <nav className="flex flex-wrap border-b border-gray-200 mb-6 max-w-full min-w-0" aria-label={__('admin.sidebar_finance_business')}>
            {SECTIONS[section].map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.url}
                    aria-current={tab.key === active ? 'page' : undefined}
                    className={`inline-flex items-center min-h-11 px-4 text-sm border-b-2 transition-colors ${
                        tab.key === active
                            ? 'border-black text-black font-semibold'
                            : 'border-transparent text-gray-600 hover:text-black hover:border-gray-300'
                    }`}
                >
                    {__(tab.labelKey)}
                </Link>
            ))}
        </nav>
    );
}
