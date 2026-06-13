import React from 'react';
import { Link } from '@inertiajs/react';
import { Inbox, Wallet, History } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import type { ChartData } from '../types';

interface ActivityFeedItem {
    id: number;
    description: string;
    created_at: string;
    icon: string;
    color: string;
}

interface FinancialHistoryProps {
    chartData: ChartData[];
    activityFeedItems: ActivityFeedItem[];
    safeRoute: (name: string, params?: any, fallbackUrl?: string) => string;
}

export default function FinancialHistory({ chartData, activityFeedItems, safeRoute }: FinancialHistoryProps) {
    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {/* Financial Chart (8-col equivalent) */}
            <div className="lg:col-span-2">
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full">
                    <h2 className="text-xl font-bold text-slate-900 mb-6">{__('general.account_activity_last_6_months')}</h2>
                    
                    <div className="h-[300px] w-full">
                        <ResponsiveContainer width="100%" height="100%" minWidth={0} minHeight={300}>
                            <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                <Tooltip 
                                    cursor={{ fill: '#f8fafc' }}
                                    contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                />
                                <Legend wrapperStyle={{ paddingTop: '20px' }} />
                                <Bar dataKey="deposit" name={__('general.deposits')} fill="#10b981" radius={[4, 4, 0, 0]} maxBarSize={40} />
                                <Bar dataKey="expense" name={__('general.expenses')} fill="#f59e0b" radius={[4, 4, 0, 0]} maxBarSize={40} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            {/* Recent Transactions (4-col equivalent) */}
            <div className="lg:col-span-1">
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col">
                    <h2 className="text-xl font-bold text-slate-900 mb-6">{__('general.latest_transactions')}</h2>
                    
                    <div className="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                        {activityFeedItems.length === 0 ? (
                            <div className="text-center py-6">
                                <Inbox className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                <p className="text-sm text-slate-500">{__('general.no_transactions_yet')}</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {activityFeedItems.slice(0, 5).map((txn) => (
                                    <div key={txn.id} className="flex items-start gap-3 pb-4 border-b border-slate-100 last:border-0">
                                        <div className={cn("w-8 h-8 rounded-full flex items-center justify-center shrink-0", txn.color === 'emerald' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600')}>
                                            {txn.icon === 'wallet' ? <Wallet className="w-4 h-4" /> : <History className="w-4 h-4" />}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-slate-900 leading-tight">{txn.description}</p>
                                            <p className="text-xs text-slate-500 mt-1">{txn.created_at}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                    
                    <div className="pt-4 mt-auto border-t border-slate-100">
                        <Link 
                            href={safeRoute('financial.transactions', undefined, '/financial/transactions')} 
                            className="flex items-center justify-center w-full py-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700 hover:text-slate-900"
                        >{__('general.view_full_history')}</Link>
                    </div>
                </div>
            </div>

        </div>
    );
}
