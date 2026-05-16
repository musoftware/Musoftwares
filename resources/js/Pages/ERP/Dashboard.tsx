import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function ERPDashboard() {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">ERP Workspace</h2>}>
            <Head title="ERP Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold mb-4">Enterprise Resource Planning</h3>
                        <p>Manage tenants, clients, invoices, and accounting.</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
