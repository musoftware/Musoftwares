import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Briefcase } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function CreateProject({ clients }: { clients: any[] }) {
    const [form, setForm] = useState({
        name: '',
        client_id: '',
        status: 'Planning',
        budget: '',
        due_date: ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.projects.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('projects');

    return (
        <ERPLayout title="Create Project" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'projects' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Create New Project</h1>
                        <p className="text-slate-500 text-sm mt-0.5">Establish a new project under a client.</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Briefcase className="w-5 h-5" /> Project Details
                        </CardTitle>
                        <CardDescription className="text-slate-500">
                            Provide the necessary information to setup the project scope.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Project Name <span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder="Website Redesign" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Client <span className="text-red-500">*</span></label>
                                    <Select value={form.client_id} onValueChange={(val) => setForm({...form, client_id: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder="Select a Client" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            {clients.map(c => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.client_id && <p className="text-xs text-red-500">{errors.client_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Status</label>
                                    <Select value={form.status} onValueChange={(val) => setForm({...form, status: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            <SelectItem value="Planning">Planning</SelectItem>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="On Hold">On Hold</SelectItem>
                                            <SelectItem value="Completed">Completed</SelectItem>
                                            <SelectItem value="Cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Due Date</label>
                                    <Input 
                                        type="date"
                                        value={form.due_date} 
                                        onChange={e => setForm({...form, due_date: e.target.value})} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.due_date && <p className="text-xs text-red-500">{errors.due_date}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Budget ($)</label>
                                    <Input 
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.budget} 
                                        onChange={e => setForm({...form, budget: e.target.value})} 
                                        placeholder="5000.00" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.budget && <p className="text-xs text-red-500">{errors.budget}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'projects' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Creating...' : 'Create Project'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
