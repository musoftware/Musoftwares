import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Plus } from 'lucide-react';
import RecurringScheduleForm, {
    type RecurringScheduleValues,
} from '@/Components/RecurringScheduleForm';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Edit({ invoice, currencies, users }) {
    const pageErrors = (router as any)?.page?.props?.errors ?? {};
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const usersList = Array.isArray(users) ? users : (users ? Object.values(users) : []);

    const [form] = useState<RecurringScheduleValues>({
        user_id: invoice.user_id,
        title: invoice.title,
        amount: invoice.amount,
        currency: invoice.currency,
        reason: invoice.reason,
        start_date: invoice.start_date,
        recurring: invoice.recurring,
        recurring_times: invoice.recurring_times,
        recurring_times_week: invoice.recurring_times_week || [],
        recurring_times_month: (invoice.recurring_times_month || []).map((v: any) => v.toString()),
        recurring_times_year: invoice.recurring_times_year || [],
    });

    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (values: RecurringScheduleValues) => {
        setSubmitting(true);
        router.put(route('admin.recurring_invoices.update', invoice.id), values, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.edit_recurring_invoice')} header="Business Operations">
            <Head title={__('general.edit_recurring_invoice')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_invoices.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <Plus className="w-4 h-4" />{__('general.back_to_recurring_invoices')}
                </Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm w-full max-w-7xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900">{__('general.edit_recurring_invoice_details')}</h2>
                    <p className="text-sm text-gray-500 mt-0.5">{__('general.modify_the_payroll_parameters_for_this_user')}</p>
                </div>

                <RecurringScheduleForm
                    kind="invoice"
                    mode="edit"
                    initialValues={form}
                    currencies={currenciesList}
                    users={usersList}
                    searchUsersEndpoint={route('admin.projects.search-clients')}
                    errors={pageErrors}
                    submitting={submitting}
                    onSubmit={handleSubmit}
                    backHref={route('admin.recurring_invoices.index')}
                    backLabel={__('general.back_to_recurring_invoices')}
                />
            </div>
        </AdminSidebarLayout>
    );
}