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
import { ArrowLeft, Wallet, TrendingUp, TrendingDown, Settings, MoreHorizontal, Trash, Edit, Coins } from 'lucide-react';
import { Label } from '@/Components/ui/label';
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
    current_value?: number;
    profit_loss?: number;
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
    karatBalances: Record<string, number>;
    hasGoalTracking: boolean;
    latestPrice?: any;
    gamification?: {
        averageCost: number;
        currentValue: number;
        isProfit: boolean;
        profitAmount: number;
        monthsToGoal?: number | null;
    };
}

export default function ShowWallet({ wallet, karatBalances, hasGoalTracking, latestPrice, gamification }: ShowProps) {
    const [isCreatingTx, setIsCreatingTx] = useState(false);
    const [isEditingWallet, setIsEditingWallet] = useState(false);
    const [editingTx, setEditingTx] = useState<GoldTransaction | null>(null);

    const [prices, setPrices] = useState<Record<string, number>>({
        '18': latestPrice?.price_gram_18k ? parseFloat(latestPrice.price_gram_18k) : 0,
        '21': latestPrice?.price_gram_21k ? parseFloat(latestPrice.price_gram_21k) : 0,
        '22': latestPrice?.price_gram_24k ? parseFloat(latestPrice.price_gram_24k) * (22/24) : 0,
        '24': latestPrice?.price_gram_24k ? parseFloat(latestPrice.price_gram_24k) : 0,
    });
    
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

    const calculatedCurrentValue = Object.entries(karatBalances || {}).reduce((sum, [karat, grams]) => {
        return sum + (grams * (prices[karat] || 0));
    }, 0);

    const calculatedProfitAmount = calculatedCurrentValue - wallet.balance_amount;
    const isProfit = calculatedProfitAmount >= 0;

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
        router.put(route('isaas.gold-savers.wallets.transactions.update', { wallet: wallet.id, transaction: editingTx.id }), editingTx as any, {
            onSuccess: () => setEditingTx(null)
        });
    };

    const handleDeleteTransaction = (txId: number) => {
        if (confirm(__('erp.confirm_delete_transaction'))) {
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
        if (confirm(__('erp.confirm_delete_wallet'))) {
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
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('erp.wallet_details')}</h2>
                </div>
            }
        >
            <Head title={`${__('erp.wallet')}: ${wallet.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-2xl font-bold flex items-center gap-2">
                                <Wallet className="text-indigo-600" />
                                {wallet.name}
                            </h3>
                            <p className="text-muted-foreground">{__(wallet.goal_type)} {__('general.goal')}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={() => setIsEditingWallet(!isEditingWallet)} variant="outline" className="flex items-center gap-2">
                                <Settings className="w-4 h-4" />
                                {__('admin.edit_settings')}
                            </Button>
                            <Button onClick={() => setIsCreatingTx(!isCreatingTx)} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {isCreatingTx ? __('general.cancel') : __('erp.add_transaction')}
                            </Button>
                        </div>
                    </div>

                    {isEditingWallet && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('erp.edit_wallet_settings')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleUpdateWallet} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('erp.wallet_name')}</label>
                                            <Input 
                                                required
                                                value={editWalletData.name} 
                                                onChange={e => setEditWalletData({...editWalletData, name: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.goal_type')}</label>
                                            <Select 
                                                value={editWalletData.goal_type} 
                                                onValueChange={value => setEditWalletData({...editWalletData, goal_type: value as string})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('general.select_goal_type')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Investment">{__('general.investment')}</SelectItem>
                                                    <SelectItem value="Savings">{__('general.savings')}</SelectItem>
                                                    <SelectItem value="Trading">{__('general.trading')}</SelectItem>
                                                    <SelectItem value="Retirement">{__('general.retirement')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        {hasGoalTracking && (
                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <label className="text-sm font-medium">{__('gold_saver.target_grams')}</label>
                                                    <Input 
                                                        type="number" step="0.01"
                                                        value={editWalletData.target_grams} 
                                                        onChange={e => setEditWalletData({...editWalletData, target_grams: e.target.value})} 
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-sm font-medium">{__('general.target_amount')}</label>
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
                                            <Trash className="w-4 h-4 me-2" />
                                            {__('erp.delete_wallet')}
                                        </Button>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" onClick={() => setIsEditingWallet(false)}>{__('general.cancel')}</Button>
                                            <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('general.save_changes')}</Button>
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* Today's Prices */}
                    <Card className="mb-6 border-slate-200 shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex items-center gap-2 mb-4">
                                <div className="bg-amber-100 p-2 rounded-lg text-amber-700">
                                    <Coins className="w-5 h-5" />
                                </div>
                                <h3 className="font-semibold text-slate-900">{__('gold_saver.todays_prices_egp')} ({wallet.currency})</h3>
                            </div>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                {Object.entries(prices).map(([carat, price]) => (
                                    <div key={carat} className="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                        <Label className="text-xs text-slate-500 mb-1 block">{carat}k</Label>
                                        <Input 
                                            type="number" 
                                            value={price || ''} 
                                            onChange={e => setPrices({...prices, [carat]: parseFloat(e.target.value) || 0})}
                                            className="font-bold text-slate-900 bg-white"
                                        />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <Card className="bg-slate-50 border-slate-200 shadow-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-slate-500">{__('gold_saver.total_grams')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-slate-900">{wallet.balance_grams} {__('general.g')}</div>
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {Object.entries(karatBalances || {}).map(([karat, grams]) => grams > 0 ? (
                                        <div key={karat} className="text-xs bg-slate-200 text-slate-700 px-2 py-1 rounded font-medium">
                                            {grams}g ({karat}k)
                                        </div>
                                    ) : null)}
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="bg-slate-50 border-slate-200 shadow-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-slate-500">{__('erp.total_cost')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-slate-900">{formatNumber(wallet.balance_amount)} <span className="text-lg text-slate-500">{wallet.currency}</span></div>
                                {gamification && gamification.averageCost > 0 && (
                                    <p className="text-xs text-slate-500 mt-1">{__('erp.avg_cost')}: {formatNumber(gamification.averageCost)} / {__('general.g')}</p>
                                )}
                            </CardContent>
                        </Card>
                        
                        <Card className={isProfit ? "bg-green-50 border-green-200 shadow-none" : "bg-red-50 border-red-200 shadow-none"}>
                            <CardHeader className="pb-2">
                                <CardTitle className={`text-sm font-medium ${isProfit ? 'text-green-700' : 'text-red-700'}`}>
                                    {__('general.current_value')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className={`text-3xl font-bold ${isProfit ? 'text-green-700' : 'text-red-700'}`}>
                                    {formatNumber(calculatedCurrentValue)} <span className="text-lg opacity-70">{wallet.currency}</span>
                                </div>
                                <div className={`text-xs mt-1 font-semibold flex items-center gap-1 ${isProfit ? 'text-green-600' : 'text-red-600'}`}>
                                    {isProfit ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
                                    {isProfit ? '+' : ''}{formatNumber(calculatedProfitAmount)} {wallet.currency}
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="bg-slate-50 border-slate-200 shadow-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-slate-500">{__('general.goal_progress')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-slate-900">
                                    {wallet.target_grams > 0 
                                        ? `${((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}%` 
                                        : __('general.na')
                                    }
                                </div>
                                <p className="text-xs text-slate-500 mt-1">
                                    {__('general.target')}: {wallet.target_grams > 0 ? `${wallet.target_grams} ${__('general.g')}` : __('general.no_target_set')}
                                </p>
                                {gamification?.monthsToGoal && gamification.monthsToGoal > 0 && (
                                    <div className="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-600">
                                        <TrendingUp className="w-4 h-4 text-indigo-500" />
                                        <span>{__('gold_saver.est_time_to_goal', { months: gamification.monthsToGoal })}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {isCreatingTx && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('erp.add_new_transaction')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCreateTransaction} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.type')}</label>
                                            <Select 
                                                value={newTx.type} 
                                                onValueChange={(value) => setNewTx({...newTx, type: (value || 'buy') as 'buy' | 'sell'})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('general.select_type')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="buy">{__('general.buy')}</SelectItem>
                                                    <SelectItem value="sell">{__('general.sell')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('gold_saver.grams')}</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.grams} 
                                                onChange={e => setNewTx({...newTx, grams: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('gold_saver.karat')}</label>
                                            <Select 
                                                value={String(newTx.karat)} 
                                                onValueChange={value => setNewTx({...newTx, karat: value as string})}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder={__('general.select_karat')} />
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
                                            <label className="text-sm font-medium">{__('gold_saver.price_per_gram')} ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0.01" required
                                                value={newTx.price_per_gram} 
                                                onChange={e => setNewTx({...newTx, price_per_gram: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.fees')} ({wallet.currency})</label>
                                            <Input 
                                                type="number" step="0.01" min="0"
                                                value={newTx.fees} 
                                                onChange={e => setNewTx({...newTx, fees: e.target.value})} 
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">{__('general.date')}</label>
                                            <Input 
                                                type="date" required
                                                value={newTx.transaction_date} 
                                                onChange={e => setNewTx({...newTx, transaction_date: e.target.value})} 
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('general.notes')}</label>
                                        <Input 
                                            value={newTx.notes} 
                                            onChange={e => setNewTx({...newTx, notes: e.target.value})} 
                                        />
                                    </div>
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button type="button" variant="outline" onClick={() => setIsCreatingTx(false)}>{__('general.cancel')}</Button>
                                        <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('erp.save_transaction')}</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('erp.transaction_history')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {wallet.transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-start">
                                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                            <tr>
                                                <th className="px-4 py-3">{__('general.date')}</th>
                                                <th className="px-4 py-3">{__('general.type')}</th>
                                                <th className="px-4 py-3">{__('gold_saver.karat')}</th>
                                                <th className="px-4 py-3">{__('gold_saver.grams')}</th>
                                                <th className="px-4 py-3">{__('gold_saver.price_per_gram')}</th>
                                                <th className="px-4 py-3">{__('general.fees')}</th>
                                                <th className="px-4 py-3">{__('general.total')}</th>
                                                <th className="px-4 py-3">{__('general.current_value')} (P/L)</th>
                                                <th className="px-4 py-3">{__('general.notes')}</th>
                                                <th className="px-4 py-3 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {wallet.transactions.map((tx) => {
                                                const currentGramPrice = prices[String(tx.karat)] || 0;
                                                const txCurrentValue = tx.grams * currentGramPrice;
                                                const txProfitLoss = tx.type === 'buy' ? txCurrentValue - tx.total_amount : 0;

                                                return (
                                                <tr key={tx.id} className="border-b hover:bg-gray-50">
                                                    <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                        {new Date(tx.transaction_date).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {tx.type === 'buy' ? (
                                                            <span className="flex items-center text-green-600 font-medium gap-1">
                                                                <TrendingUp className="w-4 h-4" /> {__('general.buy')}
                                                            </span>
                                                        ) : (
                                                            <span className="flex items-center text-red-600 font-medium gap-1">
                                                                <TrendingDown className="w-4 h-4" /> {__('general.sell')}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">{tx.karat}k</td>
                                                    <td className="px-4 py-3">{tx.grams} {__('general.g')}</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.price_per_gram)}</td>
                                                    <td className="px-4 py-3">{formatNumber(tx.fees)}</td>
                                                    <td className="px-4 py-3 font-semibold">{formatNumber(tx.total_amount)} {wallet.currency}</td>
                                                    <td className="px-4 py-3 font-semibold">
                                                        {currentGramPrice > 0 ? (
                                                            <div className="flex flex-col">
                                                                <span>{formatNumber(txCurrentValue)} {wallet.currency}</span>
                                                                {tx.type === 'buy' && (
                                                                    <span className={`text-xs ${txProfitLoss >= 0 ? 'text-green-600' : 'text-red-600'} flex items-center`}>
                                                                        {txProfitLoss >= 0 ? <TrendingUp className="w-3 h-3 me-1" /> : <TrendingDown className="w-3 h-3 me-1" />}
                                                                        {txProfitLoss >= 0 ? '+' : ''}{formatNumber(txProfitLoss)}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            '---'
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">{tx.notes}</td>
                                                    <td className="px-4 py-3 text-end">
                                                        <Dialog>
                                                            <DialogTrigger asChild>
                                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent className="sm:max-w-xs">
                                                                <DialogHeader>
                                                                    <DialogTitle>{__('general.actions')}</DialogTitle>
                                                                </DialogHeader>
                                                                <div className="flex flex-col gap-2 py-2">
                                                                    <Button variant="outline" className="justify-start" onClick={() => {
                                                                        setEditingTx(tx);
                                                                    }}>
                                                                        <Edit className="w-4 h-4 me-2" />
                                                                        {__('general.edit')}
                                                                    </Button>
                                                                    <Button variant="destructive" className="justify-start" onClick={() => handleDeleteTransaction(tx.id)}>
                                                                        <Trash className="w-4 h-4 me-2" />
                                                                        {__('general.delete')}
                                                                    </Button>
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </td>
                                                </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    {__('erp.no_transactions_yet')}
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
                            <CardTitle>{__('erp.edit_transaction')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleUpdateTransaction} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('general.type')}</label>
                                        <Select 
                                            value={editingTx.type} 
                                            onValueChange={(value) => setEditingTx({...editingTx, type: (value || 'buy') as 'buy' | 'sell'})}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder={__('general.select_type')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="buy">{__('general.buy')}</SelectItem>
                                                <SelectItem value="sell">{__('general.sell')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('gold_saver.grams')}</label>
                                        <Input 
                                            type="number" step="0.01" min="0.01" required
                                            value={editingTx.grams} 
                                            onChange={e => setEditingTx({...editingTx, grams: parseFloat(e.target.value)})} 
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">{__('gold_saver.karat')}</label>
                                        <Select 
                                            value={String(editingTx.karat)} 
                                            onValueChange={value => setEditingTx({...editingTx, karat: parseInt(value || '21')})}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder={__('general.select_karat')} />
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
                                        <label className="text-sm font-medium">{__('gold_saver.price_per_gram')} ({wallet.currency})</label>
                                        <Input 
                                            type="number" step="0.01" min="0.01" required
                                            value={editingTx.price_per_gram} 
                                            onChange={e => setEditingTx({...editingTx, price_per_gram: parseFloat(e.target.value)})} 
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end gap-2 pt-2">
                                    <Button type="button" variant="outline" onClick={() => setEditingTx(null)}>{__('general.cancel')}</Button>
                                    <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">{__('general.save_changes')}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
