import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { MoreHorizontal, Trash } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export type TransactionsTab = 'income' | 'cost';

export interface TransactionsPageProps {
    type: TransactionsTab;
    titleKey: string;
    headerKey: string;
    descriptionKey: string;
    emptyTitleKey: string;
    emptyDescriptionKey: string;
    primaryCreateType: 'receive' | 'earn';
    primaryCreateLabelKey: string;
    showMoreMenu?: boolean;
    amountColorize?: 'green' | 'red';
    transactions: any;
    filters: any;
    filteredUser?: any;
    children?: (ctx: { handleSearch: (s: string) => void; handleSort: (key: string) => void }) => React.ReactNode;
}

function TransactionActions({ tx, type }: { tx: any; type: TransactionsTab }) {
    const [pending, setPending] = useState(false);

    const handleDelete = () => {
        setPending(true);
        router.delete(`/admin/transactions/${tx.id}?type=${type}`, {
            preserveScroll: true,
            onSuccess: () => {
                setPending(false);
                toast.success(__('general.deleted') || 'Deleted');
            },
            onError: () => {
                setPending(false);
                toast.error(__('general.error_occurred') || 'Something went wrong');
            },
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">{__('general.actions')}</span>
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleDelete} className="text-red-600 focus:text-red-600">
                    <Trash className="w-4 h-4 me-2" />{__('general.delete')}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function TransactionsPage(props: TransactionsPageProps) {
    const { type, titleKey, headerKey, descriptionKey, emptyTitleKey, emptyDescriptionKey, primaryCreateType, primaryCreateLabelKey, showMoreMenu = true, amountColorize = 'green', transactions, filters, filteredUser, children } = props;

    const handleSearch = (search: string) => {
        router.get(
            '/admin/transactions',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true },
        );
    };

    const handleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/transactions',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true },
        );
    };

    const colorClass = amountColorize === 'green' ? 'text-emerald-600' : 'text-rose-600';

    const columns = [
        {
            key: 'id',
            label: __('general.id'),
            sortable: true,
            className: 'w-[60px]',
            render: (tx: any) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>,
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (tx: any) => tx.user ? (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900">{tx.user.name}</span>
                    <span className="text-xs text-slate-500">{tx.user.email}</span>
                </div>
            ) : <span className="text-slate-400">—</span>,
        },
        {
            key: 'project',
            label: __('erp.project'),
            render: (tx: any) => tx.project ? (
                <span className="text-slate-700">{tx.project.project_name}</span>
            ) : <span className="text-slate-400">—</span>,
        },
        ...(type === 'income' ? [{
            key: 'type',
            label: __('general.type'),
            render: (tx: any) => {
                const creditTypes = ['earned', 'received'];
                const isCredit = creditTypes.includes(tx.type);
                return (
                    <span className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${isCredit ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                        {tx.type}
                    </span>
                );
            },
        }] : []),
        {
            key: 'amount',
            label: __('general.amount'),
            className: 'text-end',
            render: (tx: any) => (
                <span className="font-medium font-mono text-slate-800">
                    {formatMoney(tx.amount || 0, tx.currency)}
                </span>
            ),
        },
        {
            key: 'business_amount',
            label: __('general.business_amount'),
            className: 'text-end',
            render: (tx: any) => (
                <span className={`font-medium font-mono ${colorClass}`}>
                    {formatMoney(tx.business_amount || 0, tx.business_currency)}
                </span>
            ),
        },
        {
            key: 'reason',
            label: __('general.reason'),
            render: (tx: any) => <span className="text-slate-600 max-w-[250px] truncate block" title={tx.reason}>{tx.reason}</span>,
        },
        {
            key: 'created_at',
            label: __('general.date'),
            sortable: true,
            render: (tx: any) => (
                <span className="text-slate-600 whitespace-nowrap">
                    {new Date(tx.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[50px] text-end',
            render: (tx: any) => <TransactionActions tx={tx} type={type} />,
        },
    ];

    const userParam = filteredUser ? `&user=${filteredUser.id}` : '';

    return (
        <AdminSidebarLayout title={__(titleKey)} header={__(headerKey)}>
            <Head title={__(titleKey)} />

            <div className="flex justify-end items-center mb-6 gap-4 flex-wrap">
                <div className="flex-1">
                    <h2 className="text-lg font-semibold text-slate-900">{__(titleKey)}</h2>
                    <p className="text-sm text-slate-500">{__(descriptionKey)}</p>
                </div>
                <div className="flex gap-2 flex-wrap">
                    <Button asChild>
                        <a href={`/admin/transactions/create?type=${primaryCreateType}${userParam}`}>
                            {__(primaryCreateLabelKey)}
                        </a>
                    </Button>
                    {showMoreMenu && (
                        <Button variant="outline" asChild>
                            <a href={`/admin/transactions/create?type=${type === 'income' ? 'used' : 'send'}${userParam}`}>
                                {type === 'income' ? __('general.used') : __('general.send')}
                            </a>
                        </Button>
                    )}
                </div>
            </div>

            {children?.({ handleSearch, handleSort })}

            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={transactions.data}
                    pagination={transactions}
                    filters={filters}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__(emptyTitleKey)}
                    emptyDescription={__(emptyDescriptionKey)}
                />
            </div>
        </AdminSidebarLayout>
    );
}

export default TransactionsPage;