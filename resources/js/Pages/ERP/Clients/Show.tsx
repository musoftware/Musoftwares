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
import {
    ArrowLeft, User, FileText, CheckCircle, Clock, DollarSign,
    MessageSquare, FolderOpen, Activity, ChevronRight
} from 'lucide-react';

interface Client {
    id: number;
    name: string;
    email: string;
    phone?: string;
    company?: string;
    status: string;
    created_at: string;
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
}



export default function ClientShow({ client, projects, tickets, invoices, activities }: Props) {
    const totalRevenue = invoices
        .filter(i => i.status === 'paid')
        .reduce((s, i) => s + i.total, 0);

    const { menuItems, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`Client — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Back + Header */}
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
                    <Link href={route('erp.invoices.create', { client_id: client.id })}>
                        <Button size="sm" className="gap-2">
                            <FileText className="w-4 h-4" /> New Invoice
                        </Button>
                    </Link>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {[
                        { label: 'Total Revenue', value: <CurrencyDisplay amount={totalRevenue} currency="USD" />, icon: DollarSign, color: 'text-emerald-600' },
                        { label: 'Invoices', value: invoices.length, icon: FileText, color: 'text-blue-600' },
                        { label: 'Projects', value: projects.length, icon: FolderOpen, color: 'text-primary' },
                        { label: 'Tickets', value: tickets.length, icon: MessageSquare, color: 'text-amber-600' },
                    ].map(({ label, value, icon: Icon, color }) => (
                        <Card key={label} className="bg-white border border-slate-200 shadow-sm">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                    <Icon className={`w-4 h-4 ${color}`} />
                                </div>
                                <div>
                                    <p className="text-xl font-bold text-slate-900">{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
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
                                <CardTitle className="text-slate-900 text-sm font-semibold">Invoices</CardTitle>
                                <Link href={route('erp.invoices.index')} className="text-xs text-primary hover:underline">View all</Link>
                            </CardHeader>
                            <CardContent className="p-0">
                                {invoices.length === 0 ? (
                                    <EmptyState icon={FileText} title="No invoices yet" description="Create the first invoice for this client." className="rounded-none border-0 py-6" />
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
                                                    <span className="text-slate-900 font-medium text-sm"><CurrencyDisplay amount={inv.total} currency="USD" /></span>
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
                                <CardTitle className="text-slate-900 text-sm font-semibold">Projects</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {projects.length === 0 ? (
                                    <EmptyState icon={FolderOpen} title="No projects yet" description="No projects have been linked to this client." className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {projects.map(proj => (
                                            <div key={proj.id} className="flex items-center justify-between px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FolderOpen className="w-4 h-4 text-primary" />
                                                    <span className="text-sm text-slate-900">{proj.title}</span>
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
                                    <Activity className="w-4 h-4 text-slate-400" /> Activity
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {activities.length === 0 ? (
                                    <EmptyState icon={Activity} title="No activity yet" className="rounded-none border-0 py-6" />
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
                        <Card className="bg-white border border-slate-200 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                    <MessageSquare className="w-4 h-4 text-slate-400" /> Support Tickets
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {tickets.length === 0 ? (
                                    <EmptyState icon={MessageSquare} title="No tickets" description="No support tickets from this client yet." className="rounded-none border-0 py-6" />
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
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
