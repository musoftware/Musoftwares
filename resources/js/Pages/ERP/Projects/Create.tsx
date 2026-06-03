import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Briefcase } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import { __ } from '@/lib/i18n';

export default function CreateProject({ clients = [] }: { clients?: any[] }) {
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
        <ERPLayout title={__('general.create_project')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'projects' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.create_new_project')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.establish_a_new_project_under_a_client')}</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Briefcase className="w-5 h-5" />{__('general.project_details')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.provide_the_necessary_information_to_setup_the_project_scope')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.project_name')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder={__('general.website_redesign')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Client <span className="text-red-500">*</span></label>
                                    <ClientAutocomplete
                                        value={form.client_id}
                                        onChange={(val) => setForm({...form, client_id: val})}
                                        error={errors.client_id}
                                    />
                                    {errors.client_id && <p className="text-xs text-red-500">{errors.client_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Status</label>
                                    <Select value={form.status} onValueChange={(val) => setForm({...form, status: val || ''})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder={__('general.select_status_1')} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            <SelectItem value="Planning">Planning</SelectItem>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="On Hold">{__('general.on_hold')}</SelectItem>
                                            <SelectItem value="Completed">Completed</SelectItem>
                                            <SelectItem value="Cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.due_date')}</label>
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
