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
    reviewing: 'bg-blue-100 text-blue-800',
    approved:  'bg-green-100 text-green-800',
    declined:  'bg-red-100 text-red-800',
};

export default function Index({ requests, filters }: Props) {
    const { settings } = usePage<any>().props;
    const base_currency = settings?.base_currency || 'USD';

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
            label: 'ID',
            sortable: true,
            className: 'w-[60px]',
            render: (row: WithdrawRequest) => (
                <span className="text-slate-500 font-mono text-xs">#{row.id}</span>
            ),
        },
        {
            key: 'user',
            label: 'User',
            render: (row: WithdrawRequest) => 
                row.user ? (
                    <Link href={route('admin.users.show', row.user.id)} className="flex flex-col group cursor-pointer">
                        <span className="font-medium text-slate-800 group-hover:text-blue-600 transition-colors">{row.user.name}</span>
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
            label: 'Amount',
            sortable: true,
            render: (row: WithdrawRequest) => (
                <span className="font-semibold text-slate-800">
                    {formatCurrency(row.amount, base_currency)}
                </span>
            ),
        },
        {
            key: 'method',
            label: 'Method',
            render: (row: WithdrawRequest) => (
                <span className="text-slate-600">{row.method?.name ?? '—'}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: WithdrawRequest) => (
                <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${statusStyles[row.status] ?? 'bg-slate-100 text-slate-700'}`}
                >
                    {row.status}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Requested',
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
            className: 'w-[60px] text-right',
            render: (row: WithdrawRequest) => (
                <DropdownMenu>
                    <DropdownMenuTrigger className="inline-flex h-8 w-8 items-center justify-center rounded-md p-0 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
                        <span className="sr-only">Open menu</span>
                        <MoreHorizontal className="h-4 w-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-40">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/withdraw-requests/${row.id}`}>
                                <Eye className="mr-2 h-4 w-4" /> View
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    const advancedFilters = (
        <select
            className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            value={filters.status || ''}
            onChange={(e) => handleFilter(e.target.value)}
        >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="reviewing">Reviewing</option>
            <option value="approved">Approved</option>
            <option value="declined">Declined</option>
        </select>
    );

    return (
        <AdminSidebarLayout title="Withdraw Requests" header="Withdraw Requests">
            <Head title="Withdraw Requests" />
            <DataTable
                columns={columns}
                data={requests.data}
                pagination={requests}
                filters={{ ...filters, extra: advancedFilters }}
                onSearch={handleSearch}
                onSort={handleSort}
                emptyTitle="No withdraw requests"
                emptyDescription="No withdrawal requests have been submitted yet."
            />
        </AdminSidebarLayout>
    );
}
