import React from 'react';
import { RecurringScheduleView } from '@/Components/RecurringScheduleView';

export default function View({ salary, transactions, upcomingSchedule, total_stat }) {
    return (
        <RecurringScheduleView
            kind="salary"
            item={salary}
            transactions={transactions}
            upcomingSchedule={upcomingSchedule}
            total_stat={total_stat}
            backHref={route('admin.recurring_salaries.index')}
            backLabelKey="general.back_to_recurring_salaries"
            editRoute="admin.recurring_salaries.edit"
            deleteRoute="admin.recurring_salaries.delete"
            deleteRecordRoute=""
            headerTitle="Payroll Details"
            userLabelFallback="Unknown Employee"
            showReasonColumn
        />
    );
}