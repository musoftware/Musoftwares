import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { PieChart as PieChartIcon, Lock, Activity, TrendingUp } from 'lucide-react';
import { PieChart, Pie, Cell, Tooltip as RechartsTooltip, ResponsiveContainer, BarChart, Bar, XAxis, YAxis, CartesianGrid, Legend } from 'recharts';
import { Button } from '@/Components/ui/button';
import GoldSaversTabs from '../Components/GoldSaversTabs';

interface PortfolioData {
    name: string;
    value: number;
}

interface ProfitData {
    month: string;
    holding_profit: number;
    sales_profit: number;
}

interface AnalyticsProps {
    hasAnalytics: boolean;
    hasBuySellAnalytics: boolean;
    portfolioData: PortfolioData[];
    karatData: PortfolioData[];
    profitData: ProfitData[];
    transactionBreakdown: PortfolioData[];
}

const COLORS = ['#4f46e5', '#818cf8', '#c7d2fe', '#e0e7ff', '#312e81', '#4338ca'];

export default function AnalyticsIndex({ hasAnalytics, hasBuySellAnalytics, portfolioData, karatData, profitData, transactionBreakdown }: AnalyticsProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">{__('Portfolio Analytics')}</h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('Portfolio Analytics')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {!hasAnalytics && (
                        <Card className="bg-indigo-50 border-indigo-100 shadow-sm overflow-hidden relative">
                            <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10 translate-x-1/2 -translate-y-1/2"></div>
                            <CardContent className="p-8 md:p-12 text-center relative z-10 flex flex-col items-center justify-center min-h-[400px]">
                                <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600 mb-6">
                                    <Lock className="w-10 h-10" />
                                </div>
                                <h2 className="text-2xl md:text-3xl font-bold text-slate-900 mb-4">{__('Advanced Analytics Locked')}</h2>
                                <p className="text-slate-600 text-lg mb-8 max-w-2xl mx-auto">
                                    {__('Upgrade To See Analytics Desc')}
                                </p>
                                <Link href={route('subscriptions.manage')}>
                                    <Button size="lg" className="bg-indigo-600 hover:bg-indigo-700 text-lg px-8 py-6 h-auto shadow-md">
                                        {__('Upgrade Now To Unlock')}
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    )}

                    {hasAnalytics && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                                                        {/* Portfolio Distribution */}
                            <Card className="shadow-sm border-slate-200">
                                <CardHeader className="bg-slate-50 border-b border-slate-100">
                                    <CardTitle className="flex items-center gap-2">
                                        <PieChartIcon className="w-5 h-5 text-indigo-600" />
                                        {__('Portfolio Distribution')}
                                    </CardTitle>
                                    <CardDescription>{__('Grams Per Wallet')}</CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    <div className="h-[300px] w-full">
                                        {portfolioData.length > 0 ? (
                                            <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                                                <PieChart>
                                                    <Pie
                                                        data={portfolioData}
                                                        cx="50%"
                                                        cy="50%"
                                                        outerRadius={60}
                                                        dataKey="value"
                                                    >
                                                        {portfolioData.map((entry, index) => (
                                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                        ))}
                                                    </Pie>
                                                    <Pie
                                                        data={karatData}
                                                        cx="50%"
                                                        cy="50%"
                                                        innerRadius={70}
                                                        outerRadius={100}
                                                        paddingAngle={2}
                                                        dataKey="value"
                                                        label={({ name }) => name}
                                                    >
                                                        {karatData.map((entry, index) => (
                                                            <Cell key={`cell-karat-${index}`} fill={COLORS[(index + 3) % COLORS.length]} />
                                                        ))}
                                                    </Pie>
                                                    <RechartsTooltip 
                                                        formatter={(value: any) => [`${value}`, __('Balance')] as any}
                                                        contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                                    />
                                                    <Legend />
                                                </PieChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="flex items-center justify-center h-full text-slate-500 italic">
                                                {__('No Data Available')}
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Profit / Loss Trends */}
                            <Card className="shadow-sm border-slate-200">
                                <CardHeader className="bg-slate-50 border-b border-slate-100">
                                    <CardTitle className="flex items-center gap-2">
                                        <TrendingUp className="w-5 h-5 text-green-600" />
                                        {__('Profit Loss Trends')}
                                    </CardTitle>
                                    <CardDescription>{__('Monthly Profit Growth')}</CardDescription>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    <div className="h-[300px] w-full">
                                        {profitData.length > 0 ? (
                                            <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                                                <BarChart data={profitData} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
                                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                                    <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fill: '#64748b', fontSize: 12 }} dy={10} />
                                                    <YAxis axisLine={false} tickLine={false} tick={{ fill: '#64748b', fontSize: 12 }} />
                                                    <RechartsTooltip 
                                                        cursor={{ fill: '#f1f5f9' }}
                                                        contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                                    />
                                                    <Legend verticalAlign="top" height={36}/>
                                                    <Bar dataKey="holding_profit" fill="#10b981" radius={[4, 4, 0, 0]} name={__('Holding Profit (Unrealized)')} />
                                                    <Bar dataKey="sales_profit" fill="#3b82f6" radius={[4, 4, 0, 0]} name={__('Sales Profit (Realized)')} />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="flex items-center justify-center h-full text-slate-500 italic">
                                                {__('No Data Available')}
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                        </div>
                    )}
                    
                    {/* Buy/Sell Analytics Widget */}
                    <Card className="shadow-sm border-slate-200 mt-6 relative overflow-hidden">
                        <CardHeader className="bg-slate-50 border-b border-slate-100">
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="w-5 h-5 text-indigo-600" />
                                {__('Buy Sell Analytics')}
                                {!hasBuySellAnalytics && <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase">{__('Premium')}</span>}
                            </CardTitle>
                            <CardDescription>{__('History Trade Performance')}</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {!hasBuySellAnalytics ? (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 flex-col gap-3">
                                    <div className="bg-white p-4 rounded-full shadow-lg border border-indigo-100 text-indigo-600">
                                        <Lock className="w-8 h-8" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="font-bold text-slate-900 text-lg">{__('Buy Sell Analytics Locked')}</h3>
                                        <p className="text-sm text-slate-500 mb-4">{__('Upgrade To See Buy Sell')}</p>
                                        <Link href={route('subscriptions.manage')}>
                                            <Button className="bg-indigo-600 hover:bg-indigo-700">{__('Upgrade Now')}</Button>
                                        </Link>
                                    </div>
                                </div>
                            ) : null}

                            <div className={!hasBuySellAnalytics ? "opacity-20 select-none pointer-events-none" : ""}>
                                <div className="h-[300px] w-full">
                                    {transactionBreakdown && transactionBreakdown.length > 0 ? (
                                        <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                                            <BarChart data={transactionBreakdown} layout="vertical" margin={{ top: 5, right: 20, bottom: 5, left: 20 }}>
                                                <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
                                                <XAxis type="number" axisLine={false} tickLine={false} tick={{ fill: '#64748b', fontSize: 12 }} />
                                                <YAxis type="category" dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#64748b', fontSize: 12, fontWeight: 500 }} />
                                                <RechartsTooltip 
                                                    cursor={{ fill: '#f1f5f9' }}
                                                    contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                                />
                                                <Bar dataKey="value" fill="#4f46e5" radius={[0, 4, 4, 0]} barSize={40} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="flex items-center justify-center h-full text-slate-500 italic border-2 border-dashed border-slate-200 rounded-xl">
                                            {__('No Data Available')}
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




