import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Briefcase, Edit2 } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function EditProject({ project, clients }: { project: any, clients: any[] }) {
    const [form, setForm] = useState({
        name: project.name || '',
        client_id: project.client_id ? project.client_id.toString() : '',
        status: project.status || 'Planning',
        budget: project.budget || '',
        due_date: project.due_date ? project.due_date.split('T')[0] : ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.put(route('erp.projects.update', project.id), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Project — ${project.name}`} />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'projects' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Edit Project</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Update scope and details for {project.name}.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <Edit2 className="w-5 h-5" /> Project Details
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Modify the existing project scope below.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Project Name <span className="text-red-400">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder="Website Redesign" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.name && <p className="text-xs text-red-400">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Client <span className="text-red-400">*</span></label>
                                    <Select value={form.client_id} onValueChange={(val) => setForm({...form, client_id: val})}>
                                        <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                            <SelectValue placeholder="Select a Client" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                            {clients.map(c => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.client_id && <p className="text-xs text-red-400">{errors.client_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Status</label>
                                    <Select value={form.status} onValueChange={(val) => setForm({...form, status: val})}>
                                        <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                            <SelectItem value="Planning">Planning</SelectItem>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="On Hold">On Hold</SelectItem>
                                            <SelectItem value="Completed">Completed</SelectItem>
                                            <SelectItem value="Cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-xs text-red-400">{errors.status}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Due Date</label>
                                    <Input 
                                        type="date"
                                        value={form.due_date} 
                                        onChange={e => setForm({...form, due_date: e.target.value})} 
                                        className="bg-zinc-950 border-zinc-800 text-white [color-scheme:dark]"
                                    />
                                    {errors.due_date && <p className="text-xs text-red-400">{errors.due_date}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Budget ($)</label>
                                    <Input 
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.budget} 
                                        onChange={e => setForm({...form, budget: e.target.value})} 
                                        placeholder="5000.00" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.budget && <p className="text-xs text-red-400">{errors.budget}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'projects' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
