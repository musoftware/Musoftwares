import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head } from '@inertiajs/react';
import RecurringForm from './RecurringForm';
import { PageHeader } from '@/Components/ui/PageHeader';

export default function Edit({ entry, business_currency }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={`Edit: ${entry.title}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title={`Edit: ${entry.title}`}
                        subtitle={__('general.update_your_recurring_entry_details_or_schedule')}
                    />

                    <RecurringForm entry={entry} business_currency={business_currency} />
                </div>
            </div>
        </ERPLayout>
    );
}
