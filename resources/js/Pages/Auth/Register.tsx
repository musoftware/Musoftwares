import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode } from 'react';
import { __ } from '@/lib/i18n';
import { AuthField, PasswordField } from '@/Components/Auth/AuthField';
import { AuthFooter, AuthHeader, AuthSubmit, GoogleSignIn, authLinkClass } from '@/Components/Auth/AuthParts';
import { PasswordStrength } from '@/Components/Auth/PasswordStrength';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        privacy_consent: false,
        marketing_consent: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // One password box with an eye button; the confirm value is the same text.
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    const setPassword = (value: string) => {
        setData((current) => ({ ...current, password: value, password_confirmation: value }));
    };

    return (
        <GuestLayout>
            <Head title={__('general.register')} />

            <div className="space-y-8">
                <AuthHeader title={__('general.create_your_account')} />

                <div className="space-y-6">
                    <GoogleSignIn href={route('social.google.redirect')} />

                    <form onSubmit={submit} className="space-y-5" noValidate>
                        <AuthField
                            id="name"
                            label={__('general.full_name_1')}
                            placeholder={__('general.john_doe')}
                            value={data.name}
                            autoComplete="name"
                            autoFocus
                            error={errors.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <AuthField
                            id="email"
                            type="email"
                            inputMode="email"
                            label={__('general.email_address_1')}
                            placeholder={__('general.name_company_com')}
                            value={data.email}
                            autoComplete="username"
                            error={errors.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <PasswordField
                            id="password"
                            label={__('general.password')}
                            value={data.password}
                            autoComplete="new-password"
                            error={errors.password ?? errors.password_confirmation}
                            hint={<PasswordStrength password={data.password} />}
                            onChange={(e) => setPassword(e.target.value)}
                        />

                        <div className="space-y-3 pt-1">
                            <ConsentBox
                                id="privacy_consent"
                                checked={data.privacy_consent}
                                error={errors.privacy_consent}
                                onChange={(checked) => setData('privacy_consent', checked)}
                            >
                                {__('privacy_compliance.register_privacy_consent_label')}{' '}
                                (<Link href="/privacy-policy" className={authLinkClass}>{__('privacy_compliance.read_privacy_policy')}</Link>
                                {' / '}
                                <Link href="/terms-of-service" className={authLinkClass}>{__('privacy_compliance.read_terms')}</Link>)
                            </ConsentBox>
                            <ConsentBox
                                id="marketing_consent"
                                checked={data.marketing_consent}
                                onChange={(checked) => setData('marketing_consent', checked)}
                            >
                                {__('privacy_compliance.register_marketing_consent_label')}
                            </ConsentBox>
                        </div>

                        <AuthSubmit busy={processing} label={__('general.create_account_1')} busyLabel={__('general.creating_account')} />
                    </form>
                </div>

                <AuthFooter>
                    {__('general.already_registered')}{' '}
                    <Link href={route('login')} className={authLinkClass}>{__('general.log_in')}</Link>
                </AuthFooter>
            </div>
        </GuestLayout>
    );
}

type ConsentProps = { id: string; checked: boolean; error?: string; onChange: (checked: boolean) => void; children: ReactNode };

function ConsentBox({ id, checked, error, onChange, children }: ConsentProps) {
    return (
        <div>
            <div className="flex items-start gap-3">
                <input
                    type="checkbox"
                    id={id}
                    name={id}
                    checked={checked}
                    aria-invalid={error ? true : undefined}
                    onChange={(e) => onChange(e.target.checked)}
                    className="mt-0.5 h-[18px] w-[18px] shrink-0 cursor-pointer rounded border-black/20 text-[#111] focus:ring-[#111] dark:border-white/20"
                />
                <label htmlFor={id} className="cursor-pointer select-none text-[13px] leading-relaxed text-[#555] dark:text-zinc-400">
                    {children}
                </label>
            </div>
            {error && <p className="calm-fade mt-1.5 ps-[30px] text-[13px] text-[#D93025]">{error}</p>}
        </div>
    );
}
