import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Plus } from 'lucide-react';
import RecurringScheduleForm, {
    EMPTY_RECURRING_FORM,
    type RecurringScheduleValues,
} from '@/Components/RecurringScheduleForm';
import { __ } from '@/lib/i18n';

export default function Create({ currencies, users, preselectedUserId }) {
    const { errors } = (typeof window !== 'undefined' && (window as any)) || {};
    const pageErrors = (router as any)?.page?.props?.errors ?? {};

    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const usersList = Array.isArray(users) ? users : (users ? Object.values(users) : []);

    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const queryUserId = urlParams ? urlParams.get('user') : null;

    const defaultCurrencyId = currenciesList[0]?.id || '';
    const defaultUserId =
        preselectedUserId ??
        (queryUserId ? Number(queryUserId) : null) ??
        usersList[0]?.id ??
        '';

    const [form] = useState<RecurringScheduleValues>({
        ...EMPTY_RECURRING_FORM,
        user_id: defaultUserId,
        currency: defaultCurrencyId,
    });

    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (values: RecurringScheduleValues) => {
        setSubmitting(true);
        router.post(route('admin.recurring_invoices.store'), values, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.add_recurring_invoice')} header="Business Operations">
            <Head title={__('general.add_recurring_invoice')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_invoices.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <Plus className="w-4 h-4" />{__('general.back_to_recurring_invoices')}
                </Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm w-full max-w-7xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <Plus className="w-5 h-5 text-slate-500" />
                        {__('general.add_recurring_invoice')}
                    </h2>
                    <p className="text-sm text-gray-500 mt-0.5">{__('general.create_a_repeated_salary_payment_schedule_for_a_team_member')}</p>
                </div>

                <RecurringScheduleForm
                    kind="invoice"
                    mode="create"
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