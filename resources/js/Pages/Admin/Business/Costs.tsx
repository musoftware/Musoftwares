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
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
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
import { __ } from '@/lib/i18n';
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

    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        if (!deleteId) return;
        setIsDeleting(true);
        router.delete(route('admin.costs.delete', deleteId), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteId(null);
                setIsDeleting(false);
            },
            onError: () => {
                setIsDeleting(false);
            }
        });
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
            title={__('general.business_costs')} 
            header="Business Costs"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">{__('general.monthly_costs')}</p>
                            <div className="p-2 bg-red-50 rounded-xl">
                                <ArrowDownRight className="h-4 w-4 text-slate-900" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_monthly_costs, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-2 font-medium">
                            <span className="text-slate-900 font-semibold bg-red-50 px-1.5 py-0.5 rounded me-1">{__('general.this_month')}</span>
                            {__('general.total_costs')}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardHeader className="pb-2">
                    <CardTitle className="text-lg font-semibold flex items-center gap-2">
                        <CalendarIcon className="w-5 h-5 text-slate-900" />
                        {__('general.cost_trends')}</CardTitle>
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
                        <CardTitle className="text-lg font-semibold text-slate-900">{__('general.cost_entries')}</CardTitle>
                        <CardDescription>{__('general.recent_expense_transactions')}</CardDescription>
                    </div>
                    <div className="flex flex-col sm:flex-row items-center w-full sm:w-auto gap-3">
                        <form onSubmit={handleSearch} className="flex items-center w-full sm:w-auto gap-2">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder={__('general.search_reason')}
                                    className="ps-9 h-9 border-slate-200 focus-visible:ring-red-500 rounded-lg w-full text-sm"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <Button type="submit" size="sm" variant="secondary">{__('general.search')}</Button>
                        </form>
                        <Button size="sm" onClick={() => router.visit(route('admin.costs.create'))} className="bg-slate-900 hover:bg-slate-900 text-white w-full sm:w-auto">
                            {__('admin.add_cost') || "Add Cost"}
                        </Button>
                    </div>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead className="w-[120px] font-semibold">{__('general.date')}</TableHead>
                                <TableHead className="font-semibold">{__('general.reason')}</TableHead>
                                <TableHead className="font-semibold">Project/Client</TableHead>
                                <TableHead className="text-end font-semibold">{__('general.amount')}</TableHead>
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
                                            <span className="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-900">
                                                {__('general.recurring')}</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {entry.project ? (
                                            <div className="text-sm font-medium text-slate-900">{entry.project.name}</div>
                                        ) : entry.user ? (
                                            <div className="text-sm text-slate-600">{entry.user.name}</div>
                                        ) : (
                                            <span className="text-slate-400 text-xs">--</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <div className="font-semibold font-mono tabular-nums text-slate-900">
                                            -{formatCurrency(Math.abs(entry.amount), entry.currency)}
                                        </div>
                                        {entry.currency !== stats.business_currency_code && (
                                            <div className="text-xs text-slate-400 font-mono mt-0.5">
                                                ~ {formatCurrency(Math.abs(entry.business_amount), stats.business_currency_code)}
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4 text-slate-500" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-[160px]">
                                                <DropdownMenuItem onClick={() => router.visit(route('admin.costs.edit', entry.id))}>
                                                    {__('general.edit') || "Edit Cost"}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    onClick={() => setDeleteId(entry.id)}
                                                    className="text-red-600 focus:text-red-600 focus:bg-red-50"
                                                >
                                                    {__('general.delete') || "Delete Cost"}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries?.data?.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-32 text-center text-slate-500">
                                        {__('general.no_cost_records_found_for_this_period')}</TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <ConfirmModal
                isOpen={deleteId !== null}
                title={__('general.delete_cost_transaction')}
                description="Are you sure you want to delete this cost? This will recalculate the associated user's ledger. This action cannot be undone."
                confirmLabel="Delete Cost"
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteId(null)}
                loading={isDeleting}
            />
        </AdminSidebarLayout>
    );
}

