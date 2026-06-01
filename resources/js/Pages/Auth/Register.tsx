import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Register" />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.create_your_account')}</h1>
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">{__('general.manage_invoices_wallet_and_services_from_one_workspace')}</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.full_name_1')}</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder={__('general.john_doe')}
                            value={data.name}
                            autoComplete="name"
                            autoFocus
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
                        {errors.name && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.email_address_1')}</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            placeholder={__('general.name_company_com')}
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
                        <Label htmlFor="password" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Password
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="new-password"
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                className="h-10 px-3 py-2 pr-10 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute right-3 top-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        {errors.password && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.confirm_password')}</Label>
                        <Input
                            id="password_confirmation"
                            type={showPassword ? 'text' : 'password'}
                            name="password_confirmation"
                            placeholder="••••••••"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
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
                                    <span>{__('general.creating_account')}</span>
                                </>
                            ) : (
                                <span>{__('general.create_account_1')}</span>
                            )}
                        </Button>
                    </div>
                </form>

                <div className="text-center pt-4 border-t border-zinc-100 dark:border-zinc-800/80 mt-6">
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">
                        Already registered?{' '}
                        <Link
                            href={route('login')}
                            className="font-medium text-zinc-900 hover:underline dark:text-zinc-100"
                        >{__('general.log_in')}</Link>
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}
