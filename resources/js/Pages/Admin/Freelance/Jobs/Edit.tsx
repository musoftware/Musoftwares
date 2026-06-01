import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, Save } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Edit({ job }: any) {
    const { data, setData, put, processing, errors } = useForm({
        title: job.title || '',
        description: job.description || '',
        budget: job.budget || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.freelance.jobs.update', job.id));
    };

    return (
        <AdminSidebarLayout title={`${__('freelance.edit_job')}: ${job.title}`} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.jobs.show', job.id)} className="text-gray-500 hover:text-gray-900 mr-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>{__('freelance.edit_job')}</span>
            </div>
        }>
            <div className="max-w-3xl bg-white shadow rounded-lg p-6">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <Label htmlFor="title">{__('freelance.job_title')}</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                        {errors.title && <p className="text-red-500 text-sm mt-1">{errors.title}</p>}
                    </div>

                    <div>
                        <Label htmlFor="budget">{__('freelance.budget')}</Label>
                        <Input
                            id="budget"
                            type="number"
                            step="0.01"
                            value={data.budget}
                            onChange={(e) => setData('budget', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        {errors.budget && <p className="text-red-500 text-sm mt-1">{errors.budget}</p>}
                    </div>

                    <div>
                        <Label htmlFor="description">{__('freelance.description')}</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1 block w-full min-h-[200px]"
                            required
                        />
                        {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                    </div>

                    <div className="flex items-center justify-end">
                        <Link 
                            href={route('admin.freelance.jobs.show', job.id)} 
                            className="mr-4 text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancel
                        </Link>
                        <Button type="submit" disabled={processing}>
                            <Save className="w-4 h-4 mr-2" />
                            {__('freelance.save_changes')}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
