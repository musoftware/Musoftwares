import React from 'react';
import { Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    MoreHorizontal, Eye, CheckCircle, RotateCcw,
    AlertTriangle, Zap, Clock, Inbox, CheckCheck, BarChart2,
    MessageSquare,
} from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { toastSuccess, toastError, toastInfo } from '@/Components/ui/use-toast';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';

/* ─── Types ─────────────────────────────────────────────────── */
import { useState } from 'react';
interface Ticket {
    id: number;
    ticket_subject: string;
    ticket_status: string;
    priority: string;
    status_text: string;
    priority_text: string;
    display_name: string;
    display_email: string;
    is_urgent: boolean;
    needs_attention: boolean;
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
    filters: { status?: string; priority?: string; sort?: string; direction?: string; search?: string; [key: string]: any };
    stats: Stats;
}

/* ─── Style maps ─────────────────────────────────────────────── */
const STATUS_BADGE: Record<string, string> = {
    open:          'bg-slate-50 text-slate-900 ring-1 ring-slate-200',
    agent_replied: 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200',
    user_replied:  'bg-slate-50 text-slate-900 ring-1 ring-slate-200',
    closed:        'bg-green-100 text-green-800 ring-1 ring-green-200',
};

const PRIORITY_BADGE: Record<string, string> = {
    high:   'bg-red-100 text-red-700 ring-1 ring-red-200',
    medium: 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200',
    low:    'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
};

const PRIORITY_ICON: Record<string, string> = { high: '🔴', medium: '🟡', low: '🟢' };

/* ─── Stat card ──────────────────────────────────────────────── */
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
                    ? 'border-slate-200 bg-slate-50 shadow-md'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'
            }`}
        >
            <div className="flex items-center justify-between">
                <span className={`text-xs font-semibold uppercase tracking-wider ${active ? 'text-slate-900' : 'text-slate-500'}`}>{label}</span>
                <div className={`rounded-lg p-1.5 ${color}`}>
                    <Icon className="h-3.5 w-3.5 text-white" />
                </div>
            </div>
            <span className={`text-3xl font-bold tabular-nums ${active ? 'text-slate-900' : 'text-slate-900'}`}>{value}</span>
        </button>
    );
}

/* ─── Main ───────────────────────────────────────────────────── */
export default function Index({ tickets, filters, stats }: Props) {
    const { toast } = useToast();
    const [pendingCloseId, setPendingCloseId] = useState<number | null>(null);

    const applyFilter = (update: Record<string, string>) =>
        router.get('/admin/tickets', { ...filters, ...update, page: 1 }, { preserveState: true, replace: true });

    const handleStatusFilter = (status: string) =>
        applyFilter({ status: filters.status === status ? '' : status });

    const handleSearch = (search: string) => applyFilter({ search });

    const handleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/tickets', { ...filters, sort: key, direction }, { preserveState: true, replace: true });
    };

    const confirmClose = () => {
        if (!pendingCloseId) return;
        const id = pendingCloseId;
        setPendingCloseId(null);
        router.put(`/admin/tickets/${id}`, { action: 'close' }, {
            preserveState: true,
            onSuccess: () => toastSuccess(__('general.ticket_closed') || 'Ticket closed successfully.'),
            onError:   () => toastError(__('general.failed_close_ticket') || 'Failed to close ticket.'),
        });
    };

    const handleReopen = (id: number) => {
        router.put(`/admin/tickets/${id}`, { action: 'reopen' }, {
            preserveState: true,
            onSuccess: () => toastSuccess(__('general.ticket_reopened') || 'Ticket reopened successfully.'),
            onError:   () => toastError(__('general.failed_reopen_ticket') || 'Failed to reopen ticket.'),
        });
    };

    /* ── columns ── */
    const columns = [
        {
            key: 'id',
            label: '#',
            sortable: true,
            className: 'w-[60px]',
            render: (t: Ticket) => (
                <span className="text-slate-400 font-mono text-xs">#{t.id}</span>
            ),
        },
        {
            key: 'ticket_subject',
            label: 'Subject',
            sortable: true,
            render: (t: Ticket) => (
                <div className="flex flex-col gap-0.5 min-w-0">
                    <Link
                        href={`/admin/tickets/${t.id}`}
                        className="font-semibold text-slate-800 hover:text-slate-900 transition-colors truncate max-w-xs flex items-center gap-1.5"
                    >
                        {t.needs_attention && (
                            <span className="flex-shrink-0 h-2 w-2 rounded-full bg-red-500" title={__('general.needs_attention')} />
                        )}
                        {t.is_urgent && (
                            <Zap className="flex-shrink-0 h-3 w-3 text-red-500" />
                        )}
                        <span className="truncate">{t.ticket_subject}</span>
                    </Link>
                </div>
            ),
        },
        {
            key: 'display_name',
            label: 'Client',
            render: (t: Ticket) => (
                <div className="flex flex-col gap-0.5">
                    <span className="text-sm font-medium text-slate-800">{t.display_name}</span>
                    <span className="text-xs text-slate-400">{t.display_email}</span>
                </div>
            ),
        },
        {
            key: 'priority',
            label: 'Priority',
            render: (t: Ticket) => (
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${PRIORITY_BADGE[t.priority] ?? 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'}`}>
                    {PRIORITY_ICON[t.priority]} {t.priority_text}
                </span>
            ),
        },
        {
            key: 'ticket_status',
            label: 'Status',
            render: (t: Ticket) => (
                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${STATUS_BADGE[t.ticket_status] ?? 'bg-slate-100 text-slate-600'}`}>
                    {t.status_text}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Opened',
            sortable: true,
            render: (t: Ticket) => (
                <span className="text-xs text-slate-500 whitespace-nowrap">
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
                    <DropdownMenuTrigger render={<Button variant="ghost" className="h-8 w-8 p-0 hover:bg-slate-100" />}>
                        <span className="sr-only">{__('general.actions')}</span>
                        <MoreHorizontal className="h-4 w-4 text-slate-400" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-44">
                        <DropdownMenuLabel className="text-xs text-slate-500">{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/tickets/${t.id}`} className="flex items-center">
                                <Eye className="me-2 h-4 w-4" />{__('general.view_ticket')}</Link>
                        </DropdownMenuItem>
                        {t.ticket_status !== 'closed' ? (
                            <DropdownMenuItem onClick={() => setPendingCloseId(t.id)} className="text-slate-900 focus:text-green-800">
                                <CheckCircle className="me-2 h-4 w-4" />{__('general.close_ticket')}</DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem onClick={() => handleReopen(t.id)} className="text-yellow-700 focus:text-yellow-800">
                                <RotateCcw className="me-2 h-4 w-4" /> {__('general.reopen')}</DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    /* ── filters toolbar ── */
    const advancedFilters = (
        <div className="flex items-center gap-2">
            <select
                className="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-slate-500 focus:ring-2 focus:ring-slate-50 outline-none transition-all"
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
                className="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-slate-500 focus:ring-2 focus:ring-slate-50 outline-none transition-all"
                value={filters.priority || ''}
                onChange={(e) => applyFilter({ priority: e.target.value })}
            >
                <option value="">{__('general.all_priorities')}</option>
                <option value="high">🔴 High</option>
                <option value="medium">🟡 Medium</option>
                <option value="low">🟢 Low</option>
            </select>
        </div>
    );

    const hasUrgent = tickets.data.some((t: Ticket) => t.is_urgent);

    return (
        <AdminSidebarLayout title={__('general.support_tickets')} header="Support Desk">

            {/* ── Urgent alert ── */}
            {hasUrgent && (
                <div className="mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <Zap className="h-4 w-4 text-red-500 flex-shrink-0" />
                    <span>
                        <strong>{__('general.urgent_tickets_require_attention')}</strong> — some high-priority tickets have been waiting too long.
                    </span>
                </div>
            )}

            {/* ── Stat cards ── */}
            {stats && (
                <div className="mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <StatCard
                        label={__('general.total')}
                        value={stats.total}
                        color="bg-slate-500"
                        icon={BarChart2}
                        active={!filters.status}
                        onClick={() => applyFilter({ status: '' })}
                    />
                    <StatCard
                        label={__('general.open')}
                        value={stats.open}
                        color="bg-slate-900"
                        icon={Inbox}
                        active={filters.status === 'open'}
                        onClick={() => handleStatusFilter('open')}
                    />
                    <StatCard
                        label={__('general.waiting')}
                        value={stats.waiting}
                        color="bg-slate-900"
                        icon={Clock}
                        active={filters.status === 'user_replied'}
                        onClick={() => handleStatusFilter('user_replied')}
                    />
                    <StatCard
                        label={__('general.replied')}
                        value={stats.agent_replied}
                        color="bg-yellow-600"
                        icon={MessageSquare}
                        active={filters.status === 'agent_replied'}
                        onClick={() => handleStatusFilter('agent_replied')}
                    />
                    <StatCard
                        label={__('general.resolved')}
                        value={stats.closed}
                        color="bg-slate-900"
                        icon={CheckCheck}
                        active={filters.status === 'closed'}
                        onClick={() => handleStatusFilter('closed')}
                    />
                </div>
            )}

            {/* ── Table ── */}
            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <DataTable
                    columns={columns}
                    data={tickets.data}
                    pagination={tickets}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle="No tickets found"
                    emptyDescription="Adjust filters or wait for support requests."
                />
            </div>

            <ConfirmModal
                isOpen={pendingCloseId !== null}
                title={__('general.close_ticket') || 'Close ticket?'}
                description={__('general.confirm_close_ticket') || 'This ticket will be marked as resolved. You can reopen it later.'}
                confirmLabel={__('general.close_ticket') || 'Close ticket'}
                cancelLabel={__('general.cancel') || 'Cancel'}
                onConfirm={confirmClose}
                onCancel={() => setPendingCloseId(null)}
            />
        </AdminSidebarLayout>
    );
}
