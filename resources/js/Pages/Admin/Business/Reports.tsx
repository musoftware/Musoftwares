import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Users,
    Briefcase,
    FileText,
    Activity,
    DollarSign,
    TrendingDown,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
} from 'lucide-react';
import { MetricCard } from '@/Components/ui/MetricCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney, cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

function StatCard({ title, value, icon: Icon, drillTo, colorize }: {
    title: string;
    value: number | string;
    icon: React.ElementType;
    drillTo?: string;
    colorize?: boolean;
}) {
    const numeric = typeof value === 'number' ? value : parseFloat(String(value));
    const colorClass = colorize
        ? isNaN(numeric)
            ? 'text-slate-900'
            : numeric >= 0
            ? 'text-emerald-700'
            : 'text-rose-700'
        : 'text-slate-900';

    const body = (
        <Card className="border-none shadow-sm shadow-slate-200/50 hover:shadow-md transition-shadow">
            <CardContent className="p-6">
                <div className="flex items-center justify-between space-y-0 pb-2">
                    <p className="text-sm font-medium text-slate-500">{title}</p>
                    <div className="p-2 bg-slate-50 rounded-xl">
                        <Icon className="h-4 w-4 text-slate-900" />
                    </div>
                </div>
                <div className={cn('text-3xl font-bold tracking-tight font-mono', colorClass)}>
                    {value}
                </div>
            </CardContent>
        </Card>
    );

    return drillTo ? (
        <Link href={drillTo} className="block focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 rounded-xl">
            {body}
        </Link>
    ) : (
        body
    );
}

export default function Reports({ stats, hasData = true }: { stats: any; hasData?: boolean }) {
    if (!stats || !hasData) {
        return (
            <AdminSidebarLayout title={__('general.system_reports')} header={__('general.system_reports')}>
                <Head title={__('general.system_reports')} />
                <EmptyState
                    icon={Activity}
                    title={__('general.no_reports_data_yet') || 'No data yet'}
                    description={__('general.reports_will_populate_as_data_is_created') || 'Reports will populate as soon as activity is recorded.'}
                />
            </AdminSidebarLayout>
        );
    }

    const businessCurrency = stats.business_currency_code || 'USD';
    const isPositiveProfit = (stats.net_profit ?? 0) >= 0;

    return (
        <AdminSidebarLayout title={__('general.system_reports')} header={__('general.system_reports')}>
            <Head title={__('general.system_reports')} />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <StatCard title={__('general.total_users')} value={stats.total_users ?? 0} icon={Users} drillTo={route('admin.users.index')} />
                <StatCard title={__('general.total_projects')} value={stats.total_projects ?? 0} icon={Briefcase} drillTo={route('admin.projects.index')} />
                <StatCard title={__('general.total_invoices')} value={stats.total_invoices ?? 0} icon={FileText} drillTo={route('admin.invoices.index')} />
                <StatCard title={__('general.total_transactions')} value={stats.total_transactions ?? 0} icon={Activity} drillTo={route('admin.finance.index')} />
            </div>

            <div className="flex items-end justify-between mb-4 mt-8">
                <div>
                    <h3 className="text-lg font-semibold text-slate-900">{__('general.financial_overview') || 'Financial Overview'}</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                        {__('general.all_amounts_in') || 'All amounts in'} {businessCurrency}
                    </p>
                </div>
                <Link
                    href={route('admin.finance.report.export', { type: 'pnl' })}
                    target="_blank"
                    rel="noreferrer"
                    className="text-sm text-slate-600 hover:text-black flex items-center gap-1"
                >
                    {__('general.export_csv')}
                    <ArrowUpRight className="w-3.5 h-3.5" />
                </Link>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <MetricCard
                    label={__('general.lifetime_income') || 'Lifetime Income'}
                    value={formatMoney(stats.lifetime_income ?? 0, businessCurrency)}
                    icon={TrendingUp}
                />
                <MetricCard
                    label={__('general.lifetime_expenses') || 'Lifetime Expenses'}
                    value={formatMoney(stats.lifetime_expenses ?? 0, businessCurrency)}
                    icon={TrendingDown}
                />
                <div className="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-slate-500">{__('general.net_profit') || 'Net Profit'}</p>
                        {isPositiveProfit ? (
                            <ArrowUpRight className="h-4 w-4 text-emerald-500" />
                        ) : (
                            <ArrowDownRight className="h-4 w-4 text-rose-500" />
                        )}
                    </div>
                    <div className={cn(
                        'mt-2 text-2xl font-semibold tracking-tight font-mono',
                        isPositiveProfit ? 'text-emerald-600' : 'text-rose-600',
                    )}>
                        {formatMoney(stats.net_profit ?? 0, businessCurrency)}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}