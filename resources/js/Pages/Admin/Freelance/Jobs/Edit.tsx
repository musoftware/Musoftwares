import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, Save, Briefcase } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Edit({ job }: any) {
    const { data, setData, put, processing, errors } = useForm({
        title: job.title || '',
        description: job.description || '',
        budget: job.budget || '',
        duration: job.duration || '',
        type: job.type || 'fixed',
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
            <div className="space-y-6 max-w-4xl mx-auto">
                <form onSubmit={submit}>
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                            <CardTitle className="text-lg font-semibold">{__('freelance.job_details')}</CardTitle>
                            <CardDescription>
                                {__('freelance.job_details_help', 'Update the basic information about the job.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6 pt-6">

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="title">
                                        {__('freelance.job_title')} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="h-10 transition-shadow focus-visible:ring-indigo-500/20 focus-visible:border-indigo-500"
                                        required
                                    />
                                    {errors.title && <p className="text-[13px] text-rose-500 font-medium">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="type">
                                        {__('freelance.job_type')}
                                    </Label>
                                    <select
                                        id="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="fixed">{__('freelance.fixed_price')}</option>
                                        <option value="hourly">{__('freelance.hourly_rate')}</option>
                                    </select>
                                    {errors.type && <p className="text-[13px] text-rose-500 font-medium">{errors.type}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="budget">
                                        {__('freelance.budget')}
                                    </Label>
                                    <Input
                                        id="budget"
                                        type="number"
                                        step="0.01"
                                        value={data.budget}
                                        onChange={(e) => setData('budget', e.target.value)}
                                        className="h-10 transition-shadow focus-visible:ring-indigo-500/20 focus-visible:border-indigo-500"
                                    />
                                    {errors.budget && <p className="text-[13px] text-rose-500 font-medium">{errors.budget}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="duration">
                                        {__('freelance.duration')}
                                    </Label>
                                    <Input
                                        id="duration"
                                        value={data.duration}
                                        onChange={(e) => setData('duration', e.target.value)}
                                        className="h-10 transition-shadow focus-visible:ring-indigo-500/20 focus-visible:border-indigo-500"
                                    />
                                    {errors.duration && <p className="text-[13px] text-rose-500 font-medium">{errors.duration}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="description">
                                        {__('freelance.description')} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="resize-none min-h-[200px] transition-shadow focus-visible:ring-indigo-500/20 focus-visible:border-indigo-500"
                                        required
                                    />
                                    {errors.description && <p className="text-[13px] text-rose-500 font-medium">{errors.description}</p>}
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="bg-slate-50/80 border-t border-slate-100 py-4 flex justify-end gap-3">
                            <Link href={route('admin.freelance.jobs.show', job.id)}>
                                <Button type="button" variant="ghost" disabled={processing}>
                                    {__('freelance.cancel')}
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing} className="shadow-sm">
                                <Save className="mr-2 h-4 w-4" />
                                {__('freelance.save_changes')}
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
