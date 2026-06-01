import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { CreditCard, Plus, Trash2, Edit2, CheckCircle2, Building, DollarSign, Smartphone, Zap } from 'lucide-react';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Send } from 'lucide-react';

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
        <AuthenticatedLayout header="Payout Methods">
            <Head title={__('general.payout_methods')} />

            <div className="max-w-[1000px] mx-auto px-4 py-8 space-y-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.stored_payout_methods')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.manage_destination_accounts_for_withdrawing_earned_funds')}</p>
                    </div>
                    <Button onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }} className="shadow-none">
                        <Plus className="w-4 h-4 mr-2" />{__('general.add_new_method')}</Button>
                </div>

                {(!payoutMethods || payoutMethods.length === 0) ? (
                    <Card className="shadow-none border-dashed bg-muted/10">
                        <CardContent className="p-12 text-center flex flex-col items-center justify-center">
                            <div className="w-16 h-16 bg-muted flex items-center justify-center rounded-2xl mb-4">
                                <CreditCard className="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h3 className="text-lg font-semibold text-foreground">{__('general.no_payout_methods_configured')}</h3>
                            <p className="text-sm text-muted-foreground max-w-md mx-auto mt-2 mb-6 leading-relaxed">{__('general.add_a_bank_account_paypal_vodafone_cash_or_instapay_to_enable_fast_secure_withdrawals_from_your_earned_balance')}</p>
                            <Button onClick={() => { setEditingMethod(null); reset(); setIsCreating(true); }} className="shadow-none">
                                <Plus className="w-4 h-4 mr-2" />{__('general.setup_payout_method')}</Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {payoutMethods.map((pm) => (
                            <Card key={pm.id} className={`shadow-none relative flex flex-col justify-between transition-colors ${
                                pm.is_default ? 'border-primary ring-1 ring-primary/20' : 'hover:border-primary/30'
                            }`}>
                                <CardContent className="p-6">
                                    {pm.is_default && (
                                        <Badge variant="secondary" className="absolute top-4 right-4 bg-primary/10 text-primary border-primary/20 gap-1 font-semibold">
                                            <CheckCircle2 className="w-3.5 h-3.5" /> Default
                                        </Badge>
                                    )}

                                    <div>
                                        <div className="flex items-center gap-3 mb-5">
                                            <div className="w-12 h-12 rounded-xl bg-muted flex items-center justify-center text-muted-foreground">
                                                {pm.type === 'bank_transfer' && <Building className="w-6 h-6" />}
                                                {pm.type === 'paypal' && <DollarSign className="w-6 h-6" />}
                                                {pm.type === 'vodafone_cash' && <Smartphone className="w-6 h-6" />}
                                                {pm.type === 'instapay' && <Send className="w-6 h-6" />}
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-foreground capitalize">{pm.type.replace('_', ' ')}</h3>
                                                <span className="text-xs text-muted-foreground capitalize">Status: {pm.status}</span>
                                            </div>
                                        </div>

                                        <div className="space-y-1.5 p-3.5 bg-muted/30 rounded-xl text-sm text-muted-foreground break-all">
                                            {pm.type === 'bank_transfer' && (
                                                <>
                                                    <div className="flex justify-between"><span>Name:</span> <span className="font-medium text-foreground">{pm.details?.full_name}</span></div>
                                                    <div className="flex justify-between"><span>Bank:</span> <span className="font-medium text-foreground">{pm.details?.bank_name}</span></div>
                                                    <div className="flex justify-between"><span>Acc:</span> <span className="font-medium text-foreground">••••{pm.details?.account_number?.slice(-4)}</span></div>
                                                </>
                                            )}
                                            {pm.type === 'paypal' && (
                                                <div className="flex flex-col gap-1"><span>Email:</span> <span className="font-medium text-foreground">{pm.details?.paypal_email}</span></div>
                                            )}
                                            {pm.type === 'vodafone_cash' && (
                                                <>
                                                    <div className="flex justify-between"><span>Mobile:</span> <span className="font-medium text-foreground">{pm.details?.mobile_number}</span></div>
                                                </>
                                            )}
                                            {pm.type === 'instapay' && (
                                                <>
                                                    <div className="flex justify-between"><span>Username:</span> <span className="font-medium text-foreground">{pm.details?.instapay_username}</span></div>
                                                    <div className="flex justify-between"><span>Mobile:</span> <span className="font-medium text-foreground">{pm.details?.mobile_number}</span></div>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-end gap-2 pt-4 mt-4 border-t">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => openEdit(pm)}
                                            className="h-8 w-8 text-muted-foreground hover:text-primary"
                                            title={__('general.edit_method')}
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleDelete(pm.id)}
                                            className="h-8 w-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                            title={__('general.delete_method')}
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Modal show={isCreating} onClose={() => { setIsCreating(false); setEditingMethod(null); reset(); }}>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-foreground mb-4 flex items-center gap-2 tracking-tight">
                            <CreditCard className="w-5 h-5 text-primary" /> {editingMethod ? 'Edit Payout Method' : 'Add Payout Method'}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {!editingMethod && (
                                <div>
                                    <InputLabel value="Payout Method Type" />
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mt-2">
                                        {[
                                            { id: 'bank_transfer', label: 'Bank', icon: Building },
                                            { id: 'vodafone_cash', label: 'Vodafone Cash', icon: Smartphone },
                                            { id: 'instapay', label: 'Instapay', icon: Send },
                                            { id: 'paypal', label: 'PayPal', icon: DollarSign },
                                        ].map((opt) => {
                                            const Icon = opt.icon;
                                            return (
                                                <button
                                                    type="button"
                                                    key={opt.id}
                                                    onClick={() => handleTypeChange(opt.id)}
                                                    className={`flex flex-col items-center gap-2 p-4 rounded-xl border font-semibold text-sm transition-all ${
                                                        data.type === opt.id
                                                            ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                                            : 'border-border text-muted-foreground hover:border-primary/50 hover:bg-muted/20'
                                                    }`}
                                                >
                                                    <Icon className="w-5 h-5" />
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
                                        <InputLabel htmlFor="full_name" value="Full Name" />
                                        <TextInput
                                            id="full_name"
                                            value={data.details.full_name || ''}
                                            onChange={(e) => handleDetailChange('full_name', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
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

                            {data.type === 'vodafone_cash' && (
                                <div className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="mobile_number" value="Vodafone Cash Mobile Number" />
                                        <TextInput
                                            id="mobile_number"
                                            type="tel"
                                            value={data.details.mobile_number || ''}
                                            onChange={(e) => handleDetailChange('mobile_number', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            {data.type === 'instapay' && (
                                <div className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="instapay_username" value="Instapay Username (IPA)" />
                                        <TextInput
                                            id="instapay_username"
                                            value={data.details.instapay_username || ''}
                                            onChange={(e) => handleDetailChange('instapay_username', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="mobile_number" value="Mobile Number (Optional but recommended)" />
                                        <TextInput
                                            id="mobile_number"
                                            type="tel"
                                            value={data.details.mobile_number || ''}
                                            onChange={(e) => handleDetailChange('mobile_number', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                    </div>
                                </div>
                            )}

                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                    className="rounded border-border text-primary shadow-sm focus:ring-primary/20 w-5 h-5 bg-background"
                                />
                                <span className="text-sm font-semibold text-foreground">{__('general.set_as_default_payout_method')}</span>
                            </label>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t">
                                <Button variant="outline" type="button" onClick={() => { setIsCreating(false); setEditingMethod(null); reset(); }}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing} className="shadow-none">
                                    {editingMethod ? 'Save Changes' : 'Save Payout Method'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
