import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Analytics({ service, landingPage, analytics }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Landing Page Analytics</h2>}>
            <Head title="Landing Page Analytics" />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium">A/B Testing & Conversions</h3>
                    <pre className="mt-4 bg-gray-100 p-4 rounded text-sm overflow-x-auto">
                        {JSON.stringify(analytics, null, 2)}
                    </pre>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
