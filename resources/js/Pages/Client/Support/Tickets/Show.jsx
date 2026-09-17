import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, CheckCircle, DollarSign, FolderKanban } from 'lucide-react';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import ChatWindow from '@/Components/Chat/ChatWindow';
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
        <AuthenticatedLayout>
            <Head title={`#${ticket.id} - ${ticket.ticket_subject || ticket.subject || 'Support Ticket'} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href={route('tickets.index')}
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back') || 'Back'}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                #{ticket.id} — {ticket.ticket_subject || ticket.subject || ticket.title}
                            </h1>
                            <div className="flex flex-wrap items-center gap-3 text-xs text-[#1d1d1f]/60 font-sans">
                                {isAdmin && ticket.user && (
                                    <>
                                        <span>
                                            Client: <strong className="font-semibold text-[#1d1d1f]">{ticket.user.name}</strong>
                                        </span>
                                        <span>·</span>
                                    </>
                                )}
                                <span>
                                    Priority:{' '}
                                    <strong className="font-mono uppercase text-[11px] font-bold text-[#1d1d1f]">
                                        {ticket.priority || 'Medium'}
                                    </strong>
                                </span>
                                <span>·</span>
                                <span>
                                    Created:{' '}
                                    <strong className="font-medium text-[#1d1d1f]">
                                        {new Date(ticket.created_at).toLocaleDateString()}
                                    </strong>
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 shrink-0">
                            {isAdmin && !isClosed && (
                                <button
                                    onClick={markResolved}
                                    className="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/60 rounded-full text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                >
                                    <CheckCircle className="w-3.5 h-3.5" />
                                    <span>{__('general.mark_as_resolved') || 'Mark as Resolved'}</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1200px] mx-auto px-6 sm:px-10 py-8">
                    {/* Official Quotation / Price Card */}
                    {(ticket.price !== null && ticket.price !== undefined && Number(ticket.price) > 0) && (
                        <div className="mb-6 rounded-[20px] bg-white border border-emerald-200/80 p-6 shadow-sm overflow-hidden relative">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/5 pb-4">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                        <DollarSign className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="text-base font-bold text-[#1d1d1f]">العرض المالي المعتمد للتذكرة</h3>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {ticket.pricing_status === 'quoted' ? 'تم التسعير' : (ticket.pricing_status || 'معتمد')}
                                            </span>
                                        </div>
                                        <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                            تم تحديد وتأكيد تسعير هذا الطلب من قبل الإدارة الفنية
                                        </p>
                                    </div>
                                </div>
                                <div className="text-right sm:text-end">
                                    <div className="text-xs text-[#1d1d1f]/50 mb-0.5">التكلفة الإجمالية:</div>
                                    <div className="text-2xl font-black text-emerald-600 font-mono tracking-tight">
                                        {Number(ticket.price).toLocaleString()} {ticket.currency?.symbol || ticket.currency_symbol || '$'}
                                    </div>
                                </div>
                            </div>

                            {ticket.pricing_notes && (
                                <div className="mt-4 p-3.5 rounded-xl bg-[#f5f5f7] border border-black/5 text-xs text-[#1d1d1f]/80">
                                    <span className="font-bold text-[#1d1d1f] block mb-1">تفاصيل وملاحظات التسعير:</span>
                                    <p className="whitespace-pre-wrap leading-relaxed">{ticket.pricing_notes}</p>
                                </div>
                            )}

                            {ticket.project && (
                                <div className="mt-3 flex items-center gap-2 text-xs text-[#1d1d1f]/70">
                                    <FolderKanban className="w-4 h-4 text-[#0071e3]" />
                                    <span>مرتبط بمشروع:</span>
                                    <Link
                                        href={`/client/projects/${ticket.project.id}`}
                                        className="font-semibold text-[#0071e3] hover:underline"
                                    >
                                        {ticket.project.name}
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Chat Container */}
                    <div className="bg-white rounded-[24px] border border-black/5 shadow-sm overflow-hidden flex flex-col h-[75vh]">
                        {/* Status bar */}
                        <div className="flex items-center justify-between border-b border-black/5 bg-[#f5f5f7]/60 px-6 py-3.5">
                            <div className="flex items-center gap-3">
                                <span className="text-xs font-bold uppercase tracking-wider font-mono text-[#1d1d1f]/50">Status:</span>
                                <StatusBadge status={status} />
                            </div>
                            <span className="text-[11px] font-mono text-[#1d1d1f]/40">
                                Conversation #{ticket.conversation_id || ticket.id}
                            </span>
                        </div>

                        {/* Chat Window */}
                        <div className="flex-1 overflow-hidden relative">
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
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
