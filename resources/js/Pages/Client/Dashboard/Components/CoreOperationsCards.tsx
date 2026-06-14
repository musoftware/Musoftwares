import React from 'react';
import { Link } from '@inertiajs/react';
import { Sparkles, ArrowRight, History, Wallet, FileText, MonitorPlay } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatMoney, safeRoute } from '@/lib/utils';
import type { DashboardStats } from '../types';

interface CoreOperationsCardsProps {
    stats: DashboardStats;
}

export default function CoreOperationsCards({ stats }: CoreOperationsCardsProps) {

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {/* Action Card 1: Subscriptions & Plans */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                <div className="mb-4">
                    <Sparkles className="w-6 h-6 text-slate-900" />
                </div>
                <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.active_subscriptions')}</h3>
                </div>
                <div className="mt-auto pt-4">
                    <div className="flex flex-col gap-2">
                        <Link href="/subscriptions/plans" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-primary hover:bg-slate-50 transition-colors group">
                            <span className="text-sm font-medium text-slate-700 group-hover:text-primary">{__('general.view_tool_plans')}</span>
                            <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-primary" />
                        </Link>
                        <Link href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-primary hover:bg-slate-50 transition-colors group">
                            <span className="text-sm font-medium text-slate-700 group-hover:text-primary">{__('general.subscription_history')}</span>
                            <History className="w-4 h-4 text-slate-400 group-hover:text-primary" />
                        </Link>
                    </div>
                </div>
            </div>

            {/* Action Card 2: Quick Top Up */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                <div className="mb-4">
                    <Wallet className="w-6 h-6 text-slate-900" />
                </div>
                <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.charge_balance')}</h3>
                </div>
                <div className="mt-auto pt-4">
                    <Link 
                        href={safeRoute('financial.add-balance', undefined, '/financial/add-balance')} 
                        className="flex items-center justify-center p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-slate-100 transition-colors group text-slate-700 font-medium text-sm"
                    >
                        {__('general.add_funds')}
                        <ArrowRight className="w-4 h-4 ms-2 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                    </Link>
                </div>
            </div>

            {/* Action Card 3: Billing Quick Access */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                <div className="mb-4">
                    <FileText className="w-6 h-6 text-slate-900" />
                </div>
                <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.billing_invoices')}</h3>
                </div>
                <div className="mt-auto pt-4">
                    <p className="text-xs font-medium text-slate-500 mb-2">{__('general.billing_quick_access')}</p>
                    <div className="flex flex-col gap-2">
                        <Link href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:border-slate-300 transition-colors group">
                            <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">{__('general.unpaid_invoices')}</span>
                            <span className="bg-slate-200 text-slate-700 px-2 py-0.5 rounded text-xs font-bold">{stats.unpaidInvoices}</span>
                        </Link>
                        <Link href={safeRoute('financial.transactions', undefined, '/financial/transactions')} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors group">
                            <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">{__('general.transactions')}</span>
                            <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-slate-900" />
                        </Link>
                    </div>
                </div>
            </div>

            {/* Action Card 4: Runtime App */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                <div className="mb-4">
                    <MonitorPlay className="w-6 h-6 text-slate-900" />
                </div>
                <div>
                    <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.musoftware_runtime')}</h3>
                </div>
                <div className="mt-auto pt-4">
                    <div className="flex flex-col gap-2">
                        <Link href={safeRoute('runtime.download', undefined, '/runtime/download')} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-indigo-500 hover:bg-indigo-50 transition-colors group">
                            <span className="text-sm font-medium text-slate-700 group-hover:text-indigo-700">{__('general.download_runtime')}</span>
                            <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-indigo-700" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
