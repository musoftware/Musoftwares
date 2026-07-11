import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Ticket, MessageSquare, Eye } from 'lucide-react';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/ui/EmptyState';
import { PageHeader } from '@/Components/ui/PageHeader';
import { StatusBadge } from '@/Components/ui/StatusBadge';

import { AppPage } from '@/Components/ui/AppPage';
import { SectionCard } from '@/Components/ui/SectionCard';
import { ActionBar } from '@/Components/ui/ActionBar';
import { __ } from '@/lib/i18n';

export default function TicketsIndex({ tickets, isAdmin }) {
    const { auth } = usePage().props;
    const [filterStatus, setFilterStatus] = useState('');
    const [filterPriority, setFilterPriority] = useState('');

    const columns = [
        { key: 'id', label: 'ID', render: (row) => <span className="font-medium text-gray-900 font-mono">#{row.id}</span> },
        { key: 'subject', label: 'Subject', render: (row) => <span className="font-medium">{row.ticket_subject || row.subject || row.title}</span> },
        ...(isAdmin ? [{ key: 'client', label: 'Client', render: (row) => <span className="text-gray-600">{row.user?.name}</span> }] : []),
        { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.ticket_status || row.status || 'open'} /> },
        {
            key: 'priority',
            label: 'Priority',
            render: (row) => (
                <span
                    className={`rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
                        row.priority?.toLowerCase() === 'high'
                            ? 'bg-red-50 text-red-700 border border-red-200/50'
                            : row.priority?.toLowerCase() === 'medium'
                              ? 'bg-amber-50 text-amber-700 border border-amber-200/50'
                              : 'bg-emerald-50 text-emerald-700 border border-emerald-200/50'
                    }`}
                >
                    {row.priority}
                </span>
            ),
        },
        { key: 'updated_at', label: 'Last Reply', render: (row) => <span className="text-gray-500 text-[13px]">{new Date(row.updated_at).toLocaleDateString()}</span> },
        {
            key: 'action',
            label: '',
            render: (row) => (
                <div className="text-end">
                    <Link href={route('tickets.show', row.id)}>
                        <Button variant="ghost" size="sm" className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 shadow-none h-8 px-3 text-xs">
                            <Eye className="w-3.5 h-3.5 me-1.5" />
                            {isAdmin ? 'View / Respond' : 'View Ticket'}
                        </Button>
                    </Link>
                </div>
            ),
        },
    ];

    const filteredTickets =
        tickets?.data?.filter((ticket) => {
            const tStatus = ticket.ticket_status || ticket.status;
            if (filterStatus && tStatus !== filterStatus) return false;
            if (filterPriority && ticket.priority?.toLowerCase() !== filterPriority.toLowerCase())
                return false;
            return true;
        }) || tickets?.data || [];

    const emptyStateContent = (
        <EmptyState
            icon={Ticket}
            title={__('general.no_support_tickets_yet') || 'No Support Tickets Yet'}
            description={__('general.need_help_with_billing_services_or_your_workspace_open_your_first_support_ticket') || 'Need help with billing, services, or your workspace? Open your first support ticket.'}
            actionLabel="Open Ticket"
            actionIcon={Plus}
            onClick={() => router.visit(route('tickets.create'))}
        />
    );

    return (
        <AuthenticatedLayout header="Support Tickets">
            <Head title={__('general.support_tickets') || 'Support Tickets'} />

            <AppPage>
                <PageHeader
                    title={__('general.support_tickets') || 'Support Tickets'}
                    subtitle={isAdmin ? "Manage client support requests and communications." : "Manage conversations and requests with the support team."}
                    icon={MessageSquare}
                    actions={
                        !isAdmin && (
                            <Link href={route('tickets.create')}>
                                <Button className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                    <Plus className="w-4 h-4 me-2" />
                                    {__('general.open_ticket') || 'Open Ticket'}
                                </Button>
                            </Link>
                        )
                    }
                />

                {isAdmin && (
                    <ActionBar>
                        <div className="flex gap-3 w-full max-w-md">
                            <select
                                value={filterStatus}
                                onChange={(e) => setFilterStatus(e.target.value)}
                                className="h-9 w-full rounded-md border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">{__('general.all_statuses') || 'All Statuses'}</option>
                                <option value="open">{__('general.open') || 'Open'}</option>
                                <option value="in_progress">{__('general.in_progress') || 'In Progress'}</option>
                                <option value="closed">{__('general.resolved') || 'Resolved'}</option>
                            </select>
                            <select
                                value={filterPriority}
                                onChange={(e) => setFilterPriority(e.target.value)}
                                className="h-9 w-full rounded-md border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">{__('general.all_priorities') || 'All Priorities'}</option>
                                <option value="High">{__('general.high') || 'High'}</option>
                                <option value="Medium">{__('general.medium') || 'Medium'}</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </ActionBar>
                )}

                <SectionCard noPadding>
                    <DataTable
                        columns={columns}
                        data={isAdmin ? filteredTickets : tickets?.data || []}
                        emptyState={emptyStateContent}
                        className="border-0 shadow-none rounded-none"
                    />
                </SectionCard>
            </AppPage>
        </AuthenticatedLayout>
    );
}
