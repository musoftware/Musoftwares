import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { TrendingUp, Lock, Activity } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer } from 'recharts';
import GoldSaversTabs from '../Components/GoldSaversTabs';
import { Button } from '@/Components/ui/button';

interface GoldPrice {
    id: number;
    price_ounce_usd: number;
    price_gram_24k: number;
    price_gram_22k: number;
    price_gram_21k: number;
    price_gram_18k: number;
    currency: string;
    fetched_at: string;
}

interface HistoricalPoint {
    date: string;
    avg_24k: number;
    avg_21k: number;
    avg_18k: number;
}

interface MarketProps {
    hasLivePrices: boolean;
    hasHistoricalCharts: boolean;
    latestPrice: GoldPrice | null;
    historicalData: HistoricalPoint[];
    priceChanges?: {
        price_gram_24k: number;
        price_gram_21k: number;
        price_gram_18k: number;
        price_ounce_usd: number;
    } | null;
    filters: {
        karat: number;
        period: string;
    };
}

export default function MarketIndex({ hasLivePrices, hasHistoricalCharts, latestPrice, historicalData, priceChanges, filters }: MarketProps) {
    const handleFilterChange = (key: string, value: string | number) => {
        router.get(route('isaas.gold-savers.market.index'), {
            ...filters,
            [key]: value
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const currentKaratKey = `avg_${filters.karat}k`;
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">{__('gold_saver.live_gold_prices')}</h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('general.market_prices_latest')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Live Prices Widget */}
                    <Card className="relative overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                        <CardHeader className="bg-slate-50 border-b border-slate-100">
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="w-5 h-5 text-indigo-600" />
                                {__('general.market_prices_latest')}
                                {!hasLivePrices && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('general.premium')}</span>}
                            </CardTitle>
                            <CardDescription>{__('general.realtime_prices_charts')}</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {!hasLivePrices ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 flex-col gap-3">
                                    <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600">
                                        <Lock className="w-8 h-8" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="font-bold text-slate-900 text-lg">{__('general.live_prices_locked')}</h3>
                                        <p className="text-sm text-slate-500 mb-4">{__('general.upgrade_to_see_live_prices')}</p>
                                        <Link href={route('subscriptions.manage')}>
                                            <Button className="bg-indigo-600 hover:bg-indigo-700">{__('general.upgrade_now')}</Button>
                                        </Link>
                                    </div>
                                </div>
                            ) : null}
                            
                            <div className={!hasLivePrices ? "opacity-20 select-none pointer-events-none" : ""}>
                                {latestPrice || !hasLivePrices ? (
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
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
                                    <div className="py-12 text-center">
                                        <Activity className="w-12 h-12 text-slate-300 mx-auto mb-3 animate-pulse" />
                                        <p className="text-slate-500 font-medium">{__('general.fetching_live_prices')}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Historical Charts Widget */}
                    <Card className="relative overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                        <CardHeader className="bg-slate-50 border-b border-slate-100">
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="w-5 h-5 text-indigo-600" />
                                {__('general.historical_charts')}
                                {!hasHistoricalCharts && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('general.premium')}</span>}
                            </CardTitle>
                            <CardDescription>{__('general.advanced_historical_price_data')}</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {!hasHistoricalCharts ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 flex-col gap-3">
                                    <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600">
                                        <Lock className="w-8 h-8" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="font-bold text-slate-900 text-lg">{__('general.charts_locked')}</h3>
                                        <p className="text-sm text-slate-500 mb-4">{__('general.upgrade_to_see_charts')}</p>
                                        <Link href={route('subscriptions.manage')}>
                                            <Button className="bg-indigo-600 hover:bg-indigo-700">{__('general.upgrade_now')}</Button>
                                        </Link>
                                    </div>
                                </div>
                            ) : null}

                            <div className={!hasHistoricalCharts ? "opacity-20 select-none pointer-events-none" : ""}>
                                <div className="p-6 rounded-xl bg-white border border-slate-200 shadow-sm col-span-1 md:col-span-3">
                                    <div className="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
                                        <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                                            {[24, 21, 18].map(k => (
                                                <button
                                                    key={k}
                                                    onClick={() => handleFilterChange('karat', k)}
                                                    className={`px-4 py-1.5 rounded-md text-sm font-medium transition-colors ${filters.karat == k ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                                >
                                                    {k}k
                                                </button>
                                            ))}
                                        </div>
                                        <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                                            {[
                                                { label: '1W', value: '1w' },
                                                { label: '1M', value: '1m' },
                                                { label: '6M', value: '6m' },
                                                { label: '1Y', value: '1y' }
                                            ].map(p => (
                                                <button
                                                    key={p.value}
                                                    onClick={() => handleFilterChange('period', p.value)}
                                                    className={`px-4 py-1.5 rounded-md text-sm font-medium transition-colors ${filters.period === p.value ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                                >
                                                    {p.label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="h-80 w-full">
                                        {historicalData && historicalData.length > 0 ? (
                                            <ResponsiveContainer width="100%" height="100%">
                                                <AreaChart data={historicalData}>
                                                    <defs>
                                                        <linearGradient id="colorPrice" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.3}/>
                                                            <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                                                        </linearGradient>
                                                    </defs>
                                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                                    <XAxis 
                                                        dataKey="date" 
                                                        axisLine={false} 
                                                        tickLine={false} 
                                                        tick={{ fill: '#94a3b8', fontSize: 12 }} 
                                                        dy={10}
                                                    />
                                                    <YAxis 
                                                        axisLine={false} 
                                                        tickLine={false} 
                                                        tick={{ fill: '#94a3b8', fontSize: 12 }}
                                                        domain={['auto', 'auto']}
                                                        tickFormatter={(val) => `${val}`}
                                                    />
                                                    <RechartsTooltip 
                                                        contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)' }}
                                                        formatter={(value: any) => [`${value} EGP`, `Price (${filters.karat}k)`]}
                                                        labelStyle={{ color: '#64748b', marginBottom: '4px' }}
                                                    />
                                                    <Area 
                                                        type="monotone" 
                                                        dataKey={currentKaratKey} 
                                                        stroke="#4f46e5" 
                                                        strokeWidth={3}
                                                        fillOpacity={1} 
                                                        fill="url(#colorPrice)" 
                                                        activeDot={{ r: 6, strokeWidth: 0, fill: '#4f46e5' }}
                                                    />
                                                </AreaChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="flex items-center justify-center h-full border-2 border-dashed border-slate-200 rounded-xl">
                                                <p className="text-slate-500">{__('general.no_historical_data')}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}

