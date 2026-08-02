import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, CalendarClock, FileText, ListTodo, Paperclip,
    Check, AlertTriangle, ShieldQuestion, HelpCircle, Activity,
    Sparkles, MessageSquare, ClipboardCheck, Clock, Download
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AvatarStack, AvatarStackMember } from '@/Components/ui/AvatarStack';
import { ProjectBudgetRow } from '@/Components/ProjectBudgetRow';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
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

import ProjectTasksTab, { TabTask } from './tabs/Tasks';
import ProjectDiscussionsTab, { TabDiscussion } from './tabs/Discussions';
import ProjectFilesTab, { TabFile } from './tabs/Files';
import ProjectFinancialsTab, { TabFinancials } from './tabs/Financials';

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    hold_on: 'bg-amber-50 text-amber-700 ring-amber-200',
    closed: 'bg-slate-100 text-slate-700 ring-slate-200',
};

interface ProjectDetail {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    cost: string;
    paid_invoices: string;
    pending_invoices: string;
    total_paid: string;
    hide_future_tasks: boolean;
    ai_enabled: boolean;
    currency: { id: number; currency: string; symbol: string; string_format?: string } | null;
    counts: { tasks: number; reports: number; files: number };
}

interface RecentReport {
    id: number;
    title: string;
    published_at: string | null;
}

interface SupportTicket {
    id: number;
    subject: string;
    status: string;
    created_at: string;
}

interface ActivityItem {
    type: 'comment' | 'file';
    user: string;
    description: string;
    detail: string;
    time: string;
}

interface PendingApprovalItem {
    board_item_id: number;
    type: string;
    id: number;
    title: string;
    for_date: string;
    client_approval_status: string;
    client_feedback?: string | null;
}

interface Props {
    project: ProjectDetail;
    recentReports?: RecentReport[];
    team?: AvatarStackMember[];
    activeTab?: 'tasks' | 'discussions' | 'files' | 'financials';
    tabContent?: {
        tasks?: TabTask[];
        discussions?: TabDiscussion[];
        files?: TabFile[];
        financials?: TabFinancials;
    };
    pendingApprovals?: PendingApprovalItem[];
    supportTickets?: SupportTicket[];
    projectActivity?: ActivityItem[];
}

const QUICK_ACTIONS = (project: ProjectDetail, today: string) => [
    {
        icon: ListTodo,
        label: __('general.tasks'),
        count: project.counts.tasks,
        href: route('client.projects.tasks.index', { project: project.id }),
        color: 'text-sky-600 bg-sky-50',
    },
    {
        icon: CalendarClock,
        label: __('general.day_board'),
        count: null,
        href: route('client.projects.calendar.date', { project: project.id, date: today }),
        color: 'text-indigo-600 bg-indigo-50',
    },
    {
        icon: FileText,
        label: __('general.reports'),
        count: project.counts.reports,
        href: route('client.projects.show', project.id),
        color: 'text-emerald-600 bg-emerald-50',
    },
    {
        icon: Paperclip,
        label: __('general.files'),
        count: project.counts.files,
        href: route('client.projects.files.index', { project: project.id }),
        color: 'text-amber-600 bg-amber-50',
    },
];

export default function ProjectShow({
    project,
    recentReports = [],
    team = [],
    activeTab = 'discussions',
    tabContent = {},
    pendingApprovals = [],
    supportTickets = [],
    projectActivity = [],
}: Props) {
    const today = new Date().toISOString().slice(0, 10);
    const quickActions = QUICK_ACTIONS(project, today);

    // Approval Flow Local State
    const [approvalsList, setApprovalsList] = useState<PendingApprovalItem[]>(pendingApprovals);
    const [submittingId, setSubmittingId] = useState<number | null>(null);
    const [revisionModalOpen, setRevisionModalOpen] = useState(false);
    const [selectedApprovalItem, setSelectedApprovalItem] = useState<PendingApprovalItem | null>(null);
    const [revisionFeedback, setRevisionFeedback] = useState('');

    const onTabChange = React.useCallback(
        (next: string) => {
            if (next === activeTab) return;
            const target = new URL(window.location.href);
            target.searchParams.set('tab', next);
            router.get(target.pathname + target.search, { tab: next }, {
                only: ['tabContent'],
                preserveScroll: true,
                preserveState: true,
            });
        },
        [activeTab],
    );

    // Call backend API to approve or request revision
    const handleApprovalAction = async (itemId: number, status: 'approved' | 'revision_requested', feedback?: string) => {
        setSubmittingId(itemId);
        try {
            const response = await axios.post(route('client.projects.board.items.approval', { project: project.id, boardItem: itemId }), {
                client_approval_status: status,
                client_feedback: feedback || null,
            });

            if (response.data.ok) {
                toast.success(
                    status === 'approved'
                        ? __('general.deliverable_approved') || 'Deliverable approved successfully!'
                        : __('general.revision_requested') || 'Revision requested successfully!'
                );
                // Remove or update item in local list
                setApprovalsList(prev => prev.filter(item => item.board_item_id !== itemId));
            }
        } catch (error) {
            toast.error(__('general.error') || 'Failed to update approval status.');
        } finally {
            setSubmittingId(null);
            setRevisionModalOpen(false);
            setRevisionFeedback('');
            setSelectedApprovalItem(null);
        }
    };

    const openRevisionModal = (item: PendingApprovalItem) => {
        setSelectedApprovalItem(item);
        setRevisionModalOpen(true);
    };

    const [activationLoading, setActivationLoading] = useState(false);
    const [topupModalOpen, setTopupModalOpen] = useState(false);
    const [requiredAmount, setRequiredAmount] = useState(0);

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

    return (
        <AuthenticatedLayout>
            <Head title={project.name} />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                
                {/* Executive Header */}
                <header className="space-y-4">
                    <Link
                        href={route('client.projects.index')}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 transition-colors hover:text-slate-700"
                    >
                        <ArrowLeft className="h-4 w-4" /> {__('general.all_projects')}
                    </Link>
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                                    {project.name}
                                </h1>
                                <span
                                    className={`shrink-0 rounded-full px-3 py-0.5 text-xs font-bold uppercase tracking-wider ring-1 ring-inset ${
                                        STATUS_STYLES[project.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'
                                    }`}
                                >
                                    {project.status?.replace('_', ' ')}
                                </span>
                            </div>
                            <div className="flex items-center gap-4 text-sm text-slate-500">
                                <AvatarStack members={team} max={5} size="sm" />
                                <span className="text-slate-300">|</span>
                                <span className="font-medium text-slate-600">
                                    {project.date_start ? formatDate(project.date_start) : '—'} &rarr; {project.date_end ? formatDate(project.date_end) : '—'}
                                </span>
                            </div>
                        </div>

                        {/* Interactive Circle Progress KPI */}
                        <div className="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm md:self-end">
                            <div className="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-50">
                                <span className="text-sm font-bold text-slate-800">{Math.round(project.percentage)}%</span>
                                <svg className="absolute inset-0 h-full w-full -rotate-90">
                                    <circle
                                        cx="28"
                                        cy="28"
                                        r="24"
                                        className="stroke-slate-100 fill-none"
                                        strokeWidth="4"
                                    />
                                    <circle
                                        cx="28"
                                        cy="28"
                                        r="24"
                                        className="stroke-indigo-600 fill-none transition-all duration-500"
                                        strokeWidth="4"
                                        strokeDasharray="150.79"
                                        strokeDashoffset={150.79 - (150.79 * project.percentage) / 100}
                                    />
                                </svg>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{__('general.project_status') || 'Project Progress'}</p>
                                <p className="text-sm font-bold text-slate-700">{__('general.completion') || 'Completion'}</p>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Premium Financial Summary Row */}
                <ProjectBudgetRow
                    budget={project.budget}
                    totalPaid={project.total_paid}
                    currency={project.currency}
                    orientation="row"
                />

                {!project.ai_enabled && (
                    <div className="rounded-3xl border border-indigo-100 bg-gradient-to-r from-indigo-50/85 to-purple-50/85 p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div className="space-y-2">
                            <h2 className="text-xl font-extrabold text-indigo-950 flex items-center gap-2">
                                <Sparkles className="h-5 w-5 text-indigo-600 animate-pulse" />
                                Organize Project with AI
                            </h2>
                            <p className="text-sm text-indigo-800/80 max-w-2xl leading-relaxed">
                                Activate the AI Project Manager to translate your chat messages into structured requirements, automatically manage tasks/todos, suggest questions, and keep developers aligned.
                                <span className="font-semibold block mt-1 text-xs">Cost: 10 EGP service fee from your wallet balance.</span>
                            </p>
                        </div>
                        <Button
                            onClick={handleActivateAi}
                            disabled={activationLoading}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl px-6 py-5 shadow-lg shadow-indigo-150 transition-all hover:scale-102 shrink-0 self-start md:self-center"
                        >
                            {activationLoading ? 'Activating...' : 'Organize Project with AI'}
                        </Button>
                    </div>
                )}

                <Dialog open={topupModalOpen} onOpenChange={setTopupModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-rose-600">
                                <AlertTriangle className="h-5 w-5" />
                                Insufficient Balance
                            </DialogTitle>
                            <DialogDescription className="text-slate-500 pt-2 text-xs">
                                You need to top up your wallet to activate the AI Project Manager.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-2 text-xs text-slate-600">
                            Your wallet balance is insufficient. The AI service costs 10 EGP (or equivalent in your currency).
                        </div>
                        <DialogFooter className="mt-4 flex gap-2 justify-end">
                            <Button variant="outline" size="sm" onClick={() => setTopupModalOpen(false)}>
                                Cancel
                            </Button>
                            <a href="/app/wallet" className="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors">
                                Top up Wallet
                            </a>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Main Dashboard Grid */}
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    
                    {/* Left Column: Quick Actions & Approvals List */}
                    <div className="space-y-8 lg:col-span-2">
                        
                        {/* Quick Tiles */}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {quickActions.map((action) => (
                                <Link
                                    key={action.label}
                                    href={action.href}
                                    className="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                                >
                                    <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${action.color} group-hover:scale-105 transition-transform`}>
                                        <action.icon className="h-5 w-5" aria-hidden="true" />
                                    </div>
                                    <div className="mt-4">
                                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{action.label}</p>
                                        <p className="text-xl font-extrabold text-slate-800">
                                            {action.count !== null ? action.count : 'View'}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Deliverables Approvals Center */}
                        <Card className="overflow-hidden rounded-2xl border-slate-200/80 shadow-sm">
                            <CardHeader className="border-b border-slate-50 bg-slate-50/50 px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                            <ClipboardCheck className="h-5 w-5 text-indigo-500" />
                                            {__('general.approvals_center') || 'Deliverables Awaiting Approval'}
                                        </CardTitle>
                                        <CardDescription className="text-xs text-slate-400 mt-0.5">
                                            Sign off on project increments or request modifications.
                                        </CardDescription>
                                    </div>
                                    {approvalsList.length > 0 && (
                                        <span className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-600">
                                            {approvalsList.length} Action Needed
                                        </span>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                {approvalsList.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-3">
                                            <Check className="h-6 w-6" />
                                        </div>
                                        <h3 className="text-sm font-bold text-slate-800">{__('general.all_caught_up') || 'All Caught Up!'}</h3>
                                        <p className="text-xs text-slate-400 max-w-sm mt-1">
                                            No deliverables are currently awaiting your approval. You will be notified when the team publishes new increments.
                                        </p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-slate-100">
                                        {approvalsList.map((item) => (
                                            <li key={item.board_item_id} className="py-4 first:pt-0 last:pb-0">
                                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                                {item.type}
                                                            </span>
                                                            <span className="text-xs text-slate-400">
                                                                Scheduled: {formatDate(item.for_date)}
                                                            </span>
                                                        </div>
                                                        <h4 className="text-sm font-bold text-slate-800">{item.title}</h4>
                                                        {item.client_approval_status === 'revision_requested' && (
                                                            <div className="inline-flex items-center gap-1 text-xs text-rose-500 font-semibold bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100 mt-1">
                                                                <AlertTriangle className="h-3.5 w-3.5" />
                                                                <span>Revision Requested: "{item.client_feedback}"</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            className="h-8 border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                                            disabled={submittingId === item.board_item_id}
                                                            onClick={() => openRevisionModal(item)}
                                                        >
                                                            Request Revision
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            className="h-8 bg-emerald-600 hover:bg-emerald-700 text-white"
                                                            disabled={submittingId === item.board_item_id}
                                                            onClick={() => handleApprovalAction(item.board_item_id, 'approved')}
                                                        >
                                                            {submittingId === item.board_item_id ? 'Saving...' : 'Approve'}
                                                        </Button>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        {/* Interactive Tabs */}
                        <Card className="rounded-2xl border-slate-200/80 shadow-sm">
                            <CardContent className="p-6">
                                <Tabs value={activeTab} onValueChange={onTabChange} className="space-y-6">
                                    <TabsList className="inline-flex h-10 w-full items-stretch justify-start gap-1 rounded-xl border border-slate-200 bg-white p-1 sm:w-auto">
                                        <TabsTrigger
                                            value="discussions"
                                            className="inline-flex items-center gap-2 rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                        >
                                            <MessageSquare className="h-3.5 w-3.5" /> {__('general.chat') || 'Chat'}
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="tasks"
                                            className="inline-flex items-center gap-2 rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                        >
                                            <ListTodo className="h-3.5 w-3.5" /> {__('general.tasks')}
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="files"
                                            className="inline-flex items-center gap-2 rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                        >
                                            <Paperclip className="h-3.5 w-3.5" /> {__('general.files')}
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="financials"
                                            className="inline-flex items-center gap-2 rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 transition-colors data-[state=active]:bg-slate-950 data-[state=active]:text-white"
                                        >
                                            <Clock className="h-3.5 w-3.5" /> {__('general.financials') || 'Financials'}
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="tasks">
                                        <ProjectTasksTab tasks={tabContent.tasks ?? []} />
                                    </TabsContent>
                                    <TabsContent value="discussions">
                                        <ProjectDiscussionsTab discussions={tabContent.discussions ?? []} />
                                    </TabsContent>
                                    <TabsContent value="files">
                                        <ProjectFilesTab files={tabContent.files ?? []} projectId={project.id} />
                                    </TabsContent>
                                    <TabsContent value="financials">
                                        <ProjectFinancialsTab
                                            financials={tabContent.financials ?? null}
                                            currency={project.currency}
                                        />
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Support Channels & Activity Feed */}
                    <div className="space-y-8">

                        {/* Customer Support Hub */}
                        <Card className="rounded-2xl border-slate-200/80 shadow-sm">
                            <CardHeader className="px-6 py-5">
                                <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                                    <ShieldQuestion className="h-5 w-5 text-indigo-500" />
                                    Support Channels
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-400">
                                    Need help? Reach out to our technical support team.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="px-6 pb-6 space-y-4">
                                {supportTickets.length > 0 ? (
                                    <div className="space-y-3">
                                        <p className="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Active Support Tickets</p>
                                        <ul className="space-y-2">
                                            {supportTickets.map((ticket) => (
                                                <li key={ticket.id} className="rounded-xl border border-slate-100 bg-slate-50/50 p-3 flex items-center justify-between">
                                                    <div className="min-w-0">
                                                        <p className="text-xs font-bold text-slate-700 truncate">{ticket.subject}</p>
                                                        <p className="text-[10px] text-slate-400 mt-0.5">{formatDate(ticket.created_at)}</p>
                                                    </div>
                                                    <span className={`rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider shrink-0 ${
                                                        ticket.status === 'open' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500'
                                                    }`}>
                                                        {ticket.status}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : (
                                    <p className="text-xs text-slate-400 italic">No active support tickets.</p>
                                )}
                                
                                <a
                                    href="/onboarding"
                                    className="flex w-full items-center justify-center gap-1.5 rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 shadow-sm"
                                >
                                    <HelpCircle className="h-4 w-4" />
                                    Open New Support Ticket
                                </a>
                            </CardContent>
                        </Card>

                        {/* Recent Activity Timeline Feed */}
                        <Card className="rounded-2xl border-slate-200/80 shadow-sm">
                            <CardHeader className="px-6 py-5">
                                <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                                    <Activity className="h-5 w-5 text-indigo-500" />
                                    Activity Timeline
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-400">
                                    Real-time activity log for this project.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="px-6 pb-6">
                                {projectActivity.length === 0 ? (
                                    <p className="text-xs text-slate-400 italic text-center py-4">No recent activity detected.</p>
                                ) : (
                                    <ul className="space-y-4 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                                        {projectActivity.map((activity, idx) => (
                                            <li key={idx} className="flex gap-3 items-start text-xs relative">
                                                <div className={`h-6.5 w-6.5 rounded-full shrink-0 flex items-center justify-center ring-4 ring-white ${
                                                    activity.type === 'comment' ? 'bg-indigo-50 text-indigo-600' : 'bg-amber-50 text-amber-600'
                                                }`}>
                                                    {activity.type === 'comment' ? <MessageSquare className="h-3 w-3" /> : <Paperclip className="h-3 w-3" />}
                                                </div>
                                                <div className="flex-1 space-y-0.5">
                                                    <p className="text-slate-500">
                                                        <span className="font-bold text-slate-700">{activity.user}</span> {activity.description}
                                                    </p>
                                                    {activity.detail && (
                                                        <p className="text-[11px] text-slate-400 line-clamp-1 italic bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                                                            {activity.detail}
                                                        </p>
                                                    )}
                                                    <p className="text-[10px] text-slate-400">{new Date(activity.time).toLocaleString()}</p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        {/* Recent Reports Archive */}
                        {recentReports.length > 0 && (
                            <Card className="rounded-2xl border-slate-200/80 shadow-sm">
                                <CardHeader className="px-6 py-5">
                                    <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                                        <FileText className="h-5 w-5 text-indigo-500" />
                                        Recent Reports
                                    </CardTitle>
                                    <CardDescription className="text-xs text-slate-400">
                                        Download formal progress reports.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="px-6 pb-6">
                                    <ul className="space-y-3">
                                        {recentReports.map((report) => (
                                            <li key={report.id} className="group">
                                                <Link
                                                    href={route('client.projects.reports.show', {
                                                        project: project.id,
                                                        report: report.id,
                                                    })}
                                                    className="flex items-center justify-between rounded-xl border border-slate-100 p-3 hover:bg-slate-50/50 hover:border-slate-200 transition-all"
                                                >
                                                    <span className="flex items-center gap-2 font-bold text-xs text-slate-700 group-hover:text-indigo-600 transition-colors">
                                                        <FileText className="h-4 w-4 text-slate-400 group-hover:text-indigo-500" />
                                                        {report.title}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400">
                                                        {report.published_at ? formatDate(report.published_at) : '—'}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Revision Request Feedback Dialog */}
            <Dialog open={revisionModalOpen} onOpenChange={setRevisionModalOpen}>
                <DialogContent className="w-full sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold text-slate-900">
                            Request Revision
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-400">
                            Explain the changes required. This feedback will be shared directly with the developers in context.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedApprovalItem && (
                        <div className="py-4 space-y-3">
                            <div>
                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                                    {selectedApprovalItem.type}: {selectedApprovalItem.title}
                                </span>
                            </div>
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-slate-700">Modification Description</label>
                                <Textarea
                                    rows={4}
                                    placeholder="Describe in detail what needs to be changed..."
                                    value={revisionFeedback}
                                    onChange={(e) => setRevisionFeedback(e.target.value)}
                                    className="text-xs rounded-xl focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            className="h-9 text-xs"
                            onClick={() => setRevisionModalOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            className="h-9 bg-slate-950 hover:bg-slate-800 text-white text-xs"
                            disabled={!revisionFeedback.trim()}
                            onClick={() => selectedApprovalItem && handleApprovalAction(selectedApprovalItem.board_item_id, 'revision_requested', revisionFeedback)}
                        >
                            Submit Feedback
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
