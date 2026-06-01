import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Create({ service }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.create_landing_page')}</h2>}>
            <Head title={__('general.create_landing_page')} />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium">Create Landing Page for {service?.title}</h3>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
