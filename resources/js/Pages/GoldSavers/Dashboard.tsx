import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Wallet, TrendingUp, Activity, Target, ChevronRight, ExternalLink, Lightbulb, Lock } from 'lucide-react';
import { formatNumber } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import GoldSaversTabs from './Components/GoldSaversTabs';

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
    id?: number;
    price_ounce_usd?: number;
    price_gram_24k: number;
    price_gram_22k?: number;
    price_gram_21k: number;
    price_gram_18k: number;
    currency?: string;
    fetched_at?: string;
}

interface SmartInsight {
    icon: 'TrendingUp' | 'Target' | 'Lightbulb';
    text: string;
}

interface DashboardProps {
    wallets: Wallet[];
    latestPrice: GoldPrice | null;
    portfolio: Portfolio;
    hasLivePrices: boolean;
    hasSmartInsights: boolean;
    smartInsights: SmartInsight[];
    priceChanges?: {
        price_gram_24k: number;
        price_gram_21k: number;
        price_gram_18k: number;
        price_ounce_usd: number;
    } | null;
}

export default function Dashboard({ wallets, latestPrice, portfolio, hasLivePrices, hasSmartInsights, smartInsights, priceChanges }: DashboardProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        {__('gold_saver.gold_saver_portfolio')}
                    </h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('gold_saver.gold_saver_portfolio')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Portfolio Summary */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">{__('gold_saver.total_grams')}</CardTitle>
                                <Activity className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{portfolio.total_grams.toFixed(2)} {__('general.g')}</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">{__('general.total_invested')}</CardTitle>
                                <Wallet className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.total_invested)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">{__('general.current_value')}</CardTitle>
                                <TrendingUp className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(portfolio.current_value)}</div>
                            </CardContent>
                        </Card>
                        
                        <Card className="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-default group">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground group-hover:text-primary transition-colors">{__('general.profit_loss')}</CardTitle>
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
                            {__('erp.manage_wallets')}
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>

                    {/* Active Wallets List */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('erp.active_wallets')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {wallets.length === 0 ? (
                                <p className="text-muted-foreground">{__('erp.no_active_wallets_create_one')}</p>
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
                                                    <p className="text-sm text-muted-foreground">{__(wallet.goal_type)}</p>
                                                </div>
                                            </div>
                                            <div className="text-right flex items-center gap-4">
                                                <div>
                                                    <div className="font-bold">{wallet.balance_grams} {__('general.g')}</div>
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

                    {/* Smart Insights Widget */}
                    <Card className="relative overflow-hidden bg-gradient-to-br from-indigo-50 to-white border-indigo-100">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-indigo-900">
                                <Lightbulb className="w-5 h-5 text-indigo-600" />
                                {__('general.smart_insights')}
                                {!hasSmartInsights && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('general.premium')}</span>}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="min-h-[250px] flex flex-col justify-center">
                            {!hasSmartInsights ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-10 flex-col gap-2 rounded-xl">
                                    <div className="bg-white p-3 rounded-full shadow-sm border text-indigo-600">
                                        <Lock className="w-6 h-6" />
                                    </div>
                                    <p className="font-medium text-slate-800">{__('general.upgrade_to_see_insights')}</p>
                                    <Link href={route('subscriptions.manage')} className="text-sm text-indigo-600 hover:underline">
                                        {__('general.upgrade_now')}
                                    </Link>
                                </div>
                            ) : null}

                            <div className={!hasSmartInsights ? "opacity-30 select-none pointer-events-none" : ""}>
                                <div className="space-y-4">
                                    {smartInsights && smartInsights.length > 0 ? (
                                        smartInsights.map((insight, index) => (
                                            <div key={index} className="flex items-start gap-3 bg-white p-4 rounded-xl border border-indigo-50 shadow-sm">
                                                <div className="bg-indigo-100 p-2 rounded-lg shrink-0">
                                                    {insight.icon === 'TrendingUp' && <TrendingUp className="w-5 h-5 text-indigo-600" />}
                                                    {insight.icon === 'Target' && <Target className="w-5 h-5 text-indigo-600" />}
                                                    {insight.icon === 'Lightbulb' && <Lightbulb className="w-5 h-5 text-indigo-600" />}
                                                </div>
                                                <p className="text-slate-700 font-medium leading-relaxed">{insight.text}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-muted-foreground">{__('general.no_insights_available')}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Live Prices Widget */}
                    <Card className="relative overflow-hidden">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {__('general.market_prices_latest')}
                                {!hasLivePrices && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('general.premium')}</span>}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="min-h-[250px] flex flex-col justify-center">
                            {!hasLivePrices ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-10 rounded-xl flex-col gap-2">
                                    <div className="bg-white p-3 rounded-full shadow-sm border text-indigo-600">
                                        <ExternalLink className="w-6 h-6" />
                                    </div>
                                    <p className="font-medium text-slate-800">{__('general.upgrade_to_see_live_prices')}</p>
                                    <Link href={route('subscriptions.manage')} className="text-sm text-indigo-600 hover:underline">
                                        {__('general.upgrade_now')}
                                    </Link>
                                </div>
                            ) : null}
                            
                            <div className={!hasLivePrices ? "opacity-30 select-none pointer-events-none" : ""}>
                                {latestPrice || !hasLivePrices ? (
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-center relative pt-2">
                                        <div className="p-6 rounded-xl bg-white border border-slate-200 shadow-sm relative">
                                            <div className="text-slate-500 text-sm font-medium mb-2">24k {__('gold_saver.purity')}</div>
                                            <div className="font-bold text-3xl text-slate-900">{latestPrice?.price_gram_24k ?? '4,050'}</div>
                                            <div className="text-xs text-slate-400 mt-2">{latestPrice?.currency} / {__('general.g')}</div>
                                            {priceChanges?.price_gram_24k !== undefined && (
                                                <div className={`text-xs font-semibold mt-2 flex items-center justify-center gap-1 ${priceChanges.price_gram_24k >= 0 ? 'text-green-600' : 'text-red-500'}`}>
                                                    <TrendingUp className={`w-3 h-3 ${priceChanges.price_gram_24k >= 0 ? '' : 'rotate-180'}`} />
                                                    {priceChanges.price_gram_24k >= 0 ? '+' : ''}{priceChanges.price_gram_24k}%
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-6 rounded-xl bg-indigo-50 border border-indigo-100 shadow-sm relative overflow-hidden">
                                            <div className="absolute top-0 right-0 w-3 h-3 bg-indigo-500 rounded-bl-xl opacity-20"></div>
                                            <div className="text-indigo-600 text-sm font-medium mb-2">21k {__('gold_saver.purity')}</div>
                                            <div className="font-bold text-3xl text-indigo-700">{latestPrice?.price_gram_21k ?? '3,550'}</div>
                                            <div className="text-xs text-indigo-400 mt-2">{latestPrice?.currency} / {__('general.g')}</div>
                                            {priceChanges?.price_gram_21k !== undefined && (
                                                <div className={`text-xs font-semibold mt-2 flex items-center justify-center gap-1 ${priceChanges.price_gram_21k >= 0 ? 'text-green-600' : 'text-red-500'}`}>
                                                    <TrendingUp className={`w-3 h-3 ${priceChanges.price_gram_21k >= 0 ? '' : 'rotate-180'}`} />
                                                    {priceChanges.price_gram_21k >= 0 ? '+' : ''}{priceChanges.price_gram_21k}%
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-6 rounded-xl bg-white border border-slate-200 shadow-sm relative">
                                            <div className="text-slate-500 text-sm font-medium mb-2">18k {__('gold_saver.purity')}</div>
                                            <div className="font-bold text-3xl text-slate-900">{latestPrice?.price_gram_18k ?? '3,040'}</div>
                                            <div className="text-xs text-slate-400 mt-2">{latestPrice?.currency} / {__('general.g')}</div>
                                            {priceChanges?.price_gram_18k !== undefined && (
                                                <div className={`text-xs font-semibold mt-2 flex items-center justify-center gap-1 ${priceChanges.price_gram_18k >= 0 ? 'text-green-600' : 'text-red-500'}`}>
                                                    <TrendingUp className={`w-3 h-3 ${priceChanges.price_gram_18k >= 0 ? '' : 'rotate-180'}`} />
                                                    {priceChanges.price_gram_18k >= 0 ? '+' : ''}{priceChanges.price_gram_18k}%
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-6 rounded-xl bg-slate-900 border border-slate-800 shadow-sm text-white relative">
                                            <div className="text-slate-400 text-sm font-medium mb-2">{__('gold_saver.global_ounce')}</div>
                                            <div className="font-bold text-3xl">{latestPrice?.price_ounce_usd ?? '2,350'}</div>
                                            <div className="text-xs text-slate-500 mt-2">USD / {__('gold_saver.ounce')}</div>
                                            {priceChanges?.price_ounce_usd !== undefined && (
                                                <div className={`text-xs font-semibold mt-2 flex items-center justify-center gap-1 ${priceChanges.price_ounce_usd >= 0 ? 'text-green-400' : 'text-red-400'}`}>
                                                    <TrendingUp className={`w-3 h-3 ${priceChanges.price_ounce_usd >= 0 ? '' : 'rotate-180'}`} />
                                                    {priceChanges.price_ounce_usd >= 0 ? '+' : ''}{priceChanges.price_ounce_usd}%
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground text-center">{__('general.no_market_prices_available_yet')}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}

