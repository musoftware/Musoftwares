import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { __ } from '@/lib/i18n';
import { AuthField, PasswordField } from '@/Components/Auth/AuthField';
import { AuthFooter, AuthHeader, AuthStatus, AuthSubmit, GoogleSignIn, authLinkClass } from '@/Components/Auth/AuthParts';

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: true as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    const forgotLink = canResetPassword ? (
        <Link href={route('password.request')} className={`text-[13px] ${authLinkClass}`}>{__('general.forgot_password_1')}</Link>
    ) : undefined;

    return (
        <GuestLayout>
            <Head title={__('general.log_in')} />

            <div className="space-y-8">
                <AuthHeader title={__('general.log_in_to_your_account')} />
                <AuthStatus>{status}</AuthStatus>

                <div className="space-y-6">
                    <GoogleSignIn href={route('social.google.redirect')} />

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
                        <PasswordField
                            id="password"
                            label={__('general.password')}
                            labelAction={forgotLink}
                            value={data.password}
                            autoComplete="current-password"
                            error={errors.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />

                        <label className="flex cursor-pointer select-none items-center gap-2.5 text-sm text-[#555] dark:text-zinc-400">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-[18px] w-[18px] cursor-pointer rounded border-black/20 text-[#111] focus:ring-[#111] dark:border-white/20"
                            />
                            {__('general.remember_me')}
                        </label>

                        <AuthSubmit busy={processing} label={__('general.log_in')} busyLabel={__('general.logging_in')} />
                    </form>
                </div>

                <AuthFooter>
                    {__('general.dont_have_account')}{' '}
                    <Link href={route('register')} className={authLinkClass}>{__('general.sign_up')}</Link>
                </AuthFooter>
            </div>
        </GuestLayout>
    );
}
