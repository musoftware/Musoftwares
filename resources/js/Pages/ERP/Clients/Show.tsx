import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

    return (
        <AuthenticatedLayout>
            <Head title={`Client — ${client.name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Back + Header */}
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard')} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div className="flex-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-white">{client.name}</h1>
                            <StatusBadge status={client.status} />
                        </div>
                        <p className="text-zinc-400 text-sm mt-0.5">{client.email}{client.company ? ` · ${client.company}` : ''}</p>
                    </div>
                    <Link href={route('erp.invoices.create', { client_id: client.id })}>
                        <Button size="sm" className="bg-violet-600 hover:bg-violet-500 text-white gap-2">
                            <FileText className="w-4 h-4" /> New Invoice
                        </Button>
                    </Link>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {[
                        { label: 'Total Revenue', value: <CurrencyDisplay amount={totalRevenue} currency="USD" />, icon: DollarSign, color: 'text-emerald-400' },
                        { label: 'Invoices', value: invoices.length, icon: FileText, color: 'text-blue-400' },
                        { label: 'Projects', value: projects.length, icon: FolderOpen, color: 'text-violet-400' },
                        { label: 'Tickets', value: tickets.length, icon: MessageSquare, color: 'text-amber-400' },
                    ].map(({ label, value, icon: Icon, color }) => (
                        <Card key={label} className="bg-zinc-900 border-zinc-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center shrink-0">
                                    <Icon className={`w-4 h-4 ${color}`} />
                                </div>
                                <div>
                                    <p className="text-xl font-bold text-white">{value}</p>
                                    <p className="text-xs text-zinc-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Invoices */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="bg-zinc-900 border-zinc-800">
                            <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                <CardTitle className="text-white text-sm font-semibold">Invoices</CardTitle>
                                <Link href={route('erp.invoices.index')} className="text-xs text-violet-400 hover:text-violet-300">View all</Link>
                            </CardHeader>
                            <CardContent className="p-0">
                                {invoices.length === 0 ? (
                                    <EmptyState icon={FileText} title="No invoices yet" description="Create the first invoice for this client." className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-zinc-800">
                                        {invoices.slice(0, 6).map(inv => (
                                            <Link key={inv.id} href={route('erp.invoices.show', inv.id)}
                                                className="flex items-center justify-between px-4 py-3 hover:bg-zinc-800/50 transition-colors group">
                                                <div className="flex items-center gap-3">
                                                    <FileText className="w-4 h-4 text-zinc-500 group-hover:text-zinc-300 transition-colors" />
                                                    <div>
                                                        <p className="text-sm text-white font-medium">{inv.invoice_number}</p>
                                                        <p className="text-xs text-zinc-500"><DateDisplay date={inv.created_at} /></p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-white font-medium text-sm"><CurrencyDisplay amount={inv.total} currency="USD" /></span>
                                                    <StatusBadge status={inv.status} size="sm" />
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Projects */}
                        <Card className="bg-zinc-900 border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-white text-sm font-semibold">Projects</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {projects.length === 0 ? (
                                    <EmptyState icon={FolderOpen} title="No projects yet" description="No projects have been linked to this client." className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-zinc-800">
                                        {projects.map(proj => (
                                            <div key={proj.id} className="flex items-center justify-between px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FolderOpen className="w-4 h-4 text-violet-400" />
                                                    <span className="text-sm text-white">{proj.title}</span>
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
                        <Card className="bg-zinc-900 border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-white text-sm font-semibold flex items-center gap-2">
                                    <Activity className="w-4 h-4 text-zinc-400" /> Activity
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {activities.length === 0 ? (
                                    <EmptyState icon={Activity} title="No activity yet" className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="px-4 pb-4 space-y-4">
                                        {activities.slice(0, 6).map((act, i) => (
                                            <div key={i} className="flex gap-3">
                                                <div className="mt-0.5 w-6 h-6 rounded-full bg-zinc-800 flex items-center justify-center shrink-0">
                                                    <div className="w-2 h-2 rounded-full bg-violet-400" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-white">{act.title}</p>
                                                    <p className="text-xs text-zinc-500">{act.description}</p>
                                                    <p className="text-xs text-zinc-600 mt-0.5">{act.time} · {act.user}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Support Tickets */}
                        <Card className="bg-zinc-900 border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-white text-sm font-semibold flex items-center gap-2">
                                    <MessageSquare className="w-4 h-4 text-zinc-400" /> Support Tickets
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {tickets.length === 0 ? (
                                    <EmptyState icon={MessageSquare} title="No tickets" description="No support tickets from this client yet." className="rounded-none border-0 py-6" />
                                ) : (
                                    <div className="divide-y divide-zinc-800">
                                        {tickets.slice(0, 4).map(ticket => (
                                            <div key={ticket.id} className="px-4 py-2.5 flex items-start justify-between gap-2">
                                                <p className="text-sm text-zinc-300 leading-tight">{ticket.subject}</p>
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
        </AuthenticatedLayout>
    );
}
