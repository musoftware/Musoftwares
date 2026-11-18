import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { __ } from '@/lib/i18n';
import { AuthField } from '@/Components/Auth/AuthField';
import { AuthHeader, AuthSubmit } from '@/Components/Auth/AuthParts';
import { NewPasswordFields } from '@/Components/Auth/NewPasswordFields';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <GuestLayout>
            <Head title={__('general.reset_password_1')} />

            <div className="space-y-8">
                <AuthHeader title={__('general.set_new_password')} />

                <form onSubmit={submit} className="space-y-5" noValidate>
                    <AuthField
                        id="email"
                        type="email"
                        label={__('general.email_address_1')}
                        value={data.email}
                        autoComplete="username"
                        error={errors.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <NewPasswordFields
                        password={data.password}
                        confirmation={data.password_confirmation}
                        errors={errors}
                        onPassword={(value) => setData('password', value)}
                        onConfirmation={(value) => setData('password_confirmation', value)}
                    />
                    <AuthSubmit busy={processing} label={__('general.reset_password_1')} busyLabel={__('general.resetting_password')} />
                </form>
            </div>
        </GuestLayout>
    );
}
