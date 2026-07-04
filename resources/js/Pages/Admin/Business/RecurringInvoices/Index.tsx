import React from 'react';
import { RecurringSchedulesIndex } from '@/Components/RecurringSchedulesIndex';

export default function Index({ invoices }) {
    return (
        <RecurringSchedulesIndex
            kind="invoice"
            items={invoices}
            pageTitleKey="general.recurring_invoices"
            pageHeader="Business Operations"
            descriptionKey="general.add_a_new_schedule_to_start_managing_automated_user_payroll"
            backHref={route('admin.finance.index')}
            backLabelKey="general.back_to_financial_ledger"
            toggleRoute="admin.recurring_invoices.toggle"
            deleteRoute="admin.recurring_invoices.delete"
            viewRoute="admin.recurring_invoices.view"
            editRoute="admin.recurring_invoices.edit"
            createRoute="admin.recurring_invoices.create"
            createLabelKey="general.add_recurring_invoice"
            titleKey="general.admin_recurring_invoices"
            headerTitleKey="general.active_recurring_invoices"
            headerSubtitleKey="general.manage_repeated_automated_salary_schedules_for_users"
            amountColorClass="text-rose-600 bg-rose-50 border-rose-200"
        />
    );
}