import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft, User, FileText, CheckCircle, Clock, DollarSign,
    MessageSquare, FolderOpen, Activity, ChevronRight,
    ArrowDownLeft, ArrowUpRight, RotateCcw, Wallet, Edit2, Lock
} from 'lucide-react';

interface Client {
    id: number;
    name: string;
    email: string;
    phone?: string;
    company?: string;
    status: string;
    created_at: string;
    currency?: { id: number; currency: string; symbol: string };
}

interface Invoice {
    id: number;
    invoice_number: string;
    status: string;
    total: number;
    created_at: string;
}

interface Project { id: number; title: string; status: string; }
interface Ticket { id: number; subject: string; status: string; created_at: string; }
interface ActivityItem { title: string; time: string; description: string; user: string; }

interface Props {
    client: Client;
    projects: Project[];
    tickets: Ticket[];
    invoices: Invoice[];
    activities: ActivityItem[];
    hasTickets?: boolean;
    balance: number;
    lockedBalance: number;
}



export default function ClientShow({ client, projects, tickets, invoices, activities, hasTickets = false, balance, lockedBalance }: Props) {
    const totalRevenue = invoices
        .filter(i => i.status === 'paid')
        .reduce((s, i) => s + i.total, 0);

    const unpaidRevenue = invoices
        .filter(i => ['unpaid', 'due', 'sent', 'overdue'].includes(i.status))
        .reduce((s, i) => s + i.total, 0);

    const currencyCode = client.currency?.currency;

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`${__('Client')} — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Back + Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.dashboard')} className="text-slate-400 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-slate-900">{client.name}</h1>
                                <StatusBadge status={client.status} />
                            </div>
                            <p className="text-slate-500 text-sm mt-0.5">{client.email}{client.company ? ` · ${client.company}` : ''}</p>
                        </div>
                    </div>
                    
                    {/* Action buttons */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Link href={route('erp.invoices.create', { client_id: client.id })}>
                            <Button size="sm" className="gap-1.5 shadow-none bg-slate-900 hover:bg-slate-800 text-white">
                                <FileText className="w-3.5 h-3.5" /> {__('New Invoice')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.wallet.adjust', client.id) + '?type=receive'}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <ArrowDownLeft className="w-3.5 h-3.5 text-emerald-600" /> {__('Receive Money')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.wallet.adjust', client.id) + '?type=send'}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <ArrowUpRight className="w-3.5 h-3.5 text-amber-600" /> {__('Send Money')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.wallet.adjust', client.id) + '?type=refund'}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <RotateCcw className="w-3.5 h-3.5 text-blue-600" /> {__('Refund')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.wallet.index', client.id)}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <Wallet className="w-3.5 h-3.5 text-slate-500" /> {__('Ledger')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.edit', client.id)}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <Edit2 className="w-3.5 h-3.5 text-slate-500" /> {__('Edit Profile')}
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    {[
                        { label: __('Available Balance'), value: <CurrencyDisplay amount={balance} currency={currencyCode} />, icon: Wallet, color: 'text-indigo-600' },
                        { label: __('Locked Balance'), value: <CurrencyDisplay amount={lockedBalance} currency={currencyCode} />, icon: Lock, color: 'text-amber-500' },
                        { label: __('Total Revenue'), value: <CurrencyDisplay amount={totalRevenue} currency={currencyCode} />, icon: DollarSign, color: 'text-emerald-600' },
                        { label: __('Unpaid Invoices'), value: unpaidRevenue > 0 ? <span className="text-rose-600"><CurrencyDisplay amount={unpaidRevenue} currency={currencyCode} /></span> : '—', icon: FileText, color: 'text-rose-600' },
                        { label: __('Projects'), value: projects.length, icon: FolderOpen, color: 'text-primary' },
                        ...(hasTickets ? [{ label: __('Tickets'), value: tickets.length, icon: MessageSquare, color: 'text-amber-600' }] : []),
                    ].map(({ label, value, icon: Icon, color }) => (
                        <Card key={label} className="bg-white border border-slate-200 shadow-sm">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                    <Icon className={`w-4 h-4 ${color}`} />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-base font-bold text-slate-900 truncate" title={typeof value === 'string' || typeof value === 'number' ? String(value) : undefined}>{value}</p>
                                    <p className="text-[10px] uppercase font-semibold tracking-wider text-slate-400 truncate" title={label}>{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Invoices */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                <CardTitle className="text-slate-900 text-sm font-semibold">{__('Invoices')}</CardTitle>
                                <Link href={route('erp.invoices.index')} className="text-xs text-primary hover:underline">{__('View all')}</Link>
                            </CardHeader>
                            <CardContent className="p-0">
                                {invoices.length === 0 ? (
                                    <EmptyState icon={FileText} title={__('No invoices yet')} description={__('Create the first invoice for this client.')} className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {invoices.slice(0, 6).map(inv => (
                                            <Link key={inv.id} href={route('erp.invoices.show', inv.id)}
                                                className="flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition-colors group">
                                                <div className="flex items-center gap-3">
                                                    <FileText className="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                    <div>
                                                        <p className="text-sm text-slate-900 font-medium">{inv.invoice_number}</p>
                                                        <p className="text-xs text-slate-500"><DateDisplay date={inv.created_at} /></p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-slate-900 font-medium text-sm"><CurrencyDisplay amount={inv.total} currency={currencyCode} /></span>
                                                    <StatusBadge status={inv.status} size="sm" />
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Projects */}
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-slate-900 text-sm font-semibold">{__('Projects')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {projects.length === 0 ? (
                                    <EmptyState icon={FolderOpen} title={__('No projects yet')} description={__('No projects have been linked to this client.')} className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {projects.map(proj => (
                                            <div key={proj.id} className="flex items-center justify-between px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FolderOpen className="w-4 h-4 text-primary" />
                                                    <Link href={route('erp.projects.show', proj.id)} className="text-sm text-slate-900 hover:text-primary hover:underline transition-colors font-medium">
                                                        {proj.title}
                                                    </Link>
                                                </div>
                                                <StatusBadge status={proj.status} size="sm" />
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar: Activity + Tickets */}
                    <div className="space-y-6">
                        {/* Activity */}
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                    <Activity className="w-4 h-4 text-slate-400" /> {__('Activity')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {activities.length === 0 ? (
                                    <EmptyState icon={Activity} title={__('No activity yet')} className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="px-4 pb-4 space-y-4">
                                        {activities.slice(0, 6).map((act, i) => (
                                            <div key={i} className="flex gap-3">
                                                <div className="mt-0.5 w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                                    <div className="w-2 h-2 rounded-full bg-primary" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-slate-900">{act.title}</p>
                                                    <p className="text-xs text-slate-500">{act.description}</p>
                                                    <p className="text-xs text-slate-400 mt-0.5">{act.time} · {act.user}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Support Tickets */}
                        {hasTickets && (
                            <Card className="bg-white border border-slate-200 shadow-sm">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                        <MessageSquare className="w-4 h-4 text-slate-400" /> {__('Support Tickets')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {tickets.length === 0 ? (
                                        <EmptyState icon={MessageSquare} title={__('No tickets')} description={__('No support tickets from this client yet.')} className="rounded-none border-0 py-6" />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {tickets.slice(0, 4).map(ticket => (
                                                <div key={ticket.id} className="px-4 py-2.5 flex items-start justify-between gap-2">
                                                    <p className="text-sm text-slate-700 leading-tight">{ticket.subject}</p>
                                                    <StatusBadge status={ticket.status} size="sm" />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
