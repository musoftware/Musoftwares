import { PageProps } from '@/types';
import ClientPage, { Card, TextAction } from '@/Components/Client/ClientPage';
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

/** Profile laid out like the Settings page: one card per concern, the risky one last. */
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
        <ClientPage
            title={__('client_account.profile.title')}
            hint={__('client_account.profile.hint')}
            action={<TextAction href="/settings">{__('client_account.profile.all_settings')}</TextAction>}
            width="sm"
        >
            <div id="details" className="scroll-mt-24"><Card>
                <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} className="w-full" />
            </Card></div>

            <div id="emails" className="scroll-mt-24"><Card>
                <UserEmailsForm emails={emails} className="w-full" />
            </Card></div>

            <div id="security" className="scroll-mt-24"><Card>
                <UpdatePasswordForm className="w-full" />
            </Card></div>

            <div id="preferences" className="scroll-mt-24"><Card>
                <SystemPreferencesForm className="w-full" />
            </Card></div>

            <div id="delete" className="scroll-mt-24"><Card className="border-rose-200/70 dark:border-rose-900/50">
                <DeleteUserForm className="w-full" hasUnpaidInvoices={hasUnpaidInvoices} />
            </Card></div>
        </ClientPage>
    );
}
