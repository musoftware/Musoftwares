import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ClientPageLayout from './Components/ClientPageLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';
import { FileText, FolderOpen, Activity, MessageSquare } from 'lucide-react';

interface Props {
    client: any;
    balance: number;
    lockedBalance: number;
    totalRevenue: number;
    unpaidRevenue: number;
    projectsCount: number;
    ticketsCount: number;
    hasTickets: boolean;
    invoices: any[];
    projects: any[];
    tickets: any[];
    activities: any[];
}

export default function ClientShow({
    client, balance, lockedBalance, totalRevenue, unpaidRevenue, projectsCount, ticketsCount, hasTickets,
    invoices = [], projects = [], tickets = [], activities = []
}: Props) {
    const currencyCode = client.currency?.currency;

    return (
        <ClientPageLayout
            client={client}
            balance={balance}
            lockedBalance={lockedBalance}
            totalRevenue={totalRevenue}
            unpaidRevenue={unpaidRevenue}
            projectsCount={projectsCount}
            ticketsCount={ticketsCount}
            hasTickets={hasTickets}
            activeTab="overview"
        >
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Invoices */}
                <div className="lg:col-span-2 space-y-6">
                    <Card className="bg-white border border-slate-200 shadow-sm">
                        <CardHeader className="pb-3 flex flex-row items-center justify-between">
                            <CardTitle className="text-slate-900 text-sm font-semibold">{__('general.invoices')}</CardTitle>
                            <Link href={route('erp.invoices.index')} className="text-xs text-primary hover:underline">{__('general.view_all')}</Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            {invoices.length === 0 ? (
                                <EmptyState icon={FileText} title={__('general.no_invoices_yet')} description={__('general.create_the_first_invoice_for_this_client')} className="rounded-none border-0 py-6" />
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
                        <CardHeader className="pb-3 flex flex-row items-center justify-between">
                            <CardTitle className="text-slate-900 text-sm font-semibold">{__('general.projects')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {projects.length === 0 ? (
                                <EmptyState icon={FolderOpen} title={__('general.no_projects_yet')} description={__('general.no_projects_have_been_linked_to_this_client')} className="rounded-none border-0 py-6" />
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
                                <Activity className="w-4 h-4 text-slate-400" /> {__('general.activity')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {activities.length === 0 ? (
                                <EmptyState icon={Activity} title={__('general.no_activity_yet')} className="rounded-none border-0 py-6" />
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
                            <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                                    <MessageSquare className="w-4 h-4 text-slate-400" /> {__('general.support_tickets')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {tickets.length === 0 ? (
                                    <EmptyState icon={MessageSquare} title={__('general.no_tickets')} description={__('general.no_support_tickets_from_this_client_yet')} className="rounded-none border-0 py-6" />
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
        </ClientPageLayout>
    );
}
