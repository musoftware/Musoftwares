import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { DataTable } from '@/Components/ui/DataTable';
import { MoreHorizontal, Eye, Edit, UserPlus } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuGroup,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { ClientActionModal } from './Components/ClientActionModal';
import { usePage } from '@inertiajs/react';

interface Props {
    clients: any;
    filters: any;
    stats: any;
    tenant?: { id: number; name: string; user_id: number };
}

export default function Index({ clients, filters, stats, tenant }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients', { tenantId: tenant?.id?.toString() });
    const { auth } = usePage().props as any;
    const [actionModalClient, setActionModalClient] = React.useState<any | null>(null);

    const handleDeleteClient = (client: any) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_client'))) {
            router.delete(route('erp.clients.destroy', client.id), { preserveScroll: true });
        }
    };

    const handleSearch = (search: string) => {
        router.get(
            route('erp.clients.index'),
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleFilter = (key: string, value: string) => {
        router.get(
            route('erp.clients.index'),
            { ...filters, [key]: value, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            route('erp.clients.index'),
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
            render: (client: any) => <span className="text-slate-500 font-mono text-xs">#{client.id}</span>
        },
        {
            key: 'name',
            label: __('general.client'),
            sortable: true,
            render: (client: any) => (
                <div className="flex items-center gap-3">
                    <Avatar className="h-10 w-10 border border-slate-200">
                        <AvatarImage src={client.avatar_url || ''} alt={client.name} />
                        <AvatarFallback className="bg-blue-50 text-blue-500 font-semibold uppercase">
                            {client.name.substring(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="flex flex-col text-start group">
                        <Link href={route('erp.clients.show', client.id)} className="font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">
                            {client.name}
                        </Link>
                        <div className="flex items-center gap-2 mt-0.5">
                            <span className="text-sm text-slate-500">
                                {client.email}
                            </span>
                            <span className={`px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border ${
                                client.status === 'active' ? 'bg-green-50 text-green-700 border-green-100/50' : 
                                client.status === 'lead' ? 'bg-orange-50 text-orange-700 border-orange-100/50' :
                                'bg-slate-50 text-slate-700 border-slate-100/50'
                            }`}>
                                {client.status || 'lead'}
                            </span>
                        </div>
                    </div>
                </div>
            ),
        },
        {
            key: 'phone',
            label: __('general.phone'),
            render: (client: any) => <span className="text-slate-600">{client.phone}</span>,
        },
        {
            key: 'created_at',
            label: __('general.joined'),
            sortable: true,
            render: (client: any) => (
                <span className="text-slate-600 whitespace-nowrap">
                    {client.created_at}
                </span>
            ),
        },
        {
            key: 'balance',
            label: __('general.wallet_balance'),
            render: (client: any) => {
                const balance = client.balance || 0;
                return (
                    <span className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border ${balance < 0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                        {formatCurrency(balance, client.currency)}
                    </span>
                );
            },
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[80px] text-end',
            render: (client: any) => (
                <button
                    onClick={() => setActionModalClient(client)}
                    className="p-1.5 hover:bg-slate-100 rounded-md text-slate-500 transition"
                >
                    <MoreHorizontal className="h-4 w-4" />
                </button>
            ),
        },
    ];

    const advancedFilters = (
        <div className="flex items-center gap-2">
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.status || ''}
                onChange={(e) => handleFilter('status', e.target.value)}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="active">Active</option>
                <option value="lead">Lead</option>
                <option value="paying">Paying</option>
                <option value="retained">Retained</option>
                <option value="churned">Churned</option>
            </select>
        </div>
    );

    return (
        <ERPLayout
            title={__('general.clients')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.clients_management')}</h2>
                    <p className="text-sm text-slate-500 mt-1">{__('general.manage_your_clients_and_wallet_balances')}</p>
                </div>
                <Link href={route('erp.clients.create')}>
                    <Button>
                        <UserPlus className="w-4 h-4 me-2" />
                        {__('general.add_client')}
                    </Button>
                </Link>
            </div>

            {stats && (
                <div className="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-800">{stats.total}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.total_clients')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-green-600">{stats.active}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.active')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-orange-600">{stats.leads}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.leads')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-blue-600">{stats.new_this_month}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.new_this_month')}</span>
                    </div>
                </div>
            )}

            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={clients.data}
                    pagination={clients}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__('general.no_clients_found')}
                    emptyDescription={__('general.try_adjusting_your_search_filters')}
                />
            </div>

            <ClientActionModal
                client={actionModalClient}
                isOpen={!!actionModalClient}
                onClose={() => setActionModalClient(null)}
                onDelete={handleDeleteClient}
                auth={auth}
            />
        </ERPLayout>
    );
}
