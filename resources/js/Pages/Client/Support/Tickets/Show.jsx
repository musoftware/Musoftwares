import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    CheckCircle2, 
    DollarSign, 
    FolderKanban, 
    User, 
    Clock, 
    ShieldAlert, 
    MessageSquare,
    ExternalLink
} from 'lucide-react';
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
    const priority = ticket.priority || 'Medium';

    const priorityBadgeColor = {
        low: 'bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border-slate-200 dark:border-zinc-700',
        medium: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/40',
        high: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/40',
        urgent: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/40',
    }[priority.toLowerCase()] || 'bg-slate-100 text-slate-700 border-slate-200';

    const hasQuotation = ticket.price !== null && ticket.price !== undefined && Number(ticket.price) > 0;

    return (
        <AuthenticatedLayout>
            <Head title={`#${ticket.id} - ${ticket.ticket_subject || ticket.subject || 'Support Ticket'} — Musoftware`} />

            <div className="w-full bg-[#f5f5f7] dark:bg-[#090d16] text-[#1d1d1f] dark:text-[#f8fafc] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3] pb-10">
                
                {/* Clean Top Context Bar */}
                <div className="w-full bg-white dark:bg-[#0f172a] border-b border-black/5 dark:border-white/10 py-5 px-4 sm:px-8">
                    <div className="max-w-[1400px] mx-auto">
                        
                        {/* Navigation & Actions Top Row */}
                        <div className="flex items-center justify-between gap-4 mb-3">
                            <Link
                                href={route('tickets.index')}
                                className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0071e3] dark:text-[#3898ec] hover:underline transition-colors"
                            >
                                <ArrowLeft className="h-3.5 w-3.5 rtl:rotate-180" />
                                <span>{__('general.back_to_tickets') || 'Back to Tickets'}</span>
                            </Link>

                            <div className="flex items-center gap-2 shrink-0">
                                {isAdmin && !isClosed && (
                                    <button
                                        type="button"
                                        onClick={markResolved}
                                        className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 rounded-full text-xs font-semibold transition-all shadow-2xs cursor-pointer"
                                    >
                                        <CheckCircle2 className="w-3.5 h-3.5" />
                                        <span>{__('general.mark_as_resolved') || 'Mark as Resolved'}</span>
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Title & Metadata */}
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div className="space-y-2 min-w-0">
                                <div className="flex flex-wrap items-center gap-2.5">
                                    <span className="font-mono text-sm sm:text-base font-bold text-[#0071e3] dark:text-[#3898ec] shrink-0">
                                        #{ticket.id}
                                    </span>
                                    <span className="text-zinc-300 dark:text-zinc-700">·</span>
                                    <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-[#1d1d1f] dark:text-white break-words" dir="auto">
                                        <bdi>{ticket.ticket_subject || ticket.subject || ticket.title}</bdi>
                                    </h1>
                                </div>

                                {/* Metadata Badges & Info */}
                                <div className="flex flex-wrap items-center gap-2 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                    <StatusBadge status={status} size="sm" />

                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${priorityBadgeColor}`}>
                                        <span className="capitalize">{priority}</span>
                                    </span>

                                    {isAdmin && ticket.user && (
                                        <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-medium text-[11px] border border-black/5 dark:border-white/5">
                                            <User className="w-3 h-3 text-[#0071e3]" />
                                            <span>{ticket.user.name}</span>
                                        </div>
                                    )}

                                    {ticket.project && (
                                        <Link
                                            href={`/client/projects/${ticket.project.id}`}
                                            className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/30 text-[#0071e3] dark:text-[#3898ec] font-medium text-[11px] border border-blue-200/60 dark:border-blue-900/40 hover:underline"
                                        >
                                            <FolderKanban className="w-3 h-3" />
                                            <span className="truncate max-w-[150px]">{ticket.project.name}</span>
                                        </Link>
                                    )}

                                    <span className="inline-flex items-center gap-1 text-[11px]">
                                        <Clock className="w-3 h-3 opacity-60" />
                                        <span>{new Date(ticket.created_at).toLocaleDateString()}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-4 sm:px-8 mt-6">
                    
                    {/* Official Quotation / Price Card (if quoted) */}
                    {hasQuotation && (
                        <div className="mb-6 rounded-2xl bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-800/60 p-5 sm:p-6 shadow-sm overflow-hidden relative">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/5 dark:border-white/10 pb-4">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                                        <DollarSign className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="text-sm sm:text-base font-bold text-[#1d1d1f] dark:text-white" dir="auto">
                                                العرض المالي المعتمد للتذكرة
                                            </h3>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                                {ticket.pricing_status === 'quoted' ? 'تم التسعير' : (ticket.pricing_status || 'معتمد')}
                                            </span>
                                        </div>
                                        <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400 mt-0.5" dir="auto">
                                            تم تحديد وتأكيد تسعير هذا الطلب من قبل الإدارة الفنية
                                        </p>
                                    </div>
                                </div>
                                <div className="text-start sm:text-end">
                                    <div className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500 mb-0.5">التكلفة الإجمالية:</div>
                                    <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                                        {Number(ticket.price).toLocaleString()} {ticket.currency?.symbol || ticket.currency_symbol || '$'}
                                    </div>
                                </div>
                            </div>

                            {ticket.pricing_notes && (
                                <div className="mt-4 p-3.5 rounded-xl bg-[#f5f5f7] dark:bg-zinc-800/60 border border-black/5 dark:border-white/10 text-xs text-[#1d1d1f]/80 dark:text-zinc-300">
                                    <span className="font-bold text-[#1d1d1f] dark:text-white block mb-1" dir="auto">
                                        تفاصيل وملاحظات التسعير:
                                    </span>
                                    <p className="whitespace-pre-wrap leading-relaxed" dir="auto">{ticket.pricing_notes}</p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Chat Conversation Window Container */}
                    <div className="bg-white dark:bg-zinc-900 rounded-3xl border border-black/5 dark:border-white/10 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-210px)] min-h-[580px]">
                        
                        {/* Conversation Sub-header */}
                        <div className="flex items-center justify-between border-b border-black/5 dark:border-white/10 bg-[#fbfbfd] dark:bg-zinc-800/60 px-5 sm:px-6 py-3 shrink-0 select-none">
                            <div className="flex items-center gap-2.5">
                                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0" />
                                <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300 font-sans">
                                    {__('general.support_conversation', {}, 'محادثة الدعم الفني')}
                                </span>
                            </div>
                            <span className="text-[11px] font-mono text-zinc-400 dark:text-zinc-500">
                                #{ticket.id}
                            </span>
                        </div>

                        {/* Chat Window Component (showHeader={false} prevents duplicated/clipped inner headers) */}
                        <div className="flex-1 min-h-0 overflow-hidden">
                            <ChatWindow
                                conversationId={ticket.conversation?.id}
                                participants={[
                                    {
                                        id: ticket.user_id,
                                        name: ticket.user?.name,
                                    },
                                ]}
                                readOnly={isClosed}
                                showHeader={false}
                                className="h-full border-0 rounded-none shadow-none"
                            />
                        </div>
                    </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
