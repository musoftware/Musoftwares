import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head } from '@inertiajs/react';
import RecurringForm from './RecurringForm';
import { PageHeader } from '@/Components/ui/PageHeader';

export default function Create({ business_currency }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={__('general.create_recurring_entry')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title={__('general.create_recurring_entry')}
                        subtitle={__('general.set_up_a_new_automated_income_or_expense_entry')}
                    />

                    <RecurringForm business_currency={business_currency} />
                </div>
            </div>
        </ERPLayout>
    );
}
