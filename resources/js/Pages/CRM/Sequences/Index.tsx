import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

export default function Index({ sequences }) {
    const { auth } = usePage().props as any;
    const [isCreateOpen, setIsCreateOpen] = React.useState(false);
    const [newSequence, setNewSequence] = React.useState({ name: '', trigger_type: 'manual', is_active: false });

    const handleCreate = (e) => {
        e.preventDefault();
        router.post(route('crm.sequences.store'), newSequence, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewSequence({ name: '', trigger_type: 'manual', is_active: false });
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this sequence?')) {
            router.delete(route('crm.sequences.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Sequences (Drip Campaigns)</h2>}
        >
            <div className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
            <div className="mb-6 flex justify-end">
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button><Plus className="w-4 h-4 mr-2" /> Create Sequence</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>Create New Sequence</DialogTitle>
                                <DialogDescription>A sequence is a series of automated emails or WhatsApp messages sent over time.</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label>Sequence Name</Label>
                                    <Input 
                                        required 
                                        value={newSequence.name} 
                                        onChange={(e) => setNewSequence({...newSequence, name: e.target.value})} 
                                        placeholder="e.g. Welcome Series" 
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Trigger Type</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newSequence.trigger_type}
                                        onChange={(e) => setNewSequence({...newSequence, trigger_type: e.target.value})}
                                    >
                                        <option value="manual">Manual Enrollment</option>
                                        <option value="on_register">On User Registration</option>
                                        <option value="on_purchase">On Purchase</option>
                                    </select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create Sequence</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {sequences.data.map(seq => (
                    <div key={seq.id} className="bg-white rounded-lg shadow border overflow-hidden flex flex-col">
                        <div className="p-5 flex-1">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="font-semibold text-lg text-gray-900">{seq.name}</h3>
                                {seq.is_active ? (
                                    <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                                ) : (
                                    <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Draft</span>
                                )}
                            </div>
                            
                            <div className="space-y-3 mb-6">
                                <div className="flex items-center text-sm text-gray-500">
                                    <GitMerge className="w-4 h-4 mr-2 text-gray-400" />
                                    <span>Trigger: <span className="font-medium text-gray-700">{seq.trigger_type.replace('_', ' ')}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Users className="w-4 h-4 mr-2 text-blue-400" />
                                    <span>Enrolled: <span className="font-medium text-gray-700">{seq.states_count}</span> Leads</span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <div className="w-4 h-4 mr-2 rounded-full border-2 border-indigo-400 flex items-center justify-center">
                                        <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full"></div>
                                    </div>
                                    <span>Steps: <span className="font-medium text-gray-700">{seq.steps_count}</span> Emails/Messages</span>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 border-t p-3 flex justify-between items-center">
                            <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(seq.id)}>
                                <Trash2 className="w-4 h-4" />
                            </Button>
                            <Link href={route('crm.sequences.show', seq.id)}>
                                <Button size="sm" variant="outline">
                                    <Edit className="w-4 h-4 mr-2" /> Builder
                                </Button>
                            </Link>
                        </div>
                    </div>
                ))}

                {sequences.data.length === 0 && (
                    <div className="col-span-full py-12 text-center text-gray-500 bg-white rounded-lg border shadow-sm">
                        <GitMerge className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <h3 className="text-lg font-medium text-gray-900">No Sequences Found</h3>
                        <p className="mt-1">Create your first sequence to start automating your marketing.</p>
                    </div>
                )}
            </div>
            </div>
        </AuthenticatedLayout>
    );
}
