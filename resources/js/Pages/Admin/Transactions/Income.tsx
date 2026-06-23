import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { ChevronDown, MoreHorizontal, Trash } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
 // Example for translations if applicable
import { __ } from '@/lib/i18n';
import TransactionUserCard from './Components/TransactionUserCard';

const TransactionActions = ({ tx, type }) => {
    const [isOpen, setIsOpen] = React.useState(false);

    const handleDelete = () => {
        router.delete(`/admin/transactions/${tx.id}?type=${type}`, {
            onSuccess: () => setIsOpen(false),
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">{__('general.actions')}</span>
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xs">
                <DialogHeader>
                    <DialogTitle>{__('general.actions')}</DialogTitle>
                </DialogHeader>
                <div className="flex flex-col gap-2 py-2">
                    <Button variant="destructive" className="justify-start gap-2" onClick={handleDelete}>
                        <Trash className="w-4 h-4" />
                        {__('general.delete')}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default function Income({ transactions, filters, filteredUser }) {
    const handleSearch = (search) => {
        router.get(
            '/admin/transactions',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/transactions',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true }
        );
    };

    const columns = [
        {
            key: 'id',
            label: __('general.id'),
            sortable: true,
            className: 'w-[60px]',
            render: (tx) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (tx) => tx.user ? (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900">{tx.user.name}</span>
                    <span className="text-xs text-slate-500">{tx.user.email}</span>
                </div>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'project',
            label: __('erp.project'),
            render: (tx) => tx.project ? (
                <span className="text-slate-700">{tx.project.project_name}</span>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'type',
            label: __('general.type'),
            render: (tx) => {
                const creditTypes = ['earned', 'received', 'sent'];
                const debitTypes  = ['used', 'refunded', 'send'];
                const variant = creditTypes.includes(tx.type) ? 'default'
                              : debitTypes.includes(tx.type)  ? 'destructive'
                              : 'secondary';
                return (
                    <Badge variant={variant} className="uppercase">
                        {tx.type}
                    </Badge>
                );
            }
        },
        {
            key: 'amount',
            label: __('general.amount'),
            className: 'text-end',
            render: (tx) => (
                <span className="font-medium font-mono text-slate-800">
                    {formatCurrency(tx.amount || 0, tx.currency)}
                </span>
            )
        },
        {
            key: 'business_amount',
            label: __('general.business_amount'),
            className: 'text-end',
            render: (tx) => (
                <span className="font-medium font-mono text-green-600">
                    {formatCurrency(tx.business_amount || 0, tx.business_currency)}
                </span>
            )
        },
        {
            key: 'reason',
            label: __('general.reason'),
            render: (tx) => <span className="text-slate-600 max-w-[250px] truncate block" title={tx.reason}>{tx.reason}</span>
        },
        {
            key: 'created_at',
            label: __('general.date'),
            sortable: true,
            render: (tx) => (
                <span className="text-slate-600 whitespace-nowrap">
                    {new Date(tx.created_at).toLocaleDateString()}
                </span>
            )
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[50px] text-end',
            render: (tx) => <TransactionActions tx={tx} type="income" />
        }
    ];

    return (
        <AdminSidebarLayout title={__('erp.income_transactions')} header={__('erp.transactions')}>
            {filteredUser && <TransactionUserCard user={filteredUser} />}
            
            <div className="flex justify-end items-center mb-6 gap-4">
                <div className="flex-1">
                    <h2 className="text-lg font-semibold text-slate-900">{__('erp.income_transactions')}</h2>
                    <p className="text-sm text-slate-500">{__('erp.view_all_income_and_related')}</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="default" asChild>
                        <Link href={`/admin/transactions/create?type=receive${filteredUser ? `&user=${filteredUser.id}` : ''}`}>{__('general.receive')}</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/admin/transactions/create?type=earn${filteredUser ? `&user=${filteredUser.id}` : ''}`}>{__('general.earn')}</Link>
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="gap-2">
                                {__('general.more') || 'More'}
                                <ChevronDown className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/transactions/create?type=used${filteredUser ? `&user_id=${filteredUser.id}` : ''}`} className="w-full cursor-pointer">
                                    {__('general.used') || 'Used'}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/transactions/create?type=refund${filteredUser ? `&user_id=${filteredUser.id}` : ''}`} className="w-full cursor-pointer">
                                    {__('general.refund')}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/transactions/create?type=send${filteredUser ? `&user_id=${filteredUser.id}` : ''}`} className="w-full cursor-pointer">
                                    {__('general.send')}
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={transactions.data}
                    pagination={transactions}
                    filters={filters}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__('erp.no_transactions_found')}
                    emptyDescription={__('general.try_adjusting_your_search_filters')}
                />
            </div>
        </AdminSidebarLayout>
    );
}
