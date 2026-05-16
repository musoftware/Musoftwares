import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import RecurringForm from './RecurringForm';
import { PageHeader } from '@/Components/ui/PageHeader';

export default function Edit({ entry, business_currency }) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit: ${entry.title}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <PageHeader
                        title={`Edit: ${entry.title}`}
                        subtitle="Update your recurring entry details or schedule"
                    />

                    <RecurringForm entry={entry} business_currency={business_currency} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
