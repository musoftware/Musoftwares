import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    Wallet, ArrowUpRight, ArrowDownLeft, Clock, 
    CheckCircle2, AlertCircle, Plus, ArrowLeft, ArrowDownRight 
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatMoney } from '@/lib/utils';

export default function Transactions({ transactions, wallet }) {
    const currency = wallet?.currency || 'EGP';

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.transactions')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-1.5">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.transactions')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('general.view_your_wallet_transaction_history_and_ledgers')}
                            </p>
                        </div>

                        <div className="flex items-center gap-3 shrink-0">
                            <Link
                                href="/financial/add-balance"
                                className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer"
                            >
                                <Plus className="w-4 h-4" />
                                <span>{__('general.add_balance')}</span>
                            </Link>
                            <Link
                                href="/financial/withdrawals"
                                className="px-5 py-2.5 border border-black/10 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] text-xs font-semibold rounded-[980px] transition-all shadow-sm flex items-center gap-2 cursor-pointer"
                            >
                                <ArrowUpRight className="w-4 h-4 text-[#1d1d1f]/60" />
                                <span>{__('general.request_withdrawal')}</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* 3-Pillar Balances Bento */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* 1. Total Balance */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50">
                                    {__('general.total_balance')}
                                </span>
                                <div className="w-9 h-9 rounded-xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Wallet className="w-4 h-4" />
                                </div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {formatMoney(wallet?.balance || 0, currency)}
                                </div>
                                <p className="text-xs text-[#1d1d1f]/60 mt-1">
                                    {__('general.available_across_all_platform_workspaces')}
                                </p>
                            </div>
                        </div>

                        {/* 2. Earned Balance */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex flex-col justify-between group hover:border-emerald-500/30 hover:shadow-md transition-all">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50">
                                    {__('general.earned_balance')}
                                </span>
                                <div className="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                                    <ArrowDownLeft className="w-4 h-4" />
                                </div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {formatMoney(wallet?.earned_balance || 0, currency)}
                                </div>
                                <p className="text-xs text-emerald-600 font-semibold flex items-center gap-1 mt-1">
                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                    {__('general.eligible_for_withdrawal')}
                                </p>
                            </div>
                        </div>

                        {/* 3. Locked / Pending */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex flex-col justify-between group hover:border-amber-500/30 hover:shadow-md transition-all">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50">
                                    {__('general.locked_pending')}
                                </span>
                                <div className="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                                    <Clock className="w-4 h-4" />
                                </div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {formatMoney(wallet?.locked_balance || 0, currency)}
                                </div>
                                <p className="text-xs text-amber-600 font-semibold flex items-center gap-1 mt-1">
                                    <AlertCircle className="w-3.5 h-3.5" />
                                    {__('general.pending_in_active_contracts')}
                                </p>
                            </div>
                        </div>

                    </div>

                    {/* Transactions Ledger Card */}
                    <div className="bg-white border border-black/5 rounded-[24px] shadow-sm overflow-hidden">
                        <div className="p-6 border-b border-black/5 flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {__('general.transaction_history')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                    {__('general.a_list_of_your_recent_transactions')}
                                </p>
                            </div>
                            <span className="text-[11px] font-semibold px-3 py-1 bg-[#f5f5f7] text-[#1d1d1f]/70 rounded-full border border-black/5">
                                {transactions?.total || 0} Records
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-start border-collapse">
                                <thead>
                                    <tr className="bg-[#f5f5f7]/60 text-[11px] font-semibold text-[#1d1d1f]/60 uppercase tracking-wider border-b border-black/5">
                                        <th className="py-3.5 ps-6 pe-4 text-start">{__('general.type')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.description')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.amount')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.balance_before')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.balance_after')}</th>
                                        <th className="py-3.5 ps-4 pe-6 text-end">{__('general.date')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-black/5">
                                    {(!transactions?.data || transactions.data.length === 0) ? (
                                        <tr>
                                            <td colSpan={6} className="py-16 text-center text-xs text-[#1d1d1f]/50">
                                                <Wallet className="w-8 h-8 mx-auto text-[#1d1d1f]/30 mb-2" />
                                                {__('general.no_transactions_found')}
                                            </td>
                                        </tr>
                                    ) : (
                                        transactions.data.map((tx) => {
                                            const isCredit = tx.type === 'credit';
                                            return (
                                                <tr key={tx.id} className="hover:bg-[#f5f5f7]/50 transition-colors">
                                                    <td className="py-4 ps-6 pe-4">
                                                        <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border ${
                                                            isCredit
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60'
                                                                : 'bg-rose-50 text-rose-700 border-rose-200/60'
                                                        }`}>
                                                            {isCredit ? (
                                                                <ArrowDownLeft className="w-3 h-3" />
                                                            ) : (
                                                                <ArrowUpRight className="w-3 h-3" />
                                                            )}
                                                            {isCredit ? 'Credit' : 'Debit'}
                                                        </span>
                                                    </td>
                                                    <td className="py-4 px-4">
                                                        <p className="text-xs sm:text-sm font-semibold text-[#1d1d1f]">
                                                            {tx.description || 'System transaction'}
                                                        </p>
                                                        {tx.reference_type && (
                                                            <span className="text-[10px] text-[#1d1d1f]/50 font-mono">
                                                                Ref: {tx.reference_type}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className={`py-4 px-4 font-mono font-bold text-xs sm:text-sm ${
                                                        isCredit ? 'text-emerald-600' : 'text-rose-600'
                                                    }`}>
                                                        {isCredit ? '+' : '-'}{formatMoney(tx.amount || 0, currency)}
                                                    </td>
                                                    <td className="py-4 px-4 font-mono text-xs text-[#1d1d1f]/60">
                                                        {formatMoney(tx.balance_before || 0, currency)}
                                                    </td>
                                                    <td className="py-4 px-4 font-mono text-xs font-semibold text-[#1d1d1f]">
                                                        {formatMoney(tx.balance_after || 0, currency)}
                                                    </td>
                                                    <td className="py-4 ps-4 pe-6 text-end text-xs text-[#1d1d1f]/60 font-sans">
                                                        {new Date(tx.created_at).toLocaleDateString(undefined, {
                                                            year: 'numeric', month: 'short', day: 'numeric',
                                                            hour: '2-digit', minute: '2-digit'
                                                        })}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Footer */}
                        {transactions?.links && transactions.links.length > 3 && (
                            <div className="p-4 border-t border-black/5 flex items-center justify-end gap-1">
                                {transactions.links.map((link, idx) => {
                                    const isCurrent = link.active;
                                    return (
                                        <Link
                                            key={idx}
                                            href={link.url || '#'}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                                isCurrent
                                                    ? 'bg-[#1d1d1f] text-white shadow-xs'
                                                    : link.url
                                                        ? 'bg-white border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7]'
                                                        : 'opacity-40 pointer-events-none bg-white text-[#1d1d1f]/40'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        )}
                    </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
