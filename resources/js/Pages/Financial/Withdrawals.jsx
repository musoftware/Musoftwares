import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Wallet, ArrowUpRight, CheckCircle2, AlertCircle, Plus, CreditCard } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Withdrawals({ withdrawals, payoutMethods, wallet }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        payout_method_id: payoutMethods?.[0]?.id || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('financial.withdrawals.store'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            },
        });
    };

    const maxAvailable = Number(wallet?.earned_balance || 0);

    return (
        <AuthenticatedLayout header="Request Withdrawal">
            <Head title="Withdrawals" />

            <div className="max-w-7xl mx-auto space-y-8">
                {/* Header Summary Card */}
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
                    <div className="space-y-2 text-center md:text-left">
                        <span className="text-xs uppercase tracking-wider font-semibold text-indigo-400">Available Earned Funds</span>
                        <div className="text-4xl sm:text-5xl font-extrabold tracking-tight">
                            {maxAvailable.toFixed(2)} <span className="text-xl sm:text-2xl font-normal text-slate-400">{wallet?.currency || 'USD'}</span>
                        </div>
                        <p className="text-xs text-slate-300">You can only withdraw funds that have been earned on the platform.</p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-center gap-3">
                        {(!payoutMethods || payoutMethods.length === 0) ? (
                            <Link href={route('financial.payout-methods.index')} className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-semibold transition-all shadow-md">
                                <CreditCard className="w-5 h-5" /> Setup Payout Method First
                            </Link>
                        ) : (
                            <button
                                onClick={() => setIsModalOpen(true)}
                                disabled={maxAvailable <= 0}
                                className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold transition-all shadow-md"
                            >
                                <ArrowUpRight className="w-5 h-5" /> Request Payout
                            </button>
                        )}
                    </div>
                </div>

                {/* Withdrawals Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-900">Withdrawal History</h2>
                        <span className="text-xs font-medium px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">
                            {withdrawals?.total || 0} Total Requests
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th className="py-3.5 px-6 font-medium">ID #</th>
                                    <th className="py-3.5 px-6 font-medium">Payout Method</th>
                                    <th className="py-3.5 px-6 font-medium">Amount</th>
                                    <th className="py-3.5 px-6 font-medium">Status</th>
                                    <th className="py-3.5 px-6 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-sm">
                                {(!withdrawals?.data || withdrawals.data.length === 0) ? (
                                    <tr>
                                        <td colSpan={5} className="py-12 text-center text-slate-400 font-light">
                                            No withdrawal requests found.
                                        </td>
                                    </tr>
                                ) : (
                                    withdrawals.data.map((w) => (
                                        <tr key={w.id} className="hover:bg-slate-50/75 transition-colors">
                                            <td className="py-4 px-6 font-medium text-slate-900">#{w.id}</td>
                                            <td className="py-4 px-6 font-medium text-slate-700 capitalize">
                                                {w.payout_method ? w.payout_method.type.replace('_', ' ') : 'Standard Method'}
                                            </td>
                                            <td className="py-4 px-6 font-semibold text-slate-900">
                                                {Number(w.amount).toFixed(2)} {w.currency || 'USD'}
                                            </td>
                                            <td className="py-4 px-6">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium capitalize ${
                                                    w.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' :
                                                    w.status === 'approved' ? 'bg-blue-50 text-blue-700 border border-blue-100' :
                                                    w.status === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-100' :
                                                    'bg-amber-50 text-amber-700 border border-amber-100'
                                                }`}>
                                                    {w.status}
                                                </span>
                                            </td>
                                            <td className="py-4 px-6 text-slate-500 text-xs">
                                                {new Date(w.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Request Payout Modal */}
                <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)}>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <Wallet className="w-5 h-5 text-indigo-600" /> Request Withdrawal
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="amount" value={`Amount to Withdraw (Max: ${maxAvailable.toFixed(2)} ${wallet?.currency || 'USD'})`} />
                                <div className="relative mt-1">
                                    <TextInput
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max={maxAvailable}
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="w-full pr-16 text-lg font-semibold"
                                        placeholder="0.00"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setData('amount', maxAvailable.toString())}
                                        className="absolute right-2 top-1/2 -translate-y-1/2 px-2.5 py-1 text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-md transition-colors"
                                    >
                                        MAX
                                    </button>
                                </div>
                                <InputError message={errors.amount} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="payout_method" value="Destination Payout Method" />
                                <select
                                    id="payout_method"
                                    value={data.payout_method_id}
                                    onChange={(e) => setData('payout_method_id', e.target.value)}
                                    className="mt-1 block w-full rounded-xl border-slate-200 font-medium text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    {payoutMethods?.map((pm) => (
                                        <option key={pm.id} value={pm.id}>
                                            {pm.type.replace('_', ' ').toUpperCase()} - {pm.details?.bank_name || pm.details?.paypal_email || pm.details?.wallet_address || 'Account'}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.payout_method_id} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <SecondaryButton onClick={() => setIsModalOpen(false)}>Cancel</SecondaryButton>
                                <PrimaryButton type="submit" disabled={processing}>
                                    Confirm Withdrawal
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
