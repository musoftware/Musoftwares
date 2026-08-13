import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ClientActionsSheet from './ClientActionsSheet';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { MoreHorizontal, Eye, Edit, LogIn, Key, Wallet, Users, User, FolderOpen, FileText, ShieldCheck, CheckCircle2, Copy } from 'lucide-react';
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

export default function Index({ clients, filters, stats, tabCounts = { customers: 0, leads: 0 } }) {
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
        toast({ title: __('general.copied') || 'Copied', description: `${label} ${__('general.copied_to_clipboard') || 'copied to clipboard.'}` });
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
                title: "Error",
                description: "Failed to login as client.",
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



    const columns = [
        {
            key: 'id',
            label: 'ID',
            sortable: true,
            className: 'w-[60px]',
            render: (client) => <span className="text-slate-500 font-mono text-xs">#{client.id}</span>
        },
        {
            key: 'employee',
            label: 'EMPLOYEE',
            sortable: true,
            render: (client) => (
                <div className="flex items-center gap-3">
                    <Avatar className="h-10 w-10 border border-slate-200">
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
                        className="flex flex-col text-start group"
                    >
                        <span className="font-semibold text-slate-900 group-hover:text-slate-900 transition-colors">
                            {client.name}
                        </span>
                        <div className="flex items-center gap-2 mt-0.5">
                            <span className="text-sm text-slate-500">
                                {client.email}
                            </span>
                            <span className="px-1.5 py-0.5 bg-slate-50 text-slate-900 rounded-full text-[9px] font-black uppercase border border-slate-50/50 tracking-wider">
                                {client.role || 'client'}
                            </span>
                        </div>
                    </button>
                </div>
            ),
        },
        {
            key: 'phone_number',
            label: 'Phone',
            render: (client) => <span className="text-slate-600">{client.phone_number || client.phone || '—'}</span>,
        },
        {
            key: 'created_at',
            label: 'Joined',
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
        {
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
        },
    ];

    const advancedFilters = (
        <div className="flex items-center gap-2">
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.role || ''}
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
                onChange={(e) => handleFilter('status', e.target.value)}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="active">{__('general.active')}</option>
                <option value="blocked">{__('general.blocked')}</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                value={filters.kyc || ''}
                onChange={(e) => handleFilter('kyc', e.target.value)}
            >
                <option value="">{__('general.all_kyc')}</option>
                <option value="verified">{__('general.verified')}</option>
                <option value="unverified">{__('general.unverified')}</option>
            </select>
        </div>
    );

    return (
        <AdminSidebarLayout title={__('general.clients')} header="Platform Users">
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

            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-slate-200 pb-4">
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => handleTabChange('customers')}
                        className={`flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'customers'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <span>{__('general.customers') || 'Customers'}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'customers' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.customers ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('leads')}
                        className={`flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all ${
                            activeTab === 'leads'
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                        }`}
                    >
                        <span>{__('general.leads') || 'Leads'}</span>
                        <span className={`px-2 py-0.5 text-xs rounded-full font-bold ${
                            activeTab === 'leads' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tabCounts.leads ?? 0}
                        </span>
                    </button>
                </div>

                <div className="flex items-center gap-2">
                    <Link href="/admin/users/bulk-create">
                        <Button variant="outline">{__('general.bulk_create') || 'Bulk Create'}</Button>
                    </Link>
                    <Link href="/admin/users/create">
                        <Button>{__('general.create_account') || 'Create User'}</Button>
                    </Link>
                </div>
            </div>
            
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={clients.data}
                    pagination={clients}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={activeTab === 'customers' ? "No customers found" : "No leads found"}
                    emptyDescription="Try adjusting your search filters."
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
                                                title: "Error",
                                                description: "Failed to reset password.",
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
                                    {resetPasswordState.info?.message || __('general.password_reset_email_sent_with_new_password') || 'A new password has been generated and emailed to the user.'}
                                </DialogTitle>
                                <DialogDescription className="pt-2 text-slate-600">
                                    {__('general.share_credentials_with_user') || 'You can copy these credentials and share them with the user manually if needed.'}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="my-2 space-y-3">
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {__('general.credentials_ready_to_send') || 'Credentials ready to send'}
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                    <div className="min-w-0">
                                        <div className="text-[11px] uppercase tracking-wide text-slate-500">{__('general.email') || 'Email'}</div>
                                        <div className="truncate font-mono text-sm text-slate-800">{resetPasswordState.info?.email}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.email, 'Email')}>
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                                    <div className="min-w-0 flex-1">
                                        <div className="text-[11px] uppercase tracking-wide text-amber-700">{__('general.new_password') || 'New password'}</div>
                                        <div className="truncate font-mono text-sm font-semibold text-amber-900">{resetPasswordState.info?.password}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.password, 'Password')}>
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="flex items-center justify-between gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                    <div className="min-w-0 flex-1">
                                        <div className="text-[11px] uppercase tracking-wide text-slate-500">{__('general.login_url') || 'Login URL'}</div>
                                        <div className="truncate font-mono text-sm text-slate-800">{resetPasswordState.info?.loginUrl}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => copyToClipboard(resetPasswordState.info?.loginUrl, 'Login URL')}>
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

