import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { TrendingUp, Lock, ExternalLink, Activity } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer } from 'recharts';
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
}

interface MarketProps {
    hasLivePrices: boolean;
    hasHistoricalCharts: boolean;
    latestPrice: GoldPrice | null;
    historicalData: HistoricalPoint[];
}

export default function MarketIndex({ hasLivePrices, hasHistoricalCharts, latestPrice, historicalData }: MarketProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">{__('Live Gold Prices')}</h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('Market Prices Latest')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Live Prices Widget */}
                    <Card className="relative overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                        <CardHeader className="bg-slate-50 border-b border-slate-100">
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="w-5 h-5 text-indigo-600" />
                                {__('Market Prices Latest')}
                                {!hasLivePrices && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('Premium')}</span>}
                            </CardTitle>
                            <CardDescription>{__('Realtime Prices Charts')}</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {!hasLivePrices ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 flex-col gap-3">
                                    <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600">
                                        <Lock className="w-8 h-8" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="font-bold text-slate-900 text-lg">{__('Live Prices Locked')}</h3>
                                        <p className="text-sm text-slate-500 mb-4">{__('Upgrade To See Live Prices')}</p>
                                        <Link href={route('isaas.subscriptions.index')}>
                                            <Button className="bg-indigo-600 hover:bg-indigo-700">{__('Upgrade Now')}</Button>
                                        </Link>
                                    </div>
                                </div>
                            ) : null}
                            
                            <div className={!hasLivePrices ? "opacity-20 select-none pointer-events-none" : ""}>
                                {latestPrice || !hasLivePrices ? (
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                                        <div className="p-6 rounded-xl bg-white border border-slate-200 shadow-sm">
                                            <div className="text-slate-500 text-sm font-medium mb-2">24k {__('Purity')}</div>
                                            <div className="font-bold text-3xl text-slate-900">{latestPrice?.price_gram_24k ?? '4,050'}</div>
                                            <div className="text-xs text-slate-400 mt-2">{latestPrice?.currency ?? 'EGP'} / {__('G')}</div>
                                        </div>
                                        <div className="p-6 rounded-xl bg-indigo-50 border border-indigo-100 shadow-sm relative overflow-hidden">
                                            <div className="absolute top-0 right-0 w-3 h-3 bg-indigo-500 rounded-bl-xl opacity-20"></div>
                                            <div className="text-indigo-600 text-sm font-medium mb-2">21k {__('Purity')}</div>
                                            <div className="font-bold text-3xl text-indigo-700">{latestPrice?.price_gram_21k ?? '3,550'}</div>
                                            <div className="text-xs text-indigo-400 mt-2">{latestPrice?.currency ?? 'EGP'} / {__('G')}</div>
                                        </div>
                                        <div className="p-6 rounded-xl bg-white border border-slate-200 shadow-sm">
                                            <div className="text-slate-500 text-sm font-medium mb-2">18k {__('Purity')}</div>
                                            <div className="font-bold text-3xl text-slate-900">{latestPrice?.price_gram_18k ?? '3,040'}</div>
                                            <div className="text-xs text-slate-400 mt-2">{latestPrice?.currency ?? 'EGP'} / {__('G')}</div>
                                        </div>
                                        <div className="p-6 rounded-xl bg-slate-900 border border-slate-800 shadow-sm text-white">
                                            <div className="text-slate-400 text-sm font-medium mb-2">{__('Global Ounce')}</div>
                                            <div className="font-bold text-3xl">{latestPrice?.price_ounce_usd ?? '2,350'}</div>
                                            <div className="text-xs text-slate-500 mt-2">USD / {__('Ounce')}</div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="py-12 text-center">
                                        <Activity className="w-12 h-12 text-slate-300 mx-auto mb-3 animate-pulse" />
                                        <p className="text-slate-500 font-medium">{__('Fetching Live Prices')}</p>
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
                                {__('Historical Charts')}
                                {!hasHistoricalCharts && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('Premium')}</span>}
                            </CardTitle>
                            <CardDescription>{__('Advanced Historical Price Data')}</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {!hasHistoricalCharts ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 flex-col gap-3">
                                    <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600">
                                        <Lock className="w-8 h-8" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="font-bold text-slate-900 text-lg">{__('Charts Locked')}</h3>
                                        <p className="text-sm text-slate-500 mb-4">{__('Upgrade To See Charts')}</p>
                                        <Link href={route('isaas.subscriptions.index')}>
                                            <Button className="bg-indigo-600 hover:bg-indigo-700">{__('Upgrade Now')}</Button>
                                        </Link>
                                    </div>
                                </div>
                            ) : null}

                            <div className={!hasHistoricalCharts ? "opacity-20 select-none pointer-events-none" : ""}>
                                <div className="h-[400px] w-full">
                                    {historicalData && historicalData.length > 0 ? (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={historicalData} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                                <XAxis 
                                                    dataKey="date" 
                                                    axisLine={false}
                                                    tickLine={false}
                                                    tick={{ fill: '#64748b', fontSize: 12 }}
                                                    dy={10}
                                                />
                                                <YAxis 
                                                    axisLine={false}
                                                    tickLine={false}
                                                    tick={{ fill: '#64748b', fontSize: 12 }}
                                                    domain={['auto', 'auto']}
                                                    tickFormatter={(value) => `$${value}`}
                                                />
                                                <RechartsTooltip 
                                                    contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                                    formatter={(value: number) => [`${value} EGP`, '24k Price']}
                                                />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="avg_24k" 
                                                    stroke="#4f46e5" 
                                                    strokeWidth={3}
                                                    dot={false}
                                                    activeDot={{ r: 6, fill: '#4f46e5', stroke: '#fff', strokeWidth: 2 }}
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="flex items-center justify-center h-full border-2 border-dashed border-slate-200 rounded-xl">
                                            <p className="text-slate-500">{__('No Historical Data')}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
