import React, { useState, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    MoreHorizontal, Eye, CheckCircle, RotateCcw,
    Zap, Clock, Inbox, CheckCheck, BarChart2,
    MessageSquare, Trash2, ShieldAlert, Sparkles, Crown,
    ShieldCheck, X, ChevronDown, ArrowUpDown
} from 'lucide-react';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/* ─── Types ─────────────────────────────────────────────────── */
interface ClientTier {
    slug: string;
    name: string;
    color: string;
    badge_svg?: string;
}

interface Ticket {
    id: number;
    ticket_subject: string;
    ticket_message?: string;
    ticket_status: string;
    priority: string;
    priority_score: number;
    status_text: string;
    priority_text: string;
    display_name: string;
    display_email: string;
    is_urgent: boolean;
    needs_attention: boolean;
    sla_target_minutes: number;
    sla_due_at: string;
    is_overdue: boolean;
    is_vip: boolean;
    client_tier?: ClientTier;
    project?: { id: number; name: string };
    created_at: string;
}

interface Stats {
    total: number;
    open: number;
    waiting: number;
    agent_replied: number;
    closed: number;
}

interface Props {
    tickets: { data: Ticket[]; [key: string]: any };
    filters: {
        status?: string;
        priority?: string;
        sort?: string;
        direction?: string;
        search?: string;
        view?: string;
        [key: string]: any;
    };
    stats: Stats;
}

/* ─── Status & Tier Maps ─────────────────────────────────────── */
const STATUS_BADGE: Record<string, string> = {
    open:          'bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-800',
    agent_replied: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-800',
    user_replied:  'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-800',
    closed:        'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700',
};

const TIER_STYLES: Record<string, { bg: string; text: string; ring: string; icon: React.ElementType }> = {
    obsidian: { bg: 'bg-purple-900/15', text: 'text-purple-600 dark:text-purple-400', ring: 'ring-purple-500/30', icon: Crown },
    diamond:  { bg: 'bg-sky-500/15', text: 'text-sky-600 dark:text-sky-400', ring: 'ring-sky-500/30', icon: Sparkles },
    platinum: { bg: 'bg-indigo-500/15', text: 'text-indigo-600 dark:text-indigo-400', ring: 'ring-indigo-500/30', icon: Crown },
    ruby:     { bg: 'bg-rose-500/15', text: 'text-rose-600 dark:text-rose-400', ring: 'ring-rose-500/30', icon: Zap },
    emerald:  { bg: 'bg-emerald-500/15', text: 'text-emerald-600 dark:text-emerald-400', ring: 'ring-emerald-500/30', icon: ShieldCheck },
    gold:     { bg: 'bg-amber-500/15', text: 'text-amber-600 dark:text-amber-400', ring: 'ring-amber-500/30', icon: Crown },
    silver:   { bg: 'bg-slate-500/15', text: 'text-slate-600 dark:text-slate-300', ring: 'ring-slate-500/30', icon: ShieldCheck },
    bronze:   { bg: 'bg-orange-500/15', text: 'text-orange-700 dark:text-orange-400', ring: 'ring-orange-500/30', icon: ShieldCheck },
    standard: { bg: 'bg-slate-100', text: 'text-slate-600 dark:text-zinc-400', ring: 'ring-slate-200 dark:ring-zinc-800', icon: ShieldCheck },
};

/* ─── SLA Countdown Badge ───────────────────────────────────── */
function SlaCountdownBadge({
    dueAt,
    targetMinutes,
    status,
}: {
    dueAt?: string;
    targetMinutes?: number;
    status: string;
}) {
    const [now, setNow] = useState<number>(Date.now());

    useEffect(() => {
        if (!dueAt || ['closed', 'resolved'].includes(status)) return;
        const interval = setInterval(() => {
            setNow(Date.now());
        }, 1000);
        return () => clearInterval(interval);
    }, [dueAt, status]);

    if (['closed', 'resolved'].includes(status)) {
        return (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400">
                <CheckCheck className="h-3 w-3" />
                <span>{__('admin.tickets_sla_completed')}</span>
            </span>
        );
    }

    if (!dueAt) {
        return <span className="text-xs text-slate-400">—</span>;
    }

    const dueTime = new Date(dueAt).getTime();
    const diffSeconds = Math.floor((dueTime - now) / 1000);
    const isOverdue = diffSeconds < 0;
    const absDiff = Math.abs(diffSeconds);

    const hours = Math.floor(absDiff / 3600);
    const minutes = Math.floor((absDiff % 3600) / 60);
    const seconds = absDiff % 60;

    let timeString = __('admin.tickets_time_s', { seconds });
    if (hours > 0) {
        timeString = __('admin.tickets_time_hm', { hours, minutes });
    } else if (minutes > 0) {
        timeString = __('admin.tickets_time_ms', { minutes, seconds });
    }

    if (isOverdue) {
        return (
            <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900 animate-pulse">
                <ShieldAlert className="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" />
                <span>{__('admin.tickets_sla_overdue', { time: timeString })}</span>
            </div>
        );
    }

    // Critical (< 30 min)
    if (diffSeconds <= 1800) {
        return (
            <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900">
                <Zap className="h-3 w-3 text-amber-600 dark:text-amber-400 animate-pulse" />
                <span>{__('admin.tickets_sla_remaining', { time: timeString })}</span>
            </div>
        );
    }

    // Normal safe remaining
    return (
        <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900">
            <Clock className="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
            <span>{__('admin.tickets_sla_remaining', { time: timeString })}</span>
        </div>
    );
}

/* ─── Priority Badge Component ───────────────────────────────── */
function PriorityBadge({ priority, score }: { priority: string; score?: number }) {
    const config = {
        high: {
            bg: 'bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-900',
            icon: Zap,
            label: __('general.priority_high'),
        },
        medium: {
            bg: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-900',
            icon: Clock,
            label: __('general.priority_medium'),
        },
        low: {
            bg: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700',
            icon: CheckCircle,
            label: __('general.priority_low'),
        },
    }[priority] || {
        bg: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        icon: CheckCircle,
        label: priority,
    };

    const Icon = config.icon;

    return (
        <div className="flex items-center gap-1.5 flex-wrap">
            <span className={cn('inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium', config.bg)}>
                <Icon className="h-3 w-3" />
                <span>{config.label}</span>
            </span>
            {typeof score === 'number' && score > 0 && (
                <span
                    className={cn(
                        'text-[11px] font-mono font-bold px-1.5 py-0.5 rounded',
                        score >= 50
                            ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300'
                            : 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400'
                    )}
                    title={__('admin.tickets_importance_score', { score })}
                >
                    {score}
                </span>
            )}
        </div>
    );
}

/* ─── Stat Card ──────────────────────────────────────────────── */
function StatCard({
    label, value, color, icon: Icon, active, onClick,
}: {
    label: string;
    value: number;
    color: string;
    icon: React.ElementType;
    active?: boolean;
    onClick?: () => void;
}) {
    return (
        <button
            onClick={onClick}
            className={`group flex flex-col gap-2 rounded-2xl border p-4 text-start transition-all ${
                active
                    ? 'border-slate-300 bg-slate-50 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/80'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900/60'
            }`}
        >
            <div className="flex items-center justify-between">
                <span className={`text-xs font-semibold uppercase tracking-wider ${active ? 'text-slate-900 dark:text-zinc-100' : 'text-slate-500 dark:text-zinc-400'}`}>{label}</span>
                <div className={`rounded-lg p-1.5 ${color}`}>
                    <Icon className="h-3.5 w-3.5 text-white" />
                </div>
            </div>
            <span className="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 dark:text-zinc-100">{value}</span>
        </button>
    );
}

/* ─── Main Component ─────────────────────────────────────────── */
export default function Index({ tickets, filters, stats }: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [pendingCloseId, setPendingCloseId] = useState<number | null>(null);
    const [bulkDeleteModalOpen, setBulkDeleteModalOpen] = useState<boolean>(false);
    const [isBulkProcessing, setIsBulkProcessing] = useState<boolean>(false);

    // Visible tickets on this page
    const pageTicketIds = tickets.data.map((t) => t.id);
    const isAllSelected = pageTicketIds.length > 0 && pageTicketIds.every((id) => selectedIds.includes(id));
    const isSomeSelected = selectedIds.length > 0 && !isAllSelected;

    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedIds((prev) => prev.filter((id) => !pageTicketIds.includes(id)));
        } else {
            const combined = Array.from(new Set([...selectedIds, ...pageTicketIds]));
            setSelectedIds(combined);
        }
    };

    const toggleSelectOne = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
        );
    };

    const applyFilter = (update: Record<string, string>) =>
        router.get('/admin/tickets', { ...filters, ...update, page: 1 }, { preserveState: true, replace: true });

    const handleSearch = (search: string) => applyFilter({ search });

    const handleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/tickets', { ...filters, sort: key, direction }, { preserveState: true, replace: true });
    };

    /* ── Bulk Actions ── */
    const executeBulk = (action: string, extraData: Record<string, any> = {}) => {
        if (selectedIds.length === 0) return;
        setIsBulkProcessing(true);

        router.post(
            '/admin/tickets/bulk',
            {
                action,
                ids: selectedIds,
                ...extraData,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedIds([]);
                    setIsBulkProcessing(false);
                    toastSuccess(__('admin.tickets_bulk_success'));
                },
                onError: () => {
                    setIsBulkProcessing(false);
                    toastError(__('admin.tickets_bulk_failed'));
                },
            }
        );
    };

    const confirmClose = () => {
        if (!pendingCloseId) return;
        const id = pendingCloseId;
        setPendingCloseId(null);
        router.put(`/admin/tickets/${id}`, { action: 'close' }, {
            preserveState: true,
            onSuccess: () => toastSuccess(__('general.ticket_closed')),
            onError:   () => toastError(__('general.failed_close_ticket')),
        });
    };

    const handleReopen = (id: number) => {
        router.put(`/admin/tickets/${id}`, { action: 'reopen' }, {
            preserveState: true,
            onSuccess: () => toastSuccess(__('general.ticket_reopened')),
            onError:   () => toastError(__('general.failed_reopen_ticket')),
        });
    };

    /* ── Quick Views ── */
    const VIEW_TABS = [
        { id: '', label: __('admin.tickets_view_all'), icon: Inbox },
        { id: 'vip', label: __('admin.tickets_view_vip'), icon: Crown, highlight: true },
        { id: 'sla_urgent', label: __('admin.tickets_view_sla_urgent'), icon: Zap },
        { id: 'needs_reply', label: __('admin.tickets_view_needs_reply'), icon: Clock },
        { id: 'closed', label: __('admin.tickets_view_closed'), icon: CheckCheck },
    ];

    /* ── Table Columns ── */
    const columns = [
        {
            key: 'selection',
            label: (
                <div className="flex items-center justify-center">
                    <Checkbox
                        checked={isAllSelected ? true : isSomeSelected ? 'indeterminate' : false}
                        onCheckedChange={toggleSelectAll}
                        aria-label={__('admin.tickets_select_all_on_page')}
                        className="rounded border-slate-300 dark:border-zinc-700"
                    />
                </div>
            ),
            className: 'w-[40px] px-2 text-center',
            render: (t: Ticket) => (
                <div className="flex items-center justify-center">
                    <Checkbox
                        checked={selectedIds.includes(t.id)}
                        onCheckedChange={() => toggleSelectOne(t.id)}
                        aria-label={__('admin.tickets_select_ticket', { id: t.id })}
                        className="rounded border-slate-300 dark:border-zinc-700"
                    />
                </div>
            ),
        },
        {
            key: 'id',
            label: '#',
            sortable: true,
            className: 'w-[65px]',
            render: (t: Ticket) => (
                <span className="text-slate-400 dark:text-zinc-500 font-mono text-xs font-semibold">#{t.id}</span>
            ),
        },
        {
            key: 'ticket_subject',
            label: __('admin.tickets_col_subject'),
            sortable: true,
            render: (t: Ticket) => (
                <div className="flex flex-col gap-1 min-w-0 max-w-xs sm:max-w-sm">
                    <div className="flex items-center gap-1.5">
                        {t.is_vip && (
                            <span className="shrink-0 p-0.5 rounded bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300" title={__('admin.tickets_vip_client')}>
                                <Crown className="h-3.5 w-3.5" />
                            </span>
                        )}
                        {t.needs_attention && (
                            <span className="shrink-0 h-2 w-2 rounded-full bg-red-500 animate-ping" title={__('admin.tickets_needs_urgent_attention')} />
                        )}
                        <Link
                            href={`/admin/tickets/${t.id}`}
                            className="font-semibold text-slate-900 dark:text-zinc-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate"
                        >
                            {t.ticket_subject}
                        </Link>
                    </div>
                    {t.project && (
                        <span className="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                            {__('admin.tickets_project_label', { name: t.project.name })}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'display_name',
            label: __('admin.tickets_col_client_tier'),
            render: (t: Ticket) => {
                const tierSlug = t.client_tier?.slug || 'standard';
                const tierStyle = TIER_STYLES[tierSlug] || TIER_STYLES.standard;
                const TierIcon = tierStyle.icon;

                return (
                    <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-1.5 flex-wrap">
                            <span className="text-sm font-semibold text-slate-800 dark:text-zinc-200">{t.display_name}</span>
                            {t.client_tier && (
                                <span
                                    className={cn(
                                        'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ring-1',
                                        tierStyle.bg,
                                        tierStyle.text,
                                        tierStyle.ring
                                    )}
                                    title={__('admin.tickets_tier_tooltip', { tier: t.client_tier.name, minutes: t.sla_target_minutes })}
                                >
                                    <TierIcon className="h-2.5 w-2.5" />
                                    {t.client_tier.name}
                                </span>
                            )}
                        </div>
                        <span className="text-xs text-slate-400 dark:text-zinc-500 truncate max-w-[180px]">{t.display_email}</span>
                    </div>
                );
            },
        },
        {
            key: 'sla_due_at',
            label: __('admin.tickets_col_sla'),
            render: (t: Ticket) => (
                <SlaCountdownBadge
                    dueAt={t.sla_due_at}
                    targetMinutes={t.sla_target_minutes}
                    status={t.ticket_status}
                />
            ),
        },
        {
            key: 'priority',
            label: __('admin.tickets_col_priority'),
            sortable: true,
            render: (t: Ticket) => (
                <PriorityBadge priority={t.priority} score={t.priority_score} />
            ),
        },
        {
            key: 'ticket_status',
            label: __('general.status'),
            sortable: true,
            render: (t: Ticket) => (
                <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', STATUS_BADGE[t.ticket_status] ?? 'bg-slate-100 text-slate-600')}>
                    {t.status_text}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: __('admin.tickets_col_created'),
            sortable: true,
            render: (t: Ticket) => (
                <span className="text-xs text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                    {new Date(t.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[50px] text-end',
            render: (t: Ticket) => (
                <DropdownMenu>
                    <DropdownMenuTrigger render={<Button variant="ghost" className="h-8 w-8 p-0 hover:bg-slate-100 dark:hover:bg-zinc-800" />}>
                        <span className="sr-only">{__('general.actions')}</span>
                        <MoreHorizontal className="h-4 w-4 text-slate-400 dark:text-zinc-400" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-44">
                        <DropdownMenuLabel className="text-xs text-slate-500">{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/tickets/${t.id}`} className="flex items-center cursor-pointer">
                                <Eye className="me-2 h-4 w-4" />{__('general.view_ticket')}
                            </Link>
                        </DropdownMenuItem>
                        {t.ticket_status !== 'closed' ? (
                            <DropdownMenuItem onClick={() => setPendingCloseId(t.id)} className="text-emerald-700 dark:text-emerald-400 cursor-pointer">
                                <CheckCircle className="me-2 h-4 w-4" />{__('general.close_ticket')}
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem onClick={() => handleReopen(t.id)} className="text-amber-700 dark:text-amber-400 cursor-pointer">
                                <RotateCcw className="me-2 h-4 w-4" /> {__('general.reopen')}
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    /* ── Extra Filters ── */
    const advancedFilters = (
        <div className="flex items-center gap-2">
            <select
                className="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 focus:border-slate-500 focus:ring-2 focus:ring-slate-50 outline-none transition-all"
                value={filters.status || ''}
                onChange={(e) => applyFilter({ status: e.target.value })}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="open">{__('general.open')}</option>
                <option value="user_replied">{__('general.user_replied')}</option>
                <option value="agent_replied">{__('general.agent_replied')}</option>
                <option value="closed">{__('general.closed')}</option>
            </select>
            <select
                className="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 focus:border-slate-500 focus:ring-2 focus:ring-slate-50 outline-none transition-all"
                value={filters.priority || ''}
                onChange={(e) => applyFilter({ priority: e.target.value })}
            >
                <option value="">{__('general.all_priorities')}</option>
                <option value="high">{__('general.priority_high')}</option>
                <option value="medium">{__('general.priority_medium')}</option>
                <option value="low">{__('general.priority_low')}</option>
            </select>
        </div>
    );

    const hasUrgent = tickets.data.some((t: Ticket) => t.is_urgent || t.is_overdue);

    return (
        <AdminSidebarLayout title={__('general.support_tickets')} header={__('admin.tickets_header')}>
            {/* ── Urgent Alert ── */}
            {hasUrgent && (
                <div className="mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-900/50 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    <Zap className="h-4 w-4 text-red-500 flex-shrink-0 animate-pulse" />
                    <span>
                        <strong>{__('admin.tickets_urgent_alert_title')}</strong> {__('admin.tickets_urgent_alert_body')}
                    </span>
                </div>
            )}

            {/* ── Stat Cards ── */}
            {stats && (
                <div className="mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <StatCard
                        label={__('general.total')}
                        value={stats.total}
                        color="bg-slate-500"
                        icon={BarChart2}
                        active={!filters.status && !filters.view}
                        onClick={() => applyFilter({ status: '', view: '' })}
                    />
                    <StatCard
                        label={__('general.open')}
                        value={stats.open}
                        color="bg-slate-900"
                        icon={Inbox}
                        active={filters.status === 'open'}
                        onClick={() => applyFilter({ status: filters.status === 'open' ? '' : 'open' })}
                    />
                    <StatCard
                        label={__('general.waiting')}
                        value={stats.waiting}
                        color="bg-slate-900"
                        icon={Clock}
                        active={filters.status === 'user_replied'}
                        onClick={() => applyFilter({ status: filters.status === 'user_replied' ? '' : 'user_replied' })}
                    />
                    <StatCard
                        label={__('general.replied')}
                        value={stats.agent_replied}
                        color="bg-amber-600"
                        icon={MessageSquare}
                        active={filters.status === 'agent_replied'}
                        onClick={() => applyFilter({ status: filters.status === 'agent_replied' ? '' : 'agent_replied' })}
                    />
                    <StatCard
                        label={__('general.resolved')}
                        value={stats.closed}
                        color="bg-slate-900"
                        icon={CheckCheck}
                        active={filters.status === 'closed'}
                        onClick={() => applyFilter({ status: filters.status === 'closed' ? '' : 'closed' })}
                    />
                </div>
            )}

            {/* ── View Segment Tabs ── */}
            <div className="mb-4 flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                {VIEW_TABS.map((tab) => {
                    const TabIcon = tab.icon;
                    const isActive = (filters.view || '') === tab.id;
                    return (
                        <button
                            key={tab.id}
                            onClick={() => applyFilter({ view: tab.id })}
                            className={cn(
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border',
                                isActive
                                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900 dark:border-white shadow-sm'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 dark:bg-zinc-900 dark:text-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-700'
                            )}
                        >
                            <TabIcon className={cn('h-3.5 w-3.5', tab.highlight && !isActive ? 'text-amber-500' : '')} />
                            <span>{tab.label}</span>
                        </button>
                    );
                })}
            </div>

            {/* ── Table Container ── */}
            <div className="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden mb-12">
                <DataTable
                    columns={columns}
                    data={tickets.data}
                    pagination={tickets}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__('admin.tickets_empty_title')}
                    emptyDescription={__('admin.tickets_empty_description')}
                />
            </div>

            {/* ── Floating Bulk Action Toolbar ── */}
            {selectedIds.length > 0 && (
                <div className="fixed bottom-6 inset-x-0 mx-auto max-w-xl z-50 px-4">
                    <div className="flex flex-wrap items-center justify-between gap-2 p-3 bg-slate-900 text-white dark:bg-zinc-900 dark:border dark:border-zinc-700 rounded-2xl shadow-2xl animate-in slide-in-from-bottom-5 duration-200">
                        <div className="flex items-center gap-2">
                            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 dark:bg-zinc-800 text-xs font-bold text-indigo-400">
                                {selectedIds.length}
                            </span>
                            <span className="text-xs font-semibold">{__('admin.tickets_selected_count')}</span>
                        </div>

                        <div className="flex items-center gap-1.5 flex-wrap">
                            <Button
                                size="sm"
                                variant="secondary"
                                className="h-8 text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 dark:bg-zinc-800"
                                onClick={() => executeBulk('close')}
                                disabled={isBulkProcessing}
                            >
                                <CheckCircle className="me-1.5 h-3.5 w-3.5 text-emerald-400" />
                                {__('admin.tickets_bulk_close')}
                            </Button>

                            <Button
                                size="sm"
                                variant="secondary"
                                className="h-8 text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 dark:bg-zinc-800"
                                onClick={() => executeBulk('reopen')}
                                disabled={isBulkProcessing}
                            >
                                <RotateCcw className="me-1.5 h-3.5 w-3.5 text-amber-400" />
                                {__('admin.tickets_bulk_reopen')}
                            </Button>

                            {/* Priority Dropdown */}
                            <DropdownMenu>
                                <DropdownMenuTrigger render={
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        className="h-8 text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 dark:bg-zinc-800"
                                        disabled={isBulkProcessing}
                                    >
                                        <Zap className="me-1.5 h-3.5 w-3.5 text-purple-400" />
                                        {__('admin.tickets_bulk_priority')}
                                        <ChevronDown className="ms-1 h-3 w-3" />
                                    </Button>
                                } />
                                <DropdownMenuContent align="end" className="w-36">
                                    <DropdownMenuItem onClick={() => executeBulk('priority', { priority: 'high' })}>
                                        {__('general.priority_high')}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => executeBulk('priority', { priority: 'medium' })}>
                                        {__('general.priority_medium')}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => executeBulk('priority', { priority: 'low' })}>
                                        {__('general.priority_low')}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Delete Button */}
                            <Button
                                size="sm"
                                variant="destructive"
                                className="h-8 text-xs font-medium bg-rose-600 hover:bg-rose-700 text-white"
                                onClick={() => setBulkDeleteModalOpen(true)}
                                disabled={isBulkProcessing}
                            >
                                <Trash2 className="me-1.5 h-3.5 w-3.5" />
                                {__('admin.tickets_bulk_delete')}
                            </Button>

                            {/* Deselect */}
                            <button
                                onClick={() => setSelectedIds([])}
                                className="p-1 text-slate-400 hover:text-white transition-colors"
                                title={__('admin.tickets_clear_selection')}
                                aria-label={__('admin.tickets_clear_selection')}
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* ── Single Close Confirm Modal ── */}
            <ConfirmModal
                isOpen={pendingCloseId !== null}
                title={__('general.close_ticket')}
                description={__('admin.tickets_close_confirm_body')}
                confirmLabel={__('general.close_ticket')}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmClose}
                onCancel={() => setPendingCloseId(null)}
            />

            {/* ── Bulk Delete Confirm Modal ── */}
            <ConfirmModal
                isOpen={bulkDeleteModalOpen}
                title={__('admin.tickets_bulk_delete_title')}
                description={__('admin.tickets_bulk_delete_body', { count: selectedIds.length })}
                confirmLabel={__('admin.tickets_confirm_delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={() => {
                    setBulkDeleteModalOpen(false);
                    executeBulk('delete');
                }}
                onCancel={() => setBulkDeleteModalOpen(false)}
            />
        </AdminSidebarLayout>
    );
}
