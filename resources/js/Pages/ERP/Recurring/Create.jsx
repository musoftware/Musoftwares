import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head } from '@inertiajs/react';
import RecurringForm from './RecurringForm';
import { PageHeader } from '@/Components/ui/PageHeader';

export default function Create({ business_currency }) {
    const { menuItems, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title="Create Recurring Entry" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title="Create Recurring Entry"
                        subtitle="Set up a new automated income or expense entry"
                    />

                    <RecurringForm business_currency={business_currency} />
                </div>
            </div>
        </ERPLayout>
    );
}
