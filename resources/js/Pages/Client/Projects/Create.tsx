import React from 'react';
import { useForm } from '@inertiajs/react';
import ClientPage, { Card, TextAction } from '@/Components/Client/ClientPage';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

/** Start a project: a name and a few lines about what you need. Presets fill both in. */
export default function CreateProject() {
    const { data, setData, post, processing, errors } = useForm({
        project_name: '',
        description: '',
    });

    React.useEffect(() => {
        if (typeof window !== 'undefined') {
            const prefill = new URLSearchParams(window.location.search).get('prefill_desc');
            if (prefill) setData('description', decodeURIComponent(prefill));
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const presets = (['ecommerce', 'whatsapp', 'corporate', 'saas'] as const).map((key) => ({
        title: __(`presets.${key}_title`),
        name: __(`presets.${key}_name`),
        desc: __(`presets.${key}_desc`),
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('client.projects.store-new'));
    };

    return (
        <ClientPage title={__('client_work.create.title')} hint={__('client_work.create.hint')} width="sm">
            <Card>
                <p className="mb-2 text-sm font-semibold">{__('client_work.create.presets')}</p>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {presets.map((preset) => (
                        <button
                            key={preset.title}
                            type="button"
                            onClick={() => setData((prev) => ({ ...prev, project_name: preset.name, description: preset.desc }))}
                            className="rounded-2xl border border-black/5 bg-[#f5f5f7] p-3 text-start text-sm font-semibold transition-colors hover:border-[#0071e3]/30 hover:text-[#0071e3] dark:border-white/10 dark:bg-zinc-800"
                        >
                            {preset.title}
                        </button>
                    ))}
                </div>

                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div className="space-y-1.5">
                        <label htmlFor="project_name" className="block text-sm font-semibold">
                            {__('client_work.create.name')} <span className="text-rose-500">*</span>
                        </label>
                        <Input
                            id="project_name"
                            type="text"
                            placeholder={__('client.projects_create_name_placeholder')}
                            value={data.project_name}
                            onChange={(e) => setData('project_name', e.target.value)}
                            className="h-11 rounded-xl"
                            required
                        />
                        {errors.project_name && <p className="text-sm text-rose-600">{errors.project_name}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <label htmlFor="project_description" className="block text-sm font-semibold">{__('client_work.create.description')}</label>
                        <Textarea
                            id="project_description"
                            placeholder={__('client.projects_create_description_placeholder')}
                            rows={6}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="rounded-xl"
                        />
                        {errors.description && <p className="text-sm text-rose-600">{errors.description}</p>}
                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_work.create.tip')}</p>
                    </div>

                    <div className="flex flex-wrap items-center justify-end gap-4 border-t border-black/5 pt-4 dark:border-white/10">
                        <TextAction href={route('client.projects.index')}>{__('client_work.create.cancel')}</TextAction>
                        <button
                            type="submit"
                            disabled={processing || !data.project_name.trim()}
                            className="inline-flex h-11 items-center justify-center rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50"
                        >
                            {processing ? __('client_work.create.creating') : __('client_work.create.submit')}
                        </button>
                    </div>
                </form>
            </Card>
        </ClientPage>
    );
}
