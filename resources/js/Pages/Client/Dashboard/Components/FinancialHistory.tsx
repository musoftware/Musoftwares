import React from 'react';
import { Link } from '@inertiajs/react';
import { History, Inbox, Wallet } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { __ } from '@/lib/i18n';
import { safeRoute } from '@/lib/utils';
import type { ChartData } from '../types';

interface ActivityFeedItem {
    id: string | number;
    description: string;
    created_at: string;
    icon: string;
    isDeposit: boolean;
}

interface FinancialHistoryProps {
    chartData: ChartData[];
    activityFeedItems: ActivityFeedItem[];
}

export default function FinancialHistory({ chartData, activityFeedItems }: FinancialHistoryProps) {
    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[65fr_35fr]">
            <div className="min-w-0">
                <div className="h-full rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="mb-6 text-xl font-bold text-slate-900">{__('general.account_activity_last_6_months')}</h2>

                    <div className="h-[300px] w-full">
                        <ResponsiveContainer width="100%" height="100%" minWidth={1} minHeight={300}>
                            <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                <Tooltip
                                    cursor={{ fill: '#f8fafc' }}
                                    contentStyle={{
                                        background: 'rgba(255,255,255,0.7)',
                                        backdropFilter: 'blur(8px)',
                                        WebkitBackdropFilter: 'blur(8px)',
                                        border: '1px solid rgba(226,232,240,0.6)',
                                        borderRadius: '12px',
                                        boxShadow: '0 8px 24px -8px rgb(15 23 42 / 0.15)',
                                    }}
                                />
                                <Legend wrapperStyle={{ paddingTop: '20px' }} />
                                <Bar dataKey="deposit" name={__('general.deposits')} fill="#0f172a" radius={[4, 4, 0, 0]} maxBarSize={40} />
                                <Bar dataKey="expense" name={__('general.expenses')} fill="#e2e8f0" radius={[4, 4, 0, 0]} maxBarSize={40} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            <div className="min-w-0">
                <div className="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="mb-6 text-xl font-bold text-slate-900">{__('general.latest_transactions')}</h2>

                    <div className="custom-scrollbar flex-1 overflow-y-auto pe-2">
                        {activityFeedItems.length === 0 ? (
                            <div className="py-6 text-center">
                                <Inbox className="mx-auto mb-2 h-8 w-8 text-slate-300" aria-hidden="true" />
                                <p className="text-sm text-slate-500">{__('general.no_transactions_yet')}</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {activityFeedItems.slice(0, 5).map((txn) => (
                                    <div key={txn.id} className="flex items-start gap-3 border-b border-slate-100 pb-4 last:border-0">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                                            {txn.icon === 'wallet' ? <Wallet className="h-4 w-4" aria-hidden="true" /> : <History className="h-4 w-4" aria-hidden="true" />}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium leading-tight text-slate-900">{txn.description}</p>
                                            <p className="mt-1 text-xs text-slate-500">{txn.created_at}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="mt-auto border-t border-slate-100 pt-4">
                        <Link
                            href={safeRoute('financial.transactions', undefined, '/financial/transactions')}
                            className="flex w-full items-center justify-center rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900"
                        >
                            {__('general.view_full_history')}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
