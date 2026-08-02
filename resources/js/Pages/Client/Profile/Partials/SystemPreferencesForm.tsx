import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
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

    const { data, setData, patch, processing, recentlySuccessful, errors } =
        useForm({
            enable_3d_dashboard: user.enable_3d_dashboard ?? true,
            default_ai_model: user.default_ai_model ?? 'gemini',
            openai_api_key: user.openai_api_key ?? '',
            openai_model: user.openai_model ?? 'gpt-4o-mini',
            gemini_api: user.gemini_api ?? '',
            gemini_model: user.gemini_model ?? 'gemini-2.0-flash',
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
                     'Manage dashboard layout settings and custom API integrations.'}
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* Dashboard Switch */}
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

                <div className="border-t border-gray-200 my-6 pt-6">
                    <h3 className="text-md font-medium text-gray-900">
                        {__('general.ai_settings') || 'AI & API Key Configurations'}
                    </h3>
                    <p className="mt-1 text-sm text-gray-600">
                        {__('general.ai_settings_description') || 
                         'Provide your own API keys to enable or override AI features (such as AutoSMS payment processing). If empty, platform limits apply.'}
                    </p>
                </div>

                {/* AI Preferences Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="col-span-1 md:col-span-2">
                        <InputLabel htmlFor="default_ai_model" value={__('general.default_ai_provider') || 'Default AI Provider'} />
                        <select
                            id="default_ai_model"
                            name="default_ai_model"
                            value={data.default_ai_model}
                            onChange={(e) => setData('default_ai_model', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="gemini">Google Gemini</option>
                            <option value="openai">OpenAI (ChatGPT)</option>
                        </select>
                        <InputError message={errors.default_ai_model} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="openai_api_key" value="OpenAI API Key" />
                        <TextInput
                            id="openai_api_key"
                            type="password"
                            value={data.openai_api_key}
                            onChange={(e) => setData('openai_api_key', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="sk-..."
                            autoComplete="off"
                        />
                        <InputError message={errors.openai_api_key} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="openai_model" value="OpenAI Model" />
                        <TextInput
                            id="openai_model"
                            type="text"
                            value={data.openai_model}
                            onChange={(e) => setData('openai_model', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="gpt-4o-mini"
                        />
                        <InputError message={errors.openai_model} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="gemini_api" value="Gemini API Key" />
                        <TextInput
                            id="gemini_api"
                            type="password"
                            value={data.gemini_api}
                            onChange={(e) => setData('gemini_api', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="AIzaSy..."
                            autoComplete="off"
                        />
                        <InputError message={errors.gemini_api} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="gemini_model" value="Gemini Model" />
                        <TextInput
                            id="gemini_model"
                            type="text"
                            value={data.gemini_model}
                            onChange={(e) => setData('gemini_model', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="gemini-2.0-flash"
                        />
                        <InputError message={errors.gemini_model} className="mt-2" />
                    </div>
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
