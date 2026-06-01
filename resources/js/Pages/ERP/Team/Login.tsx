import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2, Users } from 'lucide-react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('erp.team.login.store'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.erp_team_login')} />

            <div className="space-y-6">
                <div className="flex items-center space-x-3 mb-2">
                    <div className="p-2 bg-indigo-500/10 text-indigo-400 rounded-lg">
                        <Users className="w-6 h-6" />
                    </div>
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.erp_team_portal')}</h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">{__('general.sign_in_to_join_your_team_s_workspace')}</p>
                    </div>
                </div>

                {status && (
                    <div className="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 p-3 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        {status}
                    </div>
                )}


                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">{__('general.team_email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            placeholder={__('general.name_workspace_com')}
                            value={data.email}
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-indigo-500 dark:focus-visible:ring-indigo-400 transition-all font-normal"
                        />
                        {errors.email && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="password" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                Password
                            </Label>
                        </div>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                className="h-10 px-3 py-2 pr-10 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-indigo-500 dark:focus-visible:ring-indigo-400 transition-all font-normal"
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

                    <div className="flex items-center space-x-2 pt-1">
                        <input
                            type="checkbox"
                            id="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:checked:bg-zinc-100 dark:checked:text-zinc-900 dark:focus:ring-zinc-100 transition-colors cursor-pointer"
                        />
                        <Label
                            htmlFor="remember"
                            className="text-xs font-normal text-zinc-600 dark:text-zinc-400 cursor-pointer select-none"
                        >{__('general.remember_this_device')}</Label>
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-10 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-lg shadow-sm transition-all flex items-center justify-center space-x-2 border-0"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-white/50" />
                                    <span>{__('general.entering_workspace')}</span>
                                </>
                            ) : (
                                <span>{__('general.sign_in_as_team_member')}</span>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
