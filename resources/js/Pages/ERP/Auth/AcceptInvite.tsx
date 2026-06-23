import { useEffect, FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import { UserPlus } from 'lucide-react';

interface Props {
    memberId: number;
    name: string;
    email: string;
    signature: string;
    expires: string;
}

export default function AcceptInvite({ memberId, name, email, signature, expires }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        return () => {
            reset('password', 'password_confirmation');
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('erp.invite.submit', { id: memberId, signature, expires }));
    };

    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-900">
            <Head title={t('erp.accept_invitation')} />

            <div className="w-full sm:max-w-md mt-6 px-6 py-10 bg-white dark:bg-gray-800 shadow-xl overflow-hidden sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                <div className="flex justify-center mb-8">
                    <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center">
                        <UserPlus className="w-8 h-8" />
                    </div>
                </div>

                <h2 className="text-2xl font-bold text-center text-gray-900 dark:text-white mb-2">
                    {t('erp.welcome_to_team')}
                </h2>
                <p className="text-sm text-center text-gray-500 dark:text-gray-400 mb-6">
                    {t('erp.set_password_to_activate_account', { name })}
                </p>

                <div className="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg mb-6 border border-gray-100 dark:border-gray-700">
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">{t('fields.email')}</p>
                    <p className="font-medium text-gray-900 dark:text-white">{email}</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="password">{t('erp.create_password')}</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1 block w-full bg-gray-50"
                            autoComplete="new-password"
                            autoFocus
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        {errors.password && <p className="text-sm text-red-600 mt-1">{errors.password}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">{t('erp.confirm_password')}</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="mt-1 block w-full bg-gray-50"
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                        {errors.password_confirmation && <p className="text-sm text-red-600 mt-1">{errors.password_confirmation}</p>}
                    </div>

                    <Button className="w-full" size="lg" disabled={processing}>
                        {processing ? t('general.processing') : t('erp.activate_account')}
                    </Button>
                </form>
            </div>
        </div>
    );
}
