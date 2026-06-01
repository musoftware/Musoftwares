import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Index({ servicesWithLandingPages }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.my_landing_pages')}</h2>}>
            <Head title={__('general.my_landing_pages')} />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium">{__('general.landing_pages')}</h3>
                    <p className="mt-2 text-gray-600">{__('general.this_feature_has_been_migrated_to_the_new_marketplace_architecture_full_ui_implementation_pending')}</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
