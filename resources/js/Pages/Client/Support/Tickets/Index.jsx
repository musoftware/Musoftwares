import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Ticket, MessageSquare, Inbox, Eye } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { DataTable } from '@/Components/ui/DataTable';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/ui/EmptyState';
import { PageHeader } from '@/Components/ui/PageHeader';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import ChatWindow from '@/Components/Chat/ChatWindow';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';

import { AppPage } from '@/Components/ui/AppPage';
import { SectionCard } from '@/Components/ui/SectionCard';
import { ActionBar } from '@/Components/ui/ActionBar';

export default function TicketsIndex({ tickets, isAdmin }) {
    const { auth, errors } = usePage().props;
    const [isCreating, setIsCreating] = useState(false);
    const [selectedTicket, setSelectedTicket] = useState(null);
    const [filterStatus, setFilterStatus] = useState('');
    const [filterPriority, setFilterPriority] = useState('');

    const [form, setForm] = useState({
        subject: '',
        priority: 'Medium',
        description: '',
    });

    const submitTicket = (e) => {
        e.preventDefault();
        router.post(route('tickets.store'), form, {
            onSuccess: () => {
                setIsCreating(false);
                setForm({ subject: '', priority: 'Medium', description: '' });
            },
        });
    };

    const markResolved = (id) => {
        router.post(
            route('tickets.resolve', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    setSelectedTicket((prev) =>
                        prev?.id === id
                            ? { ...prev, ticket_status: 'closed' }
                            : prev,
                    ),
            },
        );
    };

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
                <div className="text-right">
                    <Button variant="ghost" size="sm" onClick={() => setSelectedTicket(row)} className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 shadow-none h-8 px-3 text-xs">
                        <Eye className="w-3.5 h-3.5 mr-1.5" />
                        {isAdmin ? 'View / Respond' : 'View Ticket'}
                    </Button>
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
            title={__('general.no_support_tickets_yet')}
            description={__('general.need_help_with_billing_services_or_your_workspace_open_your_first_support_ticket')}
            actionLabel="Open Ticket"
            actionIcon={Plus}
            onClick={() => setIsCreating(true)}
        />
    );

    return (
        <AuthenticatedLayout header="Support Tickets">
            <Head title={__('general.support_tickets')} />

            <AppPage>
                <PageHeader
                    title={__('general.support_tickets')}
                    subtitle={isAdmin ? "Manage client support requests and communications." : "Manage conversations and requests with the support team."}
                    icon={MessageSquare}
                    actions={
                        !isAdmin && (
                            <Button onClick={() => setIsCreating(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                <Plus className="w-4 h-4 mr-2" />{__('general.open_ticket')}</Button>
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
                                <option value="">{__('general.all_statuses')}</option>
                                <option value="open">Open</option>
                                <option value="in_progress">{__('general.in_progress')}</option>
                                <option value="closed">Resolved</option>
                            </select>
                            <select
                                value={filterPriority}
                                onChange={(e) => setFilterPriority(e.target.value)}
                                className="h-9 w-full rounded-md border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">{__('general.all_priorities')}</option>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
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

            {/* Create Ticket Modal */}
            <Modal show={isCreating} onClose={() => setIsCreating(false)}>
                <div className="p-6 space-y-6">
                    <div className="flex flex-col space-y-1">
                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">{__('general.open_new_ticket')}</h2>
                        <p className="text-sm text-gray-500">{__('general.please_describe_your_issue_below_we_ll_get_back_to_you_as_soon_as_possible')}</p>
                    </div>
                    
                    <form onSubmit={submitTicket} className="space-y-4">
                        <div className="space-y-2">
                            <InputLabel htmlFor="subject" value="Subject" className="text-sm font-medium" />
                            <TextInput
                                id="subject"
                                type="text"
                                className="block w-full border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 rounded-lg shadow-sm"
                                value={form.subject}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        subject: e.target.value,
                                    })
                                }
                                required
                                placeholder={__('general.e_g_problem_with_billing_invoice')}
                            />
                            <InputError message={errors.subject} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="priority" value="Priority" className="text-sm font-medium" />
                            <select
                                id="priority"
                                className="block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                value={form.priority}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        priority: e.target.value,
                                    })
                                }
                            >
                                <option value="Low">{__('general.low_general_question')}</option>
                                <option value="Medium">{__('general.medium_issue_bug')}</option>
                                <option value="High">{__('general.high_urgent_blocker')}</option>
                            </select>
                            <InputError message={errors.priority} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="description" value="Description" className="text-sm font-medium" />
                            <textarea
                                id="description"
                                rows="5"
                                className="block w-full rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none"
                                value={form.description}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        description: e.target.value,
                                    })
                                }
                                required
                                placeholder={__('general.please_provide_detailed_information_about_your_request')}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="pt-4 flex justify-end gap-3 border-t border-gray-100">
                            <Button type="button" variant="outline" onClick={() => setIsCreating(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">{__('general.submit_ticket')}</Button>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* View Ticket Chat Modal */}
            <Modal
                show={!!selectedTicket}
                onClose={() => setSelectedTicket(null)}
                maxWidth="4xl"
            >
                {selectedTicket && (
                    <div className="flex h-[80vh] flex-col overflow-hidden bg-gray-50 rounded-lg">
                        <div className="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 shadow-sm z-10">
                            <div className="space-y-1">
                                <div className="flex items-center gap-3">
                                    <h2 className="text-lg font-semibold tracking-tight text-gray-900">
                                        #{selectedTicket.id} - {selectedTicket.ticket_subject || selectedTicket.subject || selectedTicket.title}
                                    </h2>
                                    <StatusBadge status={selectedTicket.ticket_status || selectedTicket.status || 'open'} />
                                </div>
                                {isAdmin && selectedTicket.user && (
                                    <p className="text-sm text-gray-500 flex items-center gap-1">
                                        Client: <span className="font-medium text-gray-700">{selectedTicket.user.name}</span>
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                {isAdmin && selectedTicket.ticket_status !== 'closed' && (
                                    <Button
                                        variant="outline"
                                        className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                        onClick={() => markResolved(selectedTicket.id)}
                                    >{__('general.mark_as_resolved')}</Button>
                                )}
                                <Button variant="secondary" onClick={() => setSelectedTicket(null)}>
                                    Close
                                </Button>
                            </div>
                        </div>

                        <div className="flex-1 overflow-hidden bg-white/50 relative p-0">
                            <ChatWindow
                                conversationId={selectedTicket.conversation_id || selectedTicket.id}
                                participants={[
                                    {
                                        id: selectedTicket.user_id,
                                        name: selectedTicket.user?.name,
                                    },
                                ]}
                                readOnly={selectedTicket.ticket_status === 'closed'}
                            />
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
