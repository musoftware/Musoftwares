import React, { useState } from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, MessageSquare, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function CustomerShow({ customer }: { customer: any }) {
    const [note, setNote] = useState('');
    const [savingNote, setSavingNote] = useState(false);

    const handleAddNote = () => {
        if (!note.trim()) return;
        setSavingNote(true);
        router.post(route('crm.customers.notes.store', customer.id), {
            note: note,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNote('');
                setSavingNote(false);
            },
            onError: () => setSavingNote(false)
        });
    };

    return (
        <CrmLayout title={customer.name} activeMenu="customers">
            <Head title={`${__('crm.customer')}: ${customer.name}`} />
            <div className="p-6 max-w-7xl mx-auto">
                <div className="mb-6 flex items-center gap-4">
                    <Link href={route('crm.customers.index')} className="text-slate-500 hover:text-slate-900">
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <h1 className="text-2xl font-bold text-slate-900">{customer.name}</h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-1 space-y-6">
                        <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                            <h2 className="text-lg font-semibold mb-4 border-b pb-2">{__('crm.customer_details')}</h2>
                            <div className="space-y-3 text-sm">
                                <div>
                                    <span className="block text-slate-500">{__('crm.email')}</span>
                                    <span className="font-medium">{customer.email || '-'}</span>
                                </div>
                                <div>
                                    <span className="block text-slate-500">{__('crm.phone')}</span>
                                    <span className="font-medium">{customer.phone || '-'}</span>
                                </div>
                                <div>
                                    <span className="block text-slate-500">{__('crm.company')}</span>
                                    <span className="font-medium">{customer.company || '-'}</span>
                                </div>
                                <div>
                                    <span className="block text-slate-500">{__('crm.total_value')}</span>
                                    <span className="font-medium font-mono">${customer.total_value}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="md:col-span-2 space-y-6">
                        <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                            <h2 className="text-lg font-semibold mb-4 border-b pb-2">{__('crm.notes_and_activity')}</h2>
                            
                            <div className="mb-6 space-y-3">
                                <Textarea 
                                    placeholder={__('crm.add_a_note')} 
                                    value={note}
                                    onChange={(e) => setNote(e.target.value)}
                                    className="min-h-[100px]"
                                />
                                <div className="flex justify-end">
                                    <Button onClick={handleAddNote} disabled={savingNote || !note.trim()}>
                                        {savingNote ? <Loader2 className="h-4 w-4 animate-spin me-2" /> : <MessageSquare className="h-4 w-4 me-2" />}
                                        {__('crm.save_note')}
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-4">
                                {customer.activities && customer.activities.length > 0 ? (
                                    customer.activities.map((activity: any) => (
                                        <div key={activity.id} className="p-4 bg-slate-50 rounded-lg border border-slate-100">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm font-medium text-slate-700 capitalize">
                                                    {activity.event.replace('_', ' ')}
                                                </span>
                                                <span className="text-xs text-slate-500">
                                                    {new Date(activity.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            {activity.metadata && activity.metadata.note && (
                                                <p className="text-sm text-slate-600 whitespace-pre-wrap">
                                                    {activity.metadata.note}
                                                </p>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-center text-slate-500 py-4">{__('crm.no_activity_yet')}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </CrmLayout>
    );
}
