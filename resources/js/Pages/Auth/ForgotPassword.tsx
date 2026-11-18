import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { AuthField } from '@/Components/Auth/AuthField';
import { AuthFooter, AuthHeader, AuthStatus, AuthSubmit, authLinkClass } from '@/Components/Auth/AuthParts';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={__('general.forgot_password')} />

            <div className="space-y-8">
                <AuthHeader
                    title={__('general.reset_your_password')}
                    subtitle={__('general.enter_your_email_address_and_we_will_send_you_a_link_to_reset_your_password')}
                />
                <AuthStatus>{status}</AuthStatus>

                <form onSubmit={submit} className="space-y-5" noValidate>
                    <AuthField
                        id="email"
                        type="email"
                        inputMode="email"
                        label={__('general.email_address_1')}
                        placeholder={__('general.name_company_com')}
                        value={data.email}
                        autoComplete="username"
                        autoFocus
                        error={errors.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <AuthSubmit busy={processing} label={__('general.send_reset_link')} busyLabel={__('general.sending_reset_link')} />
                </form>

                <AuthFooter>
                    <Link href={route('login')} className={`inline-flex items-center gap-2 ${authLinkClass}`}>
                        <ArrowLeft className="h-4 w-4 rtl:rotate-180" />
                        {__('general.back_to_log_in')}
                    </Link>
                </AuthFooter>
            </div>
        </GuestLayout>
    );
}
