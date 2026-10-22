import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ClientActionsSheet from './ClientActionsSheet';
import ClientTierBadge from '@/Components/ClientTierBadge';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { 
    MoreHorizontal, Eye, Edit, LogIn, Key, Wallet, Users, User, FolderOpen, 
    FileText, ShieldCheck, CheckCircle2, Copy, Trophy, Crown, Medal, Award, TrendingUp 
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
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
import { useToast } from '@/Components/ui/use-toast';
import axios from 'axios';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Index({ 
    clients, 
    filters, 
    stats, 
    tabCounts = { customers: 0, leads: 0, top_clients: 0, staff_leaders: 0, affiliate_leaders: 0 },
    businessCurrency = 'EGP'
}) {
    const { toast } = useToast();
    const [selectedClient, setSelectedClient] = useState(null);
    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [isChangeRoleOpen, setIsChangeRoleOpen] = useState(false);
    const [selectedRoleUser, setSelectedRoleUser] = useState(null);
    const [selectedRole, setSelectedRole] = useState('client');
    const [resetPasswordState, setResetPasswordState] = useState({ isOpen: false, clientId: null, client: null, status: 'confirm', info: null });

    const activeTab = filters.type || 'customers';

    const handleTabChange = (type) => {
        if (type === activeTab) return;
        router.get(
            '/admin/users',
            { ...filters, type, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const copyToClipboard = (value, label) => {
        if (!value) return;
        const fallback = () => {
            const ta = document.createElement('textarea');
            ta.value = value;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) { /* ignore */ }
            document.body.removeChild(ta);
        };
        if (navigator?.clipboard?.writeText) {
            navigator.clipboard.writeText(value).catch(fallback);
        } else {
            fallback();
        }
        toast({ title: __('general.copied'), description: `${label} ${__('general.copied_to_clipboard')}` });
    };

    const handleSearch = (search) => {
        router.get(
            '/admin/users',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleFilter = (key, value) => {
        router.get(
            '/admin/users',
            { ...filters, [key]: value, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/users',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true }
        );
    };

    const handleLoginAs = (clientId) => {
        axios.post(`/admin/users/${clientId}/login-as`).then((response) => {
            if (response.data.token) {
                 localStorage.setItem('auth_token', response.data.token);
                 window.location.href = response.data.redirect_url || '/dashboard';
            }
        }).catch(() => {
            toast({
                title: __('general.error'),
                description: __('admin.user_impersonate_failed'),
                variant: "destructive"
            });
        });
    };

    const handleResetPassword = (client) => {
        setResetPasswordState({ isOpen: true, clientId: client.id, client: client, status: 'confirm', info: null });
    };

    const handleUpdateRoleSubmit = (e) => {
        e.preventDefault();
        if (!selectedRoleUser) return;
        router.post(`/admin/users/${selectedRoleUser.id}/update-role`, { role: selectedRole }, {
            onSuccess: () => {
                setIsChangeRoleOpen(false);
                setSelectedRoleUser(null);
                toast({
                    title: __("general.success"),
                    description: __("general.role_updated_successfully"),
                });
            },
            onError: () => {
                toast({
                    title: __("general.error"),
                    description: __("general.failed_to_update_role"),
                    variant: "destructive"
                });
            }
        });
    };



    const rankColumn = {
        key: 'rank',
        label: __('general.rank') || 'Rank',
        className: 'w-[75px] text-center',
        render: (client) => {
            const r = client._rank;
            if (r === 1) {
                return (
                    <div className="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 font-extrabold text-xs shadow-xs">
                        <Crown className="w-3.5 h-3.5 text-amber-600 fill-amber-500" />
                        <span>#1</span>
                    </div>
                );
            }
            if (r === 2) {
                return (
                    <div className="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-800 border border-slate-300 font-bold text-xs">
                        <Medal className="w-3.5 h-3.5 text-slate-600" />
                        <span>#2</span>
                    </div>
                );
            }
            if (r === 3) {
                return (
                    <div className="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-900 border border-orange-300 font-bold text-xs">
                        <Award className="w-3.5 h-3.5 text-orange-600" />
                        <span>#3</span>
                    </div>
                );
            }
            return <span className="font-mono text-xs font-semibold text-slate-500">#{r}</span>;
        }
    };

    const employeeColumn = {
        key: 'employee',
        label: activeTab === 'staff_leaders' ? (__('general.employee') || 'Staff') : (__('general.client') || 'Client'),
        sortable: true,
        render: (client) => (
            <div className="flex items-center gap-3">
                <Avatar className="h-10 w-10 border border-slate-200 shrink-0">
                    <AvatarImage src={client.avatar_url || ''} alt={client.name} />
                    <AvatarFallback className="bg-slate-50 text-slate-700">
                        <User className="h-5 w-5" />
                    </AvatarFallback>
                </Avatar>
                <button 
                    onClick={() => {
                        setSelectedClient(client);
                        setIsSheetOpen(true);
                    }}
                    className="flex flex-col text-start group min-w-0"
                >
                    <div className="flex items-center gap-2 flex-wrap">
                        {client.loyalty_tier?.badge_url && (
                            <img
                                src={client.loyalty_tier.badge_url}
                                alt=""
                                className="w-4 h-4 sm:w-5 sm:h-5 object-contain drop-shadow-xs shrink-0"
                                onError={(e) => {
                                    e.currentTarget.style.display = 'none';
                                }}
                            />
                        )}
                        <span className="font-semibold text-slate-900 group-hover:text-slate-900 transition-colors">
                            {client.name}
                        </span>
                        {client.loyalty_tier?.slug && (
                            <ClientTierBadge
                                tier={client.loyalty_tier?.slug}
                                title={client.loyalty_tier?.title}
                                size="xs"
                            />
                        )}
                    </div>
                    <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                        <span className="text-sm text-slate-500">
                            {client.email}
                        </span>
                        <span className="px-1.5 py-0.5 bg-slate-50 text-slate-900 rounded-full text-[9px] font-black uppercase border border-slate-50/50 tracking-wider">
                            {client.role || 'client'}
                        </span>
                        {client.loyalty_tier?.points_balance !== undefined && client.loyalty_tier?.points_balance > 0 && (
                            <span className="text-[10px] font-mono font-semibold text-amber-700 bg-amber-50 border border-amber-200/70 px-1.5 py-0.5 rounded">
                                {__('admin.loyalty_points_short', { count: client.loyalty_tier.points_balance.toLocaleString() })}
                            </span>
                        )}
                    </div>
                </button>
            </div>
        ),
    };

    const actionsColumn = {
        key: 'actions',
        label: '',
        className: 'w-[80px] text-end',
        render: (client) => (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                        <span className="sr-only">{__('general.open_menu')}</span>
                        <MoreHorizontal className="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                    <DropdownMenuGroup>
                        <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/users/${client.id}`}>
                                <Eye className="me-2 h-4 w-4" />{__('general.view_profile_1')}</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/users/${client.id}/edit`}>
                                <Edit className="me-2 h-4 w-4" />{__('general.edit_client')}</Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                    
                    <DropdownMenuSeparator />
                    
                    <DropdownMenuGroup>
                        <DropdownMenuItem onClick={() => handleLoginAs(client.id)}>
                            <LogIn className="me-2 h-4 w-4" />{__('general.login_as')}</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => handleResetPassword(client)}>
                            <Key className="me-2 h-4 w-4" />{__('general.reset_password')}</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => { setSelectedRoleUser(client); setSelectedRole(client.role || 'client'); setIsChangeRoleOpen(true); }}>
                            <ShieldCheck className="me-2 h-4 w-4" />{__('general.change_role')}</DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />
                    
                    <DropdownMenuGroup>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/users/${client.id}/referrals`}>
                                <Users className="me-2 h-4 w-4" /> {__('general.referrals')}</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/users/${client.id}/files`}>
                                <FolderOpen className="me-2 h-4 w-4" /> {__('general.files')}</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/admin/users/${client.id}/reports`}>
                                <FileText className="me-2 h-4 w-4" /> {__('general.reports')}</Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
        ),
    };

    let columns = [];
    if (activeTab === 'top_clients') {
        columns = [
            rankColumn,
            employeeColumn,
            {
                key: 'total_spent',
                label: __('general.total_spent') || 'Total Spent',
                sortable: true,
                render: (client) => (
                    <span className="font-bold font-mono text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-200 text-xs">
                        {formatCurrency(client.total_spent || 0, businessCurrency)}
                    </span>
                ),
            },
            {
                key: 'invoices_count',
                label: __('general.invoices') || 'Invoices',
                sortable: true,
                className: 'text-center',
                render: (client) => (
                    <span className="font-semibold text-slate-700 text-xs bg-slate-100 px-2 py-0.5 rounded">
                        {client.invoices_count ?? 0}
                    </span>
                ),
            },
            {
                key: 'loyalty_tier',
                label: __('general.loyalty_tier') || 'Tier',
                render: (client) => (
                    <div className="flex items-center gap-1.5 flex-wrap">
                        {client.loyalty_tier?.slug && (
                            <ClientTierBadge
                                tier={client.loyalty_tier?.slug}
                                title={client.loyalty_tier?.title}
                                size="xs"
                            />
                        )}
                        {client.loyalty_tier?.points_balance !== undefined && client.loyalty_tier.points_balance > 0 && (
                            <span className="text-[10px] font-mono text-amber-800 font-semibold bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                                {client.loyalty_tier.points_balance.toLocaleString()} pts
                            </span>
                        )}
                    </div>
                ),
            },
            {
                key: 'phone_number',
                label: __('general.phone'),
                render: (client) => <span className="text-slate-600 text-xs">{client.phone_number || client.phone || '—'}</span>,
            },
            {
                key: 'created_at',
                label: __('general.joined'),
                sortable: true,
                render: (client) => (
                    <span className="text-slate-600 text-xs whitespace-nowrap">
                        {new Date(client.created_at).toLocaleDateString()}
                    </span>
                ),
            },
            actionsColumn,
        ];
    } else if (activeTab === 'staff_leaders') {
        columns = [
            rankColumn,
            employeeColumn,
            {
                key: 'role',
                label: __('general.role') || 'Role',
                render: (client) => {
                    const isSenior = ['Admin', 'super_admin', 'tenant_admin'].includes(client.role);
                    const isMgr = client.role === 'Manager';
                    return (
                        <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border ${
                            isSenior ? 'bg-slate-900 text-white border-slate-900' : isMgr ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200'
                        }`}>
                            {client.role || 'staff'}
                        </span>
                    );
                },
            },
            {
                key: 'account_status',
                label: __('general.status'),
                render: (client) => (
                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${
                        client.account_status === 'blocked' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'
                    }`}>
                        {client.account_status === 'blocked' ? __('general.blocked') : __('general.active')}
                    </span>
                ),
            },
            {
                key: 'phone_number',
                label: __('general.phone'),
                render: (client) => <span className="text-slate-600 text-xs">{client.whatsapp_number || client.phone_number || client.phone || '—'}</span>,
            },
            {
                key: 'created_at',
                label: __('general.joined'),
                sortable: true,
                render: (client) => (
                    <span className="text-slate-600 text-xs whitespace-nowrap">
                        {new Date(client.created_at).toLocaleDateString()}
                    </span>
                ),
            },
            actionsColumn,
        ];
    } else if (activeTab === 'affiliate_leaders') {
        columns = [
            rankColumn,
            employeeColumn,
            {
                key: 'my_ref_users_count',
                label: __('general.referrals') || 'Referrals',
                sortable: true,
                render: (client) => (
                    <span className="inline-flex items-center gap-1.5 font-bold text-xs text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-md">
                        <Users className="w-3.5 h-3.5" />
                        {client.referrals_count || 0} {__('general.clients')}
                    </span>
                ),
            },
            {
                key: 'commission',
                label: __('admin.commission_rate') || 'Commission',
                render: (client) => (
                    <span className="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                        {client.affiliate_commission_percentage || 1}%
                    </span>
                ),
            },
            {
                key: 'wallet',
                label: __('erp.wallet_balance'),
                render: (client) => {
                    const balance = client.available_balance || 0;
                    return (
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border ${balance < 0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                            {formatCurrency(balance, client.currency)}
                        </span>
                    );
                },
            },
            {
                key: 'created_at',
                label: __('general.joined'),
                sortable: true,
                render: (client) => (
                    <span className="text-slate-600 text-xs whitespace-nowrap">
                        {new Date(client.created_at).toLocaleDateString()}
                    </span>
                ),
            },
            actionsColumn,
        ];
    } else {
        columns = [
            {
                key: 'id',
                label: __('general.id'),
                sortable: true,
                className: 'w-[60px]',
                render: (client) => <span className="text-slate-500 font-mono text-xs">#{client.id}</span>
            },
            employeeColumn,
            {
                key: 'phone_number',
                label: __('general.phone'),
                render: (client) => <span className="text-slate-600">{client.phone_number || client.phone || '—'}</span>,
            },
            {
                key: 'created_at',
                label: __('general.joined'),
                sortable: true,
                render: (client) => (
                    <span className="text-slate-600 whitespace-nowrap">
                        {new Date(client.created_at).toLocaleDateString()}
                    </span>
                ),
            },
            {
                key: 'wallet',
                label: __('erp.wallet_balance'),
                render: (client) => {
                    const balance = client.available_balance || 0;
                    return (
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border ${balance < 0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                            {formatCurrency(balance, client.currency)}
                        </span>
                    );
                },
            },
            actionsColumn,
        ];
    }

    const advancedFilters = (
        <div className="flex items-center gap-2">
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.role || ''}
                aria-label={__('general.all_roles')}
                onChange={(e) => handleFilter('role', e.target.value)}
            >
                <option value="">{__('general.all_roles')}</option>
                <option value="client">{__('general.clients')}</option>
                <option value="admin">{__('general.admins')}</option>
                <option value="employee">{__('general.employees')}</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.status || ''}
                aria-label={__('general.all_statuses')}
                onChange={(e) => handleFilter('status', e.target.value)}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="active">{__('general.active')}</option>
                <option value="blocked">{__('general.blocked')}</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.kyc || ''}
                aria-label={__('general.all_kyc')}
                onChange={(e) => handleFilter('kyc', e.target.value)}
            >
                <option value="">{__('general.all_kyc')}</option>
                <option value="verified">{__('general.verified')}</option>
                <option value="unverified">{__('general.unverified')}</option>
            </select>
        </div>
    );

    return (
        <AdminSidebarLayout title={__('general.clients')} header={__('admin.platform_users')}>
            {stats && (
                <div className="mb-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-800">{stats.total}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.total_users')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-green-600">{stats.active}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.active')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-red-600">{stats.blocked}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.blocked')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-900">{stats.kyc_verified}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.kyc_verified')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-900">{stats.new_this_week}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.new_this_week')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-900">{stats.new_this_month}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.new_this_month')}</span>
                    </div>
                </div>
            )}

            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 border-b border-slate-200 pb-4">
                <div className="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={() => handleTabChange('customers')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'customers'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <span>{__('general.customers')}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'customers' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.customers ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('leads')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'leads'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <span>{__('general.leads')}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'leads' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.leads ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('top_clients')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'top_clients'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <Trophy className="h-4 w-4 text-amber-500" />
                        <span>{__('general.top_clients') || 'Top Clients'}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'top_clients' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.top_clients ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('staff_leaders')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'staff_leaders'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <ShieldCheck className="h-4 w-4 text-blue-500" />
                        <span>{__('general.team_leaderships') || 'Team Leadership'}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'staff_leaders' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.staff_leaders ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('affiliate_leaders')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'affiliate_leaders'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <Users className="h-4 w-4 text-indigo-500" />
                        <span>{__('general.affiliate_leaders') || 'Affiliate Leaders'}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'affiliate_leaders' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.affiliate_leaders ?? 0}
                        </span>
                    </button>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                    <Link href="/admin/users/bulk-create">
                        <Button variant="outline">{__('general.bulk_create')}</Button>
                    </Link>
                    <Link href="/admin/users/create">
                        <Button>{__('general.create_account')}</Button>
                    </Link>
                </div>
            </div>

            {/* Top 3 Podium for Top Clients */}
            {activeTab === 'top_clients' && clients.data?.length >= 3 && (clients.current_page || 1) === 1 && (
                <div className="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Rank 1 */}
                    {clients.data[0] && (
                        <div className="relative overflow-hidden rounded-xl border border-amber-300 bg-gradient-to-b from-amber-50/80 via-white to-white p-5 shadow-xs flex flex-col justify-between">
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3 min-w-0">
                                    <Avatar className="h-12 w-12 border-2 border-amber-400 shrink-0">
                                        <AvatarImage src={clients.data[0].avatar_url || ''} alt={clients.data[0].name} />
                                        <AvatarFallback className="bg-amber-100 text-amber-900 font-bold">{clients.data[0].name?.charAt(0)}</AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-extrabold mb-1">
                                            <Crown className="w-3.5 h-3.5 text-amber-600 fill-amber-500" />
                                            <span>#1 Top Spender</span>
                                        </div>
                                        <h4 className="font-bold text-slate-900 text-sm truncate">{clients.data[0].name}</h4>
                                        <p className="text-xs text-slate-500 truncate">{clients.data[0].email}</p>
                                    </div>
                                </div>
                                {clients.data[0].loyalty_tier?.slug && (
                                    <ClientTierBadge tier={clients.data[0].loyalty_tier.slug} title={clients.data[0].loyalty_tier.title} size="xs" />
                                )}
                            </div>
                            <div className="mt-4 pt-3 border-t border-amber-100 flex items-center justify-between text-xs">
                                <div>
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.total_spent')}</span>
                                    <p className="text-base font-extrabold font-mono text-emerald-700">
                                        {formatCurrency(clients.data[0].total_spent || 0, businessCurrency)}
                                    </p>
                                </div>
                                <div className="text-end">
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.invoices')}</span>
                                    <p className="text-sm font-bold text-slate-800">{clients.data[0].invoices_count || 0}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Rank 2 */}
                    {clients.data[1] && (
                        <div className="relative overflow-hidden rounded-xl border border-slate-300 bg-gradient-to-b from-slate-100/80 via-white to-white p-5 shadow-xs flex flex-col justify-between">
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3 min-w-0">
                                    <Avatar className="h-12 w-12 border-2 border-slate-300 shrink-0">
                                        <AvatarImage src={clients.data[1].avatar_url || ''} alt={clients.data[1].name} />
                                        <AvatarFallback className="bg-slate-100 text-slate-800 font-bold">{clients.data[1].name?.charAt(0)}</AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-200 text-slate-800 border border-slate-300 text-[11px] font-bold mb-1">
                                            <Medal className="w-3.5 h-3.5 text-slate-600" />
                                            <span>#2 Runner Up</span>
                                        </div>
                                        <h4 className="font-bold text-slate-900 text-sm truncate">{clients.data[1].name}</h4>
                                        <p className="text-xs text-slate-500 truncate">{clients.data[1].email}</p>
                                    </div>
                                </div>
                                {clients.data[1].loyalty_tier?.slug && (
                                    <ClientTierBadge tier={clients.data[1].loyalty_tier.slug} title={clients.data[1].loyalty_tier.title} size="xs" />
                                )}
                            </div>
                            <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.total_spent')}</span>
                                    <p className="text-base font-extrabold font-mono text-emerald-700">
                                        {formatCurrency(clients.data[1].total_spent || 0, businessCurrency)}
                                    </p>
                                </div>
                                <div className="text-end">
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.invoices')}</span>
                                    <p className="text-sm font-bold text-slate-800">{clients.data[1].invoices_count || 0}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Rank 3 */}
                    {clients.data[2] && (
                        <div className="relative overflow-hidden rounded-xl border border-orange-300 bg-gradient-to-b from-orange-50/80 via-white to-white p-5 shadow-xs flex flex-col justify-between">
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3 min-w-0">
                                    <Avatar className="h-12 w-12 border-2 border-orange-300 shrink-0">
                                        <AvatarImage src={clients.data[2].avatar_url || ''} alt={clients.data[2].name} />
                                        <AvatarFallback className="bg-orange-100 text-orange-900 font-bold">{clients.data[2].name?.charAt(0)}</AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-100 text-orange-900 border border-orange-300 text-[11px] font-bold mb-1">
                                            <Award className="w-3.5 h-3.5 text-orange-600" />
                                            <span>#3 Third Place</span>
                                        </div>
                                        <h4 className="font-bold text-slate-900 text-sm truncate">{clients.data[2].name}</h4>
                                        <p className="text-xs text-slate-500 truncate">{clients.data[2].email}</p>
                                    </div>
                                </div>
                                {clients.data[2].loyalty_tier?.slug && (
                                    <ClientTierBadge tier={clients.data[2].loyalty_tier.slug} title={clients.data[2].loyalty_tier.title} size="xs" />
                                )}
                            </div>
                            <div className="mt-4 pt-3 border-t border-orange-100 flex items-center justify-between text-xs">
                                <div>
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.total_spent')}</span>
                                    <p className="text-base font-extrabold font-mono text-emerald-700">
                                        {formatCurrency(clients.data[2].total_spent || 0, businessCurrency)}
                                    </p>
                                </div>
                                <div className="text-end">
                                    <span className="text-[11px] text-slate-500 font-medium">{__('general.invoices')}</span>
                                    <p className="text-sm font-bold text-slate-800">{clients.data[2].invoices_count || 0}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Top 3 Podium for Affiliate Leaders */}
            {activeTab === 'affiliate_leaders' && clients.data?.length >= 3 && (clients.current_page || 1) === 1 && (
                <div className="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    {clients.data.slice(0, 3).map((affiliate, idx) => {
                        const rankStyles = [
                            { border: 'border-amber-300', bg: 'from-amber-50/80', badgeBg: 'bg-amber-100 text-amber-900 border-amber-300', icon: <Crown className="w-3.5 h-3.5 text-amber-600 fill-amber-500" />, title: '#1 Top Affiliate' },
                            { border: 'border-slate-300', bg: 'from-slate-100/80', badgeBg: 'bg-slate-200 text-slate-800 border-slate-300', icon: <Medal className="w-3.5 h-3.5 text-slate-600" />, title: '#2 Runner Up' },
                            { border: 'border-orange-300', bg: 'from-orange-50/80', badgeBg: 'bg-orange-100 text-orange-900 border-orange-300', icon: <Award className="w-3.5 h-3.5 text-orange-600" />, title: '#3 Third Place' }
                        ][idx];
                        return (
                            <div key={affiliate.id} className={`relative overflow-hidden rounded-xl border ${rankStyles.border} bg-gradient-to-b ${rankStyles.bg} via-white to-white p-5 shadow-xs flex flex-col justify-between`}>
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-center gap-3 min-w-0">
                                        <Avatar className="h-12 w-12 border-2 border-slate-200 shrink-0">
                                            <AvatarImage src={affiliate.avatar_url || ''} alt={affiliate.name} />
                                            <AvatarFallback className="bg-slate-100 font-bold">{affiliate.name?.charAt(0)}</AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0">
                                            <div className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border text-[11px] font-bold mb-1 ${rankStyles.badgeBg}`}>
                                                {rankStyles.icon}
                                                <span>{rankStyles.title}</span>
                                            </div>
                                            <h4 className="font-bold text-slate-900 text-sm truncate">{affiliate.name}</h4>
                                            <p className="text-xs text-slate-500 truncate">{affiliate.email}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                    <div>
                                        <span className="text-[11px] text-slate-500 font-medium">{__('general.referrals')}</span>
                                        <p className="text-base font-extrabold text-indigo-700 flex items-center gap-1">
                                            <Users className="w-4 h-4" />
                                            {affiliate.referrals_count || 0} {__('general.clients')}
                                        </p>
                                    </div>
                                    <div className="text-end">
                                        <span className="text-[11px] text-slate-500 font-medium">{__('admin.commission_rate') || 'Commission'}</span>
                                        <p className="text-sm font-bold text-slate-800">{affiliate.affiliate_commission_percentage || 1}%</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
            
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={(clients.data || []).map((client, index) => ({
                        ...client,
                        _rank: ((clients.current_page || 1) - 1) * (clients.per_page || 25) + index + 1,
                    }))}
                    pagination={clients}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={
                        activeTab === 'customers' ? __('admin.no_customers_found') :
                        activeTab === 'leads' ? __('admin.no_leads_found') :
                        activeTab === 'top_clients' ? (__('general.no_top_clients_found') || 'No top clients found') :
                        activeTab === 'staff_leaders' ? (__('general.no_staff_found') || 'No staff members found') :
                        (__('general.no_affiliates_found') || 'No affiliate leaders found')
                    }
                    emptyDescription={__('general.try_adjusting_your_search_filters')}
                />
            </div>
            
            <ClientActionsSheet 
                client={selectedClient} 
                isOpen={isSheetOpen} 
                onClose={() => setIsSheetOpen(false)}
                onLoginAs={(id) => {
                    setIsSheetOpen(false);
                    handleLoginAs(id);
                }}
                onResetPassword={(id) => {
                    setIsSheetOpen(false);
                    handleResetPassword(selectedClient);
                }}
                onChangeRole={(user) => {
                    setSelectedRoleUser(user);
                    setSelectedRole(user.role || 'client');
                    setIsChangeRoleOpen(true);
                }}
            />

            <Dialog open={isChangeRoleOpen} onOpenChange={setIsChangeRoleOpen}>
                <DialogContent>
                    <form onSubmit={handleUpdateRoleSubmit}>
                        <DialogHeader>
                            <DialogTitle>{__("general.change_user_role")}</DialogTitle>
                            <DialogDescription>
                                {__("general.change_direct_permissions_and_role_access_level_for_this_user")}
                            </DialogDescription>
                        </DialogHeader>
                        {selectedRoleUser && (
                            <div className="py-4 space-y-4">
                                <p className="text-sm text-slate-600">
                                    {__("general.changing_role_for")} <strong className="text-slate-900">{selectedRoleUser.name}</strong>
                                </p>
                                <div>
                                    <Label>{__("general.select_role")}</Label>
                                    <select 
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 mt-2"
                                        value={selectedRole}
                                        onChange={e => setSelectedRole(e.target.value)}
                                        required
                                    >
                                        <option value="client">{__("erp.client")}</option>
                                        <option value="software_reseller">{__("general.software_reseller")}</option>
                                        <option value="admin">{__("admin.admin")}</option>
                                        <option value="manager">{__("general.manager")}</option>
                                        <option value="employee">{__("general.employee")}</option>
                                        <option value="moderator">{__("general.moderator")}</option>
                                    </select>
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsChangeRoleOpen(false)}>{__("general.cancel")}</Button>
                            <Button type="submit">{__("general.update_role")}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Reset Password Dialog */}
            <Dialog 
                open={resetPasswordState.isOpen} 
                onOpenChange={(open) => {
                    if (!open && resetPasswordState.status !== 'loading') {
                        setResetPasswordState({ isOpen: false, clientId: null, client: null, status: 'confirm', info: null });
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    {resetPasswordState.status === 'confirm' && (
                        <>
                            <DialogHeader>
                                <DialogTitle>{__("general.reset_password")}</DialogTitle>
                                <DialogDescription>
                                    {__('general.are_you_sure_you_want_to_reset_this_user')}</DialogDescription>
                            </DialogHeader>
                            <DialogFooter className="mt-4">
                                <Button variant="ghost" onClick={() => setResetPasswordState(prev => ({ ...prev, isOpen: false }))}>
                                    {__("general.cancel")}
                                </Button>
                                <Button 
                                    variant="destructive"
                                    onClick={() => {
                                        setResetPasswordState(prev => ({ ...prev, status: 'loading' }));
                                        axios.post(`/admin/users/${resetPasswordState.clientId}/reset-password`).then((response) => {
                                            setResetPasswordState(prev => ({ ...prev, status: 'success', info: {
                                                message: response.data?.message,
                                                email: response.data?.email,
                                                name: response.data?.name,
                                                password: response.data?.password,
                                                loginUrl: response.data?.login_url,
                                            } }));
                                        }).catch(() => {
                                            toast({
                                                title: __('general.error'),
                                                description: __('admin.user_reset_password_failed'),
                                                variant: "destructive"
                                            });
                                            setResetPasswordState(prev => ({ ...prev, isOpen: false }));
                                        });
                                    }}
                                >
                                    {__('general.yes_reset_password')}</Button>
                            </DialogFooter>
                        </>
                    )}

                    {resetPasswordState.status === 'loading' && (
                        <div className="py-12 flex flex-col items-center justify-center space-y-4">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900"></div>
                            <p className="text-slate-500 text-sm">{__('general.resetting_password')}</p>
                        </div>
                    )}

                    {resetPasswordState.status === 'success' && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="text-green-600 flex items-center gap-2">
                                    <CheckCircle2 className="h-5 w-5" />
                                    {resetPasswordState.info?.message || __('general.password_reset_email_sent_with_new_password')}
                                </DialogTitle>
                                <DialogDescription className="pt-2 text-slate-600">
                                    {__('general.share_credentials_with_user')}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="my-2 space-y-3">
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {__('general.credentials_ready_to_send')}
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                    <div className="min-w-0">
                                        <div className="text-[11px] uppercase tracking-wide text-slate-500">{__('general.email')}</div>
                                        <div className="truncate font-mono text-sm text-slate-800">{resetPasswordState.info?.email}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.email, __('general.email'))} aria-label={__('admin.copy_value', { label: __('general.email') })}>
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                                    <div className="min-w-0 flex-1">
                                        <div className="text-[11px] uppercase tracking-wide text-amber-700">{__('general.new_password')}</div>
                                        <div className="truncate font-mono text-sm font-semibold text-amber-900">{resetPasswordState.info?.password}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.password, __('general.new_password'))} aria-label={__('admin.copy_value', { label: __('general.new_password') })}>
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                    <div className="min-w-0 flex-1">
                                        <div className="text-[11px] uppercase tracking-wide text-slate-500">{__('general.login_url')}</div>
                                        <div className="truncate font-mono text-sm text-slate-800">{resetPasswordState.info?.loginUrl}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.loginUrl, __('general.login_url'))} aria-label={__('admin.copy_value', { label: __('general.login_url') })}>
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button className="w-full sm:w-auto" onClick={() => setResetPasswordState({ isOpen: false, clientId: null, client: null, status: 'confirm', info: null })}>
                                    {__('general.done')}</Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

