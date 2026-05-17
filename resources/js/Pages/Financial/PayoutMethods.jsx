import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { CreditCard, Plus, Trash2, Edit2, CheckCircle2, Building, DollarSign, Key } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function PayoutMethods({ payoutMethods }) {
    const [isCreating, setIsCreating] = useState(false);
    const [editingMethod, setEditingMethod] = useState(null);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        type: 'bank_transfer',
        details: { bank_name: '', account_number: '', routing_number: '' },
        is_default: false,
    });

    const handleTypeChange = (type) => {
        if (type === 'bank_transfer') {
            setData({ type, details: { bank_name: '', account_number: '', routing_number: '' }, is_default: data.is_default });
        } else if (type === 'paypal') {
            setData({ type, details: { paypal_email: '' }, is_default: data.is_default });
        } else {
            setData({ type, details: { wallet_address: '', network: 'Ethereum' }, is_default: data.is_default });
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
        <AuthenticatedLayout header="Payout Methods">
            <Head title="Payout Methods" />

            <div className="max-w-7xl mx-auto space-y-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Stored Payout Methods</h1>
                        <p className="text-sm text-slate-500">Manage destination accounts for withdrawing earned funds.</p>
                    </div>
                    <PrimaryButton onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }}>
                        <Plus className="w-4 h-4 mr-2" /> Add New Method
                    </PrimaryButton>
                </div>

                {(!payoutMethods || payoutMethods.length === 0) ? (
                    <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                        <div className="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <CreditCard className="w-8 h-8" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-900">No payout methods configured</h3>
                        <p className="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                            Add a bank account, PayPal, or Crypto wallet to enable fast, secure withdrawals from your earned balance.
                        </p>
                        <PrimaryButton onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }}>
                            <Plus className="w-4 h-4 mr-2" /> Setup Payout Method
                        </PrimaryButton>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {payoutMethods.map((pm) => (
                            <div key={pm.id} className={`bg-white rounded-2xl border transition-all p-6 shadow-sm relative flex flex-col justify-between ${
                                pm.is_default ? 'border-indigo-500 ring-2 ring-indigo-500/10' : 'border-slate-200 hover:border-slate-300'
                            }`}>
                                {pm.is_default && (
                                    <span className="absolute top-4 right-4 bg-indigo-50 text-indigo-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-indigo-100 flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Default
                                    </span>
                                )}

                                <div>
                                    <div className="flex items-center gap-3 mb-4">
                                        <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold ${
                                            pm.type === 'bank_transfer' ? 'bg-blue-50 text-blue-600' :
                                            pm.type === 'paypal' ? 'bg-sky-50 text-sky-600' :
                                            'bg-amber-50 text-amber-600'
                                        }`}>
                                            {pm.type === 'bank_transfer' && <Building className="w-6 h-6" />}
                                            {pm.type === 'paypal' && <DollarSign className="w-6 h-6" />}
                                            {pm.type === 'crypto_wallet' && <Key className="w-6 h-6" />}
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-slate-900 capitalize">{pm.type.replace('_', ' ')}</h3>
                                            <span className="text-xs text-slate-400 capitalize">Status: {pm.status}</span>
                                        </div>
                                    </div>

                                    <div className="space-y-1 my-4 p-3 bg-slate-50 rounded-xl text-sm font-medium text-slate-700 break-all">
                                        {pm.type === 'bank_transfer' && (
                                            <>
                                                <div>Bank: <span className="font-semibold text-slate-900">{pm.details?.bank_name}</span></div>
                                                <div>Acc: <span className="font-semibold text-slate-900">••••{pm.details?.account_number?.slice(-4)}</span></div>
                                            </>
                                        )}
                                        {pm.type === 'paypal' && (
                                            <div>Email: <span className="font-semibold text-slate-900">{pm.details?.paypal_email}</span></div>
                                        )}
                                        {pm.type === 'crypto_wallet' && (
                                            <>
                                                <div>Network: <span className="font-semibold text-slate-900">{pm.details?.network}</span></div>
                                                <div>Addr: <span className="font-semibold text-slate-900">{pm.details?.wallet_address?.slice(0, 8)}...{pm.details?.wallet_address?.slice(-6)}</span></div>
                                            </>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                                    <button
                                        onClick={() => openEdit(pm)}
                                        className="p-2 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors"
                                        title="Edit method"
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => handleDelete(pm.id)}
                                        className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                        title="Delete method"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Create / Edit Modal */}
                <Modal show={isCreating} onClose={() => { setIsCreating(false); setEditingMethod(null); reset(); }}>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <CreditCard className="w-5 h-5 text-indigo-600" /> {editingMethod ? 'Edit Payout Method' : 'Add Payout Method'}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {!editingMethod && (
                                <div>
                                    <InputLabel value="Payout Method Type" />
                                    <div className="grid grid-cols-3 gap-3 mt-1">
                                        {[
                                            { id: 'bank_transfer', label: 'Bank Transfer', icon: Building },
                                            { id: 'paypal', label: 'PayPal', icon: DollarSign },
                                            { id: 'crypto_wallet', label: 'Crypto Wallet', icon: Key },
                                        ].map((opt) => {
                                            const Icon = opt.icon;
                                            return (
                                                <button
                                                    type="button"
                                                    key={opt.id}
                                                    onClick={() => handleTypeChange(opt.id)}
                                                    className={`flex flex-col items-center gap-2 p-4 rounded-xl border font-semibold text-sm transition-all ${
                                                        data.type === opt.id
                                                            ? 'border-indigo-600 bg-indigo-50 text-indigo-600 shadow-sm'
                                                            : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                                    }`}
                                                >
                                                    <Icon className="w-6 h-6" />
                                                    {opt.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {data.type === 'bank_transfer' && (
                                <div className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="bank_name" value="Bank Name" />
                                        <TextInput
                                            id="bank_name"
                                            value={data.details.bank_name || ''}
                                            onChange={(e) => handleDetailChange('bank_name', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="account_number" value="Account Number / IBAN" />
                                        <TextInput
                                            id="account_number"
                                            value={data.details.account_number || ''}
                                            onChange={(e) => handleDetailChange('account_number', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="routing_number" value="Routing Number / BIC / SWIFT" />
                                        <TextInput
                                            id="routing_number"
                                            value={data.details.routing_number || ''}
                                            onChange={(e) => handleDetailChange('routing_number', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            {data.type === 'paypal' && (
                                <div>
                                    <InputLabel htmlFor="paypal_email" value="PayPal Email Address" />
                                    <TextInput
                                        id="paypal_email"
                                        type="email"
                                        value={data.details.paypal_email || ''}
                                        onChange={(e) => handleDetailChange('paypal_email', e.target.value)}
                                        className="mt-1 block w-full"
                                        required
                                    />
                                </div>
                            )}

                            {data.type === 'crypto_wallet' && (
                                <div className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="network" value="Blockchain Network (e.g., Ethereum, TRON, Binance Smart Chain)" />
                                        <TextInput
                                            id="network"
                                            value={data.details.network || ''}
                                            onChange={(e) => handleDetailChange('network', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="wallet_address" value="Wallet Address" />
                                        <TextInput
                                            id="wallet_address"
                                            value={data.details.wallet_address || ''}
                                            onChange={(e) => handleDetailChange('wallet_address', e.target.value)}
                                            className="mt-1 block w-full font-mono text-sm"
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                    className="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5"
                                />
                                <span className="text-sm font-semibold text-slate-900">Set as default payout method</span>
                            </label>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <SecondaryButton onClick={() => { setIsCreating(false); setEditingMethod(null); reset(); }}>Cancel</SecondaryButton>
                                <PrimaryButton type="submit" disabled={processing}>
                                    {editingMethod ? 'Save Changes' : 'Save Payout Method'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
