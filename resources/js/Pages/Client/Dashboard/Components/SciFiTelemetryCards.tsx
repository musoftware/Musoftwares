import React from 'react';
import { Wallet, Award, CreditCard, AlertCircle, HelpCircle, Activity } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import type { DashboardStats } from '../types';

interface SciFiTelemetryCardsProps {
    stats: DashboardStats;
}

export default function SciFiTelemetryCards({ stats }: SciFiTelemetryCardsProps) {
    const cards = [
        {
            id: 'wallet',
            label: __('general.account_balance'),
            value: formatMoney(stats.walletBalance, stats.currency),
            subtext: stats.outstandingBalance > 0 
                ? `${__('general.outstanding_balance')}: ${formatMoney(stats.outstandingBalance, stats.currency)}`
                : __('general.scifi_all_systems_operational'),
            icon: <Wallet className="h-5 w-5" />,
            badge: 'LIVE_BAL',
            color: 'var(--scifi-primary-light)',
        },
        {
            id: 'commission',
            label: __('general.pending_commission'),
            value: formatMoney(stats.earnedBalance, stats.currency),
            subtext: `${stats.pendingWithdrawals} ${__('general.pending_withdrawal_requests')}`,
            icon: <Award className="h-5 w-5" />,
            badge: 'EARNED',
            color: '#10b981',
        },
        {
            id: 'subscriptions',
            label: __('general.active_subscriptions'),
            value: stats.activeSubscriptions.toString(),
            subtext: `${formatMoney(stats.totalMonthlySubscription, stats.currency)} / ${__('general.month')}`,
            icon: <CreditCard className="h-5 w-5" />,
            badge: 'SUBS',
            color: '#3b82f6',
        },
        {
            id: 'unpaid',
            label: __('general.unpaid_invoices'),
            value: stats.unpaidInvoices.toString(),
            subtext: stats.unpaidAmount > 0 
                ? formatMoney(stats.unpaidAmount, stats.currency) 
                : __('general.no_unpaid_invoices'),
            icon: <AlertCircle className="h-5 w-5" />,
            badge: stats.unpaidInvoices > 0 ? 'ACTION_REQ' : 'CLEARED',
            color: stats.unpaidInvoices > 0 ? '#ef4444' : '#10b981',
        },
        {
            id: 'tickets',
            label: __('general.open_support_tickets'),
            value: stats.openTickets.toString(),
            subtext: __('general.support_ticket_response_guaranteed'),
            icon: <HelpCircle className="h-5 w-5" />,
            badge: 'SUPPORT',
            color: '#8b5cf6',
        },
        {
            id: 'points',
            label: __('general.points_balance'),
            value: stats.pointsBalance.toString(),
            subtext: __('general.reward_points'),
            icon: <Activity className="h-5 w-5" />,
            badge: 'PTS',
            color: '#f59e0b',
        },
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            {cards.map((card) => (
                <div 
                    key={card.id}
                    className="scifi-panel p-4 rounded-xl relative flex flex-col justify-between group transition-all duration-300 hover:scale-[1.02]"
                >
                    <div className="scifi-corner-tl" />
                    <div className="scifi-corner-br" />

                    <div>
                        {/* Header Badge */}
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-[10px] font-mono tracking-widest text-[var(--scifi-text-muted)] uppercase truncate">
                                {card.label}
                            </span>
                            <span 
                                className="font-mono text-[9px] px-1.5 py-0.5 rounded font-bold tracking-wider"
                                style={{
                                    backgroundColor: `${card.color}18`,
                                    color: card.color,
                                    border: `1px solid ${card.color}40`,
                                }}
                            >
                                {card.badge}
                            </span>
                        </div>

                        {/* Value & Icon */}
                        <div className="flex items-center justify-between my-1">
                            <span 
                                className="font-mono text-xl font-extrabold tracking-tight"
                                style={{ color: card.color }}
                            >
                                {card.value}
                            </span>
                            <div 
                                className="p-2 rounded-lg"
                                style={{
                                    backgroundColor: `${card.color}15`,
                                    color: card.color,
                                }}
                            >
                                {card.icon}
                            </div>
                        </div>
                    </div>

                    {/* Subtext */}
                    <div className="mt-2 pt-2 border-t border-[var(--scifi-panel-border)] text-[10px] font-mono text-slate-400 truncate">
                        {card.subtext}
                    </div>
                </div>
            ))}
        </div>
    );
}
