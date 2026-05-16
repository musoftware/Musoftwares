import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import RecurringForm from './RecurringForm';
import { PageHeader } from '@/Components/ui/PageHeader';

export default function Create({ business_currency }) {
    return (
        <AuthenticatedLayout>
            <Head title="Create Recurring Entry" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title="Create Recurring Entry"
                        subtitle="Set up a new automated income or expense entry"
                    />

                    <RecurringForm business_currency={business_currency} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
