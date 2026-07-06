import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { ArrowLeft, CheckCircle2, Clock, Settings, User, Unlink, Trash2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import Pagination from '@/Components/Pagination';
import { formatMoney } from '@/lib/utils';
import { format } from 'date-fns';
import { __ } from '@/lib/i18n';

export default function Referrals({ client, referrals }) {
    const currency = client.currency;

    const getInitials = (name) => {
        if (!name) return 'U';
        return name.substring(0, 2).toUpperCase();
    };

    const handleUnlink = (referralId) => {
        if (confirm(__('general.confirm_unlink_referral'))) {
            router.delete(`/admin/users/${client.id}/referrals/${referralId}/unlink`);
        }
    };

    const handleDelete = (referralId) => {
        if (confirm(__('general.confirm_delete_user_permanently'))) {
            router.delete(`/admin/users/${referralId}`);
        }
    };

    return (
        <AdminSidebarLayout title={__('general.user_referrals')} header="User Referrals">
            <Head title={`Referrals - ${client.name}`} />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={`/admin/users/${client.id}`} className="text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="h-6 w-6" />
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">{__('general.manage_referrals')}</h2>
                            <p className="text-sm text-gray-500">View and manage users referred by {client.name}</p>
                        </div>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{__('general.referred_users')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {referrals.data.length > 0 ? (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-gray-50">
                                            <TableHead className="w-16">ID</TableHead>
                                            <TableHead>{__('general.user')}</TableHead>
                                            <TableHead>{__('general.joined_date')}</TableHead>
                                            <TableHead>{__('general.email')}</TableHead>
                                            <TableHead>{__('general.commission_earned')}</TableHead>
                                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {referrals.data.map((referral) => (
                                            <TableRow key={referral.id}>
                                                <TableCell className="text-muted-foreground">
                                                    #{referral.id}
                                                </TableCell>
                                                <TableCell>
                                                    <Link href={`/admin/users/${referral.id}`} className="flex items-center hover:bg-gray-50 p-1 -m-1 rounded">
                                                        <div className="h-9 w-9 rounded-full bg-slate-900 text-white flex items-center justify-center text-sm font-bold me-3">
                                                            {getInitials(referral.name)}
                                                        </div>
                                                        <div>
                                                            <div className="font-medium text-slate-900">{referral.name}</div>
                                                            <div className="text-xs text-slate-500">{referral.email}</div>
                                                        </div>
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {referral.created_at ? format(new Date(referral.created_at), 'MMM dd, yyyy') : '--'}
                                                </TableCell>
                                                <TableCell>
                                                    {referral.email_verified_at ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <CheckCircle2 className="w-3 h-3 me-1" />
                                                            {__('general.verified')}</span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <Clock className="w-3 h-3 me-1" />
                                                            {__('general.unverified')}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(referral.commission_earned || 0, currency)}
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="outline" size="sm">
                                                                <Settings className="w-4 h-4 me-2" />
                                                                {__('general.actions')}</Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/admin/users/${referral.id}`}>
                                                                    <User className="me-2 h-4 w-4" />{__('general.view_profile_1')}</Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => handleUnlink(referral.id)} className="text-yellow-600 focus:text-yellow-600">
                                                                <Unlink className="me-2 h-4 w-4" />{__('general.remove_referral')}</DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => handleDelete(referral.id)} className="text-red-600 focus:text-red-600">
                                                                <Trash2 className="me-2 h-4 w-4" />{__('general.delete_user_account')}</DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                {referrals.last_page > 1 && (
                                    <div className="p-4 border-t">
                                        <Pagination links={referrals.links} />
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="p-12 text-center">
                                <User className="mx-auto h-12 w-12 text-gray-300 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900">{__('general.no_referrals_found')}</h3>
                                <p className="mt-1 text-sm text-gray-500">{__('general.this_user_hasn_t_referred_anyone_yet')}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

