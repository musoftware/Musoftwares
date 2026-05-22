import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { MoreHorizontal, Eye, Edit, LogIn, Key, Wallet, Users, FolderOpen, FileText } from 'lucide-react';
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

export default function Index({ clients, filters }) {
    const { toast } = useToast();

    const handleSearch = (search) => {
        router.get(
            '/admin/clients',
            { search },
            { preserveState: true, replace: true }
        );
    };

    const handleLoginAs = (clientId) => {
        axios.post(`/admin/clients/${clientId}/login-as`).then((response) => {
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
            axios.post(`/admin/clients/${clientId}/reset-password`).then((response) => {
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

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
        }).format(amount || 0);
    };

    const columns = [
        {
            key: 'name',
            label: 'Name',
            render: (client) => (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900">{client.name}</span>
                    {client.full_name && <span className="text-xs text-slate-500">{client.full_name}</span>}
                </div>
            ),
        },
        {
            key: 'email',
            label: 'Email',
            render: (client) => <span className="text-slate-600">{client.email}</span>,
        },
        {
            key: 'phone_number',
            label: 'Phone',
            render: (client) => <span className="text-slate-600">{client.phone_number || client.phone || '—'}</span>,
        },
        {
            key: 'wallet',
            label: 'Wallet Balance',
            render: (client) => (
                <span className="font-medium">
                    {client.wallet
                        ? formatCurrency(client.wallet.balance, client.wallet.currency)
                        : formatCurrency(0, 'USD')}
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
                                <Link href={`/admin/clients/${client.id}`}>
                                    <Eye className="mr-2 h-4 w-4" /> View Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/edit`}>
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
                                <Link href={`/admin/clients/${client.id}/referrals`}>
                                    <Users className="mr-2 h-4 w-4" /> Referrals
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/files`}>
                                    <FolderOpen className="mr-2 h-4 w-4" /> Files
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/reports`}>
                                    <FileText className="mr-2 h-4 w-4" /> Reports
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <AdminSidebarLayout title="Clients" header="Platform Users">
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={clients.data}
                    pagination={clients}
                    filters={filters}
                    onSearch={handleSearch}
                    emptyTitle="No clients found"
                    emptyDescription="Try adjusting your search filters."
                />
            </div>
        </AdminSidebarLayout>
    );
}
