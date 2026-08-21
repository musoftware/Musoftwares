import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function SetPassword({
    token,
    name,
    email,
}: {
    token: string;
    name: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [passwordStrength, setPasswordStrength] = useState({ score: 0, label: '' });

    const checkPasswordStrength = (pass: string) => {
        let score = 0;
        if (pass.length > 7) score += 1;
        if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) score += 1;
        if (/\d/.test(pass)) score += 1;
        if (/[^a-zA-Z\d]/.test(pass)) score += 1;

        let label = '';
        if (score === 0) label = '';
        else if (score === 1) label = __('general.weak') || 'Weak';
        else if (score === 2) label = __('general.fair') || 'Fair';
        else if (score === 3) label = __('general.good') || 'Good';
        else if (score === 4) label = __('general.strong') || 'Strong';

        setPasswordStrength({ score, label });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Send the request as POST to the current URL so the URL-signed
        // query parameters (token + uid) remain in flight. Laravel's
        // URL::temporarySignedRoute also signs query string parameters.
        post(window.location.pathname + window.location.search, {
            preserveScroll: true,
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.set_password') || 'Set your password'} />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                        {__('general.set_your_password') || 'Set your password'}
                    </h1>
                    <p className="text-xs text-[#1d1d1f]/60 font-sans">
                        {name} · {email}
                    </p>
                    <p className="text-xs text-[#1d1d1f]/60 font-sans">
                        {__('general.choose_a_secure_key_to_protect_your_workspace') ||
                            'Choose a secure password to protect your account. This link is single-use.'}
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <input type="hidden" name="token" value={data.token} />

                    <div className="space-y-1.5">
                        <Label htmlFor="password" className="text-xs font-semibold text-[#1d1d1f]/80">
                            {__('general.new_password') || 'New password'}
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                name="password"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="new-password"
                                autoFocus
                                onChange={(e) => {
                                    setData('password', e.target.value);
                                    checkPasswordStrength(e.target.value);
                                }}
                                required
                                className="h-10 px-3 py-2 pe-10 text-sm rounded-xl border-black/10 bg-[#f5f5f7]/50 focus:bg-white focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute end-3 top-3 text-[#1d1d1f]/40 hover:text-[#1d1d1f] transition-colors"
                                aria-label={showPassword ? __('general.hide_password') : __('general.show_password')}
                            >
                                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        {data.password.length > 0 && (
                            <div className="mt-2">
                                <div className="flex items-center space-x-1">
                                    {[1, 2, 3, 4].map((i) => (
                                        <div
                                            key={i}
                                            className={`h-1 w-full rounded-full transition-all duration-300 ${
                                                passwordStrength.score >= i
                                                    ? (passwordStrength.score <= 2
                                                        ? 'bg-[#ff9500]'
                                                        : passwordStrength.score === 3
                                                            ? 'bg-[#ffcc00]'
                                                            : 'bg-[#34c759]')
                                                    : 'bg-black/5'
                                            }`}
                                        />
                                    ))}
                                </div>
                                {passwordStrength.label && (
                                    <p className={`text-[10px] font-semibold mt-1.5 uppercase tracking-wider ${
                                        passwordStrength.score <= 2 ? 'text-[#ff9500]' :
                                        passwordStrength.score === 3 ? 'text-[#ff9500]' :
                                        'text-[#34c759]'
                                    }`}>
                                        {passwordStrength.label}
                                    </p>
                                )}
                            </div>
                        )}
                        {errors.password && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="password_confirmation" className="text-xs font-semibold text-[#1d1d1f]/80">
                            {__('general.confirm_password') || 'Confirm password'}
                        </Label>
                        <div className="relative">
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                                className="h-10 px-3 py-2 pe-10 text-sm rounded-xl border-black/10 bg-[#f5f5f7]/50 focus:bg-white focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                            />
                        </div>
                        {data.password_confirmation.length > 0 && data.password !== data.password_confirmation && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">
                                {__('general.passwords_do_not_match') || 'Passwords do not match'}
                            </p>
                        )}
                        {data.password_confirmation.length > 0 && data.password === data.password_confirmation && (
                            <p className="text-xs text-[#34c759] font-medium mt-1">
                                {__('general.passwords_match') || 'Passwords match'}
                            </p>
                        )}
                        {errors.password_confirmation && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.password_confirmation}</p>
                        )}
                    </div>

                    {errors.token && (
                        <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.token}</p>
                    )}

                    <div className="pt-2">
                        <Button
                            className="w-full h-11 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-sm rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                                    <span>{__('general.setting_password') || 'Setting password...'}</span>
                                </>
                            ) : (
                                <span>{__('general.save_password') || 'Save password & sign in'}</span>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}