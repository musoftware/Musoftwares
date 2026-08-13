import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    ArrowLeft, Sparkles, Send, Paperclip, X, Download, FileText,
    BrainCircuit, CheckCircle2, HelpCircle, Check, CreditCard, MessageCircle, Bug
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { AvatarStack, AvatarStackMember } from '@/Components/ui/AvatarStack';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription
} from '@/Components/ui/dialog';
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
    id: number;
    body: string;
    author_id: number | null;
    guest_name?: string | null;
    author?: { id: number; name: string } | null;
    created_at: string;
    file?: {
        path: string;
        original_name: string;
        mime: string;
        size: number;
        url: string;
    } | null;
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

interface AiQuestion {
    id: string;
    question: string;
    answered: boolean;
}

interface StageChecklistItem {
    id: string;
    label: string;
    completed: boolean;
    active: boolean;
}

interface Props {
    project: ProjectDetail;
    team?: AvatarStackMember[];
    discussions?: ChatDiscussion[];
    aiContext?: AiContextData;
    aiQuestions?: AiQuestion[];
    aiStage?: string;
    aiStageChecklist?: StageChecklistItem[];
}

export default function ProjectShow({
    project,
    team = [],
    discussions = [],
    aiContext = {},
    aiQuestions = [],
    aiStage = 'greeting',
    aiStageChecklist = [],
}: Props) {
    const page = usePage();
    const currentUserId = (page.props.auth as any)?.user?.id;

    const [chatFeed, setChatFeed] = useState<ChatDiscussion[]>(discussions);
    const [messageText, setMessageText] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    // AI Debug & Activation State
    const [debugModalOpen, setDebugModalOpen] = useState(false);
    const [activationLoading, setActivationLoading] = useState(false);
    const [topupModalOpen, setTopupModalOpen] = useState(false);
    const [requiredAmount, setRequiredAmount] = useState(0);

    // Keep feed in sync with prop updates
    useEffect(() => {
        if (discussions && discussions.length >= chatFeed.length) {
            setChatFeed(discussions);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [discussions]);

    // Auto-scroll chat to bottom
    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatFeed]);

    const handleActivateAi = async () => {
        setActivationLoading(true);
        try {
            const response = await axios.post(route('client.projects.ai.activate', { project: project.id }));
            if (response.data.ok) {
                toast.success(response.data.message || 'AI activated successfully!');
                router.reload();
            }
        } catch (error: any) {
            if (error.response?.data?.insufficient) {
                setRequiredAmount(error.response.data.required);
                setTopupModalOpen(true);
            } else {
                toast.error(error.response?.data?.message || 'Failed to activate AI.');
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

        // Optimistic UI: Append user's message immediately to feed
        const tempMsgId = 'temp-' + Date.now();
        const tempUserMessage = {
            id: tempMsgId,
            author_id: currentUserId,
            body: outgoingText || (outgoingFile ? `📎 ${outgoingFile.name}` : ''),
            created_at: new Date().toISOString(),
            guest_name: 'Client',
        };

        setChatFeed((prev) => [...prev, tempUserMessage as any]);
        setMessageText('');
        setSelectedFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';

        setSubmitting(true);
        setTimeout(() => chatEndRef.current?.scrollIntoView({ behavior: 'smooth' }), 50);

        const formData = new FormData();
        if (outgoingText) formData.append('body', outgoingText);
        if (outgoingFile) formData.append('file', outgoingFile);

        try {
            const res = await axios.post(route('client.projects.messages.store', { project: project.id }), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.ok) {
                if (res.data.discussions) {
                    setChatFeed(res.data.discussions);
                } else if (res.data.comment) {
                    setChatFeed((prev) => [...prev.filter((m) => String(m.id) !== String(tempMsgId)), res.data.comment]);
                }

                // Suppressed billing toast popup to reduce client transaction anxiety.
                // Wallet updates silently in the background when the view variables reload.

                // Reload to fetch updated stage, context & discussions in background
                router.reload({ only: ['discussions', 'aiContext', 'aiQuestions', 'aiStage', 'project'] });
            }
        } catch (err) {
            // Revert optimistic message on failure
            setChatFeed((prev) => prev.filter((m) => String(m.id) !== String(tempMsgId)));
            toast.error(__('general.error') || 'Failed to send message.');
        } finally {
            setSubmitting(false);
            setTimeout(() => chatEndRef.current?.scrollIntoView({ behavior: 'smooth' }), 50);
        }
    };

    const handleApproveBudget = async () => {
        setSubmitting(true);
        try {
            const res = await axios.post(route('client.projects.ai.approve-budget', { project: project.id }));
            if (res.data.ok) {
                toast.success(res.data.message || 'تم اعتماد الفاتورة والميزانية بنجاح!');
                router.reload();
            }
        } catch (err) {
            toast.error('فشل في اعتماد الفاتورة.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleConfirmInvoice = async () => {
        setSubmitting(true);
        try {
            const res = await axios.post(route('client.projects.ai.confirm-invoice', { project: project.id }));
            if (res.data.ok) {
                toast.success(res.data.message || 'تم إذن إصدار الفاتورة وتأكيد الاتفاق بنجاح!');
                router.reload();
            }
        } catch (err: any) {
            toast.error(err.response?.data?.message || 'حدث خطأ أثناء إصدار الفاتورة');
        } finally {
            setSubmitting(false);
        }
    };


    const renderMarkdown = (text: string) => {
        try {
            const html = marked.parse(text);
            return { __html: DOMPurify.sanitize(html as string) };
        } catch (e) {
            return { __html: DOMPurify.sanitize(text) };
        }
    };

    const parseMessageFile = (body: string) => {
        if (!body.startsWith('[File:')) return null;
        try {
            const match = body.match(/^\[File:(.+?)\](?:\n([\s\S]*))?$/);
            if (match) {
                return {
                    fileData: JSON.parse(match[1]),
                    extraText: match[2] || '',
                };
            }
        } catch (e) {
            // Fallback
        }
        return null;
    };

    const currencySymbol = project.currency?.symbol || 'EGP';

    return (
        <AuthenticatedLayout>
            <Head title={`AI Agency — ${project.name}`} />

            <div className="mx-auto max-w-[1600px] px-4 py-4 sm:px-6 lg:px-8 space-y-4">
                
                {/* Header Navbar */}
                <header className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('client.projects.index')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200"
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Link>

                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-lg font-black text-slate-900 tracking-tight">{project.name}</h1>
                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                    AI Software Agency
                                </span>
                            </div>
                            <p className="text-xs text-slate-500 font-medium">Chat-first interactive project workspace</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        {!project.ai_enabled && (
                            <button
                                onClick={handleActivateAi}
                                disabled={activationLoading}
                                className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition"
                            >
                                <Sparkles className="h-4 w-4 text-indigo-200" />
                                {activationLoading ? 'Activating...' : 'Activate AI Manager (10 EGP)'}
                            </button>
                        )}
                        <AvatarStack members={team} max={4} size="sm" />
                    </div>
                </header>

                {/* Pure Chat-First Grid Layout: Chat (85-90% width, 10 out of 12 cols), Stage Sidebar (15% width, 2 out of 12 cols) */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    
                    {/* Main Chat Workspace (10 of 12 Columns - 85% width) */}
                    <div className="lg:col-span-10 flex flex-col h-[740px] rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        
                        {/* Chat Bar Header */}
                        <div className="px-5 py-3 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">🤖</div>
                                <div>
                                    <span className="text-xs font-black text-slate-900">AI Project Manager</span>
                                    <span className="text-[10px] text-slate-400 block font-medium">Direct interactive chat — answers, estimates & invoice inline</span>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => setDebugModalOpen(true)}
                                    className="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-700 hover:bg-amber-500/20 border border-amber-300/40 transition shadow-2xs"
                                    title="كشف الـ Context البرمجي الحالي والتشخيص (Debug Panel)"
                                >
                                    <Bug className="h-3.5 w-3.5 text-amber-600" />
                                    <span>AI Debug / كشف Context</span>
                                </button>
                                <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-extrabold text-indigo-700 border border-indigo-100">
                                    Stage: {aiStage.toUpperCase()}
                                </span>
                            </div>
                        </div>

                        {/* Messages Thread Feed */}
                        <div className="flex-1 overflow-y-auto p-5 space-y-4 bg-slate-50/20">
                            {chatFeed.length === 0 ? (
                                <div className="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                                    <BrainCircuit className="h-12 w-12 text-indigo-400 mb-3 animate-bounce" />
                                    <p className="text-sm font-black text-slate-800">أهلاً بك في AI Software Agency</p>
                                    <p className="text-xs max-w-sm mt-1 text-slate-500">اكتب فكرة مشروعك أو أي متطلبات بالأسفل. وسيقوم الـ AI Project Manager بإجابتك ودراسة المتطلبات مباشرة.</p>
                                </div>
                            ) : (
                                chatFeed.map((msg) => {
                                    // 1. System messages render as centered pills
                                    if (msg.body.startsWith('[System:')) {
                                        const cleanText = msg.body.replace('[System:', '').replace(']', '');
                                        return (
                                            <div key={msg.id} className="flex justify-center my-3 w-full">
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-4 py-1.5 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200/60 shadow-xs">
                                                    <Sparkles className="h-3 w-3 text-indigo-500" />
                                                    {cleanText}
                                                </span>
                                            </div>
                                        );
                                    }

                                    // 2. Parse file attachments if any
                                    const parsedFileMsg = parseMessageFile(msg.body);
                                    const fileData = parsedFileMsg?.fileData || msg.file;
                                    const displayText = parsedFileMsg ? parsedFileMsg.extraText : msg.body;

                                    const isCurrentUser = msg.author_id === currentUserId;
                                    const isAi = !msg.author_id && (msg.guest_name?.includes('AI') || !msg.author);
                                    const authorName = isAi ? 'AI Project Manager' : (msg.author?.name || msg.guest_name || 'Client');
                                    const initials = authorName.slice(0, 1).toUpperCase();

                                    const hasPricingCard = displayText.includes('[Card:Pricing]');
                                    const cleanTextWithoutCard = displayText.replace('[Card:Pricing]', '').replace('[Card:ConfirmInvoice]', '').trim();

                                    return (
                                        <div
                                            key={msg.id}
                                            className={`flex flex-col w-full my-1.5 ${isCurrentUser ? 'items-end' : 'items-start'}`}
                                        >
                                            <div
                                                className={`flex gap-3 max-w-2xl rounded-2xl p-4 shadow-xs relative ${
                                                    isCurrentUser
                                                        ? 'bg-indigo-600 text-white rounded-tr-none'
                                                        : 'bg-white border border-slate-200 text-slate-850 rounded-tl-none'
                                                }`}
                                            >
                                                {!isCurrentUser && (
                                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-black text-indigo-700 shadow-xs">
                                                        {isAi ? '🤖' : initials}
                                                    </div>
                                                )}

                                                <div className="min-w-0 flex-1 space-y-2">
                                                    <div className="flex items-baseline justify-between gap-4">
                                                        <span className={`text-[11px] font-black ${isCurrentUser ? 'text-indigo-100' : 'text-slate-900'}`}>
                                                            {authorName}
                                                        </span>
                                                        <span className={`text-[10px] ${isCurrentUser ? 'text-indigo-200' : 'text-slate-400'}`}>
                                                            {msg.created_at ? formatDate(msg.created_at) : ''}
                                                        </span>
                                                    </div>

                                                    {/* Attached File Display */}
                                                    {fileData && (
                                                        <div className={`my-2 flex items-center justify-between rounded-xl p-3 text-xs border ${
                                                            isCurrentUser
                                                                ? 'bg-indigo-700/60 border-indigo-400/40 text-white'
                                                                : 'bg-slate-50 border-slate-200 text-slate-800'
                                                        }`}>
                                                            <div className="flex items-center gap-2 truncate">
                                                                <FileText className="h-4 w-4 shrink-0 text-indigo-300" />
                                                                <span className="font-bold truncate">{fileData.original_name}</span>
                                                            </div>
                                                            {fileData.url && (
                                                                <a
                                                                    href={fileData.url}
                                                                    download
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="inline-flex items-center gap-1 rounded-lg bg-white/20 px-2 py-1 text-[11px] font-bold text-white hover:bg-white/30"
                                                                >
                                                                    <Download className="h-3 w-3" />
                                                                </a>
                                                            )}
                                                        </div>
                                                    )}

                                                    {/* Message Text Body */}
                                                    {cleanTextWithoutCard && (
                                                        <div
                                                            dir="auto"
                                                            className={`prose max-w-none text-xs leading-relaxed break-words ${
                                                                isCurrentUser
                                                                    ? 'text-white prose-invert prose-p:text-white prose-a:text-indigo-200'
                                                                    : 'text-slate-800 prose-p:text-slate-800 prose-a:text-indigo-600'
                                                            }`}
                                                            dangerouslySetInnerHTML={renderMarkdown(cleanTextWithoutCard)}
                                                        />
                                                    )}

                                                     {/* INLINE INTERACTIVE CARD: CONTRACT PROPOSAL */}
                                                     {(displayText.includes('/c/') || displayText.includes('العقد')) && aiContext.current_contract_url && (
                                                         <div className="mt-3 rounded-xl border border-indigo-200 bg-indigo-50/90 p-4 shadow-sm space-y-3">
                                                             <div className="flex items-center justify-between">
                                                                 <div className="flex items-center gap-2 text-indigo-900 font-extrabold text-xs">
                                                                     <FileText className="h-4 w-4 text-indigo-600" />
                                                                     <span>عقد اتفاقية المشروع والسياسات (50% دفعة أولى)</span>
                                                                 </div>
                                                             </div>
                                                             <p className="text-[11px] text-indigo-800 font-medium leading-relaxed">
                                                                 تم تجهيز العقد الرسمي الخاص بالمشروع شامل المتطلبات والأسعار وشروط السداد. اتفضل بالاطلاع والتوقيع واعتمد الدفعة الأولى لبدء التنفيذ.
                                                             </p>
                                                             <div className="flex flex-wrap items-center gap-2 pt-1">
                                                                 <a
                                                                     href={aiContext.current_contract_url}
                                                                     target="_blank"
                                                                     rel="noreferrer"
                                                                     className="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs h-9 rounded-xl px-4 shadow-xs inline-flex items-center gap-1.5 transition"
                                                                 >
                                                                     <FileText className="h-4 w-4" />
                                                                     مراجعة وتوقيع العقد (50%) 📄
                                                                 </a>
                                                             </div>
                                                         </div>
                                                     )}

                                                    {/* INLINE INTERACTIVE CARD: PRICING PROPOSAL */}
                                                    {hasPricingCard && (
                                                        <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm space-y-3">
                                                            <div className="flex items-center justify-between">
                                                                <div className="flex items-center gap-2 text-emerald-800 font-extrabold text-xs">
                                                                    <CreditCard className="h-4 w-4 text-emerald-600" />
                                                                    <span>عرض السعر المبدئي والفاتورة</span>
                                                                </div>
                                                                <span className="text-sm font-black text-emerald-700">
                                                                    {project.budget} {currencySymbol}
                                                                </span>
                                                            </div>
                                                            <p className="text-[11px] text-emerald-800 font-medium leading-relaxed">
                                                                التكلفة التقديرية تشمل تحليل الخصائص وصياغة المهام واستلام المخرجات البرمجية. عند الضغط على الاعتماد، سيتم توليد فاتورة المشروع وبدء التنفيذ الفوري.
                                                            </p>
                                                            <div className="flex flex-wrap items-center gap-2 pt-1">
                                                                <Button
                                                                    onClick={handleApproveBudget}
                                                                    disabled={submitting}
                                                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs h-9 rounded-xl px-4 shadow-xs inline-flex items-center gap-1.5"
                                                                >
                                                                    <Check className="h-4 w-4" />
                                                                    {submitting ? 'جاري الاعتماد...' : 'اعتماد الفاتورة وبدء التنفيذ'}
                                                                </Button>
                                                                <Button
                                                                    variant="outline"
                                                                    onClick={() => setMessageText('عايز اعدل الميزانية واقترح سعر اقل')}
                                                                    className="border-emerald-300 text-emerald-800 hover:bg-emerald-100 font-bold text-xs h-9 rounded-xl px-3 inline-flex items-center gap-1.5"
                                                                >
                                                                    <MessageCircle className="h-3.5 w-3.5" />
                                                                    تعديل / تفاوض الميزانية
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}

                            {/* WhatsApp-style Animated Typing Indicator (3 Dots) */}
                            {submitting && (
                                <div className="flex flex-col w-full my-1.5 items-start animate-fade-in">
                                    <div className="flex gap-3 max-w-md rounded-2xl p-3 shadow-xs bg-white border border-slate-200 text-slate-800 rounded-tl-none items-center">
                                        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700 shadow-xs">
                                            🤖
                                        </div>
                                        <div className="flex items-center gap-1.5 px-2 py-1">
                                            <span className="text-xs text-slate-500 font-bold ml-1">AI Project Manager يكتب الآن</span>
                                            <span className="h-2 w-2 rounded-full bg-indigo-600 animate-bounce [animation-delay:-0.3s]" />
                                            <span className="h-2 w-2 rounded-full bg-indigo-600 animate-bounce [animation-delay:-0.15s]" />
                                            <span className="h-2 w-2 rounded-full bg-indigo-600 animate-bounce" />
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div ref={chatEndRef} />
                        </div>

                        {/* Input Box Footer */}
                        <div className="p-3 border-t border-slate-100 bg-white">
                            {selectedFile && (
                                <div className="mb-2 flex items-center justify-between rounded-xl bg-indigo-50 px-3 py-1.5 text-xs text-indigo-700 border border-indigo-100">
                                    <span className="truncate font-bold">{selectedFile.name}</span>
                                    <button onClick={() => setSelectedFile(null)} className="text-indigo-400 hover:text-indigo-600">
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            )}

                            <form onSubmit={handleSendMessage} className="flex items-end gap-2">
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    onChange={(e) => e.target.files?.[0] && setSelectedFile(e.target.files[0])}
                                    className="hidden"
                                />

                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100 transition"
                                    title="Attach File"
                                >
                                    <Paperclip className="h-4 w-4" />
                                </button>

                                <Textarea
                                    value={messageText}
                                    onChange={(e) => setMessageText(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            handleSendMessage(e);
                                        }
                                    }}
                                    placeholder="اكتب رسالتك هنا... تفاصيل الفكرة، الاستفسارات، أو التعديلات"
                                    rows={1}
                                    className="min-h-[44px] max-h-32 flex-1 resize-none rounded-xl border-slate-200 text-xs py-2.5 focus:border-indigo-500 focus:ring-indigo-500"
                                />

                                <Button
                                    type="submit"
                                    disabled={submitting || (!messageText.trim() && !selectedFile)}
                                    className="h-10 w-10 shrink-0 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition p-0 flex items-center justify-center"
                                >
                                    <Send className="h-4 w-4" />
                                </Button>
                            </form>
                        </div>
                    </div>

                    {/* Right Sidebar: Compact Project Context (2 of 12 Columns - 15% width) */}
                    <div className="lg:col-span-2 space-y-4">
                        
                        {/* Project Stage Widget */}
                        <Card className="rounded-2xl border border-slate-200 shadow-xs">
                            <CardHeader className="p-3.5 pb-2">
                                <CardTitle className="text-xs font-black text-slate-900 tracking-tight flex items-center gap-1.5">
                                    <BrainCircuit className="h-4 w-4 text-indigo-600" />
                                    حالة المشـــــروع
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-3.5 pt-0">
                                <span className="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1 text-xs font-extrabold text-indigo-700 border border-indigo-200">
                                    <span className="h-2 w-2 rounded-full bg-indigo-500 animate-pulse" />
                                    {aiContext.current_stage ? aiContext.current_stage.toUpperCase() : 'GREETING'}
                                </span>
                            </CardContent>
                        </Card>

                        {/* Pending Features Widget */}
                        {aiContext.pending_features && aiContext.pending_features.length > 0 && (
                            <Card className="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-xs">
                                <CardHeader className="p-3.5 pb-2">
                                    <CardTitle className="text-xs font-black text-indigo-900 tracking-tight flex items-center gap-1.5">
                                        <Sparkles className="h-4 w-4 text-indigo-600" />
                                        الميزات المطلوبة
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-3.5 pt-0 space-y-1.5">
                                    {aiContext.pending_features.map((feat, idx) => (
                                        <div key={idx} className="text-[11px] font-bold text-indigo-800 bg-white p-2 rounded-lg border border-indigo-100 flex items-start gap-1.5 shadow-2xs">
                                            <span className="text-indigo-500 font-extrabold">•</span>
                                            <span className="truncate">{feat}</span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {/* Completed Features Widget */}
                        {aiContext.completed_features && aiContext.completed_features.length > 0 && (
                            <Card className="rounded-2xl border border-emerald-200 bg-emerald-50/40 shadow-xs">
                                <CardHeader className="p-3.5 pb-2">
                                    <CardTitle className="text-xs font-black text-emerald-900 tracking-tight flex items-center gap-1.5">
                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                        الميزات المكتملة
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-3.5 pt-0 space-y-1.5">
                                    {aiContext.completed_features.map((feat, idx) => (
                                        <div key={idx} className="text-[11px] font-bold text-emerald-800 bg-white p-2 rounded-lg border border-emerald-100 flex items-start gap-1.5 shadow-2xs">
                                            <span className="text-emerald-500 font-extrabold">✓</span>
                                            <span className="truncate">{feat}</span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Topup Modal */}
            <Dialog open={topupModalOpen} onOpenChange={setTopupModalOpen}>
                <DialogContent className="max-w-md rounded-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-base font-black text-slate-900">محفظة الرصيد غير كافية</DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 mt-1">
                            تحتاج إلى رصيد إضافي بقيمة <strong>{requiredAmount} EGP</strong> لتفعيل الـ AI Manager في هذا المشروع.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="mt-4 flex gap-2">
                        <Button variant="outline" onClick={() => setTopupModalOpen(false)}>إلغاء</Button>
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700"
                        >
                            شحن المحفظة الآن
                        </Link>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* AI Context Debug Modal for Admin */}
            <Dialog open={debugModalOpen} onOpenChange={setDebugModalOpen}>
                <DialogContent className="max-w-4xl max-h-[85vh] overflow-y-auto bg-slate-950 text-slate-100 border border-slate-800 rounded-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-amber-400 text-base font-black">
                            <Bug className="h-5 w-5 text-amber-400" />
                            تشخيص محركات الذكاء الاصطناعي (AI Context Debug Panel)
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-400">
                            عرض المحتوى الحقيقي المخزن في ai_context للتحقق من خلوه من التناقضات ومتابعة التفكير الحقيقي للنظام.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* Quick Status Cards */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                <span className="text-[10px] text-slate-400 block">Current Stage</span>
                                <span className="text-xs font-black text-emerald-400">{aiStage.toUpperCase()}</span>
                            </div>
                            <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                <span className="text-[10px] text-slate-400 block">Archetype</span>
                                <span className="text-xs font-black text-cyan-400">{(aiContext as any)?.current_archetype || 'N/A'}</span>
                            </div>
                            <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                <span className="text-[10px] text-slate-400 block">Conflict Status</span>
                                <span className={`text-xs font-black ${(aiContext as any)?.conflict_detected ? 'text-amber-400 animate-pulse' : 'text-slate-400'}`}>
                                    {(aiContext as any)?.conflict_detected ? '⚠️ Conflict Reconciled' : 'Clean'}
                                </span>
                            </div>
                            <div className="rounded-xl border border-slate-800 bg-slate-900/90 p-3">
                                <span className="text-[10px] text-slate-400 block">Clean Last Feature</span>
                                <span className="text-xs font-black text-indigo-300 truncate block">{(aiContext as any)?.last_user_message_clean || 'N/A'}</span>
                            </div>
                        </div>

                        {/* Conflict Reason Alert if any */}
                        {(aiContext as any)?.reconciliation_reason && (
                            <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-300 font-medium">
                                💡 <strong>سبب إعادة التوفيق والضبط:</strong> {(aiContext as any).reconciliation_reason}
                            </div>
                        )}

                        {/* Raw JSON viewer */}
                        <div>
                            <div className="flex items-center justify-between mb-1.5">
                                <span className="text-xs font-bold text-slate-300">محتوى ai_context الخام (JSON):</span>
                                <button
                                    onClick={() => {
                                        navigator.clipboard.writeText(JSON.stringify(aiContext, null, 2));
                                        toast.success('تم نسخ JSON للـ Context بنجاح!');
                                    }}
                                    className="text-[11px] text-indigo-400 hover:underline font-bold"
                                >
                                    نسخ JSON 📋
                                </button>
                            </div>
                            <pre className="rounded-xl bg-slate-900 p-4 text-[11px] font-mono text-emerald-400 overflow-x-auto max-h-[350px] border border-slate-800 dir-ltr">
                                {JSON.stringify(aiContext, null, 2)}
                            </pre>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDebugModalOpen(false)}
                            className="bg-slate-900 border-slate-800 text-slate-200 hover:bg-slate-800"
                        >
                            إغلاق نافذة Debug
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
