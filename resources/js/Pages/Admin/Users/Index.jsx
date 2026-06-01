import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ClientActionsSheet from './ClientActionsSheet';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { MoreHorizontal, Eye, Edit, LogIn, Key, Wallet, Users, User, FolderOpen, FileText, ShieldCheck } from 'lucide-react';
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

export default function Index({ clients, filters, stats }) {
    const { toast } = useToast();
    const [selectedClient, setSelectedClient] = useState(null);
    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [isChangeRoleOpen, setIsChangeRoleOpen] = useState(false);
    const [selectedRoleUser, setSelectedRoleUser] = useState(null);
    const [selectedRole, setSelectedRole] = useState('client');

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

    const handleResetPassword = (clientId) => {
        if (confirm("Are you sure you want to reset this user's password?")) {
            axios.post(`/admin/users/${clientId}/reset-password`).then((response) => {
                alert(`Password reset successfully.\nNew password: ${response.data.new_password}`);
            }).catch(() => {
                toast({
                    title: "Error",
                    description: "Failed to reset password.",
                    variant: "destructive"
                });
            });
        }
    };

    const handleUpdateRoleSubmit = (e) => {
        e.preventDefault();
        if (!selectedRoleUser) return;
        router.post(`/admin/users/${selectedRoleUser.id}/update-role`, { role: selectedRole }, {
            onSuccess: () => {
                setIsChangeRoleOpen(false);
                setSelectedRoleUser(null);
                toast({
                    title: __("Success"),
                    description: __("general.role_updated_successfully"),
                });
            },
            onError: () => {
                toast({
                    title: __("Error"),
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
                        <AvatarFallback className="bg-blue-50 text-blue-500">
                            <User className="h-5 w-5" />
                        </AvatarFallback>
                    </Avatar>
                    <button 
                        onClick={() => {
                            setSelectedClient(client);
                            setIsSheetOpen(true);
                        }}
                        className="flex flex-col text-left group"
                    >
                        <span className="font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">
                            {client.name}
                        </span>
                        <div className="flex items-center gap-2 mt-0.5">
                            <span className="text-sm text-slate-500">
                                {client.email}
                            </span>
                            <span className="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-[9px] font-black uppercase border border-indigo-100/50 tracking-wider">
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
            label: __('Wallet Balance'),
            render: (client) => {
                const balance = client.available_balance || 0;
                return (
                    <span className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border ${balance < 0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                        {formatCurrency(balance, client.currency || 'USD')}
                    </span>
                );
            },
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[80px] text-right',
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
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}`}>
                                    <Eye className="mr-2 h-4 w-4" />{__('general.view_profile_1')}</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" />{__('general.edit_client')}</Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        
                        <DropdownMenuSeparator />
                        
                        <DropdownMenuGroup>
                            <DropdownMenuItem onClick={() => handleLoginAs(client.id)}>
                                <LogIn className="mr-2 h-4 w-4" />{__('general.login_as')}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleResetPassword(client.id)}>
                                <Key className="mr-2 h-4 w-4" />{__('general.reset_password')}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setSelectedRoleUser(client); setSelectedRole(client.role || 'client'); setIsChangeRoleOpen(true); }}>
                                <ShieldCheck className="mr-2 h-4 w-4" />{__('general.change_role')}</DropdownMenuItem>
                        </DropdownMenuGroup>

                        <DropdownMenuSeparator />
                        
                        <DropdownMenuGroup>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/referrals`}>
                                    <Users className="mr-2 h-4 w-4" /> Referrals
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/files`}>
                                    <FolderOpen className="mr-2 h-4 w-4" /> Files
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/reports`}>
                                    <FileText className="mr-2 h-4 w-4" /> Reports
                                </Link>
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
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.role || ''}
                onChange={(e) => handleFilter('role', e.target.value)}
            >
                <option value="">{__('general.all_roles')}</option>
                <option value="client">Clients</option>
                <option value="admin">Admins</option>
                <option value="employee">Employees</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.status || ''}
                onChange={(e) => handleFilter('status', e.target.value)}
            >
                <option value="">{__('general.all_statuses')}</option>
                <option value="active">Active</option>
                <option value="blocked">Blocked</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.kyc || ''}
                onChange={(e) => handleFilter('kyc', e.target.value)}
            >
                <option value="">{__('general.all_kyc')}</option>
                <option value="verified">Verified</option>
                <option value="unverified">Unverified</option>
            </select>
        </div>
    );

    return (
        <AdminSidebarLayout title="Clients" header="Platform Users">
            {stats && (
                <div className="mb-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-slate-800">{stats.total}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.total_users')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-green-600">{stats.active}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">Active</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-red-600">{stats.blocked}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">Blocked</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-indigo-600">{stats.kyc_verified}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.kyc_verified')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-blue-600">{stats.new_this_week}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{__('general.new_this_week')}</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-purple-600">{stats.new_this_month}</span>
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
                    emptyTitle="No clients found"
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
                    handleResetPassword(id);
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
                                        <option value="client">{__("Client")}</option>
                                        <option value="admin">{__("Admin")}</option>
                                        <option value="manager">{__("general.manager")}</option>
                                        <option value="employee">{__("Employee")}</option>
                                        <option value="moderator">{__("general.moderator")}</option>
                                    </select>
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsChangeRoleOpen(false)}>{__("Cancel")}</Button>
                            <Button type="submit">{__("general.update_role")}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
