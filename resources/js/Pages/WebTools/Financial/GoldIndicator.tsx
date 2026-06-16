import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer, Legend, LineChart, Line } from 'recharts';
import { LineChart as LineChartIcon, Activity, TrendingUp, DollarSign, ArrowUpRight, ArrowDownRight, Gem } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

// Mock data to simulate the GoldChart.php logic
const generateMockData = () => {
    const data: any[] = [];
    const now = new Date();
    let localPrice = 3500;
    let worldPrice = 3300;
    let usd = 48.5;

    for (let i = 30; i >= 0; i--) {
        const d = new Date();
        d.setDate(now.getDate() - i);
        
        // Random walk
        localPrice += (Math.random() - 0.4) * 50;
        worldPrice += (Math.random() - 0.45) * 40;
        usd += (Math.random() - 0.5) * 0.2;

        data.push({
            date: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            local21k: Math.round(localPrice),
            world21k: Math.round(worldPrice),
            usdPrice: parseFloat(usd.toFixed(2)),
            spread: Math.round(localPrice - worldPrice)
        });
    }
    return data;
};

const mockData = generateMockData();

export default function GoldIndicator() {
    const [timeframe, setTimeframe] = useState('1m');

    return (
        <WebToolsLayout title="Gold Indicator" activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span className="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium mb-3">
                            Market Analysis
                        </span>
                        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            <Gem className="w-8 h-8 text-amber-500" />
                            Gold Indicator
                        </h1>
                        <p className="mt-2 text-lg text-slate-600">
                            Analyze gold market trends, local vs world prices, and implied USD rates.
                        </p>
                    </div>
                    <div className="flex gap-2 items-center bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                        <Select value={timeframe} onValueChange={(val) => setTimeframe(val || "")}>
                            <SelectTrigger className="w-[150px] border-none shadow-none focus:ring-0">
                                <SelectValue placeholder="Timeframe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1w">1 Week</SelectItem>
                                <SelectItem value="1m">1 Month</SelectItem>
                                <SelectItem value="3m">3 Months</SelectItem>
                                <SelectItem value="6m">6 Months</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Key Metrics */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card className="shadow-sm border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">Local 21K Price</p>
                                    <h3 className="text-3xl font-bold text-slate-900 mt-1">{mockData[mockData.length-1].local21k.toLocaleString()} <span className="text-lg font-medium text-slate-500">EGP</span></h3>
                                </div>
                                <div className="p-2 bg-emerald-100 rounded-lg">
                                    <ArrowUpRight className="w-5 h-5 text-emerald-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-sm">
                                <span className="text-emerald-600 font-medium">+1.2%</span>
                                <span className="text-slate-500 ml-2">vs yesterday</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">World 21K Price</p>
                                    <h3 className="text-3xl font-bold text-slate-900 mt-1">{mockData[mockData.length-1].world21k.toLocaleString()} <span className="text-lg font-medium text-slate-500">EGP</span></h3>
                                </div>
                                <div className="p-2 bg-red-100 rounded-lg">
                                    <ArrowDownRight className="w-5 h-5 text-red-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-sm">
                                <span className="text-red-600 font-medium">-0.4%</span>
                                <span className="text-slate-500 ml-2">vs yesterday</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">Implied USD Rate</p>
                                    <h3 className="text-3xl font-bold text-slate-900 mt-1">{mockData[mockData.length-1].usdPrice.toFixed(2)} <span className="text-lg font-medium text-slate-500">EGP</span></h3>
                                </div>
                                <div className="p-2 bg-indigo-100 rounded-lg">
                                    <DollarSign className="w-5 h-5 text-indigo-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-sm">
                                <span className="text-emerald-600 font-medium">+0.1%</span>
                                <span className="text-slate-500 ml-2">vs yesterday</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">Market Spread</p>
                                    <h3 className="text-3xl font-bold text-slate-900 mt-1">{mockData[mockData.length-1].spread.toLocaleString()} <span className="text-lg font-medium text-slate-500">EGP</span></h3>
                                </div>
                                <div className="p-2 bg-amber-100 rounded-lg">
                                    <Activity className="w-5 h-5 text-amber-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-sm">
                                <span className="text-amber-600 font-medium">High Premium</span>
                                <span className="text-slate-500 ml-2">Wait for dip</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Area */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <Card className="shadow-sm border-slate-200 lg:col-span-2">
                        <CardHeader className="pb-2 border-b border-slate-100">
                            <div className="flex justify-between items-center">
                                <div>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <LineChartIcon className="w-5 h-5 text-indigo-500" />
                                        Local vs World Price (21K)
                                    </CardTitle>
                                    <CardDescription>Compare local market price against international spot price</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[350px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={mockData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                        <defs>
                                            <linearGradient id="colorLocal" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.3}/>
                                                <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                                            </linearGradient>
                                            <linearGradient id="colorWorld" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                                                <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} domain={['dataMin - 100', 'dataMax + 100']} />
                                        <RechartsTooltip 
                                            contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)' }}
                                            formatter={(value: any) => [value.toLocaleString() + ' EGP']}
                                        />
                                        <Legend iconType="circle" wrapperStyle={{ paddingTop: '20px' }} />
                                        <Area type="monotone" name="Local 21K" dataKey="local21k" stroke="#4f46e5" strokeWidth={2} fillOpacity={1} fill="url(#colorLocal)" />
                                        <Area type="monotone" name="World 21K" dataKey="world21k" stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorWorld)" />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="pb-2 border-b border-slate-100">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Activity className="w-5 h-5 text-amber-500" />
                                Implied USD Rate
                            </CardTitle>
                            <CardDescription>Gold-implied exchange rate</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[350px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={mockData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} domain={['dataMin - 1', 'dataMax + 1']} />
                                        <RechartsTooltip 
                                            contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                            formatter={(value: any) => [value.toFixed(2) + ' EGP', 'USD Rate']}
                                        />
                                        <Line type="monotone" dataKey="usdPrice" stroke="#f59e0b" strokeWidth={3} dot={false} activeDot={{ r: 6, fill: '#f59e0b', strokeWidth: 0 }} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                
                <Card className="bg-slate-50 border-slate-200">
                    <CardContent className="p-6">
                        <h4 className="font-semibold text-slate-900 mb-2 flex items-center gap-2">
                            <TrendingUp className="w-5 h-5 text-indigo-500" />
                            Market Insights
                        </h4>
                        <p className="text-sm text-slate-600 mb-4">
                            The current spread between local and world prices is elevated. Historically, this suggests a higher premium in the local market, likely driven by local demand and currency fluctuations.
                        </p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-slate-200">
                            <div>
                                <span className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">YTD Performance</span>
                                <span className="text-lg font-bold text-emerald-600">+14.2%</span>
                            </div>
                            <div>
                                <span className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">30-Day Avg Spread</span>
                                <span className="text-lg font-bold text-slate-800">185 EGP</span>
                            </div>
                            <div>
                                <span className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Sentiment</span>
                                <span className="text-lg font-bold text-amber-600">Premium Peak</span>
                            </div>
                            <div>
                                <span className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Currency Impact</span>
                                <span className="text-lg font-bold text-red-500">-2.1%</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </WebToolsLayout>
    );
}
