import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ invoices }: any) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Invoices
                </h2>
            }
        >
            <Head title="Invoices" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-bold">
                            Invoices List
                        </h3>
                        <ul>
                            {invoices.data.map((invoice: any) => (
                                <li key={invoice.id} className="border-b py-2">
                                    Invoice #{invoice.number} -{' '}
                                    {invoice.client?.name}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
