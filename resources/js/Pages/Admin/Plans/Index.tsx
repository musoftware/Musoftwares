import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { formatMoney } from '@/lib/utils';
import { Eye, Calendar, DollarSign, Package } from 'lucide-react';
import Pagination from '@/Components/Pagination';

export default function Index({ users, business_currency }) {
    const [selectedUser, setSelectedUser] = useState(null);

    return (
        <AdminSidebarLayout title={__('admin.subscribers')} header={__('admin.subscribers')}>
            <Head title={__('admin.subscribers')} />

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50">
                            <tr>
                                <th className="p-4 font-medium text-gray-600">{__('admin.user')}</th>
                                <th className="p-4 font-medium text-gray-600">{__('admin.total_monthly')}</th>
                                <th className="p-4 font-medium text-gray-600">{__('admin.first_expire_date')}</th>
                                <th className="p-4 font-medium text-gray-600 text-right">{__('admin.actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr key={user.id} className="border-b hover:bg-gray-50">
                                    <td className="p-4">
                                        <div className="flex items-center gap-3">
                                            <img
                                                src={user.avatar_url}
                                                alt={user.name}
                                                className="h-10 w-10 rounded-full bg-gray-100 object-cover"
                                            />
                                            <div>
                                                <div className="font-medium text-gray-900">{user.name}</div>
                                                <div className="text-xs text-gray-500">{user.email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-4 font-medium text-gray-900">
                                        {formatMoney(user.total_expected_monthly, business_currency)}
                                    </td>
                                    <td className="p-4">
                                        {user.first_expire_date ? (
                                            <span className="flex items-center gap-1.5 text-gray-700">
                                                <Calendar className="h-4 w-4 text-gray-400" />
                                                {user.first_expire_date}
                                            </span>
                                        ) : (
                                            <span className="text-gray-400">--</span>
                                        )}
                                    </td>
                                    <td className="p-4 text-right">
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    className="gap-2"
                                                    onClick={() => setSelectedUser(user)}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    {__('admin.view_services')}
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent className="sm:max-w-md max-h-[80vh] overflow-y-auto">
                                                <DialogHeader>
                                                    <DialogTitle>{__('admin.user_services', { name: selectedUser?.name })}</DialogTitle>
                                                </DialogHeader>
                                                <div className="space-y-4 py-4">
                                                    {selectedUser?.services.map((service) => (
                                                        <div key={service.id} className="flex items-center justify-between p-3 border rounded-lg bg-gray-50">
                                                            <div className="flex items-center gap-3">
                                                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white border text-blue-600">
                                                                    <Package className="h-5 w-5" />
                                                                </div>
                                                                <div>
                                                                    <div className="font-medium text-gray-900">{service.name}</div>
                                                                    <div className="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                                                        <Calendar className="h-3 w-3" />
                                                                        {__('admin.expires')}: {service.expires_at}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div className="font-semibold text-gray-900 flex items-center gap-1">
                                                                <DollarSign className="h-3.5 w-3.5 text-gray-400" />
                                                                {formatMoney(service.monthly_price, business_currency)} / {__('admin.mo')}
                                                            </div>
                                                        </div>
                                                    ))}
                                                    
                                                    {selectedUser?.services.length === 0 && (
                                                        <p className="text-center text-gray-500 py-4">{__('admin.no_active_services')}</p>
                                                    )}
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    </td>
                                </tr>
                            ))}
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="p-8 text-center text-gray-500">
                                        {__('admin.no_subscribers_found')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {users.data.length > 0 && (
                <div className="mt-4">
                    <Pagination links={users.links} />
                </div>
            )}
        </AdminSidebarLayout>
    );
}
