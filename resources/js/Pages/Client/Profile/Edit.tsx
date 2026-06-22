import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FreelanceLayout from '@/Pages/Freelance/Layout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { __ } from '@/lib/i18n';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    const { is_lance_domain } = usePage().props as any;
    const Layout = is_lance_domain ? FreelanceLayout : AuthenticatedLayout;

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
                        <UpdatePasswordForm className="w-full max-w-7xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="w-full max-w-7xl" />
                    </div>
                </div>
            </div>
        </Layout>
    );
}
