import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, User, Shield, Sliders, Mail, KeyRound, AlertTriangle } from 'lucide-react';
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
    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.profile')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-1.5">
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                        >
                            <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                            {__('general.back_to_dashboard')}
                        </Link>
                        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                            {__('general.profile')} &amp; Account Settings
                        </h1>
                        <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                            Manage your personal details, language preferences, connected emails, and security credentials.
                        </p>
                    </div>
                </div>

                {/* Main Content Sections */}
                <div className="max-w-[1000px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* Profile Information */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="w-full"
                        />
                    </div>

                    {/* System Preferences */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <SystemPreferencesForm className="w-full" />
                    </div>

                    {/* Connected Emails */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <UserEmailsForm emails={emails} className="w-full" />
                    </div>

                    {/* Security & Password */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <UpdatePasswordForm className="w-full" />
                    </div>

                    {/* Danger Zone */}
                    <div className="bg-white border border-rose-200/60 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <DeleteUserForm className="w-full" hasUnpaidInvoices={hasUnpaidInvoices} />
                    </div>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
