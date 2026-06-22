import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, ToggleLeft, ToggleRight, Trash2, Clock, RefreshCw, CalendarDays, AlertCircle } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface BusyTime {
    id: number;
    is_recurring: boolean;
    day_of_week: string | null;
    specific_date: string | null;
    is_full_day: boolean;
    start_time: string | null;
    end_time: string | null;
    reason: string | null;
    is_active: boolean;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
}

interface Stats {
    total: number;
    active: number;
    recurring: number;
    one_off: number;
}

interface Props {
    busyTimes: { data: BusyTime[]; [key: string]: any };
    filters: { user_id?: string; is_recurring?: string; is_active?: string; day_of_week?: string };
    stats: Stats;
}

const DAYS_OF_WEEK = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

export default function Index({ busyTimes, filters, stats }: Props) {
    const { toast } = useToast();

    const handleFilter = (key: string, value: string) => {
        router.get(
            '/admin/busy-times',
            { ...filters, [key]: value || undefined, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSearch = (search: string) => {
        router.get(
            '/admin/busy-times',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key: string) => {
        const direction = filters['sort' as keyof typeof filters] === key && filters['direction' as keyof typeof filters] === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/busy-times',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true }
        );
    };

    const handleToggleActive = (row: BusyTime) => {
        router.post(
            `/admin/busy-times/${row.id}/toggle-active`,
            {},
            {
                preserveState: true,
                onSuccess: () => toast({ title: `Busy time ${row.is_active ? 'deactivated' : 'activated'}.` }),
                onError: () => toast({ title: 'Failed to update status.', variant: 'destructive' }),
            }
        );
    };

    const handleDelete = (row: BusyTime) => {
        if (!confirm(`Delete this busy time entry for ${row.user?.name ?? 'unknown user'}?`)) return;
        router.delete(`/admin/busy-times/${row.id}`, {
            onSuccess: () => toast({ title: 'Busy time deleted.' }),
            onError: () => toast({ title: 'Failed to delete.', variant: 'destructive' }),
        });
    };

    const formatTime = (time: string | null) => {
        if (!time) return '—';
        // time is HH:MM:SS, format to HH:MM
        return time.substring(0, 5);
    };

    const columns = [
        {
            key: 'id',
            label: 'ID',
            sortable: true,
            className: 'w-[60px]',
            render: (row: BusyTime) => (
                <span className="text-slate-500 font-mono text-xs">#{row.id}</span>
            ),
        },
        {
            key: 'user',
            label: 'User',
            render: (row: BusyTime) => (
                <div className="flex flex-col">
                    {row.user ? (
                        <>
                            <Link
                                href={`/admin/users/${row.user.id}`}
                                className="font-medium text-slate-900 hover:text-slate-900 transition-colors"
                            >
                                {row.user.name}
                            </Link>
                            <span className="text-xs text-slate-500">{row.user.email}</span>
                        </>
                    ) : (
                        <span className="text-slate-400">—</span>
                    )}
                </div>
            ),
        },
        {
            key: 'type',
            label: 'Type',
            render: (row: BusyTime) => (
                <span
                    className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${
                        row.is_recurring
                            ? 'bg-slate-50 text-slate-900'
                            : 'bg-yellow-100 text-yellow-800'
                    }`}
                >
                    {row.is_recurring ? (
                        <><RefreshCw className="h-3 w-3" /> {__('general.recurring')}</>
                    ) : (
                        <><CalendarDays className="h-3 w-3" />{__('general.one_off')}</>
                    )}
                </span>
            ),
        },
        {
            key: 'schedule',
            label: 'Schedule',
            render: (row: BusyTime) => (
                <div className="flex flex-col gap-0.5">
                    {row.is_recurring ? (
                        <span className="font-medium text-slate-700">{row.day_of_week ?? '—'}</span>
                    ) : (
                        <span className="font-medium text-slate-700">
                            {row.specific_date
                                ? new Date(row.specific_date + 'T00:00:00').toLocaleDateString(undefined, {
                                    year: 'numeric', month: 'short', day: 'numeric',
                                })
                                : '—'}
                        </span>
                    )}
                    {row.is_full_day ? (
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                            <Clock className="h-3 w-3" />{__('general.full_day')}</span>
                    ) : (
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                            <Clock className="h-3 w-3" />
                            {formatTime(row.start_time)} – {formatTime(row.end_time)}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'reason',
            label: 'Reason',
            render: (row: BusyTime) => (
                <span className="text-slate-600 text-sm line-clamp-1 max-w-[180px]" title={row.reason ?? ''}>
                    {row.reason || <span className="text-slate-400 italic">{__('general.no_reason')}</span>}
                </span>
            ),
        },
        {
            key: 'is_active',
            label: 'Status',
            render: (row: BusyTime) => (
                <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                        row.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500'
                    }`}
                >
                    {row.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            sortable: true,
            render: (row: BusyTime) => (
                <span className="text-slate-500 text-sm whitespace-nowrap">
                    {new Date(row.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[60px] text-end',
            render: (row: BusyTime) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">{__('general.open_menu')}</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-44">
                        <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuItem onClick={() => handleToggleActive(row)}>
                            {row.is_active ? (
                                <><ToggleLeft className="me-2 h-4 w-4" /> {__('general.deactivate')}</>
                            ) : (
                                <><ToggleRight className="me-2 h-4 w-4" /> {__('general.activate')}</>
                            )}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => handleDelete(row)}
                            className="text-red-600 focus:text-red-700"
                        >
                            <Trash2 className="me-2 h-4 w-4" /> {__('general.delete')}</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    const advancedFilters = (
        <div className="flex items-center gap-2 flex-wrap">
            <select
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.is_recurring ?? ''}
                onChange={(e) => handleFilter('is_recurring', e.target.value)}
            >
                <option value="">{__('general.all_types')}</option>
                <option value="1">{__('general.recurring')}</option>
                <option value="0">{__('general.one_off')}</option>
            </select>
            <select
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.is_active ?? ''}
                onChange={(e) => handleFilter('is_active', e.target.value)}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="1">{__('general.active')}</option>
                <option value="0">{__('general.inactive')}</option>
            </select>
            <select
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.day_of_week ?? ''}
                onChange={(e) => handleFilter('day_of_week', e.target.value)}
            >
                <option value="">{__('general.all_days')}</option>
                {DAYS_OF_WEEK.map((d) => (
                    <option key={d} value={d}>{d}</option>
                ))}
            </select>
        </div>
    );

    return (
        <AdminSidebarLayout title={__('general.busy_times')} header="User Busy Times">
            <Head title={__('general.busy_times')} />

            {/* Stats */}
            <div className="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold text-slate-800">{stats.total}</span>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.total')}</span>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold text-green-600">{stats.active}</span>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.active')}</span>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold text-slate-900">{stats.recurring}</span>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.recurring')}</span>
                </div>
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold text-yellow-600">{stats.one_off}</span>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.one_off')}</span>
                </div>
            </div>

            <DataTable
                columns={columns}
                data={busyTimes.data}
                pagination={busyTimes}
                filters={{ ...filters, extra: advancedFilters }}
                onSearch={handleSearch}
                onSort={handleSort}
                emptyTitle="No busy times found"
                emptyDescription="Users haven't configured any busy time slots yet."
            />
        </AdminSidebarLayout>
    );
}
