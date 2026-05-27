import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { formatNumber } from '@/lib/utils';
import { ArrowLeft, Wallet, TrendingUp, TrendingDown } from 'lucide-react';

interface GoldTransaction {
    id: number;
    type: 'buy' | 'sell';
    grams: number;
    karat: number;
    price_per_gram: number;
    total_amount: number;
    fees: number;
    transaction_date: string;
    notes: string;
}

interface GoldWallet {
    id: number;
    name: string;
    goal_type: string;
    target_grams: number;
    target_amount: number;
    balance_grams: number;
    balance_amount: number;
    currency: string;
    transactions: GoldTransaction[];
}

interface WalletsShowProps {
    wallet: GoldWallet;
}

export default function WalletsShow({ wallet }: WalletsShowProps) {
    const [isCreatingTx, setIsCreatingTx] = useState(false);
    const [newTx, setNewTx] = useState({
        type: 'buy',
        grams: '',
        karat: '21',
        price_per_gram: '',
        fees: '',
        transaction_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const handleCreateTransaction = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('isaas.gold-savers.wallets.transactions.store', wallet.id), newTx, {
            onSuccess: () => {
                setIsCreatingTx(false);
                setNewTx({
                    type: 'buy',
                    grams: '',
                    karat: '21',
                    price_per_gram: '',
                    fees: '',
                    transaction_date: new Date().toISOString().split('T')[0],
                    notes: '',
                });
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" onClick={() => router.get(route('isaas.gold-savers.wallets.index'))}>
                        <ArrowLeft className="w-5 h-5" />
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">Wallet Details</h2>
                </div>
            }
        >
            <Head title={`Wallet: ${wallet.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-2xl font-bold flex items-center gap-2">
                                <Wallet className="text-indigo-600" />
                                {wallet.name}
                            </h3>
                            <p className="text-muted-foreground">{wallet.goal_type} Goal</p>
                        </div>
                        <Button onClick={() => setIsCreatingTx(!isCreatingTx)} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {isCreatingTx ? 'Cancel' : 'Add Transaction'}
                        </Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Grams</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{wallet.balance_grams} g</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Investment Value</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Goal Progress</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">
                                    {wallet.target_grams > 0 
                                        ? `${((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}%` 
                                        : 'N/A'
                                    }
                                </div>
                                <p className="text-xs text-muted-foreground mt-1">
                                    Target: {wallet.target_grams > 0 ? `${wallet.target_grams} g` : 'No target set'}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {isCreatingTx && (
                        <Card className="border-indigo-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg">Add New Transaction</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCreateTransaction} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Type</label>
                                            <select 
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                value={newTx.type}
                                                onChange={e => setNewTx({...newTx, type: e.target.value as 'buy' | 'sell'})}
                                            >
                                                <option value="buy">Buy</option>
                                                <option value="sell">Sell</option>
                                            </select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Grams</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.grams} 
                                                onChange={e => setNewTx({...newTx, grams: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Karat</label>
                                            <select 
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                value={newTx.karat}
                                                onChange={e => setNewTx({...newTx, karat: e.target.value})}
                                            >
                                                <option value="18">18k</option>
                                                <option value="21">21k</option>
                                                <option value="22">22k</option>
                                                <option value="24">24k</option>
                                            </select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Price Per Gram ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.price_per_gram} 
                                                onChange={e => setNewTx({...newTx, price_per_gram: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Fees/Taxes ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0"
                                                value={newTx.fees} 
                                                onChange={e => setNewTx({...newTx, fees: e.target.value})} 
                                                placeholder="Optional"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Date</label>
                                            <Input 
                                                type="date" required
                                                value={newTx.transaction_date} 
                                                onChange={e => setNewTx({...newTx, transaction_date: e.target.value})} 
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Notes</label>
                                        <Input 
                                            value={newTx.notes} 
                                            onChange={e => setNewTx({...newTx, notes: e.target.value})} 
                                            placeholder="Optional details (e.g. bought from Shop X)"
                                        />
                                    </div>
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button type="button" variant="outline" onClick={() => setIsCreatingTx(false)}>Cancel</Button>
                                        <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">Save Transaction</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Transaction History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {wallet.transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                            <tr>
                                                <th className="px-4 py-3">Date</th>
                                                <th className="px-4 py-3">Type</th>
                                                <th className="px-4 py-3">Karat</th>
                                                <th className="px-4 py-3">Grams</th>
                                                <th className="px-4 py-3">Price/g</th>
                                                <th className="px-4 py-3">Fees</th>
                                                <th className="px-4 py-3">Total</th>
                                                <th className="px-4 py-3">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {wallet.transactions.map((tx) => (
                                                <tr key={tx.id} className="border-b hover:bg-gray-50">
                                                    <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                        {new Date(tx.transaction_date).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {tx.type === 'buy' ? (
                                                            <span className="flex items-center text-green-600 font-medium gap-1">
                                                                <TrendingUp className="w-4 h-4" /> Buy
                                                            </span>
                                                        ) : (
                                                            <span className="flex items-center text-red-600 font-medium gap-1">
                                                                <TrendingDown className="w-4 h-4" /> Sell
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">{tx.karat}k</td>
                                                    <td className="px-4 py-3">{tx.grams} g</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.price_per_gram)}</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.fees)}</td>
                                                    <td className="px-4 py-3 font-semibold">{formatNumber(tx.total_amount)} {wallet.currency}</td>
                                                    <td className="px-4 py-3 text-muted-foreground">{tx.notes}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    No transactions recorded in this wallet yet.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
