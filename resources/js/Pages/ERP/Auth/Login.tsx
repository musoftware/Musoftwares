import { useEffect, FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { LogIn } from 'lucide-react';

export default function Login({ status, info }: { status?: string, info?: string }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('erp.login.submit'));
    };

    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-900">
            <Head title={t('erp.login')} />

            <div className="w-full sm:max-w-md mt-6 px-6 py-10 bg-white dark:bg-gray-800 shadow-xl overflow-hidden sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                <div className="flex justify-center mb-8">
                    <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center">
                        <LogIn className="w-8 h-8" />
                    </div>
                </div>

                <h2 className="text-2xl font-bold text-center text-gray-900 dark:text-white mb-2">
                    {t('erp.workspace_login')}
                </h2>
                <p className="text-sm text-center text-gray-500 dark:text-gray-400 mb-8">
                    {t('erp.enter_credentials_to_access_workspace')}
                </p>

                {status && (
                    <Alert className="mb-4 bg-green-50 text-green-800 border-green-200">
                        <AlertDescription>{status}</AlertDescription>
                    </Alert>
                )}
                
                {info && (
                    <Alert className="mb-4 bg-blue-50 text-blue-800 border-blue-200">
                        <AlertDescription>{info}</AlertDescription>
                    </Alert>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="email">{t('fields.email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="mt-1 block w-full bg-gray-50"
                            autoComplete="username"
                            isFocused={true}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        {errors.email && <p className="text-sm text-red-600 mt-1">{errors.email}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">{t('fields.password')}</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1 block w-full bg-gray-50"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        {errors.password && <p className="text-sm text-red-600 mt-1">{errors.password}</p>}
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="remember"
                                checked={data.remember}
                                onCheckedChange={(checked) => setData('remember', checked as boolean)}
                            />
                            <Label htmlFor="remember" className="text-sm text-gray-600 dark:text-gray-400">
                                {t('auth.remember_me')}
                            </Label>
                        </div>
                    </div>

                    <Button className="w-full" size="lg" disabled={processing}>
                        {processing ? t('general.processing') : t('erp.login')}
                    </Button>
                </form>
            </div>
            
            <p className="mt-8 text-center text-sm text-gray-500">
                {t('erp.are_you_a_business_owner')}{' '}
                <Link href={route('login')} className="text-blue-600 hover:underline font-medium">
                    {t('erp.login_to_main_portal')}
                </Link>
            </p>
        </div>
    );
}
