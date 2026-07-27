import React from 'react';
import { ResponsiveContainer, AreaChart, Area, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';
import { ArrowDownRight, ArrowUpRight, Activity, Terminal } from 'lucide-react';
import { __ } from '@/lib/i18n';
import type { ChartData } from '../types';

interface ActivityFeedItem {
    id: string;
    description: string;
    created_at: string;
    icon: string;
    isDeposit: boolean;
}

interface SciFiFinancialStreamProps {
    chartData: ChartData[];
    activityFeedItems: ActivityFeedItem[];
}

export default function SciFiFinancialStream({
    chartData = [],
    activityFeedItems = [],
}: SciFiFinancialStreamProps) {
    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {/* Left 8 Cols: Neon Telemetry Chart */}
            <div className="lg:col-span-8 scifi-panel p-6 rounded-2xl relative">
                <div className="scifi-corner-tl" />
                <div className="scifi-corner-tr" />
                <div className="scifi-corner-bl" />
                <div className="scifi-corner-br" />

                <div className="flex items-center justify-between border-b border-[var(--scifi-panel-border)] pb-4 mb-6">
                    <div className="flex items-center gap-3">
                        <Activity className="h-5 w-5 text-[var(--scifi-primary-light)]" />
                        <h3 className="font-mono text-base font-bold text-slate-100 uppercase tracking-wider">
                            [ {__('general.account_activity_last_6_months')} ]
                        </h3>
                    </div>
                    <div className="flex items-center gap-4 text-xs font-mono">
                        <div className="flex items-center gap-1.5">
                            <span className="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]" />
                            <span className="text-slate-300">{__('general.deposits')}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <span className="h-2.5 w-2.5 rounded-full bg-[var(--scifi-primary)] shadow-[0_0_8px_var(--scifi-primary)]" />
                            <span className="text-slate-300">{__('general.expenses')}</span>
                        </div>
                    </div>
                </div>

                <div className="h-72 w-full">
                    {chartData.length > 0 ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                <defs>
                                    <linearGradient id="scifiDepositGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#10b981" stopOpacity={0.4} />
                                        <stop offset="95%" stopColor="#10b981" stopOpacity={0.0} />
                                    </linearGradient>
                                    <linearGradient id="scifiExpenseGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="var(--scifi-primary)" stopOpacity={0.4} />
                                        <stop offset="95%" stopColor="var(--scifi-primary)" stopOpacity={0.0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.06)" />
                                <XAxis dataKey="month" stroke="#64748b" tick={{ fill: '#94a3b8', fontSize: 11, fontFamily: 'monospace' }} />
                                <YAxis stroke="#64748b" tick={{ fill: '#94a3b8', fontSize: 11, fontFamily: 'monospace' }} />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: '#090d16',
                                        borderColor: 'var(--scifi-panel-border)',
                                        borderRadius: '8px',
                                        fontFamily: 'monospace',
                                        color: '#e2e8f0',
                                    }}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="deposit"
                                    stroke="#10b981"
                                    strokeWidth={2}
                                    fillOpacity={1}
                                    fill="url(#scifiDepositGrad)"
                                />
                                <Area
                                    type="monotone"
                                    dataKey="expense"
                                    stroke="var(--scifi-primary)"
                                    strokeWidth={2}
                                    fillOpacity={1}
                                    fill="url(#scifiExpenseGrad)"
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="flex h-full items-center justify-center font-mono text-sm text-slate-500">
                            NO_TELEMETRY_DATA
                        </div>
                    )}
                </div>
            </div>

            {/* Right 4 Cols: Sci-Fi Log Stream */}
            <div className="lg:col-span-4 scifi-panel p-6 rounded-2xl relative flex flex-col justify-between">
                <div className="scifi-corner-tl" />
                <div className="scifi-corner-tr" />
                <div className="scifi-corner-bl" />
                <div className="scifi-corner-br" />

                <div>
                    <div className="flex items-center justify-between border-b border-[var(--scifi-panel-border)] pb-4 mb-4">
                        <div className="flex items-center gap-2">
                            <Terminal className="h-5 w-5 text-[var(--scifi-primary-light)]" />
                            <h3 className="font-mono text-base font-bold text-slate-100 uppercase tracking-wider">
                                [ {__('general.scifi_financial_stream')} ]
                            </h3>
                        </div>
                        <span className="font-mono text-[10px] text-emerald-400 animate-pulse">
                            ● STREAMING
                        </span>
                    </div>

                    <div className="space-y-3 max-h-64 overflow-y-auto pr-1">
                        {activityFeedItems.length > 0 ? (
                            activityFeedItems.map((item) => (
                                <div
                                    key={item.id}
                                    className="p-3 rounded-lg border border-[var(--scifi-panel-border)] bg-[rgba(15,23,42,0.5)] flex items-start gap-3 hover:border-[var(--scifi-primary)] transition-colors"
                                >
                                    <div className={`p-1.5 rounded-md ${item.isDeposit ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'}`}>
                                        {item.isDeposit ? (
                                            <ArrowUpRight className="h-4 w-4" />
                                        ) : (
                                            <ArrowDownRight className="h-4 w-4" />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-xs font-mono text-slate-200 truncate">
                                            {item.description}
                                        </p>
                                        <span className="text-[10px] font-mono text-slate-500">
                                            {item.created_at}
                                        </span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="text-center py-8 font-mono text-xs text-slate-500">
                                {__('general.no_recent_transactions')}
                            </div>
                        )}
                    </div>
                </div>

                <div className="mt-4 pt-3 border-t border-[var(--scifi-panel-border)] text-[10px] font-mono text-slate-400 text-center">
                    LOG_BUFFER :: 64_ENTRIES_MAX
                </div>
            </div>
        </div>
    );
}
