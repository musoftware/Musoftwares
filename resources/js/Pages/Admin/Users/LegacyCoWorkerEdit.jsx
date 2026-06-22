import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Users, Save, ChevronLeft, ArrowLeft } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';

export default function LegacyCoWorkerEdit({ worker, techTags = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        person_name: worker.person_name || '',
        email: worker.email || '',
        mobile: worker.mobile || '',
        facebook: worker.facebook || '',
        linked_in: worker.linked_in || '',
        whatsapp: worker.whatsapp || '',
        time_from: worker.time_from || '',
        time_to: worker.time_to || '',
        selectedTechTags: worker.tech_tags ? worker.tech_tags.map(t => t.id) : [],
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/users/legacy-coworker/${worker.id}`);
    };

    const handleTagToggle = (tagId) => {
        if (data.selectedTechTags.includes(tagId)) {
            setData('selectedTechTags', data.selectedTechTags.filter(id => id !== tagId));
        } else {
            setData('selectedTechTags', [...data.selectedTechTags, tagId]);
        }
    };

    return (
        <AdminSidebarLayout title={`Edit ${worker.person_name}`} header="Edit Co-Worker">
            <Head title={`Edit ${worker.person_name}`} />

            <div className="mb-6 flex items-center gap-4">
                <Link
                    href={`/admin/users/legacy-coworker/${worker.id}`}
                    className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors"
                >
                    <ArrowLeft className="w-5 h-5" />
                </Link>
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">{__('general.edit_legacy_co_worker')}</h1>
                    <p className="text-sm text-slate-500">{__('general.update_coworker_contact_availability_and_skills')}</p>
                </div>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <form onSubmit={submit} className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {/* Basic Info */}
                        <div className="space-y-4">
                            <h2 className="text-lg font-semibold text-slate-800">{__('general.basic_information')}</h2>
                            
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.full_name')}</label>
                                <Input
                                    type="text"
                                    value={data.person_name}
                                    onChange={e => setData('person_name', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.person_name && <p className="text-red-500 text-xs mt-1">{errors.person_name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.email_address')}</label>
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={e => setData('email', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.mobile_number')}</label>
                                <Input
                                    type="text"
                                    value={data.mobile}
                                    onChange={e => setData('mobile', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.mobile && <p className="text-red-500 text-xs mt-1">{errors.mobile}</p>}
                            </div>
                            
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.whatsapp')}</label>
                                <Input
                                    type="text"
                                    value={data.whatsapp}
                                    onChange={e => setData('whatsapp', e.target.value)}
                                    placeholder="+20 101 521 8548"
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.whatsapp && <p className="text-red-500 text-xs mt-1">{errors.whatsapp}</p>}
                            </div>
                        </div>

                        {/* Social & Availability */}
                        <div className="space-y-4">
                            <h2 className="text-lg font-semibold text-slate-800">{__('general.social_availability')}</h2>
                            
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.facebook_url')}</label>
                                <Input
                                    type="url"
                                    value={data.facebook}
                                    onChange={e => setData('facebook', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.facebook && <p className="text-red-500 text-xs mt-1">{errors.facebook}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.linkedin_url')}</label>
                                <Input
                                    type="url"
                                    value={data.linked_in}
                                    onChange={e => setData('linked_in', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                />
                                {errors.linked_in && <p className="text-red-500 text-xs mt-1">{errors.linked_in}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.available_from')}</label>
                                    <Input
                                        type="time"
                                        value={data.time_from}
                                        onChange={e => setData('time_from', e.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                    />
                                    {errors.time_from && <p className="text-red-500 text-xs mt-1">{errors.time_from}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">{__('general.available_to')}</label>
                                    <Input
                                        type="time"
                                        value={data.time_to}
                                        onChange={e => setData('time_to', e.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800"
                                    />
                                    {errors.time_to && <p className="text-red-500 text-xs mt-1">{errors.time_to}</p>}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tech Tags */}
                    <div className="mt-8">
                        <h2 className="text-lg font-semibold text-slate-800 mb-3">{__('general.programming_technologies')}</h2>
                        <div className="flex flex-wrap gap-2">
                            {techTags.map(tag => (
                                <Button
                                    key={tag.id}
                                    type="button"
                                    onClick={() => handleTagToggle(tag.id)}
                                    className={`px-3 py-1.5 rounded-full text-sm font-medium border transition-colors ${
                                        data.selectedTechTags.includes(tag.id)
                                            ? 'bg-indigo-100 text-slate-900 border-indigo-200'
                                            : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                    }`}
                                >
                                    {tag.name}
                                </Button>
                            ))}
                        </div>
                        {errors.selectedTechTags && <p className="text-red-500 text-xs mt-2">{errors.selectedTechTags}</p>}
                    </div>

                    <div className="mt-8 flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <Link
                            href={`/admin/users/legacy-coworker/${worker.id}`}
                            className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-800"
                        >
                            {__('general.cancel')}</Link>
                        <Button type="submit" disabled={processing} className="flex items-center gap-2">
                            <Save className="w-4 h-4" />{__('general.save_changes')}</Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
