import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Wallet } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

export default function AdjustWallet({ client, wallet }: { client: any, wallet: any }) {
    const searchParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const initialType = searchParams ? (searchParams.get('type') === 'debit' ? 'debit' : 'credit') : 'credit';

    const [form, setForm] = useState({
        type: initialType,
        amount: '',
        note: ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        let endpoint = route('erp.clients.wallet.credit', client.id);
        if (form.type === 'debit') endpoint = route('erp.clients.wallet.debit', client.id);
        
        router.post(endpoint, {
            amount: form.amount,
            note: form.note
        }, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`Adjust Wallet: ${client?.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'clients' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Adjust Client Wallet</h1>
                        <p className="text-slate-500 text-sm mt-0.5">Modify the ERP wallet balance for {client?.name}.</p>
                    </div>
                </div>

                <div className="space-y-6">
                    <Card className="bg-white border border-slate-200 shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-slate-500">Current Balance</p>
                                    <div className="text-3xl font-bold tracking-tight text-slate-900">
                                        <CurrencyDisplay amount={wallet?.balance !== undefined ? parseFloat(wallet.balance) : 0} currency={client?.currency?.currency || 'USD'} />
                                    </div>
                                </div>
                                <div className="text-sm text-slate-500 max-w-[200px]">
                                    Available balance in client's ERP wallet.
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-slate-900 flex items-center gap-2">
                                <Wallet className="w-5 h-5" /> Adjustment Details
                            </CardTitle>
                            <CardDescription className="text-slate-500">
                                Add or remove funds from this client's ledger.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-slate-700">Adjustment Type</label>
                                        <Select value={form.type} onValueChange={(val) => setForm({...form, type: val})}>
                                            <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white border-slate-200 text-slate-900">
                                                <SelectItem value="credit">Credit (Add Funds)</SelectItem>
                                                <SelectItem value="debit">Debit (Remove Funds)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-slate-700">Amount <span className="text-red-500">*</span></label>
                                        <Input 
                                            required
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={form.amount} 
                                            onChange={e => setForm({...form, amount: e.target.value})} 
                                            placeholder="100.00" 
                                            className="bg-white border-slate-200 text-slate-900"
                                        />
                                        {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                                    </div>
                                    <div className="space-y-2 md:col-span-2">
                                        <label className="text-sm font-medium text-slate-700">Adjustment Note <span className="text-red-500">*</span></label>
                                        <Input 
                                            required
                                            value={form.note} 
                                            onChange={e => setForm({...form, note: e.target.value})} 
                                            placeholder="Refund for invoice #1234" 
                                            className="bg-white border-slate-200 text-slate-900"
                                        />
                                        {errors.note && <p className="text-xs text-red-500">{errors.note}</p>}
                                    </div>
                                </div>
                                
                                <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                    <Link href={route('erp.dashboard', { section: 'clients' })}>
                                        <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                            Cancel
                                        </Button>
                                    </Link>
                                    <Button type="submit" disabled={isSubmitting}>
                                        {isSubmitting ? 'Processing...' : 'Submit Adjustment'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ERPLayout>
    );
}
