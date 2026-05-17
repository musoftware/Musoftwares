import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Wallet, ArrowUpRight, ArrowDownLeft, Clock, CheckCircle2, AlertCircle } from 'lucide-react';

export default function Transactions({ transactions, wallet }) {
    return (
        <AuthenticatedLayout header="Financial Transactions">
            <Head title="Transactions" />

            <div className="max-w-7xl mx-auto space-y-8">
                {/* Balance Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                        <div className="absolute -right-6 -bottom-6 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl"></div>
                        <div className="flex items-center justify-between mb-4">
                            <span className="text-sm font-medium text-slate-300">Total Balance</span>
                            <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                                <Wallet className="w-5 h-5 text-indigo-400" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold tracking-tight">
                            {Number(wallet?.balance || 0).toFixed(2)} <span className="text-lg font-normal text-slate-400">{wallet?.currency || 'USD'}</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-400">Available across all platform workspaces</p>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-sm font-medium text-slate-500">Earned Balance</span>
                                <div className="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                                    <ArrowDownLeft className="w-5 h-5 text-emerald-600" />
                                </div>
                            </div>
                            <div className="text-3xl font-bold tracking-tight text-slate-900">
                                {Number(wallet?.earned_balance || 0).toFixed(2)} <span className="text-lg font-normal text-slate-400">{wallet?.currency || 'USD'}</span>
                            </div>
                        </div>
                        <p className="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-2">
                            <CheckCircle2 className="w-3.5 h-3.5" /> Eligible for withdrawal
                        </p>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-sm font-medium text-slate-500">Locked / Pending</span>
                                <div className="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-amber-600" />
                                </div>
                            </div>
                            <div className="text-3xl font-bold tracking-tight text-slate-900">
                                {Number(wallet?.locked_balance || 0).toFixed(2)} <span className="text-lg font-normal text-slate-400">{wallet?.currency || 'USD'}</span>
                            </div>
                        </div>
                        <p className="text-xs text-amber-600 font-medium flex items-center gap-1 mt-2">
                            <AlertCircle className="w-3.5 h-3.5" /> Pending in active contracts or requests
                        </p>
                    </div>
                </div>

                {/* Transactions Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-900">Transaction History</h2>
                        <span className="text-xs font-medium px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">
                            {transactions?.total || 0} Total Records
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th className="py-3.5 px-6 font-medium">Type</th>
                                    <th className="py-3.5 px-6 font-medium">Description</th>
                                    <th className="py-3.5 px-6 font-medium">Amount</th>
                                    <th className="py-3.5 px-6 font-medium">Balance Before</th>
                                    <th className="py-3.5 px-6 font-medium">Balance After</th>
                                    <th className="py-3.5 px-6 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                                {(!transactions?.data || transactions.data.length === 0) ? (
                                    <tr>
                                        <td colSpan={6} className="py-12 text-center text-slate-400">
                                            No transactions found.
                                        </td>
                                    </tr>
                                ) : (
                                    transactions.data.map((tx) => (
                                        <tr key={tx.id} className="hover:bg-slate-50/75 transition-colors">
                                            <td className="py-4 px-6 font-medium">
                                                {tx.type === 'credit' ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                        <ArrowDownLeft className="w-3.5 h-3.5" /> Credit
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                                        <ArrowUpRight className="w-3.5 h-3.5" /> Debit
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6">
                                                <p className="font-medium text-slate-900">{tx.description || 'System transaction'}</p>
                                                {tx.reference_type && (
                                                    <span className="text-xs text-slate-400">Ref: {tx.reference_type}</span>
                                                )}
                                            </td>
                                            <td className={`py-4 px-6 font-semibold ${tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                {tx.type === 'credit' ? '+' : '-'}{Number(tx.amount).toFixed(2)} {wallet?.currency || 'USD'}
                                            </td>
                                            <td className="py-4 px-6 text-slate-600">
                                                {Number(tx.balance_before).toFixed(2)}
                                            </td>
                                            <td className="py-4 px-6 font-medium text-slate-900">
                                                {Number(tx.balance_after).toFixed(2)}
                                            </td>
                                            <td className="py-4 px-6 text-slate-500 text-xs">
                                                {new Date(tx.created_at).toLocaleDateString(undefined, {
                                                    year: 'numeric', month: 'short', day: 'numeric',
                                                    hour: '2-digit', minute: '2-digit'
                                                })}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {transactions?.links && transactions.links.length > 3 && (
                        <div className="p-4 border-t border-slate-100 flex items-center justify-end gap-1 bg-slate-50/50">
                            {transactions.links.map((link, idx) => (
                                <a
                                    key={idx}
                                    href={link.url || '#'}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
                                        link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-200 bg-white border border-slate-200'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
