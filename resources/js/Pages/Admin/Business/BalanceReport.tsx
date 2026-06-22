import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { usePage, router } from '@inertiajs/react';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { 
    ChevronLeft,
    ChevronRight,
    TrendingUp,
    TrendingDown,
    Activity
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    ResponsiveContainer
} from 'recharts';

export default function BalanceReport() {
    const { stats } = usePage<any>().props;

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);

    const handlePrevYear = () => {
        router.get(route('admin.reports.balance'), { year: stats.year - 1 }, { preserveState: true });
    };

    const handleNextYear = () => {
        router.get(route('admin.reports.balance'), { year: stats.year + 1 }, { preserveState: true });
    };

    const formatYAxis = (value: number): string => {
        if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
        if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
        return String(value);
    };

    const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: any[]; label?: any }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-black text-white p-3 rounded-lg border border-slate-850 shadow-xl text-xs">
                    <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label} {stats.year}</p>
                    {payload.map((entry: any, idx: number) => (
                        <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                            <span className="text-slate-400 capitalize">{entry.name}:</span>
                            <span className={`font-mono font-semibold ${entry.name === 'profit' && entry.value < 0 ? 'text-rose-400' : ''}`}>
                                {formatCurrency(entry.value, stats.business_currency_code)}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <AdminSidebarLayout 
            title={__('general.balance_report')} 
            header="Balance Report"
            actions={
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="icon" onClick={handlePrevYear}>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <Select value={String(stats.year)} onValueChange={(val) => router.get(route('admin.reports.balance'), { year: val }, { preserveState: true })}>
                        <SelectTrigger className="w-[100px]">
                            <SelectValue placeholder={__('general.year')} />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map(y => (
                                <SelectItem key={y} value={String(y)}>{y}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="outline" size="icon" onClick={handleNextYear}>
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            }
        >
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <TrendingUp className="w-4 h-4 text-emerald-500" />
                            Total Income ({stats.year})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-3xl font-bold text-emerald-600 tracking-tight">
                            {formatCurrency(stats.total_income, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <TrendingDown className="w-4 h-4 text-rose-500" />
                            Total Costs ({stats.year})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-3xl font-bold text-rose-600 tracking-tight">
                            {formatCurrency(stats.total_costs, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <Activity className="w-4 h-4 text-purple-500" />
                            Net Profit ({stats.year})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className={`text-3xl font-bold tracking-tight ${stats.total_profit >= 0 ? 'text-purple-600' : 'text-rose-600'}`}>
                            {formatCurrency(stats.total_profit, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardHeader>
                    <CardTitle className="text-lg font-semibold text-slate-900">Profit & Loss Chart ({stats.year})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[400px] mt-4">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={stats.monthly_trends}>
                                <defs>
                                    <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                                        <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                    </linearGradient>
                                    <linearGradient id="colorCosts" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#e11d48" stopOpacity={0.3}/>
                                        <stop offset="95%" stopColor="#e11d48" stopOpacity={0}/>
                                    </linearGradient>
                                    <linearGradient id="colorProfit" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#8b5cf6" stopOpacity={0.3}/>
                                        <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0}/>
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                <XAxis 
                                    dataKey="name" 
                                    axisLine={false} 
                                    tickLine={false} 
                                    tick={{fontSize: 12, fill: '#64748b'}}
                                    dy={10}
                                />
                                <YAxis 
                                    axisLine={false} 
                                    tickLine={false} 
                                    tick={{fontSize: 12, fill: '#64748b'}}
                                    tickFormatter={formatYAxis}
                                    dx={-10}
                                />
                                <RechartsTooltip content={<CustomTooltip />} />
                                <Area 
                                    type="monotone" 
                                    dataKey="income" 
                                    name="income"
                                    stroke="#10b981" 
                                    strokeWidth={2}
                                    fillOpacity={1} 
                                    fill="url(#colorIncome)" 
                                />
                                <Area 
                                    type="monotone" 
                                    dataKey="costs" 
                                    name="costs"
                                    stroke="#e11d48" 
                                    strokeWidth={2}
                                    fillOpacity={1} 
                                    fill="url(#colorCosts)" 
                                />
                                <Area 
                                    type="monotone" 
                                    dataKey="profit" 
                                    name="profit"
                                    stroke="#8b5cf6" 
                                    strokeWidth={3}
                                    fillOpacity={1} 
                                    fill="url(#colorProfit)" 
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardHeader>
                    <CardTitle className="text-lg font-semibold text-slate-900">Monthly Breakdown ({stats.year})</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{__('general.month')}</TableHead>
                                <TableHead className="text-end">{__('general.income')}</TableHead>
                                <TableHead className="text-end">{__('general.costs')}</TableHead>
                                <TableHead className="text-end">{__('general.profit')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {stats.monthly_trends.map((monthData: any, idx: number) => (
                                <TableRow key={idx}>
                                    <TableCell className="font-medium text-slate-700">{monthData.name}</TableCell>
                                    <TableCell className="text-end text-emerald-600 font-medium">
                                        {formatCurrency(monthData.income, stats.business_currency_code)}
                                    </TableCell>
                                    <TableCell className="text-end text-rose-600 font-medium">
                                        {formatCurrency(monthData.costs, stats.business_currency_code)}
                                    </TableCell>
                                    <TableCell className={`text-end font-semibold ${monthData.profit >= 0 ? 'text-purple-600' : 'text-rose-600'}`}>
                                        {formatCurrency(monthData.profit, stats.business_currency_code)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AdminSidebarLayout>
    );
}

