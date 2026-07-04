import React, { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import TransactionUserCard from './Components/TransactionUserCard';

export default function Index({ filters, filteredUser }) {
    useEffect(() => {
        router.get('/admin/transactions', { type: 'income' }, { replace: true });
    }, []);

    return (
        <AdminSidebarLayout title={__('erp.transactions')} header={__('erp.transactions')}>
            <Head title={__('erp.transactions')} />
            {filteredUser && <TransactionUserCard user={filteredUser} />}
            <div className="flex items-center justify-center h-64">
                <span className="text-slate-500 animate-pulse">{__('erp.loading_transactions')}</span>
            </div>
        </AdminSidebarLayout>
    );
}