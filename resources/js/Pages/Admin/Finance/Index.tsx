import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, Edit, Plus, DollarSign, TrendingDown, TrendingUp, Users, CheckCircle2, AlertCircle, Clock, Search, X, ChevronUp, ChevronDown, Eye, ExternalLink, FileText, Layers, Calendar, RefreshCw, ChevronLeft, ChevronRight, CalendarDays } from 'lucide-react';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, addMonths, subMonths, startOfWeek, endOfWeek } from 'date-fns';
import {
    AreaChart,
    Area,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    Legend,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell
} from 'recharts';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/Components/ui/sheet";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

export default function Index({ entries, categories, users, currentTab, stats, all_currencies, filters, calendarEvents = {}, year = new Date().getFullYear(), month = new Date().getMonth() + 1 }) {
    const { errors } = usePage().props;
    const categoriesList = Array.isArray(categories) ? categories : (categories ? Object.values(categories) : []);
    const currenciesList = Array.isArray(all_currencies) ? all_currencies : (all_currencies ? Object.values(all_currencies) : []);
    
    // Recharts configurations
    const COLORS = ['#09090b', '#27272a', '#52525b', '#71717a', '#a1a1aa', '#d4d4d8', '#e4e4e7'];

    const formatYAxis = (value) => {
        if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
        if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
        return value;
    };

    const CustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-black text-white p-3 rounded-lg border border-slate-850 shadow-xl text-xs">
                    <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label}</p>
                    {payload.map((entry, idx) => (
                        <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                            <span className="text-slate-400 capitalize">{entry.name}:</span>
                            <span className="font-mono font-semibold">
                                {stats.business_currency_symbol}{entry.value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    const categoryAllocationData = (() => {
        if (currentTab === 'income') {
            return stats.income_categories || [];
        } else if (currentTab === 'salaries') {
            const employeeMap = {};
            if (entries.data) {
                entries.data.forEach(entry => {
                    if (entry.user) {
                        const name = entry.user.name;
                        employeeMap[name] = (employeeMap[name] || 0) + parseFloat(entry.amount);
                    }
                });
            }
            return Object.keys(employeeMap).map(name => ({
                name,
                value: employeeMap[name]
            })).sort((a, b) => b.value - a.value);
        } else {
            return stats.expense_categories || [];
        }
    })();

    const statusDistributionData = stats.status_distribution || [];
    
    // Filters State
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategoryFilter, setSelectedCategoryFilter] = useState(filters.category || '');
    const [selectedStatusFilter, setSelectedStatusFilter] = useState(filters.status || '');
    const [selectedUserFilter, setSelectedUserFilter] = useState(filters.user_id || '');

    // Dialog States
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [createCategoryOption, setCreateCategoryOption] = useState(categoriesList.length > 0 ? categoriesList[0].id : 'custom');
    
    const defaultCurrencyId = currenciesList.find(c => c.currency === stats.business_currency_code)?.id || (currenciesList[0]?.id || '');

    const [newEntry, setNewEntry] = useState({
        title: '',
        amount: '',
        category_id: categoriesList.length > 0 ? categoriesList[0].id : '',
        user_id: '',
        currency_id: defaultCurrencyId,
        is_recurring: false,
        recurrence_interval: 'month',
        status: 'completed',
        due_date: '',
        transaction_date: new Date().toISOString().slice(0, 10),
        type: currentTab === 'salaries' ? 'salary' : (currentTab === 'income' ? 'income' : 'expense')
    });

    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editCategoryOption, setEditCategoryOption] = useState('custom');
    const [editingEntry, setEditingEntry] = useState({
        id: '',
        title: '',
        amount: '',
        category_id: '',
        user_id: '',
        currency_id: '',
        is_recurring: false,
        recurrence_interval: 'month',
        status: 'completed',
        due_date: '',
        transaction_date: new Date().toISOString().slice(0, 10),
        type: ''
    });

    // Detail Panel State
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [selectedDetailEntry, setSelectedDetailEntry] = useState(null);

    const openDetails = (entry) => {
        setSelectedDetailEntry(entry);
        setIsDetailOpen(true);
    };

    // Handle Route Tab Changes
    const handleTabChange = (tab) => {
        router.get(route('admin.finance.index'), { tab }, { preserveState: false });
    };

    const handlePrevMonth = () => {
        const currentDateObj = new Date(year, month - 1, 1);
        const prev = subMonths(currentDateObj, 1);
        router.get(route('admin.finance.index'), {
            tab: 'calendar',
            year: prev.getFullYear(),
            month: prev.getMonth() + 1
        }, { preserveState: true });
    };

    const handleNextMonth = () => {
        const currentDateObj = new Date(year, month - 1, 1);
        const next = addMonths(currentDateObj, 1);
        router.get(route('admin.finance.index'), {
            tab: 'calendar',
            year: next.getFullYear(),
            month: next.getMonth() + 1
        }, { preserveState: true });
    };

    const handleGoToToday = () => {
        const today = new Date();
        router.get(route('admin.finance.index'), {
            tab: 'calendar',
            year: today.getFullYear(),
            month: today.getMonth() + 1
        }, { preserveState: true });
    };

    const handleDayClick = (day) => {
        const formattedDate = format(day, 'yyyy-MM-dd');
        setNewEntry(prev => ({
            ...prev,
            transaction_date: formattedDate
        }));
        setIsCreateOpen(true);
    };

    // Apply filters
    const applyFilters = () => {
        router.get(route('admin.finance.index'), {
            ...filters,
            tab: currentTab,
            search: searchTerm,
            category: selectedCategoryFilter,
            status: selectedStatusFilter,
            user_id: selectedUserFilter
        }, { preserveState: true });
    };

    // Clear filters
    const clearFilters = () => {
        setSearchTerm('');
        setSelectedCategoryFilter('');
        setSelectedStatusFilter('');
        setSelectedUserFilter('');
        router.get(route('admin.finance.index'), { tab: currentTab });
    };

    // Toggle Sort column/direction
    const handleSort = (field) => {
        let newDir = 'desc';
        if (filters.sort_by === field) {
            newDir = filters.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
            newDir = 'asc';
        }
        router.get(route('admin.finance.index'), {
            ...filters,
            tab: currentTab,
            sort_by: field,
            sort_dir: newDir
        }, { preserveState: true });
    };

    const renderSortIcon = (field) => {
        if (filters.sort_by !== field) return null;
        return filters.sort_dir === 'asc' ? <ChevronUp className="w-3.5 h-3.5 ml-1 inline" /> : <ChevronDown className="w-3.5 h-3.5 ml-1 inline" />;
    };

    const handleCreate = (e) => {
        e.preventDefault();
        router.post(route('admin.finance.store'), {
            ...newEntry,
            type: currentTab === 'salaries' ? 'salary' : (currentTab === 'income' ? 'income' : 'expense')
        }, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewEntry({
                    title: '',
                    amount: '',
                    category_id: categoriesList.length > 0 ? categoriesList[0].id : '',
                    user_id: '',
                    currency_id: defaultCurrencyId,
                    is_recurring: false,
                    recurrence_interval: 'month',
                    status: 'completed',
                    due_date: '',
                    transaction_date: new Date().toISOString().slice(0, 10),
                    type: currentTab === 'salaries' ? 'salary' : (currentTab === 'income' ? 'income' : 'expense')
                });
                setCreateCategoryOption(categoriesList.length > 0 ? categoriesList[0].id : 'custom');
            }
        });
    };

    const openEdit = (entry) => {
        const matchingCategory = categoriesList.find(c => c.name.toLowerCase() === entry.category?.name?.toLowerCase());
        const catOption = matchingCategory ? matchingCategory.id : 'custom';
        
        setEditCategoryOption(catOption);
        setEditingEntry({
            id: entry.id,
            title: entry.title,
            amount: entry.amount,
            category_id: matchingCategory ? matchingCategory.id : (entry.category?.name || ''),
            user_id: entry.user ? entry.user.id : '',
            currency_id: entry.currency_id || defaultCurrencyId,
            is_recurring: entry.is_recurring,
            recurrence_interval: 'month',
            status: entry.status || 'completed',
            due_date: entry.next_due_date || '',
            transaction_date: entry.created_at ? new Date(entry.created_at).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10),
            type: entry.type || (currentTab === 'salaries' ? 'salary' : (currentTab === 'income' ? 'income' : 'expense'))
        });
        setIsEditOpen(true);
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        router.put(route('admin.finance.update', editingEntry.id), {
            ...editingEntry
        }, {
            onSuccess: () => {
                setIsEditOpen(false);
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this record?')) {
            const entryType = currentTab === 'income' ? 'income' : (currentTab === 'salaries' ? 'salary' : 'expense');
            router.delete(route('admin.finance.destroy', { entry: id, type: entryType }));
        }
    };

    const handleMarkPaid = (id, fromDetails = false) => {
        const entryType = currentTab === 'income' ? 'income' : (currentTab === 'salaries' ? 'salary' : 'expense');
        router.post(route('admin.finance.mark-paid', { entry: id, type: entryType }), {}, {
            onSuccess: () => {
                if (fromDetails && selectedDetailEntry) {
                    setSelectedDetailEntry(prev => prev ? { ...prev, status: 'completed' } : null);
                }
            }
        });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'completed': return <span className="bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><CheckCircle2 className="w-3 h-3 mr-1"/> Completed</span>;
            case 'pending': return <span className="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><Clock className="w-3 h-3 mr-1"/> Pending</span>;
            case 'overdue': return <span className="bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><AlertCircle className="w-3 h-3 mr-1"/> Overdue</span>;
            default: return null;
        }
    };

    return (
        <AdminSidebarLayout title="Financial Operations" header="Financial Ledger">
            <Head title="Admin Financial Ledger" />
            
            {/* Top Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full mr-4 text-slate-800 border">
                        <TrendingUp className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">Monthly Net Income</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.business_currency_symbol} {parseFloat(stats.total_monthly_income).toLocaleString()}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full mr-4 text-slate-800 border">
                        <TrendingDown className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">Monthly Expenses</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.business_currency_symbol} {parseFloat(stats.total_monthly_expenses).toLocaleString()}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full mr-4 text-slate-800 border">
                        <Users className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">Monthly Payroll</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.business_currency_symbol} {parseFloat(stats.total_monthly_salaries).toLocaleString()}</h3>
                    </div>
                </div>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-200 mb-6">
                <button
                    onClick={() => handleTabChange('expenses')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 transition-colors ${currentTab === 'expenses' ? 'border-black text-black font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'}`}
                >
                    Costs & Expenses
                </button>
                <button
                    onClick={() => handleTabChange('income')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 transition-colors ${currentTab === 'income' ? 'border-black text-black font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'}`}
                >
                    Income Streams
                </button>
                <button
                    onClick={() => handleTabChange('salaries')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 transition-colors ${currentTab === 'salaries' ? 'border-black text-black font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'}`}
                >
                    Employee Payroll
                </button>
                <button
                    onClick={() => handleTabChange('calendar')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 transition-colors ${currentTab === 'calendar' ? 'border-black text-black font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'}`}
                >
                    Financial Calendar
                </button>
            </div>

            {/* Visual Charts Dashboard Section */}
            {currentTab !== 'calendar' && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    {/* 6-Month Trends Chart */}
                    <div className="lg:col-span-2 bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-1">Financial Trends (Last 6 Months)</h3>
                            <p className="text-xs text-gray-500 mb-4 font-normal">Historical comparison of net income, expenses, and payroll in {stats.business_currency_code}</p>
                        </div>
                        <div className="h-[260px] w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={stats.monthly_trends || []} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.1}/>
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                        </linearGradient>
                                        <linearGradient id="colorExpenses" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#ef4444" stopOpacity={0.1}/>
                                            <stop offset="95%" stopColor="#ef4444" stopOpacity={0}/>
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                    <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10 }} />
                                    <YAxis tickFormatter={formatYAxis} axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10 }} />
                                    <RechartsTooltip content={<CustomTooltip />} cursor={{ stroke: '#e2e8f0', strokeWidth: 1 }} />
                                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '11px', paddingTop: '15px' }} />
                                    <Area type="monotone" dataKey="income" name="Income" stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorIncome)" />
                                    <Area type="monotone" dataKey="expenses" name="Expenses" stroke="#ef4444" strokeWidth={2} fillOpacity={1} fill="url(#colorExpenses)" />
                                    <Area type="monotone" dataKey="payroll" name="Payroll" stroke="#3b82f6" strokeWidth={2} fillOpacity={0} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Breakdown & Health Summary Card */}
                    <div className="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                        {categoryAllocationData.length === 0 ? (
                            <div className="flex-1 flex flex-col items-center justify-center py-10">
                                <DollarSign className="w-10 h-10 text-slate-355 mb-2 opacity-50" />
                                <div className="text-xs text-slate-450 italic font-normal">No allocation statistics for this month</div>
                            </div>
                        ) : (
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-1">
                                    {currentTab === 'income' ? 'Income Stream Breakdown' : 'Cost & Expense Allocation'}
                                </h3>
                                <p className="text-xs text-gray-500 mb-4 font-normal">Highest spending categories for {stats.business_currency_code}</p>
                                
                                {/* Donut Chart */}
                                <div className="h-[120px] w-full flex items-center justify-center relative">
                                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                                        <span className="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Total</span>
                                        <span className="text-sm font-extrabold text-slate-900 font-mono">
                                            {stats.business_currency_symbol}{Math.round(categoryAllocationData.reduce((sum, item) => sum + item.value, 0)).toLocaleString()}
                                        </span>
                                    </div>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={categoryAllocationData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={50}
                                                outerRadius={70}
                                                paddingAngle={3}
                                                dataKey="value"
                                            >
                                                {categoryAllocationData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <RechartsTooltip content={<CustomTooltip />} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>

                                {/* Legend */}
                                <div className="mt-2 space-y-1 max-h-[100px] overflow-y-auto pr-1">
                                    {categoryAllocationData.slice(0, 4).map((entry, index) => {
                                        const total = categoryAllocationData.reduce((sum, item) => sum + item.value, 0);
                                        const percentage = total > 0 ? ((entry.value / total) * 100).toFixed(1) : 0;
                                        return (
                                            <div key={index} className="flex justify-between items-center text-[11px] text-slate-650 font-normal">
                                                <div className="flex items-center gap-1.5 truncate">
                                                    <span className="w-2 h-2 rounded-full shrink-0" style={{ backgroundColor: COLORS[index % COLORS.length] }} />
                                                    <span className="truncate font-medium text-slate-800">{entry.name}</span>
                                                </div>
                                                <span className="font-mono text-slate-500 shrink-0 font-normal">
                                                    {percentage}% ({stats.business_currency_symbol}{Math.round(entry.value).toLocaleString()})
                                                </span>
                                            </div>
                                        );
                                    })}
                                    {categoryAllocationData.length > 4 && (
                                        <div className="text-[10px] text-slate-400 text-center pt-1 italic font-normal">
                                            + {categoryAllocationData.length - 4} more records
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Status Health Distribution */}
                        <div className="mt-4 pt-4 border-t border-slate-100">
                            <h4 className="text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-2">Ledger Status Health</h4>
                            {statusDistributionData.length === 0 ? (
                                <div className="text-xs text-slate-400 italic font-normal">No ledger status data for this month</div>
                            ) : (
                                <div className="space-y-2">
                                    {/* Segmented Progress Bar */}
                                    <div className="w-full h-2 rounded-full overflow-hidden flex bg-slate-100">
                                        {statusDistributionData.map((item, idx) => {
                                            const totalAmount = statusDistributionData.reduce((sum, s) => sum + s.amount, 0);
                                            const pct = totalAmount > 0 ? (item.amount / totalAmount) * 100 : 0;
                                            if (pct === 0) return null;
                                            
                                            let bgColor = 'bg-slate-200';
                                            if (item.status.toLowerCase() === 'completed') bgColor = 'bg-green-600';
                                            if (item.status.toLowerCase() === 'pending') bgColor = 'bg-amber-500';
                                            if (item.status.toLowerCase() === 'overdue') bgColor = 'bg-red-600';
                                            
                                            return (
                                                <div 
                                                    key={idx} 
                                                    className={`${bgColor} h-full transition-all`} 
                                                    style={{ width: `${pct}%` }}
                                                    title={`${item.status}: ${pct.toFixed(1)}%`}
                                                />
                                            );
                                        })}
                                    </div>
                                    
                                    {/* Numbers Legend */}
                                    <div className="flex flex-wrap gap-x-3 gap-y-1">
                                        {statusDistributionData.map((item, idx) => {
                                            let dotColor = 'bg-slate-300';
                                            let textColor = 'text-slate-650';
                                            if (item.status.toLowerCase() === 'completed') { dotColor = 'bg-green-600'; textColor = 'text-green-700'; }
                                            if (item.status.toLowerCase() === 'pending') { dotColor = 'bg-amber-500'; textColor = 'text-amber-700'; }
                                            if (item.status.toLowerCase() === 'overdue') { dotColor = 'bg-red-600'; textColor = 'text-red-700'; }
                                            
                                            return (
                                                <div key={idx} className="flex items-center gap-1 text-[10px] font-normal">
                                                    <span className={`w-1.5 h-1.5 rounded-full ${dotColor}`} />
                                                    <span className="font-medium text-slate-800">{item.status}:</span>
                                                    <span className={`font-mono ${textColor} font-semibold`}>
                                                        {stats.business_currency_symbol}{Math.round(item.amount).toLocaleString()}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Filters and Actions Bar */}
            <div className="bg-white border rounded-xl p-4 shadow-sm mb-6 space-y-4">
                <div className="flex flex-wrap gap-3 items-center justify-between">
                    {currentTab !== 'calendar' ? (
                        <div className="flex flex-wrap gap-2 items-center flex-1 min-w-[300px]">
                            <div className="relative flex-1 max-w-[240px]">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Search by reason..."
                                    className="pl-8 h-9 text-sm"
                                    value={searchTerm}
                                    onChange={e => setSearchTerm(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && applyFilters()}
                                />
                            </div>

                            <select
                                className="rounded-md border border-gray-300 px-2 py-1.5 text-xs bg-white h-9"
                                value={selectedCategoryFilter}
                                onChange={e => setSelectedCategoryFilter(e.target.value)}
                            >
                                <option value="">All Categories</option>
                                {categoriesList.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>

                            <select
                                className="rounded-md border border-gray-300 px-2 py-1.5 text-xs bg-white h-9"
                                value={selectedStatusFilter}
                                onChange={e => setSelectedStatusFilter(e.target.value)}
                            >
                                <option value="">All Statuses</option>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="overdue">Overdue</option>
                            </select>

                            <select
                                className="rounded-md border border-gray-300 px-2 py-1.5 text-xs bg-white h-9 max-w-[180px]"
                                value={selectedUserFilter}
                                onChange={e => setSelectedUserFilter(e.target.value)}
                            >
                                <option value="">All Users</option>
                                {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>

                            <Button onClick={applyFilters} variant="secondary" size="sm" className="h-9">Filter</Button>
                            
                            {(filters.search || filters.category || filters.status || filters.user_id || searchTerm || selectedCategoryFilter || selectedStatusFilter || selectedUserFilter) && (
                                <Button onClick={clearFilters} variant="ghost" size="sm" className="h-9 text-gray-500 hover:text-black">
                                    <X className="w-4 h-4 mr-1" /> Clear
                                </Button>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 flex-1">
                            <span className="text-xs font-semibold text-gray-500 flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
                                <CalendarDays className="w-4 h-4 text-black mr-1" /> Click any day on the calendar to log a transaction.
                            </span>
                        </div>
                    )}

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button className="bg-black hover:bg-slate-800 text-white h-9">
                                <Plus className="w-4 h-4 mr-2" /> Add {currentTab === 'salaries' ? 'Salary' : currentTab === 'expenses' ? 'Expense' : currentTab === 'calendar' ? 'Record' : 'Income'}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[425px]">
                            <form onSubmit={handleCreate}>
                                <DialogHeader>
                                    <DialogTitle>Add New Ledger Record</DialogTitle>
                                    <DialogDescription>
                                        Create a new {currentTab === 'income' ? 'income stream' : 'expense entry'} in the ledger.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    {currentTab === 'calendar' && (
                                        <div className="space-y-2">
                                            <Label htmlFor="create-type">Transaction Type</Label>
                                            <select 
                                                id="create-type" 
                                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" 
                                                value={newEntry.type} 
                                                onChange={e => setNewEntry({...newEntry, type: e.target.value})}
                                            >
                                                <option value="expense">Costs & Expenses</option>
                                                <option value="income">Income Streams</option>
                                                <option value="salary">Employee Payroll</option>
                                            </select>
                                        </div>
                                    )}
                                    <div className="space-y-2">
                                        <Label htmlFor="create-title">Description / Title</Label>
                                        <Input id="create-title" required value={newEntry.title} onChange={e => setNewEntry({...newEntry, title: e.target.value})} placeholder="e.g. Server Hosting" />
                                        {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-amount">Amount</Label>
                                            <Input id="create-amount" type="number" step="0.01" min="0.01" required value={newEntry.amount} onChange={e => setNewEntry({...newEntry, amount: e.target.value})} />
                                            {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="create-currency">Currency</Label>
                                            <select id="create-currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.currency_id} onChange={e => setNewEntry({...newEntry, currency_id: e.target.value})}>
                                                {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                            </select>
                                        </div>
                                    </div>

                                    {(currentTab !== 'salaries' && newEntry.type !== 'salary') && (
                                        <div className="space-y-2">
                                            <Label>Category / Reason</Label>
                                            <div className="grid grid-cols-2 gap-2">
                                                <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={createCategoryOption} onChange={e => {
                                                    setCreateCategoryOption(e.target.value);
                                                    if (e.target.value !== 'custom') {
                                                        setNewEntry({...newEntry, category_id: e.target.value});
                                                    } else {
                                                        setNewEntry({...newEntry, category_id: ''});
                                                    }
                                                }}>
                                                    {categoriesList.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                                    <option value="custom">-- Custom Category --</option>
                                                </select>
                                                {createCategoryOption === 'custom' && (
                                                    <Input required placeholder="Enter category..." value={newEntry.category_id} onChange={e => setNewEntry({...newEntry, category_id: e.target.value})} />
                                                )}
                                            </div>
                                            {errors.category_id && <span className="text-red-600 text-xs block">{errors.category_id}</span>}
                                        </div>
                                    )}
                                    
                                    <div className="space-y-2">
                                        <Label htmlFor="create-user">
                                            {currentTab === 'salaries' || newEntry.type === 'salary' 
                                                ? 'Employee (Required)' 
                                                : (currentTab === 'income' || newEntry.type === 'income' 
                                                    ? 'Client/User (Optional)' 
                                                    : 'User/Vendor (Optional)')}
                                        </Label>
                                        <select id="create-user" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.user_id} onChange={e => setNewEntry({...newEntry, user_id: e.target.value})} required={currentTab === 'salaries' || newEntry.type === 'salary'}>
                                            <option value="">Select User...</option>
                                            {users.map(u => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                                        </select>
                                        {errors.user_id && <span className="text-red-600 text-xs block">{errors.user_id}</span>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-status">Status</Label>
                                            <select id="create-status" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.status} onChange={e => setNewEntry({...newEntry, status: e.target.value})}>
                                                <option value="completed">Completed / Paid</option>
                                                <option value="pending">Pending</option>
                                                <option value="overdue">Overdue</option>
                                            </select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="create-due">Due Date</Label>
                                            <Input id="create-due" type="date" value={newEntry.due_date} onChange={e => setNewEntry({...newEntry, due_date: e.target.value})} />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create-tx-date">Transaction Date</Label>
                                        <Input id="create-tx-date" type="date" required value={newEntry.transaction_date} onChange={e => setNewEntry({...newEntry, transaction_date: e.target.value})} />
                                        {errors.transaction_date && <span className="text-red-600 text-xs block">{errors.transaction_date}</span>}
                                    </div>

                                    {currentTab !== 'income' && (
                                        <div className="pt-2 border-t space-y-3">
                                            <div className="flex items-center space-x-2">
                                                <input
                                                    type="checkbox"
                                                    id="create-recurring"
                                                    className="rounded border-gray-300 text-black focus:ring-black h-4 w-4"
                                                    checked={newEntry.is_recurring}
                                                    onChange={e => setNewEntry({...newEntry, is_recurring: e.target.checked})}
                                                />
                                                <Label htmlFor="create-recurring" className="cursor-pointer font-medium">Recurring Expense</Label>
                                            </div>
                                            {newEntry.is_recurring && (
                                                <div className="space-y-2">
                                                    <Label htmlFor="create-interval">Interval</Label>
                                                    <select id="create-interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.recurrence_interval} onChange={e => setNewEntry({...newEntry, recurrence_interval: e.target.value})}>
                                                        <option value="day">Daily</option>
                                                        <option value="week">Weekly</option>
                                                        <option value="month">Monthly</option>
                                                        <option value="year">Annually</option>
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <DialogFooter>
                                    <Button type="submit" className="bg-black hover:bg-slate-800 text-white w-full">Save Record</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>



            {/* Data Table */}
            {currentTab !== 'calendar' && (
                <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th onClick={() => handleSort('reason')} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                    Description {renderSortIcon('reason')}
                                </th>
                                <th onClick={() => handleSort('amount')} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                    Amount {renderSortIcon('amount')}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                    Category
                                </th>
                                <th onClick={() => handleSort('created_at')} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                    Date {renderSortIcon('created_at')}
                                </th>
                                <th onClick={() => handleSort('due_date')} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                    Due Date {renderSortIcon('due_date')}
                                </th>
                                <th onClick={() => handleSort('status')} className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                    Status {renderSortIcon('status')}
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {entries.data.map((entry) => (
                                <tr key={entry.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        <div className="text-sm font-medium text-gray-900">{entry.title}</div>
                                        {entry.user && <div className="text-xs text-gray-500 mt-1">User: {entry.user.name}</div>}
                                        {entry.is_recurring && (
                                            <span className="inline-flex items-center gap-1 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-semibold px-2 py-0.5 rounded-full mt-1.5">
                                                <RefreshCw className="w-2.5 h-2.5" /> Recurring
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        <div className="text-sm font-bold text-gray-900">{parseFloat(entry.amount).toLocaleString()} {entry.currency}</div>
                                        {entry.business_amount && entry.currency !== stats.business_currency_code && (
                                            <div className="text-xs text-gray-500 mt-0.5">
                                                ≈ {parseFloat(entry.business_amount).toLocaleString()} {stats.business_currency_code}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        <div className="text-sm text-gray-500">{entry.category?.name || 'Uncategorized'}</div>
                                        {entry.project && (
                                            <div className="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                                <Layers className="w-3 h-3" /> Project: {entry.project.name}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        <span className="text-sm text-gray-900">
                                            {new Date(entry.created_at).toLocaleDateString()}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        <span className="text-sm text-gray-500">
                                            {entry.next_due_date ? new Date(entry.next_due_date).toLocaleDateString() : '-'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap cursor-pointer" onClick={() => openDetails(entry)}>
                                        {getStatusBadge(entry.status)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Button variant="ghost" size="sm" title="View Details" className="text-slate-500 hover:text-black hover:bg-slate-50 mr-1" onClick={() => openDetails(entry)}>
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                        {entry.status === 'pending' && (
                                            <Button variant="outline" size="sm" className="mr-2 border-green-200 text-green-700 hover:bg-green-50" onClick={() => handleMarkPaid(entry.id)}>
                                                <CheckCircle2 className="w-4 h-4 mr-1" /> Paid
                                            </Button>
                                        )}
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black hover:bg-slate-50 mr-1" onClick={() => openEdit(entry)}>
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-900 hover:bg-red-50" onClick={() => handleDelete(entry.id)}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {entries.data.length === 0 && (
                                <tr>
                                    <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                                        <DollarSign className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                        <h3 className="text-lg font-medium text-gray-900">No records found</h3>
                                        <p className="mt-1">Create a new entry to start tracking your finances.</p>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Pagination Controls */}
            {currentTab !== 'calendar' && entries.links && entries.links.length > 3 && (
                <div className="flex justify-between items-center mt-6">
                    <div className="text-sm text-gray-500">
                        Showing {entries.from} to {entries.to} of {entries.total} entries
                    </div>
                    <div className="flex space-x-1">
                        {entries.links.map((link, idx) => {
                            if (link.url === null) {
                                return (
                                    <span
                                        key={idx}
                                        className="px-3 py-2 border rounded text-gray-400 bg-gray-50 text-sm cursor-not-allowed"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            }
                            return (
                                <Link
                                    key={idx}
                                    href={link.url}
                                    className={`px-3 py-2 border rounded text-sm ${link.active ? 'bg-black text-white border-black font-semibold' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Calendar View */}
            {currentTab === 'calendar' && (
                <div className="space-y-6">
                    <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                        {/* Calendar Sub-header */}
                        <div className="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Button variant="outline" size="sm" onClick={handlePrevMonth}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleGoToToday}>
                                    Today
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleNextMonth}>
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                            <h2 className="text-lg font-bold text-slate-800">
                                {format(new Date(year, month - 1, 1), 'MMMM yyyy')}
                            </h2>
                        </div>

                        {/* Calendar Grid Header */}
                        <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-100/80">
                            {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(day => (
                                <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    {day}
                                </div>
                            ))}
                        </div>

                        {/* Calendar Grid Days */}
                        <div className="grid grid-cols-7 bg-white divide-x divide-y divide-slate-100">
                            {(() => {
                                const currentDateObj = new Date(year, month - 1, 1);
                                const startDate = startOfWeek(startOfMonth(currentDateObj), { weekStartsOn: 1 });
                                const endDate = endOfWeek(endOfMonth(currentDateObj), { weekStartsOn: 1 });
                                const calendarDays = eachDayOfInterval({ start: startDate, end: endDate });

                                return calendarDays.map((day, idx) => {
                                    const dateStr = format(day, 'yyyy-MM-dd');
                                    const dayEvents = calendarEvents[dateStr] || [];
                                    const isCurrentMonth = isSameMonth(day, currentDateObj);
                                    const isCurrentDay = isToday(day);

                                    return (
                                        <div
                                            key={idx}
                                            className={`min-h-[140px] p-2 flex flex-col transition-colors relative ${
                                                !isCurrentMonth ? 'bg-slate-50/30 opacity-55' : ''
                                            } ${isCurrentDay ? 'bg-indigo-50/20' : 'hover:bg-slate-50/50'}`}
                                        >
                                            <div className="flex justify-between items-start mb-2">
                                                <button
                                                    type="button"
                                                    onClick={() => handleDayClick(day)}
                                                    className={`text-xs font-semibold flex items-center justify-center h-6 w-6 rounded-full hover:bg-slate-200 transition-colors ${
                                                        isCurrentDay
                                                            ? 'bg-black text-white shadow font-bold'
                                                            : 'text-gray-700'
                                                    }`}
                                                    title="Click to add record on this day"
                                                >
                                                    {format(day, 'd')}
                                                </button>
                                            </div>

                                            <div className="flex-1 space-y-1 overflow-y-auto max-h-[110px] styled-scrollbar">
                                                {dayEvents.map((event) => {
                                                    let bgClass = 'bg-red-50 text-red-700 border-red-100';
                                                    if (event.type === 'income') {
                                                        bgClass = 'bg-green-50 text-green-700 border-green-100';
                                                    } else if (event.type === 'salary') {
                                                        bgClass = 'bg-blue-50 text-blue-700 border-blue-105';
                                                    }

                                                    return (
                                                        <button
                                                            key={`${event.type}-${event.id}`}
                                                            onClick={() => openEdit(event)}
                                                            className={`w-full text-left text-[10px] p-1 rounded border flex flex-col hover:opacity-80 transition-opacity truncate ${bgClass}`}
                                                            title={`${event.title}: ${event.currency_symbol}${parseFloat(event.amount).toLocaleString()}`}
                                                        >
                                                            <span className="font-semibold truncate">{event.title}</span>
                                                            <span className="font-bold">{event.currency_symbol} {parseFloat(event.amount).toLocaleString()}</span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                });
                            })()}
                        </div>
                    </div>
                    <style>{`
                        .styled-scrollbar::-webkit-scrollbar {
                            width: 3px;
                        }
                        .styled-scrollbar::-webkit-scrollbar-track {
                            background: transparent;
                        }
                        .styled-scrollbar::-webkit-scrollbar-thumb {
                            background-color: #d1d5db;
                            border-radius: 3px;
                        }
                    `}</style>
                </div>
            )}

            {/* Edit Dialog */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <form onSubmit={handleUpdate}>
                        <DialogHeader>
                            <DialogTitle>Edit Ledger Record</DialogTitle>
                            <DialogDescription>
                                Modify the selected transaction record in the ledger.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            {currentTab === 'calendar' && (
                                <div className="space-y-2">
                                    <Label htmlFor="edit-type">Transaction Type</Label>
                                    <select 
                                        id="edit-type" 
                                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" 
                                        value={editingEntry.type} 
                                        onChange={e => setEditingEntry({...editingEntry, type: e.target.value})}
                                    >
                                        <option value="expense">Costs & Expenses</option>
                                        <option value="income">Income Streams</option>
                                        <option value="salary">Employee Payroll</option>
                                    </select>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="edit-title">Description / Title</Label>
                                <Input id="edit-title" required value={editingEntry.title} onChange={e => setEditingEntry({...editingEntry, title: e.target.value})} />
                                {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-amount">Amount</Label>
                                    <Input id="edit-amount" type="number" step="0.01" min="0.01" required value={editingEntry.amount} onChange={e => setEditingEntry({...editingEntry, amount: e.target.value})} />
                                    {errors.amount && <span className="text-red-650 text-xs block">{errors.amount}</span>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-currency">Currency</Label>
                                    <select id="edit-currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.currency_id} onChange={e => setEditingEntry({...editingEntry, currency_id: e.target.value})}>
                                        {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                    </select>
                                </div>
                            </div>

                            {(currentTab !== 'salaries' && editingEntry.type !== 'salary') && (
                                <div className="space-y-2">
                                    <Label>Category / Reason</Label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editCategoryOption} onChange={e => {
                                            setEditCategoryOption(e.target.value);
                                            if (e.target.value !== 'custom') {
                                                setEditingEntry({...editingEntry, category_id: e.target.value});
                                            } else {
                                                setEditingEntry({...editingEntry, category_id: ''});
                                            }
                                        }}>
                                            {categoriesList.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                            <option value="custom">-- Custom Category --</option>
                                        </select>
                                        {editCategoryOption === 'custom' && (
                                            <Input required placeholder="Enter category..." value={editingEntry.category_id} onChange={e => setEditingEntry({...editingEntry, category_id: e.target.value})} />
                                        )}
                                    </div>
                                    {errors.category_id && <span className="text-red-600 text-xs block">{errors.category_id}</span>}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="edit-user">
                                    {currentTab === 'salaries' || editingEntry.type === 'salary' 
                                        ? 'Employee (Required)' 
                                        : (currentTab === 'income' || editingEntry.type === 'income' 
                                            ? 'Client/User (Optional)' 
                                            : 'User/Vendor (Optional)')}
                                </Label>
                                <select id="edit-user" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.user_id} onChange={e => setEditingEntry({...editingEntry, user_id: e.target.value})} required={currentTab === 'salaries' || editingEntry.type === 'salary'}>
                                    <option value="">Select User...</option>
                                    {users.map(u => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                                </select>
                                {errors.user_id && <span className="text-red-600 text-xs block">{errors.user_id}</span>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-status">Status</Label>
                                    <select id="edit-status" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.status} onChange={e => setEditingEntry({...editingEntry, status: e.target.value})}>
                                        <option value="completed">Completed / Paid</option>
                                        <option value="pending">Pending</option>
                                        <option value="overdue">Overdue</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-due">Due Date</Label>
                                    <Input id="edit-due" type="date" value={editingEntry.due_date} onChange={e => setEditingEntry({...editingEntry, due_date: e.target.value})} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-tx-date">Transaction Date</Label>
                                <Input id="edit-tx-date" type="date" required value={editingEntry.transaction_date} onChange={e => setEditingEntry({...editingEntry, transaction_date: e.target.value})} />
                                {errors.transaction_date && <span className="text-red-600 text-xs block">{errors.transaction_date}</span>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="submit" className="bg-black hover:bg-slate-800 text-white w-full">Update Record</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Entry Detail Sheet */}
            <Sheet open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <SheetContent className="sm:max-w-[480px] overflow-y-auto bg-white border-l">
                    {selectedDetailEntry && (
                        <div className="space-y-6 pt-4">
                            <SheetHeader className="text-left border-b pb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                                        Transaction Details
                                    </span>
                                    {getStatusBadge(selectedDetailEntry.status)}
                                </div>
                                <SheetTitle className="text-xl font-bold text-slate-900">
                                    {selectedDetailEntry.title}
                                </SheetTitle>
                                <SheetDescription className="text-xs text-gray-500">
                                    Record ID: #{selectedDetailEntry.id} • Created on {new Date(selectedDetailEntry.created_at).toLocaleString()}
                                </SheetDescription>
                            </SheetHeader>

                            {/* Financial Amount Box */}
                            <div className="bg-slate-50 border rounded-xl p-5 text-center">
                                <div className="text-xs text-gray-500 uppercase tracking-wider mb-1 font-semibold">
                                    Amount
                                </div>
                                <div className="text-3xl font-black text-slate-900 flex justify-center items-baseline gap-1">
                                    <span>{parseFloat(selectedDetailEntry.amount).toLocaleString()}</span>
                                    <span className="text-lg font-bold text-slate-500">{selectedDetailEntry.currency}</span>
                                </div>
                                {selectedDetailEntry.business_amount && selectedDetailEntry.currency !== stats.business_currency_code && (
                                    <div className="text-sm text-gray-500 mt-2 flex items-center justify-center gap-1 font-medium border-t pt-2 border-slate-200/60">
                                        <span>Equivalent:</span>
                                        <span className="font-bold text-slate-800">
                                            {parseFloat(selectedDetailEntry.business_amount).toLocaleString()} {stats.business_currency_code}
                                        </span>
                                    </div>
                                )}
                            </div>

                            {/* Core Details Grid */}
                            <div className="grid grid-cols-2 gap-4 border rounded-xl p-4">
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">Type</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        {selectedDetailEntry.type === 'received' ? (
                                            <span className="text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200 inline-flex items-center text-xs font-bold">
                                                <ArrowUpRight className="w-3 h-3 mr-1" /> Deposit / Income
                                            </span>
                                        ) : selectedDetailEntry.type === 'refunded' ? (
                                            <span className="text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200 inline-flex items-center text-xs font-bold">
                                                Refund
                                            </span>
                                        ) : selectedDetailEntry.type === 'salary' ? (
                                            <span className="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 inline-flex items-center text-xs font-bold">
                                                Payroll
                                            </span>
                                        ) : (
                                            <span className="text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200 inline-flex items-center text-xs font-bold">
                                                <ArrowDownRight className="w-3 h-3 mr-1" /> Expense
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">Category</div>
                                    <div className="text-sm font-semibold text-slate-800">
                                        {selectedDetailEntry.category?.name || 'Uncategorized'}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">Transaction Date</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5 text-gray-400" />
                                        {new Date(selectedDetailEntry.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">Due Date</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5 text-gray-400" />
                                        {selectedDetailEntry.next_due_date ? new Date(selectedDetailEntry.next_due_date).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                                {selectedDetailEntry.is_recurring && (
                                    <div className="col-span-2 space-y-1 pt-2 border-t border-slate-100">
                                        <div className="text-xs text-gray-400 font-medium">Recurrence</div>
                                        <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                            <RefreshCw className="w-3.5 h-3.5 text-slate-500" />
                                            <span>This is a recurring transaction.</span>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Client / User Card */}
                            {selectedDetailEntry.user && (
                                <div className="bg-white border rounded-xl p-4 space-y-3">
                                    <div className="flex items-center justify-between border-b pb-2">
                                        <div className="flex items-center gap-1.5">
                                            <Users className="w-4 h-4 text-slate-500" />
                                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                                Associated User / Client
                                            </span>
                                        </div>
                                        <Link
                                            href={route('admin.users.show', selectedDetailEntry.user.id)}
                                            className="text-xs text-slate-500 hover:text-black flex items-center hover:underline font-semibold"
                                        >
                                            Profile <ExternalLink className="w-3 h-3 ml-1" />
                                        </Link>
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold text-slate-800">
                                            {selectedDetailEntry.user.name}
                                        </div>
                                        <div className="text-xs text-gray-500 mt-0.5">
                                            {selectedDetailEntry.user.email}
                                        </div>
                                    </div>
                                    {/* Action Shortcuts */}
                                    <div className="grid grid-cols-2 gap-2 pt-2">
                                        <Link
                                            href={route('admin.users.notes.index', selectedDetailEntry.user.id)}
                                            className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors"
                                        >
                                            <FileText className="w-3.5 h-3.5" /> Notes
                                        </Link>
                                        <Link
                                            href={route('admin.users.files.index', selectedDetailEntry.user.id)}
                                            className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors"
                                        >
                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg> Files
                                        </Link>
                                        <Link
                                            href={route('admin.users.reports', selectedDetailEntry.user.id)}
                                            className="col-span-2 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors"
                                        >
                                            <DollarSign className="w-3.5 h-3.5" /> Financial Statement / Report
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {/* Project Card */}
                            {selectedDetailEntry.project && (
                                <div className="bg-white border rounded-xl p-4 space-y-2">
                                    <div className="flex items-center justify-between border-b pb-2">
                                        <div className="flex items-center gap-1.5">
                                            <Layers className="w-4 h-4 text-slate-500" />
                                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                                Linked Project
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold text-slate-800">
                                            {selectedDetailEntry.project.name}
                                        </div>
                                    </div>
                                    <Link
                                        href={route('admin.projects.index')}
                                        className="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors"
                                    >
                                        Go to Projects Manager
                                    </Link>
                                </div>
                            )}

                            {/* Action Buttons */}
                            <div className="border-t pt-6 space-y-2">
                                {selectedDetailEntry.status === 'pending' && (
                                    <Button
                                        className="w-full bg-green-700 hover:bg-green-800 text-white font-bold inline-flex items-center justify-center"
                                        onClick={() => {
                                            handleMarkPaid(selectedDetailEntry.id, true);
                                        }}
                                    >
                                        <CheckCircle2 className="w-4 h-4 mr-2" /> Mark as Paid / Completed
                                    </Button>
                                )}
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        className="flex-1 font-semibold text-slate-700 border-slate-300 hover:bg-slate-50 hover:text-black"
                                        onClick={() => {
                                            openEdit(selectedDetailEntry);
                                        }}
                                    >
                                        <Edit className="w-4 h-4 mr-2" /> Edit Record
                                    </Button>
                                    <Button
                                        variant="outline"
                                        className="flex-1 font-semibold border-red-200 text-red-600 hover:bg-red-50 hover:text-red-900"
                                        onClick={() => {
                                            // Close sheet before deleting
                                            setIsDetailOpen(false);
                                            handleDelete(selectedDetailEntry.id);
                                        }}
                                    >
                                        <Trash2 className="w-4 h-4 mr-2" /> Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </AdminSidebarLayout>
    );
}
