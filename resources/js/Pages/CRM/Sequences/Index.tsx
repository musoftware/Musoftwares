import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, Edit, Plus, Users, GitMerge } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

declare const route: any;

export default function Index({ sequences }: { sequences: any }) {
    const { auth } = usePage().props as any;
    const [isCreateOpen, setIsCreateOpen] = React.useState(false);
    const [newSequence, setNewSequence] = React.useState({ name: '', trigger_type: 'manual', is_active: false });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('crm.sequences.store'), newSequence, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewSequence({ name: '', trigger_type: 'manual', is_active: false });
            }
        });
    };

    const handleDelete = (id: string | number) => {
        if (confirm('Are you sure you want to delete this sequence?')) {
            router.delete(route('crm.sequences.destroy', id));
        }
    };

    return (
        <CrmLayout title={__('general.automated_sequences')} activeMenu="sequences">
            <div className="flex-1 space-y-4 p-8 pt-6">
            <div className="mb-6 flex justify-end">
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button><Plus className="w-4 h-4 me-2" />{__('general.create_sequence')}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>{__('general.create_new_sequence')}</DialogTitle>
                                <DialogDescription>{__('general.a_sequence_is_a_series_of_automated_emails_sent_over_time')}</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label>{__('general.sequence_name')}</Label>
                                    <Input 
                                        required 
                                        value={newSequence.name} 
                                        onChange={(e) => setNewSequence({...newSequence, name: e.target.value})} 
                                        placeholder={__('general.e_g_welcome_series')} 
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('general.trigger_type')}</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newSequence.trigger_type}
                                        onChange={(e) => setNewSequence({...newSequence, trigger_type: e.target.value})}
                                    >
                                        <option value="manual">{__('general.manual_enrollment')}</option>
                                        <option value="on_register">{__('general.on_user_registration')}</option>
                                        <option value="on_purchase">{__('general.on_purchase')}</option>
                                    </select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">{__('general.create_sequence')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {(sequences.data as any).map((seq: any) => (
                    <div key={seq.id} className="bg-white rounded-lg shadow border overflow-hidden flex flex-col">
                        <div className="p-5 flex-1">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="font-semibold text-lg text-gray-900">{seq.name}</h3>
                                {seq.is_active ? (
                                    <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{__('general.active')}</span>
                                ) : (
                                    <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{__('general.draft')}</span>
                                )}
                            </div>
                            
                            <div className="space-y-3 mb-6">
                                <div className="flex items-center text-sm text-gray-500">
                                    <GitMerge className="w-4 h-4 me-2 text-gray-400" />
                                    <span>Trigger: <span className="font-medium text-gray-700">{seq.trigger_type.replace('_', ' ')}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Users className="w-4 h-4 me-2 text-blue-400" />
                                    <span>Enrolled: <span className="font-medium text-gray-700">{seq.states_count}</span> {__('general.leads')}</span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <div className="w-4 h-4 me-2 rounded-full border-2 border-indigo-400 flex items-center justify-center">
                                        <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full"></div>
                                    </div>
                                    <span>Steps: <span className="font-medium text-gray-700">{seq.steps_count}</span>{__('general.emails_messages')}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 border-t p-3 flex justify-between items-center">
                            <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(seq.id)}>
                                <Trash2 className="w-4 h-4" />
                            </Button>
                            <Link href={route('crm.sequences.show', seq.id)}>
                                <Button size="sm" variant="outline">
                                    <Edit className="w-4 h-4 me-2" /> {__('general.builder')}</Button>
                            </Link>
                        </div>
                    </div>
                ))}

                {(sequences.data as any).length === 0 && (
                    <div className="col-span-full py-12 text-center text-gray-500 bg-white rounded-lg border shadow-sm">
                        <GitMerge className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <h3 className="text-lg font-medium text-gray-900">{__('general.no_sequences_found')}</h3>
                        <p className="mt-1">{__('general.create_your_first_sequence_to_start_automating_your_marketing')}</p>
                    </div>
                )}
            </div>
            </div>
        </CrmLayout>
    );
}
