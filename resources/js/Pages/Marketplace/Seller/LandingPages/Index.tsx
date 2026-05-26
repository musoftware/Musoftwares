import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ servicesWithLandingPages }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">My Landing Pages</h2>}>
            <Head title="My Landing Pages" />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium">Landing Pages</h3>
                    <p className="mt-2 text-gray-600">This feature has been migrated to the new Marketplace architecture. Full UI implementation pending.</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
