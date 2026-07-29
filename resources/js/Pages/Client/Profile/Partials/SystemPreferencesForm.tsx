import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Switch } from '@/Components/ui/switch';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { __ } from '@/lib/i18n';

export default function SystemPreferencesForm({
    className = '',
}: {
    className?: string;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, processing, recentlySuccessful } =
        useForm({
            enable_3d_dashboard: user.enable_3d_dashboard ?? true,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.preferences.update'), {
            preserveScroll: true,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    {__('general.system_preferences') || 'System Preferences'}
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    {__('general.dashboard_layout_description') || 
                     'Switch between the 3D holographic dashboard with interactive animations and the clean projects portal layout.'}
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="flex items-center gap-4">
                    <Switch
                        id="enable_3d_dashboard"
                        checked={data.enable_3d_dashboard}
                        onCheckedChange={(checked) => setData('enable_3d_dashboard', checked)}
                    />
                    <label
                        htmlFor="enable_3d_dashboard"
                        className="text-sm font-medium text-gray-700 cursor-pointer select-none"
                    >
                        {__('general.enable_3d_dashboard') || 'Enable 3D Holographic Dashboard'}
                    </label>
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>{__('general.save')}</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">{__('general.saved')}</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
