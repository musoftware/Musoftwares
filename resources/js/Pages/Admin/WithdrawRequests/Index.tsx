import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, Eye } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface WithdrawRequest {
    id: number;
    status: string;
    amount: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
    user: { id: number; name: string; email: string } | null;
    method: { id: number; name: string } | null;
}

interface Props {
    requests: { data: WithdrawRequest[]; [key: string]: any };
    filters: { status?: string };
}

const statusStyles: Record<string, string> = {
    pending:   'bg-yellow-100 text-yellow-800',
    reviewing: 'bg-slate-50 text-slate-900',
    approved:  'bg-green-100 text-green-800',
    declined:  'bg-red-100 text-red-800',
};

const statusLabelKeys: Record<string, string> = {
    pending:   'general.pending',
    reviewing: 'general.reviewing',
    approved:  'general.approved',
    declined:  'general.declined',
};

const statusLabel = (status: string): string =>
    statusLabelKeys[status] ? __(statusLabelKeys[status]) : status;

export default function Index({ requests, filters }: Props) {
    const { settings } = usePage<any>().props;
    const base_currency = settings?.base_currency;

    const handleFilter = (value: string) => {
        router.get(
            '/admin/withdraw-requests',
            { ...filters, status: value || undefined, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSearch = (search: string) => {
        router.get(
            '/admin/withdraw-requests',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key: string) => {
        const direction = filters['sort'] === key && filters['direction'] === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/withdraw-requests',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true }
        );
    };

    const columns = [
        {
            key: 'id',
            label: __('admin.withdraw_requests_col_id'),
            sortable: true,
            className: 'w-[60px]',
            render: (row: WithdrawRequest) => (
                <span className="text-slate-500 font-mono text-xs">#{row.id}</span>
            ),
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (row: WithdrawRequest) => 
                row.user ? (
                    <Link href={route('admin.users.show', row.user.id)} className="flex flex-col group cursor-pointer">
                        <span className="font-medium text-slate-800 group-hover:text-slate-900 transition-colors">{row.user.name}</span>
                        <span className="text-xs text-slate-500">{row.user.email}</span>
                    </Link>
                ) : (
                    <div className="flex flex-col">
                        <span className="font-medium text-slate-800">—</span>
                    </div>
                ),
        },
        {
            key: 'amount',
            label: __('general.amount'),
            sortable: true,
            render: (row: WithdrawRequest) => (
                <span className="font-semibold text-slate-800">
                    {formatCurrency(row.amount, base_currency)}
                </span>
            ),
        },
        {
            key: 'method',
            label: __('admin.withdraw_requests_col_method'),
            render: (row: WithdrawRequest) => (
                <span className="text-slate-600">{row.method?.name ?? '—'}</span>
            ),
        },
        {
            key: 'status',
            label: __('general.status'),
            render: (row: WithdrawRequest) => (
                <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${statusStyles[row.status] ?? 'bg-slate-100 text-slate-700'}`}
                >
                    {statusLabel(row.status)}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: __('admin.withdraw_requests_col_requested'),
            sortable: true,
            render: (row: WithdrawRequest) => (
                <span className="text-slate-500 text-sm whitespace-nowrap">
                    {new Date(row.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[60px] text-end',
            render: (row: WithdrawRequest) => (
                <DropdownMenu>
                    <DropdownMenuTrigger className="inline-flex h-8 w-8 items-center justify-center rounded-md p-0 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
                        <span className="sr-only">{__('general.open_menu')}</span>
                        <MoreHorizontal className="h-4 w-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-40">
                        <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/withdraw-requests/${row.id}`}>
                                <Eye className="me-2 h-4 w-4" /> {__('general.view')}</Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    const advancedFilters = (
        <select
            className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
            value={filters.status || ''}
            onChange={(e) => handleFilter(e.target.value)}
        >
            <option value="">{__('general.all_statuses')}</option>
            <option value="pending">{__('general.pending')}</option>
            <option value="reviewing">{__('general.reviewing')}</option>
            <option value="approved">{__('general.approved')}</option>
            <option value="declined">{__('general.declined')}</option>
        </select>
    );

    return (
        <AdminSidebarLayout title={__('general.withdraw_requests')} header={__('general.withdraw_requests')}>
            <Head title={__('general.withdraw_requests')} />
            <DataTable
                columns={columns}
                data={requests.data}
                pagination={requests}
                filters={{ ...filters, extra: advancedFilters }}
                onSearch={handleSearch}
                onSort={handleSort}
                emptyTitle={__('admin.withdraw_requests_empty_title')}
                emptyDescription={__('admin.withdraw_requests_empty_description')}
            />
        </AdminSidebarLayout>
    );
}

