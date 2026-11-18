import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { __ } from '@/lib/i18n';
import { PasswordField } from '@/Components/Auth/AuthField';
import { AuthHeader, AuthSubmit } from '@/Components/Auth/AuthParts';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title={__('general.confirm_password')} />

            <div className="space-y-8">
                <AuthHeader
                    title={__('general.confirm_your_password')}
                    subtitle={__('general.please_confirm_your_identity_before_accessing_restricted_settings')}
                />

                <form onSubmit={submit} className="space-y-5" noValidate>
                    <PasswordField
                        id="password"
                        label={__('general.password')}
                        value={data.password}
                        autoComplete="current-password"
                        autoFocus
                        error={errors.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <AuthSubmit busy={processing} label={__('general.confirm_identity')} busyLabel={__('general.verifying_identity')} />
                </form>
            </div>
        </GuestLayout>
    );
}
