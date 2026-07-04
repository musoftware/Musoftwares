import React from 'react';
import { RecurringScheduleView } from '@/Components/RecurringScheduleView';

export default function View({ invoice, records, transactions, upcomingSchedule, total_stat }) {
    return (
        <RecurringScheduleView
            kind="invoice"
            item={invoice}
            records={records}
            transactions={transactions}
            upcomingSchedule={upcomingSchedule}
            total_stat={total_stat}
            backHref={route('admin.recurring_invoices.index')}
            backLabelKey="general.back_to_recurring_invoices"
            editRoute="admin.recurring_invoices.edit"
            deleteRoute="admin.recurring_invoices.delete"
            deleteRecordRoute="admin.recurring_invoices.records.delete"
            headerTitle="Payroll Details"
            userLabelFallback="Unknown user"
        />
    );
}