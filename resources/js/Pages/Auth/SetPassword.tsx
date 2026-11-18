import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { __ } from '@/lib/i18n';
import { AuthHeader, AuthSubmit } from '@/Components/Auth/AuthParts';
import { NewPasswordFields } from '@/Components/Auth/NewPasswordFields';

export default function SetPassword({ token, name, email }: { token: string; name: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // Post to the current URL so the signed query string (token + uid) stays on the request.
        post(window.location.pathname + window.location.search, {
            preserveScroll: true,
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.set_password')} />

            <div className="space-y-8">
                <AuthHeader title={__('general.set_your_password')} subtitle={<span dir="auto">{name} · {email}</span>} />

                <form onSubmit={submit} className="space-y-5" noValidate>
                    <NewPasswordFields
                        password={data.password}
                        confirmation={data.password_confirmation}
                        errors={errors}
                        onPassword={(value) => setData('password', value)}
                        onConfirmation={(value) => setData('password_confirmation', value)}
                    />
                    {errors.token && <p className="calm-fade text-[13px] text-[#D93025]">{errors.token}</p>}
                    <AuthSubmit busy={processing} label={__('general.save_password')} busyLabel={__('general.setting_password')} />
                </form>
            </div>
        </GuestLayout>
    );
}
