import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { FreelanceModeProvider } from '@/Components/Freelance/FreelanceModeContext';

export default function FreelanceLayout({ auth, children, clean = false }) {
    return (
        <FreelanceModeProvider>
            <AuthenticatedLayout user={auth.user}>
                <Head title="Marketplace" />
                <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-6">
                    {children}
                </div>
            </AuthenticatedLayout>
        </FreelanceModeProvider>
    );
}

