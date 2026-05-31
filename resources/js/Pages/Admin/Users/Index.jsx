import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ClientActionsSheet from './ClientActionsSheet';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { MoreHorizontal, Eye, Edit, LogIn, Key, Wallet, Users, User, FolderOpen, FileText } from 'lucide-react';
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
                        <span className="text-sm text-slate-500">
                            {client.email}
                        </span>
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
            label: 'Wallet Balance',
            render: (client) => (
                <span className="font-medium">
                    {formatCurrency(client.available_balance || 0, client.currency || 'USD')}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[80px] text-right',
            render: (client) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}`}>
                                    <Eye className="mr-2 h-4 w-4" /> View Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit Client
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        
                        <DropdownMenuSeparator />
                        
                        <DropdownMenuGroup>
                            <DropdownMenuItem onClick={() => handleLoginAs(client.id)}>
                                <LogIn className="mr-2 h-4 w-4" /> Login As
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleResetPassword(client.id)}>
                                <Key className="mr-2 h-4 w-4" /> Reset Password
                            </DropdownMenuItem>
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
                <option value="">All Roles</option>
                <option value="user">Users</option>
                <option value="admin">Admins</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.status || ''}
                onChange={(e) => handleFilter('status', e.target.value)}
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="blocked">Blocked</option>
            </select>
            <select 
                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                value={filters.kyc || ''}
                onChange={(e) => handleFilter('kyc', e.target.value)}
            >
                <option value="">All KYC</option>
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
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">Total Users</span>
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
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">KYC Verified</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-blue-600">{stats.new_this_week}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">New This Week</span>
                    </div>
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                        <span className="text-2xl font-semibold text-purple-600">{stats.new_this_month}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">New This Month</span>
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
            />
        </AdminSidebarLayout>
    );
}
