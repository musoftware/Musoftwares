import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Building2, 
    Users, 
    FileText, 
    UserCheck, 
    CheckSquare, 
    ArrowLeft, 
    DollarSign, 
    Mail, 
    Calendar,
    Eye,
    Zap,
    Lock,
    Unlock,
    ShieldAlert
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { formatCurrency } from '@/lib/utils';

interface TenantDetail {
    id: number;
    name: string;
    owner_name: string;
    owner_email: string;
    user_id: number;
    status: string;
    created_at: string;
    revenue: number;
}

interface ClientRow {
    id: number;
    name: string;
    email: string;
    phone: string;
    currency: any;
    invoices_count: number;
}

interface InvoiceRow {
    id: number;
    invoice_number: string;
    client_name: string;
    amount: number;
    currency: any;
    status: string;
    issued_at: string;
}

interface TeamMemberRow {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    last_login_at: string;
}

interface TaskRow {
    id: number;
    title: string;
    priority: string;
    status: string;
    due_date: string;
}

interface ShowProps {
    tenant: TenantDetail;
    clients: ClientRow[];
    invoices: InvoiceRow[];
    teamMembers: TeamMemberRow[];
    tasks: TaskRow[];
    auth: {
        user: any;
    };
}

export default function Show({ tenant, clients, invoices, teamMembers, tasks, auth }: ShowProps) {
    const [activeTab, setActiveTab] = useState<'clients' | 'invoices' | 'team' | 'tasks'>('clients');

    const handleImpersonate = () => {
        router.get(route('admin.erp.impersonate', tenant.user_id));
    };

    return (
        <AdminSidebarLayout title={__('general.erp_client')} header="ERP Client">
            <Head title={`Oversight — ${tenant.name}`} />

            <div className="space-y-6">
                {/* Back Link */}
                <div className="flex items-center gap-2">
                    <Link 
                        href={route('admin.erp.index')}
                        className="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />{__('general.back_to_registry')}</Link>
                </div>

                {/* Workspace Profile Header */}
                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <CardContent className="p-6">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div className="flex items-start gap-4">
                                <div className="h-14 w-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl shrink-0">
                                    {tenant.name.charAt(0).toUpperCase()}
                                </div>
                                <div className="space-y-1.5">
                                    <div className="flex items-center gap-2">
                                        <h1 className="text-xl font-bold text-slate-900">{tenant.name}</h1>
                                        <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                                            tenant.status === 'active' 
                                                ? 'bg-emerald-50 text-emerald-700' 
                                                : 'bg-rose-50 text-rose-700'
                                        }`}>
                                            {tenant.status}
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500">
                                        <span className="flex items-center gap-1 font-semibold text-slate-700">
                                            Owner: {tenant.owner_name}
                                        </span>
                                        <span className="flex items-center gap-1 font-mono">
                                            <Mail className="h-3.5 w-3.5 text-slate-400" /> {tenant.owner_email}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Calendar className="h-3.5 w-3.5 text-slate-400" /> Created {tenant.created_at}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                                <div className="bg-slate-50 border border-slate-100 rounded-lg p-3 text-center sm:text-left">
                                    <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">{__('general.workspace_revenue')}</span>
                                    <span className="text-lg font-mono font-bold text-slate-900">
                                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tenant.revenue)}
                                    </span>
                                </div>
                                <Button 
                                    onClick={handleImpersonate}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center justify-center gap-2 py-5 shadow-none border-0"
                                >
                                    <Zap className="h-4 w-4" />{__('general.impersonate_workspace')}</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tab Navigation */}
                <div className="border-b border-slate-200 flex gap-6 text-sm font-semibold text-slate-500 overflow-x-auto pb-px">
                    <button 
                        onClick={() => setActiveTab('clients')}
                        className={`pb-3 transition-colors border-b-2 -mb-px flex items-center gap-1.5 whitespace-nowrap ${
                            activeTab === 'clients' 
                                ? 'border-indigo-600 text-indigo-600 font-bold' 
                                : 'border-transparent hover:text-slate-900'
                        }`}
                    >
                        <Users className="h-4 w-4" /> Clients ({clients.length})
                    </button>
                    <button 
                        onClick={() => setActiveTab('invoices')}
                        className={`pb-3 transition-colors border-b-2 -mb-px flex items-center gap-1.5 whitespace-nowrap ${
                            activeTab === 'invoices' 
                                ? 'border-indigo-600 text-indigo-600 font-bold' 
                                : 'border-transparent hover:text-slate-900'
                        }`}
                    >
                        <FileText className="h-4 w-4" /> Invoices ({invoices.length})
                    </button>
                    <button 
                        onClick={() => setActiveTab('team')}
                        className={`pb-3 transition-colors border-b-2 -mb-px flex items-center gap-1.5 whitespace-nowrap ${
                            activeTab === 'team' 
                                ? 'border-indigo-600 text-indigo-600 font-bold' 
                                : 'border-transparent hover:text-slate-900'
                        }`}
                    >
                        <UserCheck className="h-4 w-4" /> Team Members ({teamMembers.length})
                    </button>
                    <button 
                        onClick={() => setActiveTab('tasks')}
                        className={`pb-3 transition-colors border-b-2 -mb-px flex items-center gap-1.5 whitespace-nowrap ${
                            activeTab === 'tasks' 
                                ? 'border-indigo-600 text-indigo-600 font-bold' 
                                : 'border-transparent hover:text-slate-900'
                        }`}
                    >
                        <CheckSquare className="h-4 w-4" /> Tasks ({tasks.length})
                    </button>
                </div>

                {/* Tab Content Cards */}
                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    {/* CLIENTS TAB */}
                    {activeTab === 'clients' && (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th className="px-6 py-3.5">{__('general.client_name')}</th>
                                        <th className="px-6 py-3.5">Email</th>
                                        <th className="px-6 py-3.5">Phone</th>
                                        <th className="px-6 py-3.5 text-center">{__('general.wallet_currency')}</th>
                                        <th className="px-6 py-3.5 text-center">{__('general.invoices_count')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {clients.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center text-slate-500 text-xs font-medium">{__('general.no_clients_registered_in_this_workspace')}</td>
                                        </tr>
                                    ) : (
                                        clients.map((client) => (
                                            <tr key={client.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                <td className="px-6 py-4 font-semibold text-slate-900">
                                                    {client.name}
                                                </td>
                                                <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                                    {client.email}
                                                </td>
                                                <td className="px-6 py-4 text-slate-500">
                                                    {client.phone}
                                                </td>
                                                <td className="px-6 py-4 text-center font-semibold text-slate-600 font-mono">
                                                    {client.currency?.currency}
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className="inline-flex items-center justify-center px-2.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold font-mono text-xs">
                                                        {client.invoices_count} invoices
                                                    </span>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* INVOICES TAB */}
                    {activeTab === 'invoices' && (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th className="px-6 py-3.5">{__('general.invoice_number')}</th>
                                        <th className="px-6 py-3.5">Client</th>
                                        <th className="px-6 py-3.5 text-right">Amount</th>
                                        <th className="px-6 py-3.5">Status</th>
                                        <th className="px-6 py-3.5">{__('general.issued_at')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {invoices.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center text-slate-500 text-xs font-medium">{__('general.no_invoices_created_in_this_workspace')}</td>
                                        </tr>
                                    ) : (
                                        invoices.map((invoice) => (
                                            <tr key={invoice.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                <td className="px-6 py-4 font-mono font-bold text-indigo-600">
                                                    {invoice.invoice_number}
                                                </td>
                                                <td className="px-6 py-4 font-semibold text-slate-900">
                                                    {invoice.client_name}
                                                </td>
                                                <td className="px-6 py-4 text-right font-bold text-slate-900 font-mono">
                                                    {formatCurrency(invoice.amount, invoice.currency)}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                                                        invoice.status === 'paid' ? 'bg-emerald-50 text-emerald-700' :
                                                        invoice.status === 'sent' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600'
                                                    }`}>
                                                        {invoice.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                    {invoice.issued_at}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* TEAM TAB */}
                    {activeTab === 'team' && (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th className="px-6 py-3.5">Name</th>
                                        <th className="px-6 py-3.5">Email</th>
                                        <th className="px-6 py-3.5">Role</th>
                                        <th className="px-6 py-3.5">Status</th>
                                        <th className="px-6 py-3.5">{__('general.last_login')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {teamMembers.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center text-slate-500 text-xs font-medium">{__('general.no_team_members_registered_in_this_workspace')}</td>
                                        </tr>
                                    ) : (
                                        teamMembers.map((member) => (
                                            <tr key={member.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                <td className="px-6 py-4 font-semibold text-slate-900">
                                                    {member.name}
                                                </td>
                                                <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                                    {member.email}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider ${
                                                        member.role === 'manager' 
                                                            ? 'bg-purple-50 text-purple-700' 
                                                            : 'bg-blue-50 text-blue-700'
                                                    }`}>
                                                        {member.role}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider ${
                                                        member.status === 'active' 
                                                            ? 'bg-emerald-50 text-emerald-700' 
                                                            : 'bg-rose-50 text-rose-700'
                                                    }`}>
                                                        {member.status === 'active' ? <Unlock className="h-3 w-3" /> : <Lock className="h-3 w-3" />}
                                                        {member.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                    {member.last_login_at}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* TASKS TAB */}
                    {activeTab === 'tasks' && (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th className="px-6 py-3.5">{__('general.task_title')}</th>
                                        <th className="px-6 py-3.5">Priority</th>
                                        <th className="px-6 py-3.5">Status</th>
                                        <th className="px-6 py-3.5">{__('general.due_date')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tasks.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-6 py-12 text-center text-slate-500 text-xs font-medium">{__('general.no_tasks_created_in_this_workspace')}</td>
                                        </tr>
                                    ) : (
                                        tasks.map((task) => (
                                            <tr key={task.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                <td className="px-6 py-4 font-semibold text-slate-900">
                                                    {task.title}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold uppercase tracking-wider ${
                                                        task.priority === 'high' ? 'bg-rose-50 text-rose-700' :
                                                        task.priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'
                                                    }`}>
                                                        {task.priority}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider ${
                                                        task.status === 'completed' ? 'bg-emerald-50 text-emerald-700' :
                                                        task.status === 'in_progress' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500'
                                                    }`}>
                                                        {task.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                    {task.due_date}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
