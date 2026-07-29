import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UserEmailsForm from './Partials/UserEmailsForm';
import SystemPreferencesForm from './Partials/SystemPreferencesForm';
import { __ } from '@/lib/i18n';

interface SecondaryEmail {
    id: number;
    email: string;
    verified_at: string | null;
    source: string;
}

export default function Edit({
    mustVerifyEmail,
    status,
    hasUnpaidInvoices = false,
    emails = [],
}: PageProps<{
    mustVerifyEmail: boolean;
    status?: string;
    hasUnpaidInvoices?: boolean;
    emails?: SecondaryEmail[];
}>) {
    const Layout = AuthenticatedLayout;

    return (
        <Layout
            header={
                <h2 className="text-xl leading-tight font-semibold text-gray-800">
                    {__('general.profile')}</h2>
            }
        >
            <Head title={__('general.profile')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="w-full max-w-7xl"
                        />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <SystemPreferencesForm className="w-full max-w-7xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UserEmailsForm emails={emails} className="w-full max-w-7xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="w-full max-w-7xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="w-full max-w-7xl" hasUnpaidInvoices={hasUnpaidInvoices} />
                    </div>
                </div>
            </div>
        </Layout>
    );
}
