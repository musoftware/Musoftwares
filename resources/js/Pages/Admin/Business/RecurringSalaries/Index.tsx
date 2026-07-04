import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Plus, ArrowLeft } from 'lucide-react';
import RecurringScheduleForm, {
    EMPTY_RECURRING_FORM,
    type RecurringScheduleValues,
} from '@/Components/RecurringScheduleForm';
import { __ } from '@/lib/i18n';

export default function Index({ salaries, currencies, users }) {
    const { errors } = usePage().props as any;
    const currenciesList = Array.isArray(currencies) ? currencies : currencies ? Object.values(currencies) : [];
    const usersList = Array.isArray(users) ? users : users ? Object.values(users) : [];

    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const queryAction = urlParams ? urlParams.get('action') : null;
    const queryUserId = urlParams ? urlParams.get('user') : null;

    const defaultCurrencyId = currenciesList[0]?.id || '';
    const defaultUserId = queryUserId ? String(queryUserId) : (usersList[0]?.id || '');

    const [isCreateOpen, setIsCreateOpen] = useState(queryAction === 'create');
    const [submitting, setSubmitting] = useState(false);

    const [form, setForm] = useState<RecurringScheduleValues>({
        ...EMPTY_RECURRING_FORM,
        user_id: defaultUserId,
        title: 'Monthly Salary',
        currency: defaultCurrencyId,
    });

    const handleCreate = (values: RecurringScheduleValues) => {
        setSubmitting(true);
        router.post(route('admin.recurring_salaries.store'), values, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setForm({
                    ...EMPTY_RECURRING_FORM,
                    user_id: defaultUserId,
                    title: 'Monthly Salary',
                    currency: defaultCurrencyId,
                });
            },
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.recurring_salaries')} header="Business Operations">
            <Head title={__('general.admin_recurring_salaries')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}
                </Link>
            </div>

            <div className="flex justify-end gap-4 items-center mb-6">
                <div className="me-auto">
                    <h2 className="text-xl font-bold text-slate-900">{__('general.active_recurring_salaries')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_repeated_automated_salary_schedules_for_employees')}</p>
                </div>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <Button onClick={() => setIsCreateOpen(true)} className="bg-black hover:bg-slate-800 text-white h-9">
                        <Plus className="w-4 h-4 me-2" />{__('general.add_recurring_salary')}
                    </Button>
                    <DialogContent className="sm:max-w-[720px] max-h-[85vh] overflow-y-auto p-0">
                        <DialogHeader className="px-6 pt-6 pb-2">
                            <DialogTitle>{__('general.add_recurring_salary')}</DialogTitle>
                            <DialogDescription>{__('general.create_a_repeated_salary_payment_schedule_for_a_team_member')}</DialogDescription>
                        </DialogHeader>
                        <RecurringScheduleForm
                            kind="salary"
                            mode="create"
                            initialValues={form}
                            currencies={currenciesList}
                            users={usersList}
                            errors={errors ?? {}}
                            submitting={submitting}
                            onSubmit={handleCreate}
                            backHref={route('admin.recurring_salaries.index')}
                            backLabel={__('general.cancel')}
                        />
                        <DialogFooter className="hidden" />
                    </DialogContent>
                </Dialog>
            </div>

            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.employee_user')}</th>
                                <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.title_schedule')}</th>
                                <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.start_date')}</th>
                                <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</th>
                                <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.active')}</th>
                                <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {(salaries.data as any).map((salary: any) => (
                                <tr key={salary.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <div className="text-sm font-semibold text-gray-900">{salary.user?.name || 'Unknown Employee'}</div>
                                        <div className="text-xs text-gray-500">{salary.user?.email || 'N/A'}</div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="text-sm font-semibold text-gray-900">{salary.title}</div>
                                        <div className="text-xs text-gray-500 mt-1">
                                            Every {salary.recurring_times} {salary.recurring}(s)
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {new Date(salary.start_date).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                        <span className="text-sm font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-1 rounded">
                                            {salary.amount} {salary.currency}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                        {salary.is_active ? __('general.active') : __('general.inactive')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                        <Link href={route('admin.recurring_salaries.view', salary.id)} className="text-slate-700 hover:text-black me-3">
                                            {__('general.view_details')}
                                        </Link>
                                        <Link href={route('admin.recurring_salaries.edit', salary.id)} className="text-slate-700 hover:text-black me-3">
                                            {__('general.edit')}
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}