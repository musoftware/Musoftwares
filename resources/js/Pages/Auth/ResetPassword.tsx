import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
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

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.reset_password_1')} />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.set_new_password')}</h1>
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">{__('general.choose_a_secure_key_to_protect_your_workspace')}</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.email_address_1')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
                        {errors.email && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.new_password')}</Label>
                        <div className="relative">
                            <Input
                                id="password"
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
                                className="h-10 px-3 py-2 pe-10 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute end-3 top-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
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
                                                    ? (passwordStrength.score <= 2 ? 'bg-orange-500' : passwordStrength.score === 3 ? 'bg-amber-500' : 'bg-emerald-500') 
                                                    : 'bg-zinc-200 dark:bg-zinc-800'
                                            }`} 
                                        />
                                    ))}
                                </div>
                                {passwordStrength.label && (
                                    <p className={`text-[10px] font-medium mt-1.5 uppercase tracking-wider ${
                                        passwordStrength.score <= 2 ? 'text-orange-600 dark:text-orange-400' : 
                                        passwordStrength.score === 3 ? 'text-amber-600 dark:text-amber-400' : 
                                        'text-emerald-600 dark:text-emerald-400'
                                    }`}>
                                        {passwordStrength.label}
                                    </p>
                                )}
                            </div>
                        )}
                        {errors.password && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.confirm_password')}</Label>
                        <Input
                            id="password_confirmation"
                            type={showPassword ? 'text' : 'password'}
                            placeholder="••••••••"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
                        {data.password_confirmation.length > 0 && data.password !== data.password_confirmation && (
                            <p className="text-xs text-red-500 font-medium mt-1">{__('general.passwords_do_not_match') || 'Passwords do not match'}</p>
                        )}
                        {data.password_confirmation.length > 0 && data.password === data.password_confirmation && (
                            <p className="text-xs text-emerald-500 font-medium mt-1">{__('general.passwords_match') || 'Passwords match'}</p>
                        )}
                        {errors.password_confirmation && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.password_confirmation}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-10 bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 font-medium text-sm rounded-lg shadow-xs transition-all flex items-center justify-center space-x-2"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-zinc-400 dark:text-zinc-600" />
                                    <span>{__('general.resetting_password')}</span>
                                </>
                            ) : (
                                <span>{__('general.reset_password_1')}</span>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
