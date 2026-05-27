import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { formatNumber } from '@/lib/utils';
import { Wallet, Target } from 'lucide-react';

interface GoldWallet {
    id: number;
    name: string;
    goal_type: string;
    target_grams: number;
    target_amount: number;
    balance_grams: number;
    balance_amount: number;
    currency: string;
    transactions: any[];
}

interface WalletsProps {
    wallets: GoldWallet[];
}

export default function WalletsIndex({ wallets }: WalletsProps) {
    const [isCreating, setIsCreating] = useState(false);
    const [newWallet, setNewWallet] = useState({
        name: '',
        goal_type: 'Investment',
        target_grams: '',
    });

    const handleCreateWallet = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('isaas.gold-savers.wallets.store'), newWallet, {
            onSuccess: () => {
                setIsCreating(false);
                setNewWallet({ name: '', goal_type: 'Investment', target_grams: '' });
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">My Gold Wallets</h2>}
        >
            <Head title="Gold Wallets" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-lg font-semibold">Your Goals & Wallets</h3>
                            <p className="text-sm text-muted-foreground">Manage your separate savings pools</p>
                        </div>
                        <Button onClick={() => setIsCreating(!isCreating)} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {isCreating ? 'Cancel' : 'Create New Wallet'}
                        </Button>
                    </div>

                    {isCreating && (
                        <Card className="border-indigo-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg">Create a New Wallet</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCreateWallet} className="flex gap-4 items-end">
                                    <div className="space-y-2 flex-1">
                                        <label className="text-sm font-medium">Wallet Name</label>
                                        <Input 
                                            value={newWallet.name} 
                                            onChange={e => setNewWallet({...newWallet, name: e.target.value})} 
                                            placeholder="e.g. Wedding Fund, Kids Education" 
                                            required 
                                        />
                                    </div>
                                    <div className="space-y-2 flex-1">
                                        <label className="text-sm font-medium">Goal Type</label>
                                        <select 
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            value={newWallet.goal_type}
                                            onChange={e => setNewWallet({...newWallet, goal_type: e.target.value})}
                                        >
                                            <option value="Investment">Investment</option>
                                            <option value="Wedding">Wedding</option>
                                            <option value="Emergency">Emergency</option>
                                            <option value="Kids">Kids Savings</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2 flex-1">
                                        <label className="text-sm font-medium">Target Grams (Optional)</label>
                                        <Input 
                                            type="number"
                                            step="0.01"
                                            value={newWallet.target_grams} 
                                            onChange={e => setNewWallet({...newWallet, target_grams: e.target.value})} 
                                            placeholder="e.g. 100" 
                                        />
                                    </div>
                                    <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">Save</Button>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {wallets.map(wallet => (
                            <div key={wallet.id} onClick={() => router.get(route('isaas.gold-savers.wallets.show', wallet.id))} className="cursor-pointer">
                            <Card className="hover:shadow-md transition">
                                <CardHeader className="pb-3 border-b border-slate-100">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <Wallet className="w-5 h-5 text-indigo-500" />
                                                {wallet.name}
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground mt-1">{wallet.goal_type}</p>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-4 space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <div className="text-sm text-muted-foreground">Current Balance</div>
                                            <div className="text-2xl font-bold">{wallet.balance_grams} g</div>
                                            <div className="text-sm text-slate-500">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                                        </div>
                                        {wallet.target_grams > 0 && (
                                            <div>
                                                <div className="text-sm text-muted-foreground flex items-center gap-1">
                                                    <Target className="w-4 h-4" /> Target
                                                </div>
                                                <div className="text-xl font-semibold">{wallet.target_grams} g</div>
                                                <div className="text-sm text-slate-500">
                                                    {((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}% achieved
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Progress Bar */}
                                    {wallet.target_grams > 0 && (
                                        <div className="w-full bg-slate-100 rounded-full h-2 mt-2">
                                            <div 
                                                className="bg-indigo-600 h-2 rounded-full" 
                                                style={{ width: `${Math.min((wallet.balance_grams / wallet.target_grams) * 100, 100)}%` }}
                                            ></div>
                                        </div>
                                    )}

                                </CardContent>
                            </Card>
                            </div>
                        ))}

                        {wallets.length === 0 && !isCreating && (
                            <div className="col-span-full text-center py-12 text-muted-foreground">
                                You don't have any wallets yet. Create one to get started.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
