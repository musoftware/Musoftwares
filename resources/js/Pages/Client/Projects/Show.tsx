import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    AlertTriangle, ArrowLeft, Bot, BrainCircuit, Bug, CalendarDays, Check, CheckCircle2, CreditCard, Download,
    FileText, FolderOpen, HelpCircle, LifeBuoy, Lightbulb, ListTodo, MessageCircle, Paperclip, Send, Sparkles, X,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AvatarStack, AvatarStackMember } from '@/Components/ui/AvatarStack';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

interface ProjectDetail {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    total_paid: string;
    ai_enabled: boolean;
    ai_understanding_pct: number;
    currency: { id: number; currency: string; symbol: string; string_format?: string } | null;
}

interface ChatDiscussion {
    id: number | string;
    body: string;
    author_id: number | null;
    guest_name?: string | null;
    author?: { id: number; name: string } | null;
    created_at: string;
    file?: { path: string; original_name: string; mime: string; size: number; url: string } | null;
}

interface AiContextData {
    current_goal?: string | null;
    current_stage?: string;
    completed_features?: string[];
    pending_features?: string[];
    current_invoice_status?: string;
    current_invoice_id?: number | null;
    current_contract_url?: string | null;
    current_contract_id?: number | null;
    current_contract_uuid?: string | null;
    tech_stack?: string[];
    developer_notes?: string | null;
    known_decisions?: string[];
}

interface AiQuestion { id: string; question: string; answered: boolean }

interface Props {
    project: ProjectDetail;
    team?: AvatarStackMember[];
    discussions?: ChatDiscussion[];
    aiContext?: AiContextData;
    aiQuestions?: AiQuestion[];
    aiStage?: string;
}

/** The AI engine's stages, shown to the client as five plain steps. */
const STEPS = ['discovery', 'valuation', 'proposal', 'execution', 'completed'] as const;
const stepOf = (stage?: string): (typeof STEPS)[number] => {
    const s = String(stage || 'greeting').toLowerCase();
    return (STEPS as readonly string[]).includes(s) ? (s as (typeof STEPS)[number]) : 'discovery';
};

/**
 * A project for the client: where it stands and what is next, what we need from them, quick links
 * to tasks, files and today's board, then the conversation with the project manager (AI + team).
 */
export default function ProjectShow({ project, team = [], discussions = [], aiContext = {}, aiQuestions = [], aiStage = 'greeting' }: Props) {
    const page = usePage();
    const authUser = (page.props.auth as any)?.user;
    const currentUserId = authUser?.id;
    const isAdmin = authUser?.role === 'admin' || !!authUser?.is_admin;

    const [chatFeed, setChatFeed] = useState<ChatDiscussion[]>(discussions);
    const [messageText, setMessageText] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [questions, setQuestions] = useState<AiQuestion[]>(aiQuestions);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);
    const messageRef = useRef<HTMLTextAreaElement>(null);

    const [debugModalOpen, setDebugModalOpen] = useState(false);
    const [activationLoading, setActivationLoading] = useState(false);
    const [topupModalOpen, setTopupModalOpen] = useState(false);
    const [requiredAmount, setRequiredAmount] = useState(0);

    useEffect(() => {
        if (discussions && discussions.length >= chatFeed.length) setChatFeed(discussions);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [discussions]);
    useEffect(() => setQuestions(aiQuestions), [aiQuestions]);
    useEffect(() => { chatEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, [chatFeed]);

    const step = stepOf(aiContext.current_stage || aiStage);
    const stepIndex = STEPS.indexOf(step);
    const nextFeature = aiContext.pending_features?.[0];
    const currencySymbol = project.currency?.symbol || project.currency?.currency || '$';
    const invoicePending = ['pending', 'draft'].includes(String(aiContext.current_invoice_status || ''));

    const handleActivateAi = async () => {
        setActivationLoading(true);
        try {
            const response = await axios.post(route('client.projects.ai.activate', { project: project.id }));
            if (response.data.ok) {
                toast.success(response.data.message || __('client.projects_ai_activated'));
                router.reload();
            }
        } catch (error: any) {
            if (error.response?.data?.insufficient) {
                setRequiredAmount(error.response.data.required);
                setTopupModalOpen(true);
            } else {
                toast.error(error.response?.data?.message || __('client.projects_ai_activate_failed'));
            }
        } finally {
            setActivationLoading(false);
        }
    };

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!messageText.trim() && !selectedFile) return;

        const outgoingText = messageText.trim();
        const outgoingFile = selectedFile;
        const tempMsgId = 'temp-' + Date.now();
        setChatFeed((prev) => [...prev, {
            id: tempMsgId,
            author_id: currentUserId,
            body: outgoingText || (outgoingFile ? outgoingFile.name : ''),
            created_at: new Date().toISOString(),
            guest_name: __('client.projects_chat_client'),
        }]);
        setMessageText('');
        setSelectedFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        setSubmitting(true);

        const formData = new FormData();
        if (outgoingText) formData.append('body', outgoingText);
        if (outgoingFile) formData.append('file', outgoingFile);

        try {
            const res = await axios.post(route('client.projects.messages.store', { project: project.id }), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (res.data.ok) {
                if (res.data.discussions) setChatFeed(res.data.discussions);
                else if (res.data.comment) setChatFeed((prev) => [...prev.filter((m) => String(m.id) !== tempMsgId), res.data.comment]);
                router.reload({ only: ['discussions', 'aiContext', 'aiQuestions', 'aiStage', 'project'] });
            }
        } catch {
            setChatFeed((prev) => prev.filter((m) => String(m.id) !== tempMsgId));
            toast.error(__('general.error'));
        } finally {
            setSubmitting(false);
        }
    };

    const postAction = async (name: 'ai.approve-budget' | 'ai.confirm-invoice', okText: string, failText: string) => {
        setSubmitting(true);
        try {
            const res = await axios.post(route(`client.projects.${name}`, { project: project.id }));
            if (res.data.ok) {
                toast.success(res.data.message || okText);
                router.reload();
            }
        } catch (err: any) {
            toast.error(err.response?.data?.message || failText);
        } finally {
            setSubmitting(false);
        }
    };
    const handleApproveBudget = () => postAction('ai.approve-budget', __('client.projects_budget_approved'), __('client.projects_budget_approve_failed'));
    const handleConfirmInvoice = () => postAction('ai.confirm-invoice', __('client.projects_invoice_confirmed'), __('client.projects_invoice_confirm_failed'));

    const answerQuestion = (q: AiQuestion) => {
        setMessageText((prev) => (prev ? prev + '\n' : '') + q.question + '\n');
        messageRef.current?.focus();
        messageRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    const dismissQuestion = async (q: AiQuestion) => {
        try {
            await axios.patch(route('client.projects.ai-questions.dismiss', { project: project.id, questionId: q.id }));
            setQuestions((prev) => prev.filter((x) => x.id !== q.id));
        } catch {
            toast.error(__('general.error'));
        }
    };

    const renderMarkdown = (text: string) => {
        try {
            return { __html: DOMPurify.sanitize(marked.parse(text) as string) };
        } catch {
            return { __html: DOMPurify.sanitize(text) };
        }
    };

    const parseMessageFile = (body: string) => {
        if (!body.startsWith('[File:')) return null;
        try {
            const match = body.match(/^\[File:(.+?)\](?:\n([\s\S]*))?$/);
            if (match) return { fileData: JSON.parse(match[1]), extraText: match[2] || '' };
        } catch {
            // not a file message
        }
        return null;
    };

    const quickLinks = [
        { href: route('client.projects.tasks.index', project.id), icon: ListTodo, label: __('client_work.show.tasks') },
        { href: route('client.projects.files.index', project.id), icon: FolderOpen, label: __('client_work.show.files') },
        { href: route('client.projects.calendar.date', { project: project.id, date: new Date().toISOString().slice(0, 10) }), icon: CalendarDays, label: __('client_work.show.today') },
        { href: route('tickets.create', { project_id: project.id }), icon: LifeBuoy, label: __('client_work.show.ticket') },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={__('client.projects_page_title', { name: project.name })} />

            <div className="mx-auto w-full max-w-[1100px] space-y-5 px-4 py-6 sm:px-6 sm:py-8">
                {/* Title */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <Link href={route('client.projects.index')} className="inline-flex items-center gap-1 text-sm font-semibold text-[#0071e3]">
                            <ArrowLeft className="h-4 w-4 rtl:rotate-180" />{__('client_work.show.back')}
                        </Link>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{project.name}</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <AvatarStack members={team} max={4} size="sm" />
                        {!project.ai_enabled && (
                            <button
                                onClick={handleActivateAi}
                                disabled={activationLoading}
                                className="inline-flex h-11 items-center gap-2 rounded-full bg-[#0071e3] px-5 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-60"
                            >
                                <Sparkles className="h-4 w-4" />
                                {activationLoading ? __('client.projects_activating') : __('client.projects_activate_ai', { amount: 10 })}
                            </button>
                        )}
                    </div>
                </div>

                {/* Where it stands */}
                <section className="rounded-[20px] border border-black/5 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-5">
                    <ol className="flex items-center gap-1.5" aria-label={__('client_work.show.stage')}>
                        {STEPS.map((s, i) => (
                            <li key={s} className="flex flex-1 flex-col gap-1.5">
                                <span className={cn('h-1.5 rounded-full', i <= stepIndex ? 'bg-[#0071e3]' : 'bg-[#f5f5f7] dark:bg-zinc-800')} />
                                <span className={cn('hidden text-xs sm:block', i === stepIndex ? 'font-semibold text-[#0071e3]' : 'text-[#1d1d1f]/50 dark:text-zinc-500')}>
                                    {__(`client_work.stages.${s}`)}
                                </span>
                            </li>
                        ))}
                    </ol>
                    <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_work.show.now')}</p>
                            <p className="mt-0.5 font-semibold">{__(`client_work.stages.${step}`)}</p>
                            <p className="mt-0.5 text-sm text-[#1d1d1f]/70 dark:text-zinc-300">{__(`client_work.stage_hint.${step}`)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_work.show.next')}</p>
                            <p className="mt-0.5 text-sm font-medium" dir="auto">{nextFeature || aiContext.current_goal || __('client_work.show.next_unknown')}</p>
                        </div>
                    </div>

                    {/* What we need from you */}
                    {(questions.length > 0 || invoicePending || aiContext.current_contract_url) && (
                        <div className="mt-4 border-t border-black/5 pt-4 dark:border-white/10">
                            <p className="mb-2 text-sm font-bold">{__('client_work.show.need_from_you')}</p>
                            <ul className="space-y-2">
                                {aiContext.current_contract_url && (
                                    <li className="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#f5f5f7] p-3 text-sm dark:bg-zinc-800">
                                        <span className="flex items-center gap-2"><FileText className="h-4 w-4 text-[#0071e3]" />{__('client.projects_contract_card_title')}</span>
                                        <a href={aiContext.current_contract_url} target="_blank" rel="noreferrer" className="font-semibold text-[#0071e3]">{__('client.projects_contract_review_sign')}</a>
                                    </li>
                                )}
                                {invoicePending && (
                                    <li className="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#f5f5f7] p-3 text-sm dark:bg-zinc-800">
                                        <span className="flex items-center gap-2"><CreditCard className="h-4 w-4 text-[#0071e3]" />{__('client_work.show.invoice_waiting')}</span>
                                        <Link href={route('billing.invoices.index')} className="font-semibold text-[#0071e3]">{__('client_work.show.open_invoices')}</Link>
                                    </li>
                                )}
                                {questions.map((q) => (
                                    <li key={q.id} className="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#f5f5f7] p-3 text-sm dark:bg-zinc-800">
                                        <span className="flex min-w-0 items-start gap-2" dir="auto"><HelpCircle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />{q.question}</span>
                                        <span className="flex items-center gap-3">
                                            <button type="button" onClick={() => answerQuestion(q)} className="font-semibold text-[#0071e3]">{__('client_work.show.answer')}</button>
                                            <button type="button" onClick={() => dismissQuestion(q)} className="text-[#1d1d1f]/50 dark:text-zinc-400">{__('client_work.show.dismiss')}</button>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Done and remaining features */}
                    {((aiContext.completed_features?.length ?? 0) > 0 || (aiContext.pending_features?.length ?? 0) > 0) && (
                        <details className="mt-4 border-t border-black/5 pt-3 dark:border-white/10">
                            <summary className="cursor-pointer text-sm font-semibold text-[#0071e3]">
                                {__('client_work.show.features', { done: aiContext.completed_features?.length ?? 0, left: aiContext.pending_features?.length ?? 0 })}
                            </summary>
                            <ul className="mt-3 space-y-1.5 text-sm" dir="auto">
                                {(aiContext.completed_features ?? []).map((f, i) => (
                                    <li key={`d${i}`} className="flex items-start gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />{f}</li>
                                ))}
                                {(aiContext.pending_features ?? []).map((f, i) => (
                                    <li key={`p${i}`} className="flex items-start gap-2 text-[#1d1d1f]/70 dark:text-zinc-300"><span className="mt-1.5 h-2 w-2 shrink-0 rounded-full border border-[#0071e3]" />{f}</li>
                                ))}
                            </ul>
                        </details>
                    )}
                </section>

                {/* Quick links */}
                <nav className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {quickLinks.map(({ href, icon: Icon, label }) => (
                        <Link key={label} href={href} className="flex items-center gap-2 rounded-2xl border border-black/5 bg-white px-3 py-3 text-sm font-medium shadow-sm hover:text-[#0071e3] dark:border-white/10 dark:bg-zinc-900">
                            <Icon className="h-4 w-4 text-[#0071e3]" />{label}
                        </Link>
                    ))}
                </nav>

                {/* Conversation */}
                <section className="flex h-[calc(100dvh-200px)] min-h-[480px] flex-col overflow-hidden rounded-[20px] border border-black/5 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900 lg:h-[680px]">
                    <div className="flex items-center justify-between gap-3 border-b border-black/5 px-4 py-3 dark:border-white/10">
                        <div className="flex items-center gap-2">
                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Bot className="h-4 w-4" /></span>
                            <div>
                                <p className="text-sm font-bold">{__('client.projects_ai_manager')}</p>
                                <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_work.show.chat_hint')}</p>
                            </div>
                        </div>
                        {isAdmin && (
                            <button onClick={() => setDebugModalOpen(true)} className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700" title={__('client.projects_debug_title_attr')}>
                                <Bug className="h-3.5 w-3.5" />{__('client.projects_debug_button')}
                            </button>
                        )}
                    </div>

                    <div className="flex-1 space-y-3 overflow-y-auto bg-[#fbfbfd] p-4 dark:bg-zinc-950/40">
                        {chatFeed.length === 0 ? (
                            <div className="flex h-full flex-col items-center justify-center p-6 text-center">
                                <BrainCircuit className="mb-3 h-10 w-10 text-[#0071e3]" />
                                <p className="font-semibold">{__('client.projects_chat_welcome')}</p>
                                <p className="mt-1 max-w-sm text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client.projects_chat_welcome_hint')}</p>
                            </div>
                        ) : (
                            chatFeed.map((msg) => {
                                if (msg.body.startsWith('[System:')) {
                                    return (
                                        <div key={msg.id} className="flex justify-center">
                                            <span className="rounded-full bg-[#0071e3]/10 px-3 py-1 text-xs font-medium text-[#0071e3]">{msg.body.replace('[System:', '').replace(']', '')}</span>
                                        </div>
                                    );
                                }

                                const parsedFileMsg = parseMessageFile(msg.body);
                                const fileData = parsedFileMsg?.fileData || msg.file;
                                const displayText = parsedFileMsg ? parsedFileMsg.extraText : msg.body;
                                const isCurrentUser = msg.author_id === currentUserId;
                                const isAi = !msg.author_id && (msg.guest_name?.includes('AI') || !msg.author);
                                const authorName = isAi ? __('client.projects_ai_manager') : (msg.author?.name || msg.guest_name || __('client.projects_chat_client'));
                                const hasPricingCard = displayText.includes('[Card:Pricing]');
                                const hasConfirmInvoiceCard = displayText.includes('[Card:ConfirmInvoice]');
                                const cleanText = displayText.replace('[Card:Pricing]', '').replace('[Card:ConfirmInvoice]', '').trim();
                                const time = msg.created_at ? new Date(msg.created_at).toLocaleString((page.props as any).locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';

                                return (
                                    <div key={msg.id} className={cn('flex w-full', isCurrentUser ? 'justify-end' : 'justify-start')}>
                                        <div className={cn('max-w-[85%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-2xl', isCurrentUser ? 'rounded-ee-md bg-[#0071e3] text-white' : 'rounded-es-md border border-black/5 bg-white dark:border-white/10 dark:bg-zinc-900')}>
                                            <div className="mb-1 flex items-baseline justify-between gap-4 text-xs">
                                                <span className={cn('font-semibold', isCurrentUser ? 'text-white/90' : 'text-[#1d1d1f] dark:text-zinc-100')}>{authorName}</span>
                                                <span className={isCurrentUser ? 'text-white/70' : 'text-[#1d1d1f]/40 dark:text-zinc-500'}>{time}</span>
                                            </div>

                                            {fileData && (
                                                <div className={cn('my-2 flex items-center justify-between gap-2 rounded-xl p-2.5 text-xs', isCurrentUser ? 'bg-white/15' : 'bg-[#f5f5f7] dark:bg-zinc-800')}>
                                                    <span className="flex min-w-0 items-center gap-2"><FileText className="h-4 w-4 shrink-0" /><span className="truncate font-semibold">{fileData.original_name}</span></span>
                                                    {fileData.url && (
                                                        <a href={fileData.url} download target="_blank" rel="noreferrer" aria-label={__('client.board_download_file', { name: fileData.original_name })} className="shrink-0 rounded-lg p-1 hover:bg-black/10">
                                                            <Download className="h-4 w-4" />
                                                        </a>
                                                    )}
                                                </div>
                                            )}

                                            {cleanText && (
                                                <div
                                                    dir="auto"
                                                    className={cn('prose prose-sm max-w-none break-words leading-relaxed', isCurrentUser ? 'prose-invert text-white prose-p:text-white prose-a:text-white' : 'text-[#1d1d1f] dark:prose-invert prose-a:text-[#0071e3]')}
                                                    dangerouslySetInnerHTML={renderMarkdown(cleanText)}
                                                />
                                            )}

                                            {(displayText.includes('/c/') || displayText.includes('العقد')) && aiContext.current_contract_url && (
                                                <div className="mt-3 rounded-xl border border-[#0071e3]/20 bg-[#0071e3]/5 p-3 text-[#1d1d1f] dark:text-zinc-100">
                                                    <p className="flex items-center gap-2 text-sm font-semibold"><FileText className="h-4 w-4 text-[#0071e3]" />{__('client.projects_contract_card_title')}</p>
                                                    <p className="mt-1 text-xs">{__('client.projects_contract_card_body')}</p>
                                                    <a href={aiContext.current_contract_url} target="_blank" rel="noreferrer" className="mt-2 inline-flex h-9 items-center gap-1.5 rounded-full bg-[#0071e3] px-4 text-xs font-semibold text-white">
                                                        {__('client.projects_contract_review_sign')}
                                                    </a>
                                                </div>
                                            )}

                                            {hasPricingCard && (
                                                <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-900">
                                                    <div className="flex items-center justify-between gap-3">
                                                        <p className="flex items-center gap-2 text-sm font-semibold"><CreditCard className="h-4 w-4" />{__('client.projects_pricing_card_title')}</p>
                                                        <span className="text-sm font-bold" dir="ltr">{project.budget} {currencySymbol}</span>
                                                    </div>
                                                    <p className="mt-1 text-xs">{__('client.projects_pricing_card_body')}</p>
                                                    <div className="mt-2 flex flex-wrap gap-2">
                                                        <Button onClick={handleApproveBudget} disabled={submitting} className="h-9 rounded-full bg-emerald-600 px-4 text-xs font-semibold text-white hover:bg-emerald-700">
                                                            <Check className="me-1 h-4 w-4" />{submitting ? __('client.projects_approving') : __('client.projects_approve_invoice_start')}
                                                        </Button>
                                                        <Button variant="outline" onClick={() => setMessageText(__('client.projects_negotiate_message'))} className="h-9 rounded-full border-emerald-300 px-4 text-xs font-semibold text-emerald-800">
                                                            <MessageCircle className="me-1 h-3.5 w-3.5" />{__('client.projects_negotiate_budget')}
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}

                                            {hasConfirmInvoiceCard && (
                                                <div className="mt-3 rounded-xl border border-[#0071e3]/20 bg-[#0071e3]/5 p-3 text-[#1d1d1f] dark:text-zinc-100">
                                                    <p className="text-sm font-semibold">{__('client_work.show.confirm_invoice_title')}</p>
                                                    <Button onClick={handleConfirmInvoice} disabled={submitting} className="mt-2 h-9 rounded-full bg-[#0071e3] px-4 text-xs font-semibold text-white hover:bg-[#0077ed]">
                                                        <Check className="me-1 h-4 w-4" />{__('client_work.show.confirm_invoice')}
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })
                        )}

                        {submitting && (
                            <div className="flex items-center gap-2 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                <Bot className="h-4 w-4 text-[#0071e3]" />{__('client.projects_ai_typing')}
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-[#0071e3] [animation-delay:-0.3s]" />
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-[#0071e3] [animation-delay:-0.15s]" />
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-[#0071e3]" />
                            </div>
                        )}
                        <div ref={chatEndRef} />
                    </div>

                    <div className="border-t border-black/5 p-3 dark:border-white/10">
                        {selectedFile && (
                            <div className="mb-2 flex items-center justify-between rounded-xl bg-[#0071e3]/5 px-3 py-1.5 text-xs text-[#0071e3]">
                                <span className="truncate font-semibold">{selectedFile.name}</span>
                                <button type="button" onClick={() => setSelectedFile(null)} aria-label={__('client.projects_remove_file')}><X className="h-4 w-4" /></button>
                            </div>
                        )}
                        <form onSubmit={handleSendMessage} className="flex items-end gap-2">
                            <input type="file" ref={fileInputRef} onChange={(e) => e.target.files?.[0] && setSelectedFile(e.target.files[0])} className="hidden" />
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1d1d1f]/60 hover:bg-[#ebebed] dark:bg-zinc-800"
                                title={__('client.projects_attach_file')}
                                aria-label={__('client.projects_attach_file')}
                            >
                                <Paperclip className="h-4 w-4" />
                            </button>
                            <Textarea
                                ref={messageRef}
                                value={messageText}
                                onChange={(e) => setMessageText(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleSendMessage(e);
                                    }
                                }}
                                placeholder={__('client.projects_message_placeholder')}
                                aria-label={__('client.projects_message_placeholder')}
                                rows={1}
                                className="max-h-32 min-h-[44px] flex-1 resize-none rounded-2xl text-sm"
                            />
                            <Button
                                type="submit"
                                disabled={submitting || (!messageText.trim() && !selectedFile)}
                                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0071e3] p-0 text-white hover:bg-[#0077ed]"
                                aria-label={__('client.projects_send_message')}
                            >
                                <Send className="h-4 w-4 rtl:-scale-x-100" />
                            </Button>
                        </form>
                    </div>
                </section>
            </div>

            <Dialog open={topupModalOpen} onOpenChange={setTopupModalOpen}>
                <DialogContent className="max-w-md rounded-2xl p-6">
                    <DialogHeader>
                        <DialogTitle>{__('client.projects_topup_title')}</DialogTitle>
                        <DialogDescription>{__('client.projects_topup_desc', { amount: requiredAmount })}</DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="mt-4 flex gap-2">
                        <Button variant="outline" onClick={() => setTopupModalOpen(false)}>{__('general.cancel')}</Button>
                        <Link href="/financial/add-balance" className="inline-flex items-center justify-center rounded-full bg-[#0071e3] px-4 py-2 text-sm font-semibold text-white">
                            {__('client.projects_topup_now')}
                        </Link>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {isAdmin && (
                <Dialog open={debugModalOpen} onOpenChange={setDebugModalOpen}>
                    <DialogContent className="max-h-[85vh] max-w-4xl overflow-y-auto rounded-2xl border border-slate-800 bg-slate-950 p-6 text-slate-100">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-black text-amber-400"><Bug className="h-5 w-5" />{__('client.projects_debug_heading')}</DialogTitle>
                            <DialogDescription className="text-xs text-slate-400">{__('client.projects_debug_desc')}</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-2">
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                    <span className="block text-[10px] text-slate-400">{__('client.projects_debug_current_stage')}</span>
                                    <span className="text-xs font-black text-emerald-400">{aiStage.toUpperCase()}</span>
                                </div>
                                <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                    <span className="block text-[10px] text-slate-400">{__('client.projects_debug_archetype')}</span>
                                    <span className="text-xs font-black text-cyan-400">{(aiContext as any)?.current_archetype || __('general.n_a')}</span>
                                </div>
                                <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                    <span className="block text-[10px] text-slate-400">{__('client.projects_debug_conflict_status')}</span>
                                    <span className={cn('text-xs font-black', (aiContext as any)?.conflict_detected ? 'text-amber-400' : 'text-slate-400')}>
                                        {(aiContext as any)?.conflict_detected
                                            ? <span className="inline-flex items-center gap-1"><AlertTriangle className="h-3.5 w-3.5" />{__('client.projects_debug_conflict_reconciled')}</span>
                                            : __('client.projects_debug_clean')}
                                    </span>
                                </div>
                                <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                    <span className="block text-[10px] text-slate-400">{__('client.projects_debug_last_feature')}</span>
                                    <span className="block truncate text-xs font-black text-indigo-300">{(aiContext as any)?.last_user_message_clean || __('general.n_a')}</span>
                                </div>
                            </div>
                            {(aiContext as any)?.reconciliation_reason && (
                                <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-300">
                                    <Lightbulb className="me-1 inline h-3.5 w-3.5" /><strong>{__('client.projects_debug_reconcile_reason')}</strong> {(aiContext as any).reconciliation_reason}
                                </div>
                            )}
                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <span className="text-xs font-bold text-slate-300">{__('client.projects_debug_raw_json')}</span>
                                    <button
                                        onClick={() => { navigator.clipboard.writeText(JSON.stringify(aiContext, null, 2)); toast.success(__('client.projects_debug_json_copied')); }}
                                        className="text-[11px] font-bold text-indigo-400 hover:underline"
                                    >
                                        {__('client.projects_debug_copy_json')}
                                    </button>
                                </div>
                                <pre className="max-h-[350px] overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 p-4 font-mono text-[11px] text-emerald-400" dir="ltr">
                                    {JSON.stringify(aiContext, null, 2)}
                                </pre>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDebugModalOpen(false)} className="border-slate-800 bg-slate-900 text-slate-200 hover:bg-slate-800">
                                {__('client.projects_debug_close')}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </AuthenticatedLayout>
    );
}
