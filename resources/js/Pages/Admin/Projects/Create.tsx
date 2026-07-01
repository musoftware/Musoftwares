import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Plus, AlertCircle } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { ProjectFormFields, EMPTY_PROJECT_FORM, formToPayload, type ProjectFormState } from './Components/ProjectFormFields';
import { __ } from '@/lib/i18n';

interface CreateProps {
    errors?: Record<string, string>;
    initialClient?: { id: number; name: string } | null;
    prefillClientId?: number | null;
}

export default function Create({ errors = {}, initialClient = null, prefillClientId = null }: CreateProps) {
    const [form, setForm] = useState<ProjectFormState>(() => ({
        ...EMPTY_PROJECT_FORM,
        user_id: prefillClientId ? String(prefillClientId) : EMPTY_PROJECT_FORM.user_id,
    }));
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(route('admin.projects.store'), formToPayload(form), {
            onFinish: () => setSubmitting(false),
        });
    };

    const errorFor = (key: string) => errors[key];

    return (
        <AdminSidebarLayout title={__('general.create_new_project')} header={__('general.create_new_project')}>
            <Head title={__('general.create_new_project')} />

            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div>
                    <Link
                        href={route('admin.projects.index')}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ChevronLeft className="h-4 w-4" /> {__('general.back_to_projects')}
                    </Link>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">{__('general.create_new_project')}</h1>
                    <p className="mt-1 text-sm text-slate-500">{__('general.create_first_project_cta')}</p>
                </div>

                {Object.keys(errors).length > 0 && (
                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                        <div>
                            <p className="font-semibold">{__('general.please_fix_the_following')}</p>
                            <ul className="mt-1 list-disc space-y-0.5 ps-4">
                                {Object.entries(errors).map(([key, message]) => (
                                    <li key={key}>
                                        <span className="font-medium">{key}:</span> {message}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <ProjectFormFields
                        form={form}
                        setForm={setForm}
                        includeClient
                        initialClient={initialClient}
                        disabled={submitting}
                    />

                    {errorFor('user_id') && <p className="text-xs text-red-600">{errorFor('user_id')}</p>}
                    {errorFor('project_name') && <p className="text-xs text-red-600">{errorFor('project_name')}</p>}
                    {errorFor('date_end') && <p className="text-xs text-red-600">{errorFor('date_end')}</p>}

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="outline" asChild disabled={submitting}>
                            <Link href={route('admin.projects.index')}>{__('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={submitting} className="gap-2">
                            <Plus className="h-4 w-4" />
                            {submitting ? __('general.saving') : __('general.create_project')}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
