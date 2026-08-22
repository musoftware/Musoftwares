import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { 
    Wallet, ArrowUpRight, ShieldAlert, CreditCard, 
    ArrowLeft, CheckCircle2, Clock, XCircle, Sparkles, Check 
} from 'lucide-react';
import Modal from '@/Components/Modal';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Withdrawals({ auth, withdrawals, payoutMethods, wallet }) {
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
    const currency = wallet?.currency || 'EGP';

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.withdrawals')} — Musoftwares Studio`} />

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
                                {__('general.request_withdrawal')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('general.you_can_only_withdraw_funds_that_have_been_earned_on_the_platform')}
                            </p>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-3 shrink-0">
                            {!auth?.user?.kyc_verified ? (
                                <Link
                                    href={route('kyc.index')}
                                    className="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-amber-500/20"
                                >
                                    <ShieldAlert className="w-4 h-4" />
                                    <span>Verify KYC Identity</span>
                                </Link>
                            ) : (!payoutMethods || payoutMethods.length === 0) ? (
                                <Link
                                    href={route('financial.payout-methods.index')}
                                    className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20"
                                >
                                    <CreditCard className="w-4 h-4" />
                                    <span>{__('general.setup_payout_method')}</span>
                                </Link>
                            ) : (
                                <button
                                    onClick={() => setIsModalOpen(true)}
                                    disabled={maxAvailable <= 0}
                                    className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer"
                                >
                                    <ArrowUpRight className="w-4 h-4" />
                                    <span>{__('general.request_payout')}</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* Available Earned Balance Bento Tile */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="flex items-center gap-4">
                            <div className="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 shrink-0">
                                <Wallet className="w-7 h-7" />
                            </div>
                            <div>
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50">
                                    {__('general.available_earned_funds')}
                                </span>
                                <div className="text-3xl sm:text-4xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                    {formatMoney(maxAvailable, currency)}
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 text-xs text-[#1d1d1f]/60 bg-[#f5f5f7] px-4 py-2.5 rounded-xl border border-black/5">
                            <CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0" />
                            <span>Available balance ready for instant payout dispatch.</span>
                        </div>
                    </div>

                    {/* Withdrawal History Card */}
                    <div className="bg-white border border-black/5 rounded-[24px] shadow-sm overflow-hidden">
                        <div className="p-6 border-b border-black/5 flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {__('general.withdrawal_history')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                    {__('general.view_your_past_withdrawal_requests_and_their_statuses')}
                                </p>
                            </div>
                            <span className="text-[11px] font-semibold px-3 py-1 bg-[#f5f5f7] text-[#1d1d1f]/70 rounded-full border border-black/5">
                                {withdrawals?.total || 0} Requests
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-start border-collapse">
                                <thead>
                                    <tr className="bg-[#f5f5f7]/60 text-[11px] font-semibold text-[#1d1d1f]/60 uppercase tracking-wider border-b border-black/5">
                                        <th className="py-3.5 ps-6 pe-4 text-start">ID #</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.payout_method')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.amount')}</th>
                                        <th className="py-3.5 px-4 text-start">{__('general.status')}</th>
                                        <th className="py-3.5 ps-4 pe-6 text-end">{__('general.date')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-black/5">
                                    {(!withdrawals?.data || withdrawals.data.length === 0) ? (
                                        <tr>
                                            <td colSpan={5} className="py-16 text-center text-xs text-[#1d1d1f]/50">
                                                <Wallet className="w-8 h-8 mx-auto text-[#1d1d1f]/30 mb-2" />
                                                {__('general.no_withdrawal_requests_found')}
                                            </td>
                                        </tr>
                                    ) : (
                                        withdrawals.data.map((w) => {
                                            const isPaid = w.status === 'paid';
                                            const isApproved = w.status === 'approved';
                                            const isRejected = w.status === 'rejected';
                                            return (
                                                <tr key={w.id} className="hover:bg-[#f5f5f7]/50 transition-colors">
                                                    <td className="py-4 ps-6 pe-4 font-mono text-xs font-semibold text-[#1d1d1f]/70">
                                                        #{w.id}
                                                    </td>
                                                    <td className="py-4 px-4 text-xs sm:text-sm font-semibold text-[#1d1d1f] capitalize">
                                                        {w.payout_method ? w.payout_method.type.replace('_', ' ') : 'Standard Method'}
                                                    </td>
                                                    <td className="py-4 px-4 font-mono font-bold text-xs sm:text-sm text-[#1d1d1f]">
                                                        {formatMoney(w.amount, w.currency || currency)}
                                                    </td>
                                                    <td className="py-4 px-4">
                                                        <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border capitalize ${
                                                            isPaid
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60'
                                                                : isApproved
                                                                    ? 'bg-blue-50 text-blue-700 border-blue-200/60'
                                                                    : isRejected
                                                                        ? 'bg-rose-50 text-rose-700 border-rose-200/60'
                                                                        : 'bg-amber-50 text-amber-700 border-amber-200/60'
                                                        }`}>
                                                            {isPaid && <CheckCircle2 className="w-3 h-3" />}
                                                            {isRejected && <XCircle className="w-3 h-3" />}
                                                            {!isPaid && !isRejected && <Clock className="w-3 h-3" />}
                                                            {w.status}
                                                        </span>
                                                    </td>
                                                    <td className="py-4 ps-4 pe-6 text-end text-xs text-[#1d1d1f]/60 font-sans">
                                                        {new Date(w.created_at).toLocaleDateString(undefined, {
                                                            year: 'numeric', month: 'short', day: 'numeric'
                                                        })}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                {/* Request Payout Modal */}
                <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="md">
                    <div className="p-6 sm:p-8 space-y-6">
                        <div className="flex items-center gap-3 pb-4 border-b border-black/5">
                            <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                <Wallet className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-lg font-bold text-[#1d1d1f] font-sans">
                                    {__('general.request_withdrawal')}
                                </h2>
                                <p className="text-xs text-[#1d1d1f]/60">
                                    Available: {formatMoney(maxAvailable, currency)}
                                </p>
                            </div>
                        </div>
                        
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="amount" className="text-xs font-semibold text-[#1d1d1f]">
                                    Amount to Withdraw ({currency})
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max={maxAvailable}
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="h-11 pe-20 font-bold text-sm bg-white border border-black/10 rounded-xl focus:ring-2 focus:ring-[#0071e3]"
                                        placeholder="0.00"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setData('amount', maxAvailable.toString())}
                                        className="absolute end-2 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] text-[11px] font-bold rounded-lg border border-black/5 transition-colors cursor-pointer"
                                    >
                                        {__('general.max')}
                                    </button>
                                </div>
                                {errors.amount && <p className="text-xs font-medium text-rose-600">{errors.amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payout_method" className="text-xs font-semibold text-[#1d1d1f]">
                                    {__('general.destination_payout_method')}
                                </Label>
                                <select
                                    id="payout_method"
                                    value={data.payout_method_id}
                                    onChange={(e) => setData('payout_method_id', e.target.value)}
                                    className="flex h-11 w-full rounded-xl border border-black/10 bg-white px-3.5 py-2 text-xs sm:text-sm font-medium text-[#1d1d1f] focus:outline-none focus:ring-2 focus:ring-[#0071e3]"
                                    required
                                >
                                    {payoutMethods?.map((pm) => (
                                        <option key={pm.id} value={pm.id}>
                                            {pm.type.replace('_', ' ').toUpperCase()} — {pm.details?.bank_name || pm.details?.instapay_username || pm.details?.mobile_number || pm.details?.paypal_email || 'Account'}
                                        </option>
                                    ))}
                                </select>
                                {errors.payout_method_id && <p className="text-xs font-medium text-rose-600">{errors.payout_method_id}</p>}
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-black/5">
                                <button
                                    type="button"
                                    onClick={() => setIsModalOpen(false)}
                                    className="px-5 py-2.5 text-xs font-semibold text-[#1d1d1f]/70 hover:text-[#1d1d1f] rounded-full transition-colors cursor-pointer"
                                >
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] shadow-sm shadow-blue-500/20 transition-all cursor-pointer"
                                >
                                    {__('general.confirm_withdrawal')}
                                </button>
                            </div>
                        </form>
                    </div>
                </Modal>

            </div>
        </AuthenticatedLayout>
    );
}
