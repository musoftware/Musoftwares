import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ShieldCheck, Plus, Clock } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function AddSubscription({ user, serviceItems }) {
    const { data, setData, post, processing, errors } = useForm({
        object: '',
        duration_days: 30,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/admin/users/${user.id}/membership`, {
            preserveScroll: true,
            onSuccess: () => {
                // Return to user profile or show success
            }
        });
    };

    return (
        <AdminSidebarLayout title={`Add Subscription: ${user.name}`} header="Add Subscription">
            <Head title={`Add Subscription - ${user.name}`} />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-3xl font-bold font-sora">{__('admin.add_subscription')}</h1>
                    <p className="text-slate-500">{__('admin.assign_new_module_or_addon_to')} {user.name}</p>
                </div>
                <div className="flex space-x-2">
                    <Link href={`/admin/users/${user.id}`} className="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        {__('admin.back_to_profile')}
                    </Link>
                </div>
            </div>

            <div className="max-w-2xl mx-auto bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center gap-3 mb-6 border-b pb-4">
                    <div className="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                        <ShieldCheck size={20} />
                    </div>
                    <div>
                        <h3 className="font-bold text-slate-900 font-sora">{__('admin.subscription_details')}</h3>
                        <p className="text-xs text-slate-500">{__('admin.select_the_service_plan_and_duration')}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <Label htmlFor="object" className="text-slate-700 font-bold mb-1.5 block">{__('admin.select_module_addon')}</Label>
                        <select 
                            id="object"
                            className="w-full border-slate-300 rounded-lg text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                            value={data.object} 
                            onChange={e => setData('object', e.target.value)} 
                            disabled={processing}
                            required
                        >
                            <option value="">-- {__('general.select')} --</option>
                            {serviceItems.map(item => (
                                <option key={item.id} value={item.id}>{item.name}</option>
                            ))}
                        </select>
                        {errors.object && <p className="text-red-500 text-sm mt-1">{errors.object}</p>}
                    </div>

                    <div>
                        <Label htmlFor="duration_days" className="text-slate-700 font-bold mb-1.5 flex items-center gap-1 block">
                            <Clock size={16} className="text-slate-500" />
                            {__('admin.duration_days')}
                        </Label>
                        <Input
                            id="duration_days"
                            type="number"
                            min="1"
                            className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                            value={data.duration_days}
                            onChange={e => setData('duration_days', e.target.value)}
                            required
                            disabled={processing}
                        />
                        {errors.duration_days && <p className="text-red-500 text-sm mt-1">{errors.duration_days}</p>}
                    </div>

                    <Button 
                        type="submit" 
                        disabled={processing || !data.object || !data.duration_days} 
                        className="w-full py-6 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg shadow-sm text-base transition-all"
                    >
                        <Plus className="mr-2" size={18} />
                        {processing ? __('general.saving') : __('admin.activate_subscription')}
                    </Button>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
