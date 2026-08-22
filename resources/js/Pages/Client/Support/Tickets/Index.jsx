import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Ticket, MessageSquare, Eye, ExternalLink, ArrowLeft } from 'lucide-react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import ChatWindow from '@/Components/Chat/ChatWindow';
import { __ } from '@/lib/i18n';

export default function TicketsIndex({ tickets, isAdmin }) {
    const { auth } = usePage().props;
    const [filterStatus, setFilterStatus] = useState('');
    const [filterPriority, setFilterPriority] = useState('');
    const [openTicketId, setOpenTicketId] = useState(null);
    const triggerRef = React.useRef(null);

    const openTicket = openTicketId != null
        ? (tickets?.data ?? []).find((t) => t.id === openTicketId) ?? null
        : null;

    const handleRowOpen = (id, el) => {
        triggerRef.current = el;
        setOpenTicketId(id);
        const url = new URL(window.location.href);
        url.searchParams.set('open', String(id));
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
    };

    const handleClose = () => {
        const prev = triggerRef.current;
        setOpenTicketId(null);
        prev?.focus();
        const url = new URL(window.location.href);
        if (url.searchParams.has('open')) {
            url.searchParams.delete('open');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    };

    const columns = [
        { 
            key: 'id', 
            label: 'ID', 
            render: (row) => <span className="font-mono text-xs font-semibold text-[#1d1d1f]/60">#{row.id}</span> 
        },
        { 
            key: 'subject', 
            label: 'Subject', 
            render: (row) => <span className="font-semibold text-xs sm:text-sm text-[#1d1d1f]">{row.ticket_subject || row.subject || row.title}</span> 
        },
        ...(isAdmin ? [{ key: 'client', label: 'Client', render: (row) => <span className="text-xs text-[#1d1d1f]/70">{row.user?.name}</span> }] : []),
        { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.ticket_status || row.status || 'open'} /> },
        {
            key: 'priority',
            label: 'Priority',
            render: (row) => (
                <span
                    className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider font-mono border ${
                        row.priority?.toLowerCase() === 'high'
                            ? 'bg-rose-50 text-rose-700 border-rose-200/60'
                            : row.priority?.toLowerCase() === 'medium'
                              ? 'bg-amber-50 text-amber-700 border-amber-200/60'
                              : 'bg-emerald-50 text-emerald-700 border-emerald-200/60'
                    }`}
                >
                    {row.priority}
                </span>
            ),
        },
        { 
            key: 'updated_at', 
            label: 'Last Reply', 
            render: (row) => <span className="text-[#1d1d1f]/50 text-xs font-sans">{new Date(row.updated_at).toLocaleDateString()}</span> 
        },
        {
            key: 'action',
            label: '',
            render: (row) => (
                <div className="text-end">
                    <button
                        type="button"
                        onClick={(e) => handleRowOpen(row.id, e.currentTarget)}
                        className="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold text-[#0071e3] hover:bg-[#0071e3]/10 transition-colors cursor-pointer"
                        aria-label={isAdmin ? 'View / Respond' : 'View Ticket'}
                    >
                        <Eye className="w-3.5 h-3.5 me-1.5" />
                        {isAdmin ? 'View / Respond' : 'View Ticket'}
                    </button>
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

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.support_tickets') || 'Support Tickets'} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.support_tickets') || 'Support Tickets'}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {isAdmin ? "Manage client support requests and communications." : "Direct communication channel with our engineering & support team."}
                            </p>
                        </div>

                        {!isAdmin && (
                            <Link
                                href={route('tickets.create')}
                                className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer shrink-0"
                            >
                                <Plus className="w-4 h-4" />
                                <span>{__('general.open_ticket') || 'Open Ticket'}</span>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-6">
                    
                    {isAdmin && (
                        <div className="flex gap-3 w-full max-w-md bg-white p-3 rounded-2xl border border-black/5 shadow-sm">
                            <select
                                value={filterStatus}
                                onChange={(e) => setFilterStatus(e.target.value)}
                                className="h-10 w-full rounded-xl border-black/10 text-xs font-medium text-[#1d1d1f] focus:ring-2 focus:ring-[#0071e3]"
                            >
                                <option value="">{__('general.all_statuses') || 'All Statuses'}</option>
                                <option value="open">{__('general.open') || 'Open'}</option>
                                <option value="in_progress">{__('general.in_progress') || 'In Progress'}</option>
                                <option value="closed">{__('general.resolved') || 'Resolved'}</option>
                            </select>
                            <select
                                value={filterPriority}
                                onChange={(e) => setFilterPriority(e.target.value)}
                                className="h-10 w-full rounded-xl border-black/10 text-xs font-medium text-[#1d1d1f] focus:ring-2 focus:ring-[#0071e3]"
                            >
                                <option value="">{__('general.all_priorities') || 'All Priorities'}</option>
                                <option value="High">{__('general.high') || 'High'}</option>
                                <option value="Medium">{__('general.medium') || 'Medium'}</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    )}

                    <div className="bg-white rounded-[24px] border border-black/5 shadow-sm p-6 sm:p-8">
                        <DataTable
                            columns={columns}
                            data={isAdmin ? filteredTickets : tickets?.data || []}
                            emptyTitle={__('general.no_support_tickets_yet') || 'No Support Tickets Yet'}
                            emptyDescription={__('general.empty_tickets_friendly') || 'Quiet inbox. Need anything? We are here.'}
                        />
                    </div>

                </div>

            </div>

            {/* Support Ticket Sheet */}
            <Sheet open={openTicketId != null} onOpenChange={(o) => (o ? null : handleClose())}>
                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto p-0 sm:max-w-md bg-white border-s border-black/5 text-[#1d1d1f]"
                >
                    {openTicket && (
                        <>
                            <SheetHeader className="border-b border-black/5 bg-[#f5f5f7]/50 p-6 text-start">
                                <SheetTitle className="text-base font-bold text-[#1d1d1f] font-sans">
                                    #{openTicket.id} — {openTicket.ticket_subject || openTicket.subject || openTicket.title}
                                </SheetTitle>
                                <SheetDescription className="mt-1 flex flex-wrap items-center gap-2 text-xs text-[#1d1d1f]/60">
                                    <StatusBadge status={openTicket.ticket_status || openTicket.status || 'open'} />
                                    <span>·</span>
                                    <span className="capitalize">{openTicket.priority || 'medium'}</span>
                                </SheetDescription>
                            </SheetHeader>
                            <div className="flex h-[60vh] flex-col p-0">
                                <ChatWindow
                                    conversationId={openTicket.conversation_id || openTicket.id}
                                    participants={[
                                        {
                                            id: openTicket.user_id,
                                            name: openTicket.user?.name,
                                        },
                                    ]}
                                    readOnly={openTicket.ticket_status === 'closed' || openTicket.ticket_status === 'resolved'}
                                />
                            </div>
                            <div className="border-t border-black/5 p-4 bg-[#f5f5f7]/30">
                                <Link
                                    href={route('tickets.show', openTicket.id)}
                                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed]"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" /> {__('general.open_in_page') || 'Open in page'}
                                </Link>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}
