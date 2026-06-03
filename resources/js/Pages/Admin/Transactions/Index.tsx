import React, { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';

export default function Index({ filters }) {
    useEffect(() => {
        router.get('/admin/transactions', { ...filters, type: 'income' }, { replace: true });
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <AdminSidebarLayout title={__('Transactions')} header={__('Transactions')}>
            <div className="flex items-center justify-center h-64">
                <span className="text-slate-500">{__('Loading transactions...')}</span>
            </div>
        </AdminSidebarLayout>
    );
}
