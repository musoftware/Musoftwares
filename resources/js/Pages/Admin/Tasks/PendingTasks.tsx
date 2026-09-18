import React, { useState, useMemo, useCallback } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Search,
    Filter,
    Clock,
    CheckCircle2,
    AlertCircle,
    EyeOff,
    RotateCcw,
    History,
    FileText,
    ChevronLeft,
    ChevronRight,
    Sparkles,
    ShieldAlert,
    DollarSign,
    UserCheck,
    Layers,
    ArrowUpDown,
    MoreHorizontal,
    CheckSquare,
    Square,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';

interface ClientSummary {
    id: number;
    name: string;
    email: string;
    tier_name: string;
    tier_slug: string;
    tier_badge: string;
    points_balance: number;
    points_discount_value: number;
}

interface AuditLog {
    id: number;
    action: string;
    reason: string | null;
    notes: string | null;
    user_name: string;
    created_at: string;
}

interface TaskItem {
    id: number;
    task_name: string;
    task_description: string | null;
    priority: string;
    billing_type: 'billable' | 'non_billable';
    billing_status: 'open' | 'in_progress' | 'ready_to_invoice' | 'invoiced' | 'ignored';
    pending_reason: 'waiting_client' | 'waiting_execution' | 'waiting_review' | 'ready_to_invoice' | string;
    ignore_reason: string | null;
    ignore_notes: string | null;
    ignored_at: string | null;
    ignored_by_user: { id: number; name: string } | null;
    sla_hours: number | null;
    sla_due_at: string | null;
    created_at: string;
    due_date: string | null;
    project: { id: number; name: string } | null;
    client: ClientSummary | null;
    audit_logs: AuditLog[];
}

interface Stats {
    all: number;
    waiting_client: number;
    waiting_execution: number;
    waiting_review: number;
    ready_to_invoice: number;
    ignored: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string;
    tab?: string;
    billing_type?: string;
    priority?: string;
    per_page?: number;
}

interface Props {
    tasks: TaskItem[];
    stats: Stats;
    filters: Filters;
    pagination: Pagination;
}

const REASON_LABELS: Record<string, string> = {
    non_billable: 'Non-billable internal work',
    duplicate: 'Duplicate task',
    entry_error: 'Entry error / accidental submission',
    free_promo: 'Complimentary / promotional',
    client_cancelled: 'Client cancelled request',
    other: 'Other reason',
};

const STAGE_LABELS: Record<string, { label: string; bg: string; text: string }> = {
    waiting_client: { label: 'Waiting Client Action', bg: 'bg-amber-50 dark:bg-amber-950/30', text: 'text-amber-800 dark:text-amber-300' },
    waiting_execution: { label: 'Waiting Execution', bg: 'bg-slate-100 dark:bg-slate-800', text: 'text-slate-800 dark:text-slate-200' },
    waiting_review: { label: 'Waiting Final Review', bg: 'bg-indigo-50 dark:bg-indigo-950/30', text: 'text-indigo-800 dark:text-indigo-300' },
    ready_to_invoice: { label: 'Ready to Invoice', bg: 'bg-emerald-50 dark:bg-emerald-950/30', text: 'text-emerald-800 dark:text-emerald-300' },
};

export default function PendingTasks({ tasks, stats, filters, pagination }: Props) {
    const currentTab = filters.tab || 'all';
    const [search, setSearch] = useState(filters.search || '');
    const [billingType, setBillingType] = useState(filters.billing_type || 'all');
    const [priority, setPriority] = useState(filters.priority || 'all');
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    // Modals
    const [ignoringTask, setIgnoringTask] = useState<TaskItem | null>(null);
    const [updatingTask, setUpdatingTask] = useState<TaskItem | null>(null);
    const [viewingAuditTask, setViewingAuditTask] = useState<TaskItem | null>(null);

    // Forms
    const ignoreForm = useForm({
        ignore_reason: 'non_billable',
        ignore_notes: '',
    });

    const updateForm = useForm({
        billing_type: 'billable',
        billing_status: 'open',
        pending_reason: 'waiting_execution',
        sla_hours: 48,
    });

    const applyFilters = useCallback((overrides: Record<string, any> = {}) => {
        const payload: Record<string, any> = {
            tab: currentTab,
            search: search.trim() || undefined,
            billing_type: billingType !== 'all' ? billingType : undefined,
            priority: priority !== 'all' ? priority : undefined,
            ...overrides,
        };

        router.get(route('admin.tasks.pending'), payload, {
            preserveState: true,
            replace: true,
        });
    }, [currentTab, search, billingType, priority]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search: search.trim() });
    };

    const handleTabChange = (tabKey: string) => {
        setSelectedIds([]);
        applyFilters({ tab: tabKey });
    };

    const handleSelectAll = () => {
        if (selectedIds.length === tasks.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(tasks.map(t => t.id));
        }
    };

    const toggleSelect = (id: number) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(item => item !== id) : [...prev, id]
        );
    };

    // Ignore Action
    const openIgnoreModal = (task: TaskItem) => {
        setIgnoringTask(task);
        ignoreForm.reset();
        ignoreForm.setData({
            ignore_reason: 'non_billable',
            ignore_notes: '',
        });
    };

    const submitIgnore = (e: React.FormEvent) => {
        e.preventDefault();
        if (!ignoringTask) return;

        ignoreForm.post(route('admin.tasks.ignore', ignoringTask.id), {
            onSuccess: () => {
                setIgnoringTask(null);
                ignoreForm.reset();
            },
        });
    };

    // Restore Action
    const handleRestore = (task: TaskItem) => {
        router.post(route('admin.tasks.restore', task.id), {}, {
            preserveScroll: true,
        });
    };

    // Update Status Action
    const openUpdateModal = (task: TaskItem) => {
        setUpdatingTask(task);
        updateForm.setData({
            billing_type: task.billing_type || 'billable',
            billing_status: task.billing_status || 'open',
            pending_reason: task.pending_reason || 'waiting_execution',
            sla_hours: task.sla_hours || 48,
        });
    };

    const submitUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!updatingTask) return;

        updateForm.post(route('admin.tasks.billing-status', updatingTask.id), {
            onSuccess: () => {
                setUpdatingTask(null);
            },
        });
    };

    // Bulk Actions
    const handleBulkAction = (action: string) => {
        if (selectedIds.length === 0) return;

        if (action === 'ignore') {
            const reason = window.prompt('Enter reason for ignoring selected tasks (e.g., non_billable, duplicate):', 'non_billable');
            if (!reason) return;
            router.post(route('admin.tasks.bulk-pending-action'), {
                task_ids: selectedIds,
                action: 'ignore',
                ignore_reason: reason,
            }, {
                onSuccess: () => setSelectedIds([]),
            });
            return;
        }

        router.post(route('admin.tasks.bulk-pending-action'), {
            task_ids: selectedIds,
            action,
        }, {
            onSuccess: () => setSelectedIds([]),
        });
    };

    return (
        <AdminSidebarLayout>
            <Head title="Pending Tasks & Workflow — Musoftwares" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-border/50 pb-5">
                    <div>
                        <div className="flex items-center gap-2">
                            <Layers className="h-6 w-6 text-slate-900 dark:text-white" />
                            <h1 className="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Pending Tasks & Execution Pipeline
                            </h1>
                        </div>
                        <p className="text-sm text-muted-foreground mt-1">
                            A centralized hub for unbilled tasks, SLA checkpoints, client actions, and permanent ignore audit logging.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link href="/admin/tasks/as_list">
                            <Button variant="outline" size="sm" className="hover:text-black">
                                <FileText className="h-4 w-4 me-1 text-slate-700" />
                                Tasks List
                            </Button>
                        </Link>
                        <Link href="/admin/tasks/board-explorer">
                            <Button variant="outline" size="sm" className="hover:text-black">
                                <History className="h-4 w-4 me-1 text-slate-700" />
                                Board Explorer
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Top Stats Overview */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <Card
                        onClick={() => handleTabChange('all')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'all'
                                ? 'border-slate-900 dark:border-white shadow-sm ring-1 ring-slate-900/10'
                                : 'border-border/60 hover:border-slate-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                All Pending
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">
                                {stats.all}
                            </span>
                        </CardContent>
                    </Card>

                    <Card
                        onClick={() => handleTabChange('waiting_client')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'waiting_client'
                                ? 'border-amber-600 shadow-sm ring-1 ring-amber-500/20'
                                : 'border-border/60 hover:border-amber-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                                Client Action
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-400 mt-2">
                                {stats.waiting_client}
                            </span>
                        </CardContent>
                    </Card>

                    <Card
                        onClick={() => handleTabChange('waiting_execution')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'waiting_execution'
                                ? 'border-slate-900 dark:border-white shadow-sm ring-1 ring-slate-900/10'
                                : 'border-border/60 hover:border-slate-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                In Execution
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">
                                {stats.waiting_execution}
                            </span>
                        </CardContent>
                    </Card>

                    <Card
                        onClick={() => handleTabChange('waiting_review')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'waiting_review'
                                ? 'border-indigo-600 shadow-sm ring-1 ring-indigo-500/20'
                                : 'border-border/60 hover:border-indigo-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">
                                Final Review
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-indigo-700 dark:text-indigo-400 mt-2">
                                {stats.waiting_review}
                            </span>
                        </CardContent>
                    </Card>

                    <Card
                        onClick={() => handleTabChange('ready_to_invoice')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'ready_to_invoice'
                                ? 'border-green-600 shadow-sm ring-1 ring-green-500/20'
                                : 'border-border/60 hover:border-green-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-green-700 dark:text-green-400 uppercase tracking-wider">
                                Ready to Bill
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-green-700 dark:text-green-400 mt-2">
                                {stats.ready_to_invoice}
                            </span>
                        </CardContent>
                    </Card>

                    <Card
                        onClick={() => handleTabChange('ignored')}
                        className={`cursor-pointer transition-all border ${
                            currentTab === 'ignored'
                                ? 'border-slate-600 shadow-sm ring-1 ring-slate-500/20'
                                : 'border-border/60 hover:border-slate-400'
                        }`}
                    >
                        <CardContent className="p-3.5 flex flex-col justify-between">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                Ignored Archive
                            </span>
                            <span className="text-2xl font-bold tracking-tight text-slate-600 dark:text-slate-400 mt-2">
                                {stats.ignored}
                            </span>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters & Search Toolbar */}
                <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-muted/30 p-3 rounded-lg border border-border/40">
                    <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 flex-1 max-w-md">
                        <div className="relative flex-1">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search task name, client, or project..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 h-9 text-sm bg-background"
                            />
                        </div>
                        <Button type="submit" size="sm" variant="default" className="bg-black text-white hover:bg-slate-800">
                            Search
                        </Button>
                    </form>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Billing Type Selector */}
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span>Type:</span>
                            <select
                                value={billingType}
                                onChange={(e) => {
                                    setBillingType(e.target.value);
                                    applyFilters({ billing_type: e.target.value });
                                }}
                                className="h-9 text-xs rounded border border-border bg-background px-2 py-1 text-slate-900 dark:text-white"
                            >
                                <option value="all">All Types</option>
                                <option value="billable">Billable Only</option>
                                <option value="non_billable">Non-Billable</option>
                            </select>
                        </div>

                        {/* Priority Selector */}
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span>Priority:</span>
                            <select
                                value={priority}
                                onChange={(e) => {
                                    setPriority(e.target.value);
                                    applyFilters({ priority: e.target.value });
                                }}
                                className="h-9 text-xs rounded border border-border bg-background px-2 py-1 text-slate-900 dark:text-white"
                            >
                                <option value="all">All Priorities</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Bulk Actions Bar */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between p-3 bg-slate-900 text-white rounded-lg shadow-sm">
                        <span className="text-xs font-semibold">
                            {selectedIds.length} tasks selected
                        </span>
                        <div className="flex items-center gap-2">
                            {currentTab !== 'ignored' ? (
                                <>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => handleBulkAction('mark_ready_to_invoice')}
                                        className="text-xs h-7"
                                    >
                                        Mark Ready to Invoice
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => handleBulkAction('mark_billable')}
                                        className="text-xs h-7"
                                    >
                                        Set Billable
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => handleBulkAction('ignore')}
                                        className="text-xs h-7"
                                    >
                                        Ignore Selected
                                    </Button>
                                </>
                            ) : (
                                <Button
                                    size="sm"
                                    variant="secondary"
                                    onClick={() => handleBulkAction('restore')}
                                    className="text-xs h-7"
                                >
                                    Restore Selected
                                </Button>
                            )}
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => setSelectedIds([])}
                                className="text-xs h-7 text-white hover:bg-slate-800"
                            >
                                Clear Selection
                            </Button>
                        </div>
                    </div>
                )}

                {/* Tasks Table */}
                <div className="border border-border/60 rounded-lg overflow-hidden bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="text-xs uppercase bg-muted/50 text-muted-foreground border-b border-border/50">
                                <tr>
                                    <th className="p-3 w-10 text-center">
                                        <button
                                            type="button"
                                            onClick={handleSelectAll}
                                            className="text-slate-600 hover:text-black"
                                        >
                                            {selectedIds.length === tasks.length && tasks.length > 0 ? (
                                                <CheckSquare className="h-4 w-4" />
                                            ) : (
                                                <Square className="h-4 w-4" />
                                            )}
                                        </button>
                                    </th>
                                    <th className="p-3 font-semibold">Task & Project</th>
                                    <th className="p-3 font-semibold">Client & Loyalty Tier</th>
                                    <th className="p-3 font-semibold">Billing & Stage</th>
                                    <th className="p-3 font-semibold">SLA / Cairo Time</th>
                                    <th className="p-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/40">
                                {tasks.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="p-8 text-center text-muted-foreground">
                                            No pending tasks found for the selected criteria.
                                        </td>
                                    </tr>
                                ) : (
                                    tasks.map((task) => {
                                        const isSelected = selectedIds.includes(task.id);
                                        const stageInfo = STAGE_LABELS[task.pending_reason] || STAGE_LABELS.waiting_execution;

                                        return (
                                            <tr
                                                key={task.id}
                                                className={`transition-colors hover:bg-muted/20 ${
                                                    isSelected ? 'bg-muted/40' : ''
                                                }`}
                                            >
                                                <td className="p-3 text-center">
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleSelect(task.id)}
                                                        className="text-slate-600 hover:text-black"
                                                    >
                                                        {isSelected ? (
                                                            <CheckSquare className="h-4 w-4 text-black dark:text-white" />
                                                        ) : (
                                                            <Square className="h-4 w-4" />
                                                        )}
                                                    </button>
                                                </td>

                                                {/* Task & Project */}
                                                <td className="p-3 max-w-xs">
                                                    <div className="font-semibold text-slate-900 dark:text-white line-clamp-1">
                                                        {task.task_name}
                                                    </div>
                                                    {task.project && (
                                                        <div className="text-xs text-muted-foreground mt-0.5">
                                                            Project:{' '}
                                                            <span className="font-medium text-slate-700 dark:text-slate-300">
                                                                {task.project.name}
                                                            </span>
                                                        </div>
                                                    )}
                                                    {task.task_description && (
                                                        <p className="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                                                            {task.task_description}
                                                        </p>
                                                    )}
                                                </td>

                                                {/* Client & Loyalty Tier */}
                                                <td className="p-3">
                                                    {task.client ? (
                                                        <div className="flex items-center gap-2.5">
                                                            <img
                                                                src={task.client.tier_badge}
                                                                alt={task.client.tier_name}
                                                                className="w-8 h-8 rounded object-contain bg-slate-50 dark:bg-slate-900 border border-border/40 p-0.5"
                                                                onError={(e) => {
                                                                    (e.target as HTMLElement).style.display = 'none';
                                                                }}
                                                            />
                                                            <div>
                                                                <div className="font-medium text-slate-900 dark:text-white text-xs">
                                                                    {task.client.name}
                                                                </div>
                                                                <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                                                                    <span className="font-semibold text-slate-800 dark:text-slate-200">
                                                                        {task.client.tier_name}
                                                                    </span>
                                                                    <span>•</span>
                                                                    <span>{task.client.points_balance} PTS</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">Direct / Platform</span>
                                                    )}
                                                </td>

                                                {/* Billing & Stage */}
                                                <td className="p-3">
                                                    <div className="flex flex-col gap-1">
                                                        <div className="flex items-center gap-1.5">
                                                            <span
                                                                className={`inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded ${
                                                                    task.billing_type === 'billable'
                                                                        ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                                                                        : 'bg-muted text-muted-foreground'
                                                                }`}
                                                            >
                                                                {task.billing_type === 'billable' ? 'Billable' : 'Non-Billable'}
                                                            </span>

                                                            <span
                                                                className={`inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded ${stageInfo.bg} ${stageInfo.text}`}
                                                            >
                                                                {stageInfo.label}
                                                            </span>
                                                        </div>

                                                        {task.billing_status === 'ignored' && (
                                                            <span className="inline-flex items-center gap-1 text-[11px] text-red-600 font-medium">
                                                                <EyeOff className="h-3 w-3" />
                                                                Ignored: {REASON_LABELS[task.ignore_reason || ''] || task.ignore_reason}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>

                                                {/* SLA / Cairo Time */}
                                                <td className="p-3 text-xs">
                                                    {task.sla_due_at ? (
                                                        <div className="flex items-center gap-1 text-slate-800 dark:text-slate-200 font-medium">
                                                            <Clock className="h-3.5 w-3.5 text-slate-500" />
                                                            <span>Due: {task.sla_due_at}</span>
                                                        </div>
                                                    ) : (
                                                        <div className="text-muted-foreground">
                                                            Created: {task.created_at}
                                                        </div>
                                                    )}
                                                    {task.ignored_at && (
                                                        <div className="text-[11px] text-muted-foreground mt-0.5">
                                                            Ignored at: {task.ignored_at}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Actions */}
                                                <td className="p-3 text-right">
                                                    <div className="inline-flex items-center gap-1.5">
                                                        {task.billing_status !== 'ignored' ? (
                                                            <>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => openUpdateModal(task)}
                                                                    className="h-7 text-xs hover:text-black"
                                                                >
                                                                    Stage & SLA
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => openIgnoreModal(task)}
                                                                    className="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50"
                                                                >
                                                                    Ignore
                                                                </Button>
                                                            </>
                                                        ) : (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => handleRestore(task)}
                                                                className="h-7 text-xs text-green-700 hover:text-green-800 hover:bg-green-50"
                                                            >
                                                                <RotateCcw className="h-3 w-3 me-1" />
                                                                Restore
                                                            </Button>
                                                        )}

                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => setViewingAuditTask(task)}
                                                            className="h-7 px-2 text-slate-600 hover:text-black"
                                                            title="View Audit Logs"
                                                        >
                                                            <History className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="flex items-center justify-between p-3 border-t border-border/50 text-xs text-muted-foreground">
                            <span>
                                Page {pagination.current_page} of {pagination.last_page} ({pagination.total} total items)
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={pagination.current_page <= 1}
                                    onClick={() => applyFilters({ page: pagination.current_page - 1 })}
                                    className="h-7 px-2"
                                >
                                    <ChevronLeft className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={pagination.current_page >= pagination.last_page}
                                    onClick={() => applyFilters({ page: pagination.current_page + 1 })}
                                    className="h-7 px-2"
                                >
                                    <ChevronRight className="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Formal Ignore Task */}
            <Dialog open={!!ignoringTask} onOpenChange={(open) => !open && setIgnoringTask(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">
                            Ignore Task from Billing Pipeline
                        </DialogTitle>
                        <DialogDescription className="text-xs text-muted-foreground">
                            This task will be archived from active execution queues. It will never be deleted and remains restorable anytime.
                        </DialogDescription>
                    </DialogHeader>

                    {ignoringTask && (
                        <form onSubmit={submitIgnore} method="post" className="space-y-4 py-2">
                            <div className="bg-muted/40 p-2.5 rounded text-xs border border-border/40">
                                <span className="font-semibold text-slate-900 dark:text-white">
                                    {ignoringTask.task_name}
                                </span>
                                {ignoringTask.project && (
                                    <div className="text-muted-foreground mt-0.5">
                                        Project: {ignoringTask.project.name}
                                    </div>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Reason for Ignoring <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={ignoreForm.data.ignore_reason}
                                    onChange={(e) => ignoreForm.setData('ignore_reason', e.target.value)}
                                    className="w-full text-xs rounded border border-border bg-background p-2 text-slate-900 dark:text-white"
                                    required
                                >
                                    <option value="non_billable">Non-billable internal work</option>
                                    <option value="duplicate">Duplicate task</option>
                                    <option value="entry_error">Entry error / accidental submission</option>
                                    <option value="free_promo">Complimentary / promotional</option>
                                    <option value="client_cancelled">Client cancelled request</option>
                                    <option value="other">Other reason</option>
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Internal Notes (Audit Trail)
                                </label>
                                <textarea
                                    rows={3}
                                    value={ignoreForm.data.ignore_notes}
                                    onChange={(e) => ignoreForm.setData('ignore_notes', e.target.value)}
                                    placeholder="Explain why this task is being ignored..."
                                    className="w-full text-xs rounded border border-border bg-background p-2 text-slate-900 dark:text-white"
                                />
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIgnoringTask(null)}
                                    className="text-xs"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={ignoreForm.processing}
                                    className="bg-red-600 hover:bg-red-700 text-white text-xs"
                                >
                                    {ignoreForm.processing ? 'Saving...' : 'Confirm Ignore'}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Modal: Update Status & SLA */}
            <Dialog open={!!updatingTask} onOpenChange={(open) => !open && setUpdatingTask(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">
                            Update Task Status & SLA
                        </DialogTitle>
                        <DialogDescription className="text-xs text-muted-foreground">
                            Update execution stage, classification, or target SLA turnaround time.
                        </DialogDescription>
                    </DialogHeader>

                    {updatingTask && (
                        <form onSubmit={submitUpdate} className="space-y-4 py-2">
                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Billing Type
                                </label>
                                <select
                                    value={updateForm.data.billing_type}
                                    onChange={(e) => updateForm.setData('billing_type', e.target.value as any)}
                                    className="w-full text-xs rounded border border-border bg-background p-2 text-slate-900 dark:text-white"
                                >
                                    <option value="billable">Billable</option>
                                    <option value="non_billable">Non-Billable</option>
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Pending Stage / Reason
                                </label>
                                <select
                                    value={updateForm.data.pending_reason}
                                    onChange={(e) => updateForm.setData('pending_reason', e.target.value)}
                                    className="w-full text-xs rounded border border-border bg-background p-2 text-slate-900 dark:text-white"
                                >
                                    <option value="waiting_client">Waiting Client Action</option>
                                    <option value="waiting_execution">Waiting Execution</option>
                                    <option value="waiting_review">Waiting Final Review</option>
                                    <option value="ready_to_invoice">Ready to Invoice</option>
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    SLA Target Hours (Cairo Timezone)
                                </label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={720}
                                    value={updateForm.data.sla_hours}
                                    onChange={(e) => updateForm.setData('sla_hours', parseInt(e.target.value) || 24)}
                                    className="h-9 text-xs"
                                />
                                <span className="text-[11px] text-muted-foreground">
                                    Computes deadline automatically based on Cairo local time.
                                </span>
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setUpdatingTask(null)}
                                    className="text-xs"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={updateForm.processing}
                                    className="bg-black hover:bg-slate-800 text-white text-xs"
                                >
                                    {updateForm.processing ? 'Saving...' : 'Save Updates'}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Modal: Task Audit History */}
            <Dialog open={!!viewingAuditTask} onOpenChange={(open) => !open && setViewingAuditTask(null)}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold flex items-center gap-2">
                            <History className="h-5 w-5 text-slate-800 dark:text-slate-200" />
                            Task Audit History
                        </DialogTitle>
                        <DialogDescription className="text-xs text-muted-foreground">
                            Immutable audit trail of state transitions, ignore reasons, and timestamps (Cairo Time).
                        </DialogDescription>
                    </DialogHeader>

                    {viewingAuditTask && (
                        <div className="space-y-3 py-2 max-h-96 overflow-y-auto">
                            <div className="bg-muted/40 p-2.5 rounded text-xs border border-border/40 mb-3">
                                <span className="font-semibold text-slate-900 dark:text-white">
                                    {viewingAuditTask.task_name}
                                </span>
                            </div>

                            {viewingAuditTask.audit_logs.length === 0 ? (
                                <p className="text-xs text-center text-muted-foreground py-4">
                                    No audit entries recorded yet for this task.
                                </p>
                            ) : (
                                <div className="space-y-2.5">
                                    {viewingAuditTask.audit_logs.map((log) => (
                                        <div
                                            key={log.id}
                                            className="p-2.5 rounded border border-border/50 text-xs bg-card"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-slate-900 dark:text-white uppercase tracking-wider text-[10px]">
                                                    {log.action}
                                                </span>
                                                <span className="text-[11px] text-muted-foreground">
                                                    {log.created_at}
                                                </span>
                                            </div>
                                            {log.reason && (
                                                <div className="mt-1 text-slate-700 dark:text-slate-300">
                                                    <span className="text-muted-foreground">Reason:</span>{' '}
                                                    {REASON_LABELS[log.reason] || log.reason}
                                                </div>
                                            )}
                                            {log.notes && (
                                                <div className="mt-0.5 text-muted-foreground text-[11px]">
                                                    Note: {log.notes}
                                                </div>
                                            )}
                                            <div className="mt-1 text-[10px] text-muted-foreground">
                                                By: {log.user_name}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setViewingAuditTask(null)}
                            className="text-xs"
                        >
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
