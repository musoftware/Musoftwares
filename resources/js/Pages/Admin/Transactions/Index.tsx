import React from 'react';
import { Head } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import TransactionUserCard from './Components/TransactionUserCard';

// Not rendered any more: AdminTransactionController@index redirects unknown types server-side.
export default function Index({ filteredUser }) {
    return (
        <AdminSidebarLayout title={__('erp.transactions')} header={__('erp.transactions')}>
            <Head title={__('erp.transactions')} />
            {filteredUser && <TransactionUserCard user={filteredUser} />}
        </AdminSidebarLayout>
    );
}
