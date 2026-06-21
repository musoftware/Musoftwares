import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function CustomersIndex({ customers }: { customers: any }) {
    return (
        <CrmLayout title="Customers" activeMenu="customers">
            <Head title={__('crm.customers')} />
            <div className="p-6">
                <div className="mb-6 flex justify-between items-center">
                    <h1 className="text-2xl font-bold">{__('crm.customers')}</h1>
                </div>

                <div className="bg-white rounded shadow overflow-hidden">
                    <table className="w-full text-sm text-start">
                        <thead className="bg-gray-50 border-b">
                            <tr>
                                <th className="px-6 py-3">{__('crm.name')}</th>
                                <th className="px-6 py-3">{__('crm.email')}</th>
                                <th className="px-6 py-3">{__('crm.company')}</th>
                                <th className="px-6 py-3">{__('crm.total_value')}</th>
                                <th className="px-6 py-3 text-end">{__('crm.actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(customers.data as any).map((customer: any) => (
                                <tr key={customer.id} className="border-b hover:bg-gray-50">
                                    <td className="px-6 py-4 font-medium">{customer.name}</td>
                                    <td className="px-6 py-4">{customer.email}</td>
                                    <td className="px-6 py-4">{customer.company}</td>
                                    <td className="px-6 py-4">{customer.total_value}</td>
                                    <td className="px-6 py-4 text-end">
                                        <Link href={route('crm.customers.show', customer.id)} className="text-blue-600 hover:underline">
                                            {__('crm.view')}
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </CrmLayout>
    );
}
