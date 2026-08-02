import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    ArrowLeft, Sparkles, Send, Paperclip, X, Download, FileText,
    BrainCircuit, CheckCircle2, HelpCircle, Activity, AlertTriangle, ShieldQuestion,
    MessageSquare, Check
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
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

interface AiSummaryData {
    project_type?: string | null;
    features?: string[];
    current_goal?: string | null;
    missing_info?: string[];
    complexity?: string | null;
}

interface AiQuestion {
    id: string;
    question: string;
    answered: boolean;
}

interface AiActionLog {
    action: string;
    detail?: string;
    timestamp: string;
}

interface Props {
    project: ProjectDetail;
    team?: AvatarStackMember[];
    discussions?: ChatDiscussion[];
    aiSummary?: AiSummaryData;
    aiQuestions?: AiQuestion[];
    aiActionsLog?: AiActionLog[];
}

export default function ProjectShow({
    project,
    team = [],
    discussions = [],
    aiSummary = {},
    aiQuestions = [],
    aiActionsLog = [],
}: Props) {
    const page = usePage();
    const currentUserId = (page.props.auth as any)?.user?.id;

    const [chatFeed, setChatFeed] = useState<ChatDiscussion[]>(discussions);
    const [messageText, setMessageText] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    // AI Activation State
    const [activationLoading, setActivationLoading] = useState(false);
    const [topupModalOpen, setTopupModalOpen] = useState(false);
    const [requiredAmount, setRequiredAmount] = useState(0);

    // Keep feed in sync with prop updates
    useEffect(() => {
        setChatFeed(discussions);
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

        setSubmitting(true);
        const formData = new FormData();
        if (messageText.trim()) formData.append('body', messageText);
        if (selectedFile) formData.append('file', selectedFile);

        try {
            const res = await axios.post(route('client.projects.messages.store', { project: project.id }), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.ok && res.data.comment) {
                setChatFeed((prev) => [...prev, res.data.comment]);
                setMessageText('');
                setSelectedFile(null);
                if (fileInputRef.current) fileInputRef.current.value = '';

                // Reload to fetch updated AI summary/questions/actions in background
                router.reload({ only: ['aiSummary', 'aiQuestions', 'aiActionsLog', 'project'] });
            }
        } catch (err) {
            toast.error(__('general.error') || 'Failed to send message.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDismissQuestion = async (qId: string) => {
        try {
            await axios.patch(route('client.projects.ai-questions.dismiss', { project: project.id, questionId: qId }));
            router.reload({ only: ['aiQuestions'] });
        } catch (err) {
            // Silent fallback
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
        } catch (e) {}
        return null;
    };

    const understandingPct = project.ai_understanding_pct || 0;

    return (
        <AuthenticatedLayout>
            <Head title={project.name} />
            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                
                {/* Header: Project Title, AI Status Badge, Understanding KPI */}
                <header className="space-y-3 border-b border-slate-200 pb-4">
                    <Link
                        href={route('client.projects.index')}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 transition-colors hover:text-slate-700"
                    >
                        <ArrowLeft className="h-4 w-4" /> {__('general.all_projects')}
                    </Link>

                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-1">
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                    {project.name}
                                </h1>

                                {/* AI Manager Status Badge */}
                                {project.ai_enabled ? (
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        <Sparkles className="h-3.5 w-3.5 text-emerald-500 animate-pulse" />
                                        AI Project Manager Active
                                    </span>
                                ) : (
                                    <button
                                        onClick={handleActivateAi}
                                        disabled={activationLoading}
                                        className="inline-flex items-center gap-1.5 rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                                    >
                                        <Sparkles className="h-3.5 w-3.5 text-amber-300" />
                                        {activationLoading ? 'Activating...' : 'Activate AI Manager (10 EGP)'}
                                    </button>
                                )}
                            </div>

                            <div className="flex items-center gap-3 text-xs text-slate-500">
                                <AvatarStack members={team} max={4} size="sm" />
                                <span className="text-slate-300">|</span>
                                <span>{project.date_start ? formatDate(project.date_start) : '—'}</span>
                            </div>
                        </div>

                        {/* AI Understanding Progress Header Badge */}
                        <div className="flex items-center gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-3 shadow-xs md:self-end">
                            <div className="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white shadow-xs">
                                <span className="text-xs font-black text-indigo-700">{understandingPct}%</span>
                                <svg className="absolute inset-0 h-full w-full -rotate-90">
                                    <circle cx="24" cy="24" r="20" className="stroke-indigo-100 fill-none" strokeWidth="3.5" />
                                    <circle
                                        cx="24"
                                        cy="24"
                                        r="20"
                                        className="stroke-indigo-600 fill-none transition-all duration-700"
                                        strokeWidth="3.5"
                                        strokeDasharray="125.66"
                                        strokeDashoffset={125.66 - (125.66 * understandingPct) / 100}
                                    />
                                </svg>
                            </div>
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-indigo-400">AI Understanding</p>
                                <p className="text-xs font-extrabold text-indigo-900">
                                    {understandingPct > 80 ? 'High Clarity' : understandingPct > 40 ? 'Analyzing Details' : 'Gathering Requirements'}
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                {/* AI Workspace Core Layout: Chat (70%) + AI Context Sidebar (30%) */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    
                    {/* Left Column (70% width): Chat Workspace */}
                    <div className="lg:col-span-2 flex flex-col h-[680px] rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        
                        {/* Workspace Header */}
                        <div className="px-5 py-3.5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <BrainCircuit className="h-4 w-4 text-indigo-600" />
                                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">AI Interactive Workspace</span>
                            </div>
                            <span className="text-[11px] text-slate-400 font-medium">Just chat naturally — AI handles tasks & specs</span>
                        </div>

                        {/* Messages Thread Container */}
                        <div className="flex-1 overflow-y-auto p-5 space-y-3 bg-slate-50/30">
                            {chatFeed.length === 0 ? (
                                <div className="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                                    <BrainCircuit className="h-10 w-10 text-indigo-300 mb-2 animate-bounce" />
                                    <p className="text-sm font-bold text-slate-700">Your AI Workspace is Ready</p>
                                    <p className="text-xs max-w-sm mt-1">Type your project needs, ideas, or requirements below. The AI Project Manager will start organizing everything automatically.</p>
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
                                    const authorName = msg.author?.name || msg.guest_name || 'Client';
                                    const initials = authorName.slice(0, 1).toUpperCase();

                                    return (
                                        <div
                                            key={msg.id}
                                            className={`flex flex-col w-full my-1.5 ${isCurrentUser ? 'items-end' : 'items-start'}`}
                                        >
                                            <div
                                                className={`flex gap-3 max-w-xl rounded-2xl p-4 shadow-xs relative ${
                                                    isCurrentUser
                                                        ? 'bg-indigo-600 text-white rounded-tr-none'
                                                        : 'bg-white border border-slate-200 text-slate-850 rounded-tl-none'
                                                }`}
                                            >
                                                {!isCurrentUser && (
                                                    <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700">
                                                        {msg.guest_name === 'AI' ? '🤖' : initials}
                                                    </div>
                                                )}

                                                <div className="min-w-0 flex-1 space-y-1">
                                                    <div className="flex items-baseline justify-between gap-4">
                                                        <span className={`text-[11px] font-extrabold ${isCurrentUser ? 'text-indigo-100' : 'text-slate-900'}`}>
                                                            {authorName}
                                                        </span>
                                                        <span className={`text-[10px] ${isCurrentUser ? 'text-indigo-200' : 'text-slate-400'}`}>
                                                            {msg.created_at ? formatDate(msg.created_at) : ''}
                                                        </span>
                                                    </div>

                                                    {/* Attached File Display (WhatsApp style inline file card) */}
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
                                                                    className="ml-2 shrink-0 p-1 hover:opacity-80 transition-opacity"
                                                                >
                                                                    <Download className="h-4 w-4" />
                                                                </a>
                                                            )}
                                                        </div>
                                                    )}

                                                    {displayText && (
                                                        <div
                                                            className={`prose max-w-none text-xs leading-relaxed break-words ${
                                                                isCurrentUser
                                                                    ? 'text-white prose-invert prose-p:text-white prose-a:text-indigo-200'
                                                                    : 'text-slate-700 prose-a:text-indigo-600 font-medium'
                                                            }`}
                                                            dangerouslySetInnerHTML={renderMarkdown(displayText)}
                                                        />
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                            <div ref={chatEndRef} />
                        </div>

                        {/* File Attachment Pill Preview */}
                        {selectedFile && (
                            <div className="px-4 py-2 border-t border-slate-200 bg-indigo-50/60 flex items-center justify-between text-xs text-indigo-900">
                                <span className="flex items-center gap-2 truncate font-bold">
                                    <Paperclip className="h-3.5 w-3.5 text-indigo-600" />
                                    {selectedFile.name} ({Math.round(selectedFile.size / 1024)} KB)
                                </span>
                                <button onClick={() => setSelectedFile(null)} className="text-slate-400 hover:text-slate-600">
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        )}

                        {/* Bottom Chat Input Controls */}
                        <form onSubmit={handleSendMessage} className="p-4 bg-white border-t border-slate-200 flex items-end gap-3">
                            <input
                                type="file"
                                ref={fileInputRef}
                                onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
                                className="hidden"
                            />

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="h-10 w-10 text-slate-400 hover:text-indigo-600 rounded-xl shrink-0"
                                onClick={() => fileInputRef.current?.click()}
                            >
                                <Paperclip className="h-5 w-5" />
                            </Button>

                            <div className="flex-1 min-w-0">
                                <Textarea
                                    placeholder="Type anything... e.g., 'Add online payment module', 'Change budget to 50k'"
                                    rows={1}
                                    className="w-full text-xs rounded-xl focus:ring-indigo-500 border-slate-200 resize-none py-2.5 max-h-32"
                                    value={messageText}
                                    onChange={(e) => setMessageText(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            handleSendMessage(e);
                                        }
                                    }}
                                />
                            </div>

                            <Button
                                type="submit"
                                size="icon"
                                disabled={(!messageText.trim() && !selectedFile) || submitting}
                                className="h-10 w-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shrink-0 shadow-md"
                            >
                                <Send className="h-4 w-4" />
                            </Button>
                        </form>
                    </div>

                    {/* Right Column (30% width): AI Live Understanding Sidebar */}
                    <div className="space-y-6">

                        {/* 1. AI Live Understanding Box */}
                        <Card className="rounded-2xl border-indigo-100 shadow-sm overflow-hidden bg-gradient-to-b from-indigo-50/40 to-white">
                            <CardHeader className="px-5 py-4 border-b border-indigo-50 bg-indigo-50/30">
                                <CardTitle className="text-sm font-extrabold text-indigo-950 flex items-center gap-2">
                                    <BrainCircuit className="h-4 w-4 text-indigo-600" />
                                    AI Live Understanding
                                </CardTitle>
                                <CardDescription className="text-[11px] text-indigo-700/70">
                                    Automatically updated as you message.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4 text-xs">
                                <div>
                                    <span className="font-bold text-slate-400 uppercase text-[10px]">Project Type</span>
                                    <p className="font-extrabold text-slate-800">{aiSummary.project_type || 'Analyzing from chat...'}</p>
                                </div>

                                <div>
                                    <span className="font-bold text-slate-400 uppercase text-[10px]">Current Goal</span>
                                    <p className="font-bold text-indigo-900 bg-indigo-50/70 p-2.5 rounded-xl border border-indigo-100">
                                        {aiSummary.current_goal || 'Describe your vision in chat...'}
                                    </p>
                                </div>

                                {aiSummary.missing_info && aiSummary.missing_info.length > 0 && (
                                    <div>
                                        <span className="font-bold text-amber-600 uppercase text-[10px]">Missing Information</span>
                                        <div className="flex flex-wrap gap-1.5 mt-1">
                                            {aiSummary.missing_info.map((info, idx) => (
                                                <span key={idx} className="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200">
                                                    ? {info}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* 2. AI Questions Card (Only shown if unanswered questions exist) */}
                        {aiQuestions.length > 0 && (
                            <Card className="rounded-2xl border-amber-200 bg-amber-50/30 shadow-sm">
                                <CardHeader className="px-5 py-4 border-b border-amber-100">
                                    <CardTitle className="text-xs font-extrabold text-amber-900 flex items-center gap-2">
                                        <HelpCircle className="h-4 w-4 text-amber-600" />
                                        AI Needs Clarification
                                    </CardTitle>
                                    <CardDescription className="text-[10px] text-amber-700">
                                        Answer in chat to help AI structure your project.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-5 space-y-3">
                                    {aiQuestions.map((q) => (
                                        <div key={q.id} className="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-white border border-amber-100 shadow-xs">
                                            <p className="text-xs font-bold text-slate-800">{q.question}</p>
                                            <button
                                                onClick={() => handleDismissQuestion(q.id)}
                                                className="text-slate-400 hover:text-slate-600 text-[10px] shrink-0 font-bold"
                                                title="Dismiss question"
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {/* 3. AI Actions Log Card */}
                        <Card className="rounded-2xl border-slate-200 shadow-sm">
                            <CardHeader className="px-5 py-4 border-b border-slate-100">
                                <CardTitle className="text-xs font-extrabold text-slate-800 flex items-center gap-2">
                                    <Activity className="h-4 w-4 text-indigo-500" />
                                    AI Action Stream
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-5">
                                {aiActionsLog.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic">No AI actions logged yet.</p>
                                ) : (
                                    <ul className="space-y-3">
                                        {aiActionsLog.map((log, idx) => (
                                            <li key={idx} className="flex items-start gap-2 text-xs">
                                                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-bold text-slate-700">{log.action}</p>
                                                    {log.detail && <p className="text-[10px] text-slate-400 truncate">{log.detail}</p>}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Top-up Dialog for AI Activation */}
            <Dialog open={topupModalOpen} onOpenChange={setTopupModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-rose-600">
                            <AlertTriangle className="h-5 w-5" />
                            Insufficient Balance
                        </DialogTitle>
                        <DialogDescription className="text-slate-500 pt-2 text-xs">
                            Top up your wallet balance to activate the AI Project Manager.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-2 text-xs text-slate-600">
                        Activation costs 10 EGP. Required: {requiredAmount ? Number(requiredAmount).toFixed(2) : '10.00'}.
                    </div>
                    <DialogFooter className="mt-4 flex gap-2 justify-end">
                        <Button variant="outline" size="sm" onClick={() => setTopupModalOpen(false)}>
                            Cancel
                        </Button>
                        <a
                            href="/app/wallet"
                            className="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors"
                        >
                            Top up Wallet
                        </a>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
