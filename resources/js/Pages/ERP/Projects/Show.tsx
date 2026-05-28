import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { EmptyState } from '@/Components/ui/EmptyState';
import {
    ArrowLeft, Briefcase, FileText, CheckCircle2, Clock, DollarSign,
    MessageSquare, Activity, ChevronRight, Edit2, ShieldAlert,
    TrendingUp, TrendingDown, Layers, CheckSquare, Plus, ArrowUpRight, BarChart2
} from 'lucide-react';

interface Project {
    id: number;
    name: string;
    description?: string;
    status: string;
    budget: number;
    deadline?: string;
    created_at: string;
    client?: { id: number; name: string; email: string };
    leader: string;
}

interface Stats {
    businessCurrency: string;
    paidInvoicesCount: number;
    unpaidInvoicesCount: number;
    totalInvoicesCount: number;
    totalInvoiced: number;
    totalPaid: number;
    totalUnpaid: number;
    totalExpenses: number;
    netRevenue: number;
}

interface Invoice {
    id: number;
    invoice_number: string;
    status: string;
    amount: number;
    business_amount: number;
    currency: string;
    created_at: string;
}

interface Expense {
    id: number;
    title: string;
    description?: string;
    amount: number;
    business_amount: number;
    currency: string;
    date: string;
    payer: string;
}

interface Task {
    id: number;
    title: string;
    due: string;
    assignee: string;
    priority: string;
    category: string;
}

interface Ticket {
    id: number;
    subject: string;
    status: string;
    priority: string;
    assignee: string;
    created_at: string;
}

interface ActivityItem {
    title: string;
    time: string;
    description: string;
    user: string;
}

interface Props {
    project: Project;
    stats: Stats;
    invoices: Invoice[];
    expenses: Expense[];
    tasks: Task[];
    tickets: Ticket[];
    activities: ActivityItem[];
    hasTickets?: boolean;
}

export default function ProjectShow({
    project,
    stats,
    invoices,
    expenses,
    tasks,
    tickets,
    activities,
    hasTickets = false
}: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('projects');
    const [activeTab, setActiveTab] = useState<'invoices' | 'expenses' | 'tasks' | 'tickets' | 'activities'>('invoices');

    const businessCurrency = stats.businessCurrency || 'USD';

    // Calculate tasks progress percentage
    const completedTasks = tasks.filter(t => t.category === 'Done').length;
    const totalTasks = tasks.length;
    const taskProgress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

    return (
        <ERPLayout title={`Project — ${project.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={`Project — ${project.name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 dashboard-container at-mobile-scroll-fix">
                {/* Back Link + Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.dashboard', { section: 'projects' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-slate-900">{project.name}</h1>
                                <Badge className={`text-[10px] rounded uppercase font-bold tracking-wider ${
                                    project.status === 'Completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    project.status === 'Active' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-600 border-slate-200'
                                }`}>
                                    {project.status}
                                </Badge>
                            </div>
                            {project.client && (
                                <p className="text-slate-500 text-sm mt-1">
                                    Client: <Link href={route('erp.clients.show', project.client.id)} className="text-primary hover:underline font-medium">{project.client.name}</Link>
                                    {project.deadline && ` · Deadline: ${new Date(project.deadline).toLocaleDateString()}`}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Edit Project Button */}
                    <div className="flex items-center gap-2">
                        <Link href={route('erp.projects.edit', project.id)}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <Edit2 className="w-3.5 h-3.5 text-slate-500" /> Edit Project
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    {[
                        { 
                            label: 'Project Budget', 
                            value: <CurrencyDisplay amount={project.budget} currency={businessCurrency} />, 
                            sub: 'Allocated budget',
                            icon: Briefcase, 
                            color: 'text-indigo-600' 
                        },
                        { 
                            label: 'Total Invoiced', 
                            value: <CurrencyDisplay amount={stats.totalInvoiced} currency={businessCurrency} />, 
                            sub: `${stats.totalInvoicesCount} invoices issued`,
                            icon: FileText, 
                            color: 'text-blue-500' 
                        },
                        { 
                            label: 'Paid Invoices', 
                            value: <CurrencyDisplay amount={stats.totalPaid} currency={businessCurrency} />, 
                            sub: `${stats.paidInvoicesCount} paid invoices`,
                            icon: CheckCircle2, 
                            color: 'text-emerald-600' 
                        },
                        { 
                            label: 'Unpaid Invoices', 
                            value: stats.totalUnpaid > 0 ? <span className="text-rose-600"><CurrencyDisplay amount={stats.totalUnpaid} currency={businessCurrency} /></span> : '—', 
                            sub: `${stats.unpaidInvoicesCount} outstanding`,
                            icon: Clock, 
                            color: 'text-rose-600' 
                        },
                        { 
                            label: 'Project Expenses', 
                            value: stats.totalExpenses > 0 ? <span className="text-amber-600"><CurrencyDisplay amount={stats.totalExpenses} currency={businessCurrency} /></span> : '—', 
                            sub: 'Invoice costs logged',
                            icon: TrendingDown, 
                            color: 'text-amber-500' 
                        },
                        { 
                            label: 'Net Profit', 
                            value: <span className={stats.netRevenue >= 0 ? 'text-emerald-600' : 'text-rose-600'}><CurrencyDisplay amount={stats.netRevenue} currency={businessCurrency} /></span>, 
                            sub: 'Total Paid - Expenses',
                            icon: stats.netRevenue >= 0 ? TrendingUp : ShieldAlert, 
                            color: stats.netRevenue >= 0 ? 'text-emerald-600' : 'text-rose-600' 
                        },
                    ].map(({ label, value, sub, icon: Icon, color }) => (
                        <Card key={label} className="bg-white border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardContent className="p-4 flex flex-col justify-between h-full space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] uppercase font-semibold tracking-wider text-slate-400 truncate" title={label}>{label}</span>
                                    <div className="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                        <Icon className={`w-3.5 h-3.5 ${color}`} />
                                    </div>
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 tracking-tight truncate">{value}</h3>
                                    <p className="text-[10px] text-slate-500 truncate mt-0.5">{sub}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Main Content Area */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Details Panel + Tabs content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Project Description */}
                        {project.description && (
                            <Card className="bg-white border border-slate-200 shadow-sm">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-slate-900 text-sm font-semibold">About Project</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-slate-600 text-sm leading-relaxed">{project.description}</p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Tabs Container */}
                        <Card className="bg-white border border-slate-200 shadow-sm overflow-hidden">
                            <div className="flex border-b border-slate-100 bg-slate-50/50 overflow-x-auto">
                                {[
                                    { id: 'invoices', label: 'Invoices', count: invoices.length, icon: FileText },
                                    { id: 'expenses', label: 'Expenses', count: expenses.length, icon: TrendingDown },
                                    { id: 'tasks', label: 'Tasks', count: tasks.length, icon: CheckSquare },
                                    ...(hasTickets ? [{ id: 'tickets', label: 'Tickets', count: tickets.length, icon: MessageSquare }] : []),
                                    { id: 'activities', label: 'Activity Log', count: null, icon: Activity },
                                ].map((tab) => {
                                    const Icon = tab.icon;
                                    const isActive = activeTab === tab.id;
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id as any)}
                                            className={`flex items-center gap-2 px-5 py-3 text-xs font-medium border-b-2 whitespace-nowrap transition-colors ${
                                                isActive
                                                    ? 'border-primary text-primary bg-white'
                                                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/40'
                                            }`}
                                        >
                                            <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-primary' : 'text-slate-400'}`} />
                                            {tab.label}
                                            {tab.count !== null && (
                                                <Badge variant="secondary" className="ml-1 px-1.5 py-0 rounded-full font-bold text-[9px] bg-slate-200 text-slate-700">
                                                    {tab.count}
                                                </Badge>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>

                            <CardContent className="p-0">
                                {/* 1. Invoices Tab */}
                                {activeTab === 'invoices' && (
                                    invoices.length === 0 ? (
                                        <EmptyState icon={FileText} title="No invoices linked" description="Create invoices for this project to track billings." className="border-0 rounded-none py-10" />
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-sm border-collapse">
                                                <thead>
                                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                        <th className="px-6 py-3">Invoice Number</th>
                                                        <th className="px-6 py-3">Date</th>
                                                        <th className="px-6 py-3 text-right">Amount</th>
                                                        <th className="px-6 py-3 text-right">Value (Base)</th>
                                                        <th className="px-6 py-3 text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 text-[13px] text-slate-600">
                                                    {invoices.map((inv) => (
                                                        <tr key={inv.id} className="hover:bg-slate-50/50 transition-colors">
                                                            <td className="px-6 py-3.5 font-medium text-slate-900">
                                                                <Link href={route('erp.invoices.show', inv.id)} className="hover:underline hover:text-primary">
                                                                    {inv.invoice_number}
                                                                </Link>
                                                            </td>
                                                            <td className="px-6 py-3.5">
                                                                <DateDisplay date={inv.created_at} />
                                                            </td>
                                                            <td className="px-6 py-3.5 text-right font-mono font-semibold">
                                                                <CurrencyDisplay amount={inv.amount} currency={inv.currency} />
                                                            </td>
                                                            <td className="px-6 py-3.5 text-right font-mono text-slate-500">
                                                                <CurrencyDisplay amount={inv.business_amount} currency={businessCurrency} />
                                                            </td>
                                                            <td className="px-6 py-3.5 text-center">
                                                                <StatusBadge status={inv.status} size="sm" />
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )
                                )}

                                {/* 2. Expenses Tab */}
                                {activeTab === 'expenses' && (
                                    expenses.length === 0 ? (
                                        <EmptyState icon={TrendingDown} title="No expenses recorded" description="Add costs inside invoices to log project expenses." className="border-0 rounded-none py-10" />
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-sm border-collapse">
                                                <thead>
                                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                        <th className="px-6 py-3">Cost Item</th>
                                                        <th className="px-6 py-3">Date</th>
                                                        <th className="px-6 py-3">Payer</th>
                                                        <th className="px-6 py-3 text-right">Amount</th>
                                                        <th className="px-6 py-3 text-right">Value (Base)</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 text-[13px] text-slate-600">
                                                    {expenses.map((exp) => (
                                                        <tr key={exp.id} className="hover:bg-slate-50/50 transition-colors">
                                                            <td className="px-6 py-3.5">
                                                                <p className="font-medium text-slate-900">{exp.title}</p>
                                                                {exp.description && <p className="text-xs text-slate-400 mt-0.5">{exp.description}</p>}
                                                            </td>
                                                            <td className="px-6 py-3.5">
                                                                {exp.date ? new Date(exp.date).toLocaleDateString() : '—'}
                                                            </td>
                                                            <td className="px-6 py-3.5 text-xs">
                                                                {exp.payer}
                                                            </td>
                                                            <td className="px-6 py-3.5 text-right font-mono font-medium">
                                                                <CurrencyDisplay amount={exp.amount} currency={exp.currency} />
                                                            </td>
                                                            <td className="px-6 py-3.5 text-right font-mono text-slate-500">
                                                                <CurrencyDisplay amount={exp.business_amount} currency={businessCurrency} />
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )
                                )}

                                {/* 3. Tasks Tab */}
                                {activeTab === 'tasks' && (
                                    tasks.length === 0 ? (
                                        <EmptyState icon={CheckSquare} title="No tasks assigned" description="Create tasks and link them to this project." className="border-0 rounded-none py-10" />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {tasks.map((task) => (
                                                <div key={task.id} className="p-4 hover:bg-slate-50/30 transition-colors flex items-center justify-between gap-4">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <CheckCircle2 className={`w-4 h-4 ${task.category === 'Done' ? 'text-emerald-500' : 'text-slate-300'}`} />
                                                            <span className={`text-[13px] font-medium ${task.category === 'Done' ? 'line-through text-slate-400' : 'text-slate-700'}`}>{task.title}</span>
                                                        </div>
                                                        <div className="flex items-center gap-3 text-xs text-slate-400">
                                                            <span>Assignee: <span className="font-semibold text-slate-600">{task.assignee}</span></span>
                                                            <span>Due: <span className="font-semibold text-slate-600">{task.due}</span></span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="outline" className="text-[10px] uppercase font-bold tracking-wider">{task.priority}</Badge>
                                                        <Badge className={`text-[10px] rounded uppercase font-bold tracking-wider ${
                                                            task.category === 'Done' ? 'bg-emerald-50 text-emerald-700' :
                                                            task.category === 'In Progress' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600'
                                                        }`}>
                                                            {task.category}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )
                                )}

                                {/* 4. Tickets Tab */}
                                {activeTab === 'tickets' && hasTickets && (
                                    tickets.length === 0 ? (
                                        <EmptyState icon={MessageSquare} title="No tickets submitted" description="No support tickets opened for this project." className="border-0 rounded-none py-10" />
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-sm border-collapse">
                                                <thead>
                                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                        <th className="px-6 py-3">Subject</th>
                                                        <th className="px-6 py-3">Date</th>
                                                        <th className="px-6 py-3">Assignee</th>
                                                        <th className="px-6 py-3 text-center">Priority</th>
                                                        <th className="px-6 py-3 text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 text-[13px] text-slate-600">
                                                    {tickets.map((ticket) => (
                                                        <tr key={ticket.id} className="hover:bg-slate-50/50 transition-colors">
                                                            <td className="px-6 py-3.5 font-medium text-slate-900">
                                                                {ticket.subject}
                                                            </td>
                                                            <td className="px-6 py-3.5">
                                                                {ticket.created_at ? new Date(ticket.created_at).toLocaleDateString() : '—'}
                                                            </td>
                                                            <td className="px-6 py-3.5 text-xs">
                                                                {ticket.assignee}
                                                            </td>
                                                            <td className="px-6 py-3.5 text-center">
                                                                <Badge variant="outline" className="text-[10px] uppercase font-bold tracking-wider">{ticket.priority}</Badge>
                                                            </td>
                                                            <td className="px-6 py-3.5 text-center">
                                                                <StatusBadge status={ticket.status} size="sm" />
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )
                                )}

                                {/* 5. Activities Tab */}
                                {activeTab === 'activities' && (
                                    activities.length === 0 ? (
                                        <EmptyState icon={Activity} title="No activity yet" description="History actions will show up here." className="border-0 rounded-none py-10" />
                                    ) : (
                                        <div className="p-6 space-y-6">
                                            {activities.map((act, index) => (
                                                <div key={index} className="flex gap-4">
                                                    <div className="mt-0.5 w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                                                        <div className="w-2.5 h-2.5 rounded-full bg-primary" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-800">{act.title}</p>
                                                        <p className="text-xs text-slate-500 mt-0.5">{act.description}</p>
                                                        <p className="text-[10px] text-slate-400 mt-1 font-mono">{act.time} · {act.user}</p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Panel: Project Details / Summary */}
                    <div className="space-y-6">
                        {/* Tasks Progress Card */}
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                    <CheckSquare className="w-4 h-4 text-slate-400" /> Tasks Progress
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                                    <span>Completed</span>
                                    <span>{completedTasks} / {totalTasks} ({taskProgress}%)</span>
                                </div>
                                <div className="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div className="bg-indigo-600 h-full rounded-full transition-all duration-500" style={{ width: `${taskProgress}%` }} />
                                </div>
                                <p className="text-xs text-slate-400">Completion rate of tasks assigned directly to this project.</p>
                            </CardContent>
                        </Card>

                        {/* Project Metadata Card */}
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                    <BarChart2 className="w-4 h-4 text-slate-400" /> Project Summary
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="divide-y divide-slate-100">
                                <div className="py-2.5 flex justify-between text-sm">
                                    <span className="text-slate-500">Project Leader</span>
                                    <span className="font-medium text-slate-900">{project.leader}</span>
                                </div>
                                <div className="py-2.5 flex justify-between text-sm">
                                    <span className="text-slate-500">Created Date</span>
                                    <span className="font-medium text-slate-900">{project.created_at ? new Date(project.created_at).toLocaleDateString() : '—'}</span>
                                </div>
                                <div className="py-2.5 flex justify-between text-sm">
                                    <span className="text-slate-500">Total Invoiced</span>
                                    <span className="font-semibold text-slate-900"><CurrencyDisplay amount={stats.totalInvoiced} currency={businessCurrency} /></span>
                                </div>
                                <div className="py-2.5 flex justify-between text-sm">
                                    <span className="text-slate-500">Total Paid</span>
                                    <span className="font-semibold text-emerald-600"><CurrencyDisplay amount={stats.totalPaid} currency={businessCurrency} /></span>
                                </div>
                                <div className="py-2.5 flex justify-between text-sm">
                                    <span className="text-slate-500">Total Costs</span>
                                    <span className="font-semibold text-rose-600"><CurrencyDisplay amount={stats.totalExpenses} currency={businessCurrency} /></span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
