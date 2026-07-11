import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, CheckCircle } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { PageHeader } from '@/Components/ui/PageHeader';
import { SectionCard } from '@/Components/ui/SectionCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import ChatWindow from '@/Components/Chat/ChatWindow';
import { AppPage } from '@/Components/ui/AppPage';
import { __ } from '@/lib/i18n';

export default function Show({ ticket, isAdmin }) {
    const markResolved = () => {
        router.post(
            route('tickets.resolve', ticket.id),
            {},
            {
                preserveScroll: true,
            }
        );
    };

    const status = ticket.ticket_status || ticket.status || 'open';
    const isClosed = status === 'closed' || status === 'resolved';

    return (
        <AuthenticatedLayout header={__('general.support_ticket') || 'Support Ticket'}>
            <Head title={`#${ticket.id} - ${ticket.ticket_subject || ticket.subject || 'Support Ticket'}`} />

            <AppPage>
                <PageHeader
                    title={`#${ticket.id} - ${ticket.ticket_subject || ticket.subject || ticket.title}`}
                    subtitle={
                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-1 text-sm text-gray-500">
                            {isAdmin && ticket.user && (
                                <span>
                                    Client: <span className="font-medium text-gray-700">{ticket.user.name}</span>
                                </span>
                            )}
                            <span className="hidden sm:inline">|</span>
                            <span>
                                Priority:{' '}
                                <span className="font-medium uppercase text-xs tracking-wider">
                                    {ticket.priority || 'Medium'}
                                </span>
                            </span>
                            <span className="hidden sm:inline">|</span>
                            <span>
                                Created:{' '}
                                <span className="font-medium">
                                    {new Date(ticket.created_at).toLocaleDateString()}
                                </span>
                            </span>
                        </div>
                    }
                    icon={MessageSquare}
                    actions={
                        <div className="flex flex-wrap items-center gap-3">
                            {isAdmin && !isClosed && (
                                <Button
                                    variant="outline"
                                    className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 shadow-none"
                                    onClick={markResolved}
                                >
                                    <CheckCircle className="w-4 h-4 me-2" />
                                    {__('general.mark_as_resolved') || 'Mark as Resolved'}
                                </Button>
                            )}
                            <Link href={route('tickets.index')}>
                                <Button variant="outline" className="shadow-none">
                                    <ArrowLeft className="w-4 h-4 me-2" />
                                    {__('general.back') || 'Back'}
                                </Button>
                            </Link>
                        </div>
                    }
                />

                <div className="grid grid-cols-1 gap-6">
                    <SectionCard noPadding className="w-full overflow-hidden">
                        <div className="flex flex-col bg-gray-50 border border-gray-100 rounded-xl overflow-hidden h-[70vh]">
                            {/* Inner Info Bar */}
                            <div className="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 z-10 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <span className="text-sm font-semibold text-gray-700">Status:</span>
                                    <StatusBadge status={status} />
                                </div>
                                <span className="text-xs text-gray-400">
                                    Conversation ID: {ticket.conversation_id || ticket.id}
                                </span>
                            </div>

                            {/* Chat Container */}
                            <div className="flex-1 overflow-hidden bg-white/50 relative p-0">
                                <ChatWindow
                                    conversationId={ticket.conversation_id || ticket.id}
                                    participants={[
                                        {
                                            id: ticket.user_id,
                                            name: ticket.user?.name,
                                        },
                                    ]}
                                    readOnly={isClosed}
                                />
                            </div>
                        </div>
                    </SectionCard>
                </div>
            </AppPage>
        </AuthenticatedLayout>
    );
}
