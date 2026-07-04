import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { ArrowLeft } from 'lucide-react';
import RecurringScheduleForm, {
    type RecurringScheduleValues,
} from '@/Components/RecurringScheduleForm';
import { __ } from '@/lib/i18n';

export default function Edit({ salary, currencies, users }) {
    const pageErrors = (router as any)?.page?.props?.errors ?? {};
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const usersList = Array.isArray(users) ? users : (users ? Object.values(users) : []);

    const [form] = useState<RecurringScheduleValues>({
        user_id: salary.user_id,
        title: salary.title,
        amount: salary.amount,
        currency: salary.currency,
        reason: salary.reason,
        start_date: salary.start_date,
        recurring: salary.recurring,
        recurring_times: salary.recurring_times,
        recurring_times_week: salary.recurring_times_week || [],
        recurring_times_month: (salary.recurring_times_month || []).map((v: any) => v.toString()),
        recurring_times_year: salary.recurring_times_year || [],
    });

    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (values: RecurringScheduleValues) => {
        setSubmitting(true);
        router.put(route('admin.recurring_salaries.update', salary.id), values, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.edit_recurring_salary')} header="Business Operations">
            <Head title={__('general.edit_recurring_salary')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_salaries.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_salaries')}
                </Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm w-full max-w-7xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900">{__('general.edit_recurring_salary_details')}</h2>
                    <p className="text-sm text-gray-500 mt-0.5">{__('general.modify_the_payroll_parameters_for_this_employee')}</p>
                </div>

                <RecurringScheduleForm
                    kind="salary"
                    mode="edit"
                    initialValues={form}
                    currencies={currenciesList}
                    users={usersList}
                    errors={pageErrors}
                    submitting={submitting}
                    onSubmit={handleSubmit}
                    backHref={route('admin.recurring_salaries.index')}
                    backLabel={__('general.back_to_recurring_salaries')}
                />
            </div>
        </AdminSidebarLayout>
    );
}