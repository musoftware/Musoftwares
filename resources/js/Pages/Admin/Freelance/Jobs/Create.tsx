import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Briefcase } from 'lucide-react';
import { AsyncCombobox } from '@/Components/ui/AsyncCombobox';
import { __ } from '@/lib/i18n';

export default function Create() {
    const [clientIdStr, setClientIdStr] = useState<string>('');

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        budget: '',
        duration: '',
        type: 'fixed',
        client_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.freelance.jobs.store'));
    };

    return (
        <AdminSidebarLayout title={__('freelance.create_job')} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.jobs.index')} className="text-gray-500 hover:text-gray-900 me-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>{__('freelance.create_job')}</span>
            </div>
        }>
            <div className="space-y-6 w-full max-w-7xl mx-auto">

                <form onSubmit={submit}>
                    <Card className="shadow-sm border-slate-200">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                            <CardTitle className="text-lg font-semibold">{__('freelance.job_details')}</CardTitle>
                            <CardDescription>
                                {__('freelance.job_details_help', undefined, 'Fill in the basic information about the job.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6 pt-6">
                            
                            {/* Client Selection (Async Combobox) */}
                            <div className="space-y-2">
                                <Label className="text-slate-700 font-semibold" htmlFor="client_id">
                                    {__('freelance.client')} <span className="text-rose-500">*</span>
                                </Label>
                                <AsyncCombobox
                                    endpoint={route('search-users')}
                                    value={clientIdStr}
                                    initialLabel={__('freelance.search_client', undefined, 'Search for client...')}
                                    onChange={(val) => {
                                        setClientIdStr(val ? val.toString() : '');
                                        setData('client_id', val ? val.toString() : '');
                                    }}
                                    placeholder={__('freelance.search_client_placeholder', undefined, 'Type name or email...')}
                                    className="w-full bg-white"
                                />
                                {errors.client_id && <p className="text-[13px] text-rose-500 font-medium">{errors.client_id}</p>}
                                <p className="text-xs text-slate-500">{__('freelance.client_search_hint', undefined, 'Type to search clients by name or email.')}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="title">
                                        {__('freelance.job_title')} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder={__('freelance.job_title_placeholder', undefined, 'e.g. Build an E-commerce Website')}
                                        className="h-10 transition-shadow focus-visible:ring-slate-800/20 focus-visible:border-slate-800"
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
                                        min="0"
                                        step="0.01"
                                        value={data.budget}
                                        onChange={(e) => setData('budget', e.target.value)}
                                        placeholder="0.00"
                                        className="h-10 transition-shadow focus-visible:ring-slate-800/20 focus-visible:border-slate-800"
                                    />
                                    {errors.budget && <p className="text-[13px] text-rose-500 font-medium">{errors.budget}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="duration">
                                        {__('freelance.expected_duration')}
                                    </Label>
                                    <Input
                                        id="duration"
                                        value={data.duration}
                                        onChange={(e) => setData('duration', e.target.value)}
                                        placeholder={__('freelance.duration_placeholder', undefined, 'e.g. 2-4 weeks, Less than 1 month')}
                                        className="h-10 transition-shadow focus-visible:ring-slate-800/20 focus-visible:border-slate-800"
                                    />
                                    {errors.duration && <p className="text-[13px] text-rose-500 font-medium">{errors.duration}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold" htmlFor="description">
                                        {__('freelance.job_description')} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder={__('freelance.job_description_placeholder', undefined, 'Describe the job requirements in detail...')}
                                        rows={6}
                                        className="resize-none transition-shadow focus-visible:ring-slate-800/20 focus-visible:border-slate-800"
                                    />
                                    {errors.description && <p className="text-[13px] text-rose-500 font-medium">{errors.description}</p>}
                                </div>
                            </div>

                        </CardContent>
                        <CardFooter className="bg-slate-50/80 border-t border-slate-100 py-4 flex justify-end gap-3">
                            <Link href={route('admin.freelance.jobs.index')}>
                                <Button type="button" variant="ghost" disabled={processing}>
                                    {__('freelance.cancel')}
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing} className="shadow-sm">
                                <Save className="me-2 h-4 w-4" />
                                {__('freelance.create_job')}
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
