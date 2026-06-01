import React, { useRef, useEffect, useState, useCallback } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { __ } from '@/lib/i18n';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    ArrowLeft, CheckCircle, RotateCcw, Send, Paperclip, X,
    AlertTriangle, Clock, MessageSquare, User, Calendar, Tag,
    Star, ExternalLink, FileText, Image as ImageIcon, Zap, Lock
} from 'lucide-react';

/* ─── Types ─────────────────────────────────────────────────── */
interface Message {
    id: number;
    body: string;
    attachment?: string | null;
    is_system: boolean;
    is_internal: boolean;
    sender: { id: number; name: string; email: string; avatar?: string | null } | null;
    created_at: string;
}

interface Ticket {
    id: number;
    ticket_subject: string;
    ticket_message: string;
    ticket_status: string;
    priority: string;
    status_text: string;
    priority_text: string;
    display_name: string;
    display_email: string;
    is_urgent: boolean;
    needs_attention: boolean;
    assigned_employee_id?: number | null;
    rate?: number | null;
    closed_at?: string | null;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string; email: string } | null;
    conversation?: { id: number; messages: Message[] } | null;
}

interface Props {
    ticket: Ticket;
    supportAgents: { id: number; name: string; email?: string; avatar?: string }[];
    cannedResponses: { id: number; title: string; body: string }[];
}

/* ─── Helpers ───────────────────────────────────────────────── */
function initials(name: string): string {
    return name.split(' ').slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('');
}

function relativeTime(iso: string): string {
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function fullDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function isImageFile(path: string): boolean {
    return /\.(png|jpe?g|gif|webp|svg)$/i.test(path);
}

const renderMarkdown = (text: string) => {
    return { __html: DOMPurify.sanitize(marked.parse(text, { breaks: true }) as string) };
};

/* ─── Maps ──────────────────────────────────────────────────── */
const STATUS_STYLES: Record<string, { badge: string; dot: string; label: string }> = {
    open:          { badge: 'bg-blue-100 text-blue-800 ring-blue-200',       dot: 'bg-blue-500',    label: 'Open' },
    agent_replied: { badge: 'bg-amber-100 text-amber-800 ring-amber-200',    dot: 'bg-amber-500',   label: 'Agent Replied' },
    user_replied:  { badge: 'bg-violet-100 text-violet-800 ring-violet-200', dot: 'bg-violet-500',  label: 'User Replied' },
    closed:        { badge: 'bg-emerald-100 text-emerald-800 ring-emerald-200', dot: 'bg-emerald-500', label: 'Resolved' },
};

const PRIORITY_STYLES: Record<string, { badge: string; icon: string }> = {
    high:   { badge: 'bg-red-100 text-red-700 ring-red-200',         icon: '🔴' },
    medium: { badge: 'bg-orange-100 text-orange-700 ring-orange-200', icon: '🟡' },
    low:    { badge: 'bg-slate-100 text-slate-600 ring-slate-200',   icon: '🟢' },
};

const AVATAR_COLORS = [
    'bg-violet-500', 'bg-blue-500', 'bg-emerald-500',
    'bg-rose-500',   'bg-amber-500', 'bg-cyan-500',
];

/* ─── Avatar ────────────────────────────────────────────────── */
function Avatar({ name, size = 'md' }: { name: string; size?: 'sm' | 'md' | 'lg' }) {
    const color = AVATAR_COLORS[name.charCodeAt(0) % AVATAR_COLORS.length];
    const sz = size === 'sm' ? 'h-7 w-7 text-xs' : size === 'lg' ? 'h-11 w-11 text-base' : 'h-9 w-9 text-sm';
    return (
        <div className={`${sz} ${color} rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0 shadow-sm select-none`}>
            {initials(name)}
        </div>
    );
}

/* ─── Attachment Link ───────────────────────────────────────── */
function AttachmentLink({ path }: { path: string }) {
    const url = `/storage/${path}`;
    const filename = path.split('/').pop() ?? 'Attachment';
    const isImg = isImageFile(path);
    return (
        <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-2 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-sm"
        >
            {isImg
                ? <ImageIcon className="h-3.5 w-3.5 text-blue-500 flex-shrink-0" />
                : <FileText className="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />}
            <span className="max-w-[180px] truncate">{filename}</span>
            <ExternalLink className="h-3 w-3 text-slate-400 flex-shrink-0" />
        </a>
    );
}

/* ─── Main ──────────────────────────────────────────────────── */
export default function Show({ ticket, supportAgents, cannedResponses }: Props) {
    const { toast } = useToast();
    const [replyBody, setReplyBody] = useState('');
    const [attachments, setAttachments] = useState<File[]>([]);
    const [isInternal, setIsInternal] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    
    // Modals & Assign state
    const [closeModalOpen, setCloseModalOpen] = useState(false);
    const [closeComment, setCloseComment] = useState('');
    const [assigningId, setAssigningId] = useState<string | number>('');

    const fileInputRef = useRef<HTMLInputElement>(null);
    const bottomRef = useRef<HTMLDivElement>(null);

    const rawMessages = ticket.conversation?.messages;
    const messages = Array.isArray(rawMessages) ? rawMessages : (rawMessages?.data ?? []);
    const statusMeta   = STATUS_STYLES[ticket.ticket_status]  ?? STATUS_STYLES.open;
    const priorityMeta = PRIORITY_STYLES[ticket.priority]     ?? PRIORITY_STYLES.low;
    const isClosed = ticket.ticket_status === 'closed';

    // HTTP Polling every 60 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['ticket'], preserveScroll: true, preserveState: true });
        }, 60000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages.length]);

    const handleReply = useCallback((e?: React.FormEvent) => {
        e?.preventDefault();
        if ((!replyBody.trim() && attachments.length === 0) || submitting) return;
        
        const data = new FormData();
        if (replyBody.trim()) data.append('body', replyBody);
        data.append('is_internal', isInternal ? '1' : '0');
        
        attachments.forEach((file, index) => {
            data.append(`attachments[${index}]`, file);
        });

        setSubmitting(true);
        router.post(`/admin/tickets/${ticket.id}/reply`, data as any, {
            forceFormData: true,
            onSuccess: () => {
                setReplyBody('');
                setAttachments([]);
                setIsInternal(false);
                toast({ title: 'Reply sent successfully.' });
            },
            onError: () => toast({ title: 'Failed to send reply', variant: 'destructive' }),
            onFinish: () => setSubmitting(false),
        });
    }, [replyBody, attachments, isInternal, submitting, ticket.id, toast]);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            handleReply();
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setAttachments(prev => [...prev, ...Array.from(e.target.files!)]);
        }
        if (fileInputRef.current) fileInputRef.current.value = ''; // Reset
    };

    const removeFile = (index: number) => {
        setAttachments(prev => prev.filter((_, i) => i !== index));
    };

    const handleCloseConfirm = () => {
        router.put(`/admin/tickets/${ticket.id}`, { action: 'close', comment: closeComment }, {
            onSuccess: () => {
                toast({ title: 'Ticket closed.' });
                setCloseModalOpen(false);
                setCloseComment('');
            },
            onError:   () => toast({ title: 'Action failed.', variant: 'destructive' }),
        });
    };

    const handleReopen = () => {
        router.put(`/admin/tickets/${ticket.id}`, { action: 'reopen' }, {
            onSuccess: () => toast({ title: 'Ticket reopened.' }),
            onError:   () => toast({ title: 'Action failed.', variant: 'destructive' }),
        });
    };

    const handleAssign = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const empId = e.target.value;
        if (!empId) return;
        setAssigningId(empId);
        router.post(`/admin/tickets/${ticket.id}/assign`, { assigned_employee_id: empId }, {
            onSuccess: () => toast({ title: 'Ticket assigned.' }),
            onError: () => toast({ title: 'Failed to assign.', variant: 'destructive' }),
            onFinish: () => setAssigningId('')
        });
    };

    const insertCannedResponse = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const text = e.target.value;
        if (text) {
            setReplyBody(prev => prev + (prev ? '\n\n' : '') + text);
            e.target.value = ''; // reset select
        }
    };

    return (
        <AdminSidebarLayout title={`Ticket #${ticket.id} — ${ticket.ticket_subject}`} header="Support Desk">

            {/* ── Top bar ── */}
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link
                    href="/admin/tickets"
                    className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />{__('general.all_tickets')}</Link>
                <div className="flex items-center gap-2">
                    <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ${statusMeta.badge}`}>
                        <span className={`h-1.5 w-1.5 rounded-full ${statusMeta.dot}`} />
                        {statusMeta.label}
                    </span>
                    {!isClosed ? (
                        <Button size="sm" variant="outline" onClick={() => setCloseModalOpen(true)}
                            className="border-emerald-200 text-emerald-700 hover:bg-emerald-50">
                            <CheckCircle className="mr-1.5 h-4 w-4" />{__('general.close_ticket')}</Button>
                    ) : (
                        <Button size="sm" variant="outline" onClick={handleReopen}
                            className="border-amber-200 text-amber-700 hover:bg-amber-50">
                            <RotateCcw className="mr-1.5 h-4 w-4" /> Reopen
                        </Button>
                    )}
                </div>
            </div>

            {/* ── Attention banners ── */}
            {ticket.is_urgent && !isClosed && (
                <div className="mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <Zap className="h-4 w-4 flex-shrink-0 text-red-500" />
                    <span><strong>{__('general.urgent_ticket')}</strong> — High priority and still open. Please respond ASAP.</span>
                </div>
            )}
            {ticket.needs_attention && !ticket.is_urgent && !isClosed && (
                <div className="mb-4 flex items-center gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <AlertTriangle className="h-4 w-4 flex-shrink-0 text-amber-500" />
                    <span>{__('general.this_ticket_needs_attention_the_client_is_waiting_for_a_reply')}</span>
                </div>
            )}

            {/* ── Main layout ── */}
            <div className="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6 items-start">

                {/* ════ Left: conversation panel ════ */}
                <div className="flex flex-col gap-4 min-w-0">

                    {/* Original ticket card */}
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
                            <div className="flex items-start gap-3 min-w-0">
                                <Avatar name={ticket.display_name} size="lg" />
                                <div className="min-w-0">
                                    <h2 className="text-base font-semibold text-slate-900 leading-snug">{ticket.ticket_subject}</h2>
                                    <p className="mt-0.5 text-xs text-slate-500">
                                        <span className="font-medium text-slate-700">{ticket.display_name}</span>
                                        {' · '}
                                        <span>{ticket.display_email}</span>
                                        {' · '}
                                        <span title={fullDate(ticket.created_at)}>{relativeTime(ticket.created_at)}</span>
                                    </p>
                                </div>
                            </div>
                            <span className={`flex-shrink-0 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${priorityMeta.badge}`}>
                                {priorityMeta.icon} {ticket.priority_text}
                            </span>
                        </div>
                        <div 
                            className="px-6 py-5 text-sm text-slate-700 leading-relaxed overflow-hidden"
                            dangerouslySetInnerHTML={renderMarkdown(ticket.ticket_message)}
                        />
                    </div>

                    {/* Thread count separator */}
                    {messages.length > 0 && (
                        <div className="flex items-center gap-3 px-1">
                            <div className="h-px flex-1 bg-slate-100" />
                            <span className="text-xs text-slate-400 font-medium flex items-center gap-1.5">
                                <MessageSquare className="h-3.5 w-3.5" />
                                {messages.length} {messages.length === 1 ? 'reply' : 'replies'}
                            </span>
                            <div className="h-px flex-1 bg-slate-100" />
                        </div>
                    )}

                    {/* Empty state */}
                    {messages.length === 0 && !isClosed && (
                        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                            <MessageSquare className="mx-auto h-8 w-8 text-slate-300 mb-2" />
                            <p className="text-sm text-slate-400">{__('general.no_replies_yet_be_the_first_to_respond')}</p>
                        </div>
                    )}

                    {/* Message bubbles */}
                    <div className="flex flex-col gap-4">
                        {messages.map((msg) => {
                            if (msg.is_system) {
                                return (
                                    <div key={msg.id} className="flex items-center gap-3 px-2">
                                        <div className="h-px flex-1 bg-slate-100" />
                                        <span className="text-xs text-slate-400 italic whitespace-nowrap">{msg.body}</span>
                                        <div className="h-px flex-1 bg-slate-100" />
                                    </div>
                                );
                            }

                            const isAdminMsg = msg.sender && msg.sender.id !== ticket.user?.id;
                            
                            // Style differences for Internal Notes
                            const isInternal = msg.is_internal;
                            const bubbleBg = isInternal 
                                ? 'bg-amber-50 border border-amber-200 text-amber-900 rounded-tr-none' 
                                : isAdminMsg
                                    ? 'bg-indigo-600 text-white rounded-tr-none'
                                    : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none';

                            return (
                                <div key={msg.id} className={`flex gap-3 ${isAdminMsg ? 'flex-row-reverse' : 'flex-row'}`}>
                                    <Avatar name={msg.sender?.name ?? 'User'} size="sm" />
                                    <div className={`flex flex-col gap-1 max-w-[82%] ${isAdminMsg ? 'items-end' : 'items-start'}`}>
                                        <div className="flex items-center gap-2 px-1">
                                            <span className="text-xs font-semibold text-slate-700">
                                                {msg.sender?.name ?? 'Unknown'}
                                            </span>
                                            {isAdminMsg && (
                                                <span className="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-600">{__('general.support_agent')}</span>
                                            )}
                                            {isInternal && (
                                                <span className="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                                    <Lock className="h-3 w-3" />{__('general.private_note')}</span>
                                            )}
                                            <span className="text-[10px] text-slate-400" title={fullDate(msg.created_at)}>
                                                {relativeTime(msg.created_at)}
                                            </span>
                                        </div>
                                        <div 
                                            className={`rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm overflow-hidden prose prose-sm max-w-none ${bubbleBg}`}
                                            dangerouslySetInnerHTML={renderMarkdown(msg.body)}
                                        />
                                        {msg.attachment && <AttachmentLink path={msg.attachment} />}
                                    </div>
                                </div>
                            );
                        })}
                        <div ref={bottomRef} />
                    </div>

                    {/* Reply composer */}
                    {!isClosed ? (
                        <div className={`rounded-2xl border transition-colors shadow-sm overflow-hidden ${isInternal ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white'}`}>
                            <div className={`border-b px-5 py-3 flex items-center justify-between ${isInternal ? 'border-amber-200' : 'border-slate-100'}`}>
                                <span className="text-xs font-semibold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                                    {isInternal ? <Lock className="h-3.5 w-3.5 text-amber-500" /> : <MessageSquare className="h-3.5 w-3.5" />}
                                    {isInternal ? 'Internal Note (Hidden from client)' : 'Reply to Client'}
                                </span>
                                <div className="flex items-center gap-2">
                                    <label className="text-xs text-slate-500 flex items-center gap-1.5 cursor-pointer select-none">
                                        <input 
                                            type="checkbox" 
                                            checked={isInternal}
                                            onChange={(e) => setIsInternal(e.target.checked)}
                                            className="rounded text-amber-500 border-slate-300 focus:ring-amber-500 h-3.5 w-3.5"
                                        />{__('general.internal_note')}</label>
                                </div>
                            </div>
                            <form onSubmit={handleReply} className="p-4 space-y-3">
                                
                                {/* Quick Replies Dropdown */}
                                {cannedResponses.length > 0 && !isInternal && (
                                    <div className="flex justify-end mb-1">
                                        <select 
                                            className="text-xs border-slate-200 rounded-lg py-1 pl-2 pr-6 text-slate-600 bg-slate-50 focus:ring-indigo-200"
                                            onChange={insertCannedResponse}
                                            defaultValue=""
                                        >
                                            <option value="" disabled>{__('general.insert_quick_reply')}</option>
                                            {cannedResponses.map(cr => (
                                                <option key={cr.id} value={cr.body}>{cr.title}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                <textarea
                                    className={`w-full rounded-xl border px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:ring-2 resize-none transition-all outline-none ${
                                        isInternal 
                                            ? 'bg-amber-100/50 border-amber-200 focus:border-amber-400 focus:ring-amber-100 focus:bg-white' 
                                            : 'bg-slate-50 border-slate-200 focus:border-indigo-400 focus:ring-indigo-100 focus:bg-white'
                                    }`}
                                    rows={5}
                                    placeholder={isInternal ? "Type an internal note for the team..." : "Type your reply... (Markdown supported. Ctrl+Enter to send)"}
                                    value={replyBody}
                                    onChange={(e) => setReplyBody(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                />
                                
                                {/* Attachments List */}
                                {attachments.length > 0 && (
                                    <div className="flex flex-wrap gap-2 pt-1">
                                        {attachments.map((file, idx) => (
                                            <span key={idx} className="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs text-slate-600 border border-slate-200">
                                                <Paperclip className="h-3 w-3 text-slate-400" />
                                                <span className="truncate max-w-[120px]">{file.name}</span>
                                                <button type="button" onClick={() => removeFile(idx)} className="text-slate-400 hover:text-red-500">
                                                    <X className="h-3 w-3" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                )}

                                <div className="flex items-center justify-between gap-3">
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => fileInputRef.current?.click()}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                                        >
                                            <Paperclip className="h-3.5 w-3.5" />{__('general.attach_files')}</button>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            multiple
                                            className="hidden"
                                            onChange={handleFileChange}
                                        />
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="text-xs text-slate-400 tabular-nums">{replyBody.length} chars</span>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={submitting || (!replyBody.trim() && attachments.length === 0)}
                                            className={`text-white min-w-[110px] ${
                                                isInternal 
                                                    ? 'bg-amber-600 hover:bg-amber-700' 
                                                    : 'bg-indigo-600 hover:bg-indigo-700'
                                            }`}
                                        >
                                            {submitting ? (
                                                <span className="flex items-center gap-1.5">
                                                    <span className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                                    Sending…
                                                </span>
                                            ) : (
                                                <span className="flex items-center gap-1.5">
                                                    <Send className="h-3.5 w-3.5" />
                                                    {isInternal ? 'Save Note' : 'Send Reply'}
                                                </span>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 flex items-center gap-3">
                            <CheckCircle className="h-5 w-5 text-emerald-600 flex-shrink-0" />
                            <div>
                                <p className="text-sm font-semibold text-emerald-800">{__('general.this_ticket_has_been_resolved')}</p>
                                {ticket.closed_at && (
                                    <p className="text-xs text-emerald-600 mt-0.5">Closed on {fullDate(ticket.closed_at)}</p>
                                )}
                                <button
                                    onClick={handleReopen}
                                    className="mt-1 text-xs text-emerald-700 underline underline-offset-2 hover:text-emerald-900"
                                >{__('general.reopen_ticket')}</button>
                            </div>
                        </div>
                    )}
                </div>

                {/* ════ Right: sidebar ════ */}
                <div className="flex flex-col gap-4 lg:sticky lg:top-6">

                    {/* Ticket Details */}
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="border-b border-slate-100 px-5 py-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-400">{__('general.ticket_details')}</h3>
                        </div>
                        <dl className="divide-y divide-slate-100">
                            <div className="flex items-center justify-between px-5 py-3">
                                <dt className="flex items-center gap-2 text-xs text-slate-500"><Tag className="h-3.5 w-3.5" /> Status</dt>
                                <dd>
                                    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${statusMeta.badge}`}>
                                        <span className={`h-1.5 w-1.5 rounded-full ${statusMeta.dot}`} />
                                        {statusMeta.label}
                                    </span>
                                </dd>
                            </div>
                            <div className="flex items-center justify-between px-5 py-3">
                                <dt className="flex items-center gap-2 text-xs text-slate-500"><AlertTriangle className="h-3.5 w-3.5" /> Priority</dt>
                                <dd>
                                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${priorityMeta.badge}`}>
                                        {priorityMeta.icon} {ticket.priority_text}
                                    </span>
                                </dd>
                            </div>
                            <div className="flex items-center justify-between px-5 py-3">
                                <dt className="flex items-center gap-2 text-xs text-slate-500"><MessageSquare className="h-3.5 w-3.5" /> Replies</dt>
                                <dd className="text-sm font-semibold text-slate-700">{messages.length}</dd>
                            </div>
                            <div className="flex items-center justify-between px-5 py-3">
                                <dt className="flex items-center gap-2 text-xs text-slate-500"><Calendar className="h-3.5 w-3.5" /> Opened</dt>
                                <dd className="text-xs text-slate-700" title={fullDate(ticket.created_at)}>{relativeTime(ticket.created_at)}</dd>
                            </div>
                            {ticket.closed_at && (
                                <div className="flex items-center justify-between px-5 py-3">
                                    <dt className="flex items-center gap-2 text-xs text-slate-500"><CheckCircle className="h-3.5 w-3.5" /> Closed</dt>
                                    <dd className="text-xs text-slate-700" title={fullDate(ticket.closed_at)}>{relativeTime(ticket.closed_at)}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* Client info */}
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="border-b border-slate-100 px-5 py-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-400">Client</h3>
                        </div>
                        <div className="px-5 py-4 flex items-start gap-3">
                            <Avatar name={ticket.display_name} size="lg" />
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-slate-900 truncate">{ticket.display_name}</p>
                                <p className="text-xs text-slate-500 truncate">{ticket.display_email}</p>
                                {ticket.user && (
                                    <Link
                                        href={`/admin/users/${ticket.user.id}`}
                                        className="mt-2 inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                                    >
                                        <User className="h-3.5 w-3.5" />{__('general.view_profile')}<ExternalLink className="h-3 w-3" />
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Assignment */}
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="border-b border-slate-100 px-5 py-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-400">Assignment</h3>
                        </div>
                        <div className="px-5 py-4">
                            <select 
                                className="w-full text-sm rounded-lg border-slate-200 focus:ring-indigo-200 disabled:opacity-50"
                                value={ticket.assigned_employee_id ?? ''}
                                onChange={handleAssign}
                                disabled={!!assigningId}
                            >
                                <option value="">-- Unassigned --</option>
                                {supportAgents.map(emp => (
                                    <option key={emp.id} value={emp.id}>{emp.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Quick actions */}
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="border-b border-slate-100 px-5 py-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-400">{__('general.quick_actions')}</h3>
                        </div>
                        <div className="p-4 space-y-2">
                            {!isClosed ? (
                                <button
                                    onClick={() => setCloseModalOpen(true)}
                                    className="w-full flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors border border-emerald-200"
                                >
                                    <CheckCircle className="h-4 w-4" />{__('general.mark_as_resolved')}</button>
                            ) : (
                                <button
                                    onClick={handleReopen}
                                    className="w-full flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors border border-amber-200"
                                >
                                    <RotateCcw className="h-4 w-4" />{__('general.reopen_ticket_1')}</button>
                            )}
                            <Link
                                href="/admin/tickets"
                                className="w-full flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-50 hover:bg-slate-100 transition-colors border border-slate-200"
                            >
                                <ArrowLeft className="h-4 w-4" />{__('general.back_to_all_tickets')}</Link>
                        </div>
                    </div>

                    <p className="text-center text-xs text-slate-400">Ticket #{ticket.id}</p>
                </div>
            </div>

            {/* Close Ticket Modal */}
            <Dialog open={closeModalOpen} onOpenChange={setCloseModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.close_ticket')}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <p className="text-sm text-slate-600 mb-4">{__('general.you_are_about_to_mark_this_ticket_as_resolved_you_can_optionally_send_a_final_comment_to_the_user_before_closing_it')}</p>
                        <textarea
                            className="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-200 focus:border-emerald-400 placeholder-slate-400"
                            rows={4}
                            placeholder={__('general.optional_final_comment')}
                            value={closeComment}
                            onChange={(e) => setCloseComment(e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCloseModalOpen(false)}>Cancel</Button>
                        <Button 
                            onClick={handleCloseConfirm}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white"
                        >{__('general.close_ticket')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </AdminSidebarLayout>
    );
}
