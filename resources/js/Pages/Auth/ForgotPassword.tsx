import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { ArrowLeft, Loader2 } from 'lucide-react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot password" />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Reset your password
                    </h1>
                    <p className="text-xs text-zinc-500 dark:text-zinc-400 font-normal">
                        Enter your email address and we will send you a link to reset your password.
                    </p>
                </div>

                {status && (
                    <div className="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 p-3 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            placeholder="name@company.com"
                            value={data.email}
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="h-10 px-3 py-2 text-sm rounded-lg border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 focus-visible:ring-2 focus-visible:ring-zinc-900 dark:focus-visible:ring-zinc-100 transition-all font-normal"
                        />
                        {errors.email && (
                            <p className="text-xs text-red-500 font-medium mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-10 bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 font-medium text-sm rounded-lg shadow-xs transition-all flex items-center justify-center space-x-2"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-zinc-400 dark:text-zinc-600" />
                                    <span>Sending reset link...</span>
                                </>
                            ) : (
                                <span>Send reset link</span>
                            )}
                        </Button>
                    </div>
                </form>

                <div className="text-center pt-4 border-t border-zinc-100 dark:border-zinc-800/80 mt-6">
                    <Link
                        href={route('login')}
                        className="inline-flex items-center space-x-2 text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        <span>Back to log in</span>
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
