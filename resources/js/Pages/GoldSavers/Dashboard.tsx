import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Wallet, TrendingUp, Activity, Target, ChevronRight, ExternalLink } from 'lucide-react';
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
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">Total Grams</CardTitle>
                                <Activity className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{portfolio.total_grams.toFixed(2)} g</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">Total Invested</CardTitle>
                                <Wallet className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.total_invested)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">Current Value</CardTitle>
                                <TrendingUp className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.current_value)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">Profit/Loss</CardTitle>
                                <Target className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
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
                        <Link href={route('isaas.gold-savers.wallets.index')} className="group flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-md hover:bg-indigo-700 hover:shadow-md transition-all duration-300">
                            Manage Wallets
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
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
                                        <Link 
                                            key={wallet.id} 
                                            href={route('isaas.gold-savers.wallets.show', wallet.id)}
                                            className="group flex items-center justify-between p-4 border rounded-lg hover:border-indigo-200 hover:bg-indigo-50/50 hover:shadow-sm transition-all duration-300"
                                        >
                                            <div className="flex items-center gap-4">
                                                <div className="bg-indigo-100 p-3 rounded-full group-hover:bg-indigo-200 transition-colors">
                                                    <Wallet className="w-5 h-5 text-indigo-600" />
                                                </div>
                                                <div>
                                                    <h4 className="font-semibold group-hover:text-indigo-700 transition-colors">{wallet.name}</h4>
                                                    <p className="text-sm text-muted-foreground">{wallet.goal_type}</p>
                                                </div>
                                            </div>
                                            <div className="text-right flex items-center gap-4">
                                                <div>
                                                    <div className="font-bold">{wallet.balance_grams} g</div>
                                                    <div className="text-sm text-muted-foreground">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                                                </div>
                                                <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all" />
                                            </div>
                                        </Link>
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
                                <div className="grid grid-cols-3 gap-4 text-center relative overflow-hidden">
                                    <div className="absolute top-0 right-0 w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                    <div className="p-4 rounded-xl bg-slate-50 border hover:border-indigo-200 hover:shadow-sm transition-all cursor-default">
                                        <div className="text-muted-foreground text-sm font-medium mb-1">24k</div>
                                        <div className="font-bold text-lg">{latestPrice.karat_24}</div>
                                    </div>
                                    <div className="p-4 rounded-xl bg-slate-50 border hover:border-indigo-200 hover:shadow-sm transition-all cursor-default">
                                        <div className="text-muted-foreground text-sm font-medium mb-1">21k</div>
                                        <div className="font-bold text-lg text-indigo-700">{latestPrice.karat_21}</div>
                                    </div>
                                    <div className="p-4 rounded-xl bg-slate-50 border hover:border-indigo-200 hover:shadow-sm transition-all cursor-default">
                                        <div className="text-muted-foreground text-sm font-medium mb-1">18k</div>
                                        <div className="font-bold text-lg">{latestPrice.karat_18}</div>
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
