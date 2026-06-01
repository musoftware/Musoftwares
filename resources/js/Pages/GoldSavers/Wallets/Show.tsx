import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { formatNumber } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { ArrowLeft, Wallet, TrendingUp, TrendingDown, Settings, MoreHorizontal, Trash, Edit } from 'lucide-react';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

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

interface ShowProps {
    wallet: GoldWallet;
    hasGoalTracking: boolean;
}

export default function ShowWallet({ wallet, hasGoalTracking }: ShowProps) {
    const [isCreatingTx, setIsCreatingTx] = useState(false);
    const [isEditingWallet, setIsEditingWallet] = useState(false);
    const [editingTx, setEditingTx] = useState<GoldTransaction | null>(null);
    
    const [newTx, setNewTx] = useState({
        type: 'buy',
        grams: '',
        karat: '21',
        price_per_gram: '',
        fees: '',
        transaction_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const [editWalletData, setEditWalletData] = useState({
        name: wallet.name,
        goal_type: wallet.goal_type,
        target_grams: wallet.target_grams || '',
        target_amount: wallet.target_amount || '',
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

    const handleUpdateTransaction = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingTx) return;
        router.put(route('isaas.gold-savers.wallets.transactions.update', { wallet: wallet.id, transaction: editingTx.id }), editingTx, {
            onSuccess: () => setEditingTx(null)
        });
    };

    const handleDeleteTransaction = (txId: number) => {
        if (confirm(__('Confirm Delete Transaction'))) {
            router.delete(route('isaas.gold-savers.wallets.transactions.destroy', { wallet: wallet.id, transaction: txId }));
        }
    };

    const handleUpdateWallet = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('isaas.gold-savers.wallets.update', wallet.id), editWalletData, {
            onSuccess: () => setIsEditingWallet(false)
        });
    };

    const handleDeleteWallet = () => {
        if (confirm(__('Confirm Delete Wallet'))) {
            router.delete(route('isaas.gold-savers.wallets.destroy', wallet.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" onClick={() => router.get(route('isaas.gold-savers.wallets.index'))}>
                        <ArrowLeft className="w-5 h-5" />
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('Wallet Details')}</h2>
                </div>
            }
        >
            <Head title={`${__('Wallet')}: ${wallet.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-2xl font-bold flex items-center gap-2">
                                <Wallet className="text-indigo-600" />
                                {wallet.name}
                            </h3>
                            <p className="text-muted-foreground">{__(wallet.goal_type)} {__('Goal')}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={() => setIsEditingWallet(!isEditingWallet)} variant="outline" className="flex items-center gap-2">
                                <Settings className="w-4 h-4" />
                                {__('Edit Settings')}
                            </Button>
                            <Button onClick={() => setIsCreatingTx(!isCreatingTx)} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {isCreatingTx ? __('Cancel') : __('Add Transaction')}
                            </Button>
                        </div>
                    </div>

                    {isEditingWallet && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('Edit Wallet Settings')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleUpdateWallet} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Wallet Name')}</label>
                                            <Input 
                                                required
                                                value={editWalletData.name} 
                                                onChange={e => setEditWalletData({...editWalletData, name: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Goal Type')}</label>
                                            <Select 
                                                value={editWalletData.goal_type} 
                                                onValueChange={value => setEditWalletData({...editWalletData, goal_type: value})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('Select Goal Type')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Investment">{__('Investment')}</SelectItem>
                                                    <SelectItem value="Savings">{__('Savings')}</SelectItem>
                                                    <SelectItem value="Trading">{__('Trading')}</SelectItem>
                                                    <SelectItem value="Retirement">{__('Retirement')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        {hasGoalTracking && (
                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <label className="text-sm font-medium">{__('Target Grams')}</label>
                                                    <Input 
                                                        type="number" step="0.01"
                                                        value={editWalletData.target_grams} 
                                                        onChange={e => setEditWalletData({...editWalletData, target_grams: e.target.value})} 
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-sm font-medium">{__('Target Amount')}</label>
                                                    <Input 
                                                        type="number" step="0.01"
                                                        value={editWalletData.target_amount} 
                                                        onChange={e => setEditWalletData({...editWalletData, target_amount: e.target.value})} 
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex justify-between items-center pt-2">
                                        <Button type="button" variant="destructive" onClick={handleDeleteWallet}>
                                            <Trash className="w-4 h-4 mr-2" />
                                            {__('Delete Wallet')}
                                        </Button>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" onClick={() => setIsEditingWallet(false)}>{__('Cancel')}</Button>
                                            <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('Save Changes')}</Button>
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">{__('Total Grams')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-indigo-700">{wallet.balance_grams} {__('G')}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">{__('Total Investment Value')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{formatNumber(wallet.balance_amount)} <span className="text-lg text-muted-foreground">{wallet.currency}</span></div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">{__('Goal Progress')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">
                                    {wallet.target_grams > 0 
                                        ? `${((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}%` 
                                        : __('Na')
                                    }
                                </div>
                                <p className="text-xs text-muted-foreground mt-1">
                                    {__('Target')}: {wallet.target_grams > 0 ? `${wallet.target_grams} ${__('G')}` : __('No Target Set')}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {isCreatingTx && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('Add New Transaction')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCreateTransaction} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Type')}</label>
                                            <Select 
                                                value={newTx.type} 
                                                onValueChange={(value: 'buy' | 'sell') => setNewTx({...newTx, type: value})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('Select Type')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="buy">{__('Buy')}</SelectItem>
                                                    <SelectItem value="sell">{__('Sell')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Grams')}</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.grams} 
                                                onChange={e => setNewTx({...newTx, grams: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Karat')}</label>
                                            <Select 
                                                value={String(newTx.karat)} 
                                                onValueChange={value => setNewTx({...newTx, karat: value})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('Select Karat')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="18">18k</SelectItem>
                                                    <SelectItem value="21">21k</SelectItem>
                                                    <SelectItem value="22">22k</SelectItem>
                                                    <SelectItem value="24">24k</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Price Per Gram')} ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.price_per_gram} 
                                                onChange={e => setNewTx({...newTx, price_per_gram: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Fees')} ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0"
                                                value={newTx.fees} 
                                                onChange={e => setNewTx({...newTx, fees: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('Date')}</label>
                                            <Input 
                                                type="date" required
                                                value={newTx.transaction_date} 
                                                onChange={e => setNewTx({...newTx, transaction_date: e.target.value})} 
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('Notes')}</label>
                                        <Input 
                                            value={newTx.notes} 
                                            onChange={e => setNewTx({...newTx, notes: e.target.value})} 
                                        />
                                    </div>
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button type="button" variant="outline" onClick={() => setIsCreatingTx(false)}>{__('Cancel')}</Button>
                                        <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('Save Transaction')}</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('Transaction History')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {wallet.transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                            <tr>
                                                <th className="px-4 py-3">{__('Date')}</th>
                                                <th className="px-4 py-3">{__('Type')}</th>
                                                <th className="px-4 py-3">{__('Karat')}</th>
                                                <th className="px-4 py-3">{__('Grams')}</th>
                                                <th className="px-4 py-3">{__('Price Per Gram')}</th>
                                                <th className="px-4 py-3">{__('Fees')}</th>
                                                <th className="px-4 py-3">{__('Total')}</th>
                                                <th className="px-4 py-3">{__('Notes')}</th>
                                                <th className="px-4 py-3 w-10"></th>
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
                                                                <TrendingUp className="w-4 h-4" /> {__('Buy')}
                                                            </span>
                                                        ) : (
                                                            <span className="flex items-center text-red-600 font-medium gap-1">
                                                                <TrendingDown className="w-4 h-4" /> {__('Sell')}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">{tx.karat}k</td>
                                                    <td className="px-4 py-3">{tx.grams} {__('G')}</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.price_per_gram)}</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.fees)}</td>
                                                    <td className="px-4 py-3 font-semibold">{formatNumber(tx.total_amount)} {wallet.currency}</td>
                                                    <td className="px-4 py-3 text-muted-foreground">{tx.notes}</td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Dialog>
                                                            <DialogTrigger asChild>
                                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                                    <span className="sr-only">Open menu</span>
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent className="sm:max-w-xs">
                                                                <DialogHeader>
                                                                    <DialogTitle>{__('Actions')}</DialogTitle>
                                                                </DialogHeader>
                                                                <div className="flex flex-col gap-2 py-2">
                                                                    <Button variant="outline" className="justify-start" onClick={() => {
                                                                        setEditingTx(tx);
                                                                    }}>
                                                                        <Edit className="w-4 h-4 mr-2" />
                                                                        {__('Edit')}
                                                                    </Button>
                                                                    <Button variant="destructive" className="justify-start" onClick={() => handleDeleteTransaction(tx.id)}>
                                                                        <Trash className="w-4 h-4 mr-2" />
                                                                        {__('Delete')}
                                                                    </Button>
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    {__('No Transactions Yet')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                </div>
            </div>

            {/* Edit Transaction Modal */}
            {editingTx && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <Card className="w-full max-w-lg mx-4">
                        <CardHeader>
                            <CardTitle>{__('Edit Transaction')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleUpdateTransaction} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('Type')}</label>
                                        <Select 
                                            value={editingTx.type} 
                                            onValueChange={(value: 'buy' | 'sell') => setEditingTx({...editingTx, type: value})}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder={__('Select Type')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="buy">{__('Buy')}</SelectItem>
                                                <SelectItem value="sell">{__('Sell')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('Grams')}</label>
                                        <Input 
                                            type="number" step="0.01" min="0.01" required
                                            value={editingTx.grams} 
                                            onChange={e => setEditingTx({...editingTx, grams: parseFloat(e.target.value)})} 
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('Karat')}</label>
                                        <Select 
                                            value={String(editingTx.karat)} 
                                            onValueChange={value => setEditingTx({...editingTx, karat: parseInt(value)})}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder={__('Select Karat')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="18">18k</SelectItem>
                                                <SelectItem value="21">21k</SelectItem>
                                                <SelectItem value="22">22k</SelectItem>
                                                <SelectItem value="24">24k</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('Price Per Gram')} ({wallet.currency})</label>
                                        <Input 
                                            type="number" step="0.01" min="0.01" required
                                            value={editingTx.price_per_gram} 
                                            onChange={e => setEditingTx({...editingTx, price_per_gram: parseFloat(e.target.value)})} 
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end gap-2 pt-2">
                                    <Button type="button" variant="outline" onClick={() => setEditingTx(null)}>{__('Cancel')}</Button>
                                    <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('Save Changes')}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
