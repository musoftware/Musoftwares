import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.log_in')} />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] dark:text-white font-sans">{__('general.log_in_to_your_account')}</h1>
                    <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 font-sans">{__('general.enter_your_email_and_password_to_open_your_workspace')}</p>
                </div>

                {status && (
                    <div className="rounded-xl bg-[#34c759]/10 border border-[#34c759]/20 p-3 text-xs font-semibold text-[#28a745]">
                        {status}
                    </div>
                )}

                <div className="pt-1">
                    <a href={route('social.google.redirect')} className="flex w-full items-center justify-center space-x-2 rounded-[980px] border border-black/10 dark:border-white/15 bg-white dark:bg-[#1d1d1f] px-4 py-2.5 text-xs font-semibold text-[#1d1d1f] dark:text-white shadow-xs hover:bg-[#f5f5f7] dark:hover:bg-[#2d2d2f] transition-all">
                        <svg className="w-4 h-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span>{__('general.continue_with_google')}</span>
                    </a>
                </div>

                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <span className="w-full border-t border-black/5 dark:border-white/10" />
                    </div>
                    <div className="relative flex justify-center text-[10px] uppercase tracking-wider font-semibold">
                        <span className="bg-white dark:bg-[#161617] px-3 text-[#1d1d1f]/40 dark:text-[#f5f5f7]/40">
                            {__('general.or_continue_with')}
                        </span>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="email" className="text-xs font-semibold text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80">{__('general.email_address_1')}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            placeholder={__('general.name_company_com')}
                            value={data.email}
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            className="h-10 px-3 py-2 text-sm rounded-xl border-black/10 dark:border-white/15 bg-[#f5f5f7]/50 dark:bg-black/40 text-[#1d1d1f] dark:text-white focus:bg-white dark:focus:bg-black/60 focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                        />
                        {errors.email && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="password" className="text-xs font-semibold text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80">
                                {__('general.password')}
                            </Label>
                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-xs font-semibold text-[#0071e3] hover:underline transition-colors"
                                >{__('general.forgot_password_1')}</Link>
                            )}
                        </div>
                        <div className="relative">
                            <Input
                                id="password"
                                name="password"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                className="h-10 px-3 py-2 pe-10 text-sm rounded-xl border-black/10 dark:border-white/15 bg-[#f5f5f7]/50 dark:bg-black/40 text-[#1d1d1f] dark:text-white focus:bg-white dark:focus:bg-black/60 focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute end-3 top-3 text-[#1d1d1f]/40 dark:text-[#f5f5f7]/40 hover:text-[#1d1d1f] dark:hover:text-white transition-colors"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        {errors.password && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="flex items-center space-x-2 pt-1">
                        <input
                            type="checkbox"
                            id="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="h-4 w-4 rounded border-black/20 dark:border-white/20 text-[#0071e3] focus:ring-[#0071e3] transition-colors cursor-pointer"
                        />
                        <Label
                            htmlFor="remember"
                            className="text-xs font-medium text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70 cursor-pointer select-none"
                        >{__('general.remember_me')}</Label>
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-11 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-sm rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                                    <span>{__('general.logging_in')}</span>
                                </>
                            ) : (
                                <span>{__('general.log_in')}</span>
                            )}
                        </Button>
                    </div>
                </form>

                <div className="text-center pt-4 border-t border-black/5 dark:border-white/10 mt-6">
                    <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 font-medium">
                        {__('general.dont_have_account')}{' '}
                        <Link
                            href={route('register')}
                            className="font-semibold text-[#0071e3] hover:underline"
                        >{__('general.sign_up')}</Link>
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}
