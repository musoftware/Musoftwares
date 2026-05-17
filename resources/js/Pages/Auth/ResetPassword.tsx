import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';

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

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Reset password" />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Set new password
                    </h1>
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">
                        Choose a secure key to protect your workspace.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            autoComplete="username"
                            error={errors.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            New password
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="new-password"
                                autoFocus
                                error={errors.password}
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
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Confirm password
                        </Label>
                        <Input
                            id="password_confirmation"
                            type={showPassword ? 'text' : 'password'}
                            placeholder="••••••••"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            error={errors.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
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
                                    <span>Resetting password...</span>
                                </>
                            ) : (
                                <span>Reset password</span>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
