import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Wallet, TrendingUp, Activity, Target } from 'lucide-react';
import { formatNumber } from '@/lib/utils';

interface Portfolio {
    total_grams: number;
    total_invested: number;
    current_value: number;
    total_profit: number;
    profit_percentage: number;
}

interface Wallet {
    id: number;
    name: string;
    goal_type: string;
    balance_grams: number;
    balance_amount: number;
    target_grams: number;
    target_amount: number;
    currency: string;
}

interface GoldPrice {
    karat_24: number;
    karat_21: number;
    karat_18: number;
    currency: string;
    date: string;
}

interface DashboardProps {
    wallets: Wallet[];
    latestPrice: GoldPrice | null;
    portfolio: Portfolio;
}

export default function Dashboard({ wallets, latestPrice, portfolio }: DashboardProps) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Gold Saver Portfolio</h2>}
        >
            <Head title="Gold Saver Portfolio" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Portfolio Summary */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Grams</CardTitle>
                                <Activity className="w-4 h-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{portfolio.total_grams.toFixed(2)} g</div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Invested</CardTitle>
                                <Wallet className="w-4 h-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.total_invested)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Current Value</CardTitle>
                                <TrendingUp className="w-4 h-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.current_value)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Profit/Loss</CardTitle>
                                <Target className="w-4 h-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className={`text-2xl font-bold ${portfolio.total_profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {portfolio.total_profit >= 0 ? '+' : ''}{formatNumber(portfolio.total_profit)} 
                                    <span className="text-sm font-normal ml-1">({portfolio.profit_percentage}%)</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Quick Access */}
                    <div className="flex gap-4">
                        <Link href={route('isaas.gold-savers.wallets.index')} className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                            Manage Wallets
                        </Link>
                    </div>

                    {/* Active Wallets List */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Active Wallets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {wallets.length === 0 ? (
                                <p className="text-muted-foreground">No active wallets. Create one to start tracking your goals.</p>
                            ) : (
                                <div className="space-y-4">
                                    {wallets.map(wallet => (
                                        <div key={wallet.id} className="flex items-center justify-between p-4 border rounded-lg">
                                            <div>
                                                <h4 className="font-semibold">{wallet.name}</h4>
                                                <p className="text-sm text-muted-foreground">{wallet.goal_type}</p>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-bold">{wallet.balance_grams} g</div>
                                                <div className="text-sm text-muted-foreground">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Live Prices Widget */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Market Prices (Latest)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {latestPrice ? (
                                <div className="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div className="text-muted-foreground text-sm">24k</div>
                                        <div className="font-bold">{latestPrice.karat_24}</div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground text-sm">21k</div>
                                        <div className="font-bold">{latestPrice.karat_21}</div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground text-sm">18k</div>
                                        <div className="font-bold">{latestPrice.karat_18}</div>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-center">No market prices available yet.</p>
                            )}
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
