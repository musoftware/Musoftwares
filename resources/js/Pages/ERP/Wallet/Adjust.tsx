import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Wallet } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function AdjustWallet({ client, wallet }: { client: any, wallet: any }) {
    const [form, setForm] = useState({
        type: 'credit',
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

    return (
        <AuthenticatedLayout>
            <Head title={`Adjust Wallet: ${client?.name}`} />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'clients' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Adjust Client Wallet</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Modify the ERP wallet balance for {client?.name}.</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="bg-zinc-900 border-zinc-800 md:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-white text-sm">Current Balance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-white mb-1">
                                {wallet?.balance !== undefined ? wallet.balance : '0.00'}
                            </div>
                            <div className="text-xs text-zinc-500 uppercase tracking-wider font-mono">
                                {client?.currency_id || 'USD'}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-zinc-900 border-zinc-800 md:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-white flex items-center gap-2">
                                <Wallet className="w-5 h-5" /> Adjustment Details
                            </CardTitle>
                            <CardDescription className="text-zinc-400">
                                Add or remove funds from this client's ledger.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-zinc-300">Adjustment Type</label>
                                        <Select value={form.type} onValueChange={(val) => setForm({...form, type: val})}>
                                            <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                                <SelectItem value="credit">Credit (Add Funds)</SelectItem>
                                                <SelectItem value="debit">Debit (Remove Funds)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-zinc-300">Amount <span className="text-red-400">*</span></label>
                                        <Input 
                                            required
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={form.amount} 
                                            onChange={e => setForm({...form, amount: e.target.value})} 
                                            placeholder="100.00" 
                                            className="bg-zinc-950 border-zinc-800 text-white"
                                        />
                                        {errors.amount && <p className="text-xs text-red-400">{errors.amount}</p>}
                                    </div>
                                    <div className="space-y-2 md:col-span-2">
                                        <label className="text-sm font-medium text-zinc-300">Adjustment Note <span className="text-red-400">*</span></label>
                                        <Input 
                                            required
                                            value={form.note} 
                                            onChange={e => setForm({...form, note: e.target.value})} 
                                            placeholder="Refund for invoice #1234" 
                                            className="bg-zinc-950 border-zinc-800 text-white"
                                        />
                                        {errors.note && <p className="text-xs text-red-400">{errors.note}</p>}
                                    </div>
                                </div>
                                
                                <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                    <Link href={route('erp.dashboard', { section: 'clients' })}>
                                        <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                            Cancel
                                        </Button>
                                    </Link>
                                    <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                        {isSubmitting ? 'Processing...' : 'Submit Adjustment'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
