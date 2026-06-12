import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
    CardDescription
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { 
    ArrowDownRight, 
    Calendar as CalendarIcon, 
    Search,
    MoreHorizontal
} from 'lucide-react';
import { formatCurrency } from '@/lib/utils';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    ResponsiveContainer
} from 'recharts';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';

export default function Costs() {
    const { entries, stats, filters } = usePage<any>().props;

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.costs.index'), { search: searchTerm }, { preserveState: true });
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
                    <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label}</p>
                    {payload.map((entry: any, idx: number) => (
                        <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                            <span className="text-slate-400 capitalize">{entry.name}:</span>
                            <span className="font-mono font-semibold">
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
            title="Business Costs" 
            header="Business Costs"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">Monthly Costs</p>
                            <div className="p-2 bg-rose-50 rounded-xl">
                                <ArrowDownRight className="h-4 w-4 text-rose-600" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_monthly_costs, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-2 font-medium">
                            <span className="text-rose-600 font-semibold bg-rose-50 px-1.5 py-0.5 rounded mr-1">This Month</span>
                            Total Costs
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardHeader className="pb-2">
                    <CardTitle className="text-lg font-semibold flex items-center gap-2">
                        <CalendarIcon className="w-5 h-5 text-rose-500" />
                        Cost Trends
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[250px] mt-4">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={stats.monthly_trends}>
                                <defs>
                                    <linearGradient id="colorCosts" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#e11d48" stopOpacity={0.3}/>
                                        <stop offset="95%" stopColor="#e11d48" stopOpacity={0}/>
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
                                    dataKey="costs" 
                                    name="Costs"
                                    stroke="#e11d48" 
                                    strokeWidth={3}
                                    fillOpacity={1} 
                                    fill="url(#colorCosts)" 
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <Card className="border-none shadow-sm shadow-slate-200/50">
                <CardHeader className="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <CardTitle className="text-lg font-semibold text-slate-900">Cost Entries</CardTitle>
                        <CardDescription>Recent expense transactions</CardDescription>
                    </div>
                    <div className="flex flex-col sm:flex-row items-center w-full sm:w-auto gap-3">
                        <form onSubmit={handleSearch} className="flex items-center w-full sm:w-auto gap-2">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder="Search reason..."
                                    className="pl-9 h-9 border-slate-200 focus-visible:ring-rose-500 rounded-lg w-full text-sm"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <Button type="submit" size="sm" variant="secondary">Search</Button>
                        </form>
                        <Button size="sm" onClick={() => router.visit(route('admin.costs.create'))} className="bg-rose-600 hover:bg-rose-700 text-white w-full sm:w-auto">
                            {__('admin.add_cost') || "Add Cost"}
                        </Button>
                    </div>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead className="w-[120px] font-semibold">Date</TableHead>
                                <TableHead className="font-semibold">Reason</TableHead>
                                <TableHead className="font-semibold">Project/Client</TableHead>
                                <TableHead className="text-right font-semibold">Amount</TableHead>
                                <TableHead className="w-[80px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries?.data?.map((entry: any) => (
                                <TableRow key={entry.id} className="group hover:bg-slate-50/80 transition-colors">
                                    <TableCell className="text-xs text-slate-500 whitespace-nowrap">
                                        {new Date(entry.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium text-slate-900">{entry.title}</div>
                                        {entry.is_recurring && (
                                            <span className="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700">
                                                Recurring
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {entry.project ? (
                                            <div className="text-sm font-medium text-indigo-600">{entry.project.name}</div>
                                        ) : entry.user ? (
                                            <div className="text-sm text-slate-600">{entry.user.name}</div>
                                        ) : (
                                            <span className="text-slate-400 text-xs">--</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="font-semibold font-mono tabular-nums text-rose-600">
                                            -{formatCurrency(Math.abs(entry.amount), entry.currency)}
                                        </div>
                                        {entry.currency !== stats.business_currency_code && (
                                            <div className="text-xs text-slate-400 font-mono mt-0.5">
                                                ~ {formatCurrency(Math.abs(entry.business_amount), stats.business_currency_code)}
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0" disabled>
                                            <MoreHorizontal className="h-4 w-4 text-slate-400" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries?.data?.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-32 text-center text-slate-500">
                                        No cost records found for this period.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </AdminSidebarLayout>
    );
}
