import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { 
    CreditCard, Plus, Trash2, Edit2, CheckCircle2, 
    Building, DollarSign, Smartphone, Send, ArrowLeft, Check 
} from 'lucide-react';
import Modal from '@/Components/Modal';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';

export default function PayoutMethods({ payoutMethods }) {
    const [isCreating, setIsCreating] = useState(false);
    const [editingMethod, setEditingMethod] = useState(null);

    const { data, setData, post, patch, processing, reset } = useForm({
        type: 'bank_transfer',
        details: { full_name: '', bank_name: '', account_number: '', routing_number: '' },
        is_default: false,
    });

    const handleTypeChange = (type) => {
        if (type === 'bank_transfer') {
            setData({ type, details: { full_name: '', bank_name: '', account_number: '', routing_number: '' }, is_default: data.is_default });
        } else if (type === 'paypal') {
            setData({ type, details: { paypal_email: '' }, is_default: data.is_default });
        } else if (type === 'vodafone_cash') {
            setData({ type, details: { mobile_number: '' }, is_default: data.is_default });
        } else if (type === 'instapay') {
            setData({ type, details: { mobile_number: '', instapay_username: '' }, is_default: data.is_default });
        }
    };

    const handleDetailChange = (key, val) => {
        setData('details', { ...data.details, [key]: val });
    };

    const openEdit = (method) => {
        setEditingMethod(method);
        setData({
            type: method.type,
            details: method.details || {},
            is_default: method.is_default,
        });
        setIsCreating(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingMethod) {
            patch(route('financial.payout-methods.update', editingMethod.id), {
                onSuccess: () => {
                    setIsCreating(false);
                    setEditingMethod(null);
                    reset();
                },
            });
        } else {
            post(route('financial.payout-methods.store'), {
                onSuccess: () => {
                    setIsCreating(false);
                    reset();
                },
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to remove this payout method?')) {
            router.delete(route('financial.payout-methods.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.payout_methods')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href="/financial/withdrawals"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.request_withdrawal')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.stored_payout_methods')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('general.manage_destination_accounts_for_withdrawing_earned_funds')}
                            </p>
                        </div>

                        <button
                            onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }}
                            className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer shrink-0"
                        >
                            <Plus className="w-4 h-4" />
                            <span>{__('general.add_new_method')}</span>
                        </button>
                    </div>
                </div>

                {/* Main Grid */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8">
                    {(!payoutMethods || payoutMethods.length === 0) ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm max-w-xl mx-auto">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <CreditCard className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                {__('general.no_payout_methods_configured')}
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1.5 mb-6 leading-relaxed">
                                {__('general.add_a_bank_account_paypal_vodafone_cash_or_instapay_to_enable_fast_secure_withdrawals_from_your_earned_balance')}
                            </p>
                            <button
                                onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }}
                                className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] shadow-sm shadow-blue-500/20 transition-all cursor-pointer inline-flex items-center gap-2"
                            >
                                <Plus className="w-4 h-4" />
                                <span>{__('general.setup_payout_method')}</span>
                            </button>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {payoutMethods.map((pm) => (
                                <div
                                    key={pm.id}
                                    className={`bg-white border rounded-[24px] p-6 shadow-sm flex flex-col justify-between transition-all relative overflow-hidden ${
                                        pm.is_default
                                            ? 'border-[#0071e3] ring-2 ring-[#0071e3]/10'
                                            : 'border-black/5 hover:border-[#0071e3]/30 hover:shadow-md'
                                    }`}
                                >
                                    <div>
                                        <div className="flex items-center justify-between mb-5">
                                            <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 border border-[#0071e3]/15 flex items-center justify-center text-[#0071e3]">
                                                {pm.type === 'bank_transfer' && <Building className="w-6 h-6" />}
                                                {pm.type === 'paypal' && <DollarSign className="w-6 h-6" />}
                                                {pm.type === 'vodafone_cash' && <Smartphone className="w-6 h-6" />}
                                                {pm.type === 'instapay' && <Send className="w-6 h-6" />}
                                            </div>
                                            {pm.is_default ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/60 font-mono">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    {__('general.default')}
                                                </span>
                                            ) : (
                                                <span className="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/60 font-mono capitalize">
                                                    {pm.status}
                                                </span>
                                            )}
                                        </div>

                                        <h3 className="text-base font-bold text-[#1d1d1f] font-sans capitalize mb-3">
                                            {pm.type.replace('_', ' ')}
                                        </h3>

                                        <div className="space-y-1.5 p-4 bg-[#f5f5f7] rounded-[16px] border border-black/5 text-xs text-[#1d1d1f]/70 font-sans">
                                            {pm.type === 'bank_transfer' && (
                                                <>
                                                    <div className="flex justify-between"><span>Name:</span> <span className="font-semibold text-[#1d1d1f]">{pm.details?.full_name}</span></div>
                                                    <div className="flex justify-between"><span>Bank:</span> <span className="font-semibold text-[#1d1d1f]">{pm.details?.bank_name}</span></div>
                                                    <div className="flex justify-between"><span>Acc:</span> <span className="font-mono font-semibold text-[#1d1d1f]">••••{pm.details?.account_number?.slice(-4)}</span></div>
                                                </>
                                            )}
                                            {pm.type === 'paypal' && (
                                                <div className="flex flex-col gap-0.5"><span>Email:</span> <span className="font-semibold text-[#1d1d1f]">{pm.details?.paypal_email}</span></div>
                                            )}
                                            {pm.type === 'vodafone_cash' && (
                                                <div className="flex justify-between"><span>Mobile:</span> <span className="font-mono font-semibold text-[#1d1d1f]">{pm.details?.mobile_number}</span></div>
                                            )}
                                            {pm.type === 'instapay' && (
                                                <>
                                                    <div className="flex justify-between"><span>IPA:</span> <span className="font-semibold text-[#1d1d1f]">{pm.details?.instapay_username}</span></div>
                                                    {pm.details?.mobile_number && (
                                                        <div className="flex justify-between"><span>Mobile:</span> <span className="font-mono font-semibold text-[#1d1d1f]">{pm.details?.mobile_number}</span></div>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-black/5">
                                        <button
                                            onClick={() => openEdit(pm)}
                                            className="w-8 h-8 rounded-full bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f]/70 hover:text-[#1d1d1f] flex items-center justify-center transition-colors cursor-pointer"
                                            title={__('general.edit_method')}
                                        >
                                            <Edit2 className="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(pm.id)}
                                            className="w-8 h-8 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors cursor-pointer"
                                            title={__('general.delete_method')}
                                        >
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Create/Edit Modal */}
                <Modal show={isCreating} onClose={() => { setIsCreating(false); setEditingMethod(null); reset(); }}>
                    <div className="p-6 sm:p-8 space-y-6">
                        <div className="flex items-center gap-3 pb-4 border-b border-black/5">
                            <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                <CreditCard className="w-5 h-5" />
                            </div>
                            <h2 className="text-lg font-bold text-[#1d1d1f] font-sans">
                                {editingMethod ? 'Edit Payout Method' : 'Add Payout Method'}
                            </h2>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {!editingMethod && (
                                <div className="space-y-2">
                                    <Label className="text-xs font-semibold text-[#1d1d1f]">Payout Method Type</Label>
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                        {[
                                            { id: 'bank_transfer', label: 'Bank', icon: Building },
                                            { id: 'vodafone_cash', label: 'Vodafone', icon: Smartphone },
                                            { id: 'instapay', label: 'Instapay', icon: Send },
                                            { id: 'paypal', label: 'PayPal', icon: DollarSign },
                                        ].map((opt) => {
                                            const Icon = opt.icon;
                                            const isSelected = data.type === opt.id;
                                            return (
                                                <button
                                                    type="button"
                                                    key={opt.id}
                                                    onClick={() => handleTypeChange(opt.id)}
                                                    className={`flex flex-col items-center gap-2 p-3.5 rounded-2xl border text-xs font-semibold transition-all cursor-pointer ${
                                                        isSelected
                                                            ? 'border-[#0071e3] bg-[#0071e3]/10 text-[#0071e3] shadow-xs'
                                                            : 'border-black/5 bg-[#f5f5f7] text-[#1d1d1f]/70 hover:bg-black/5'
                                                    }`}
                                                >
                                                    <Icon className="w-4 h-4" />
                                                    {opt.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {data.type === 'bank_transfer' && (
                                <div className="space-y-3">
                                    <div className="space-y-1">
                                        <Label htmlFor="full_name" className="text-xs font-semibold">Full Name</Label>
                                        <Input
                                            id="full_name"
                                            value={data.details.full_name || ''}
                                            onChange={(e) => handleDetailChange('full_name', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="bank_name" className="text-xs font-semibold">Bank Name</Label>
                                        <Input
                                            id="bank_name"
                                            value={data.details.bank_name || ''}
                                            onChange={(e) => handleDetailChange('bank_name', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="account_number" className="text-xs font-semibold">Account Number / IBAN</Label>
                                        <Input
                                            id="account_number"
                                            value={data.details.account_number || ''}
                                            onChange={(e) => handleDetailChange('account_number', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm font-mono"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="routing_number" className="text-xs font-semibold">Routing Number / BIC / SWIFT</Label>
                                        <Input
                                            id="routing_number"
                                            value={data.details.routing_number || ''}
                                            onChange={(e) => handleDetailChange('routing_number', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm font-mono"
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            {data.type === 'paypal' && (
                                <div className="space-y-1">
                                    <Label htmlFor="paypal_email" className="text-xs font-semibold">PayPal Email Address</Label>
                                    <Input
                                        id="paypal_email"
                                        type="email"
                                        value={data.details.paypal_email || ''}
                                        onChange={(e) => handleDetailChange('paypal_email', e.target.value)}
                                        className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm"
                                        required
                                    />
                                </div>
                            )}

                            {data.type === 'vodafone_cash' && (
                                <div className="space-y-1">
                                    <Label htmlFor="mobile_number" className="text-xs font-semibold">Vodafone Cash Mobile Number</Label>
                                    <Input
                                        id="mobile_number"
                                        type="tel"
                                        value={data.details.mobile_number || ''}
                                        onChange={(e) => handleDetailChange('mobile_number', e.target.value)}
                                        className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm font-mono"
                                        required
                                    />
                                </div>
                            )}

                            {data.type === 'instapay' && (
                                <div className="space-y-3">
                                    <div className="space-y-1">
                                        <Label htmlFor="instapay_username" className="text-xs font-semibold">Instapay Username (IPA)</Label>
                                        <Input
                                            id="instapay_username"
                                            value={data.details.instapay_username || ''}
                                            onChange={(e) => handleDetailChange('instapay_username', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="mobile_number" className="text-xs font-semibold">Mobile Number (Optional)</Label>
                                        <Input
                                            id="mobile_number"
                                            type="tel"
                                            value={data.details.mobile_number || ''}
                                            onChange={(e) => handleDetailChange('mobile_number', e.target.value)}
                                            className="h-10 rounded-xl bg-white border-black/10 text-xs sm:text-sm font-mono"
                                        />
                                    </div>
                                </div>
                            )}

                            <label className="flex items-center gap-3 cursor-pointer pt-2">
                                <input
                                    type="checkbox"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                    className="rounded border-black/10 text-[#0071e3] focus:ring-[#0071e3] w-4 h-4"
                                />
                                <span className="text-xs font-semibold text-[#1d1d1f]">
                                    {__('general.set_as_default_payout_method')}
                                </span>
                            </label>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-black/5">
                                <button
                                    type="button"
                                    onClick={() => { setIsCreating(false); setEditingMethod(null); reset(); }}
                                    className="px-5 py-2.5 text-xs font-semibold text-[#1d1d1f]/70 hover:text-[#1d1d1f] rounded-full transition-colors cursor-pointer"
                                >
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] shadow-sm shadow-blue-500/20 transition-all cursor-pointer"
                                >
                                    {editingMethod ? 'Save Changes' : 'Save Payout Method'}
                                </button>
                            </div>
                        </form>
                    </div>
                </Modal>

            </div>
        </AuthenticatedLayout>
    );
}
