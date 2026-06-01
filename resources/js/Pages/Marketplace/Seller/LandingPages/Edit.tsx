import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Edit({ service, landingPage }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.edit_landing_page_builder')}</h2>}>
            <Head title={__('general.edit_landing_page')} />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium">Landing Page Builder: {landingPage?.hero_title}</h3>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
