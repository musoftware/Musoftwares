import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Ticket } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function CreateTicket() {
    const [form, setForm] = useState({
        title: '',
        description: '',
        priority: 'Normal'
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.tickets.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__('general.create_ticket')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'tickets' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.create_new_ticket')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.submit_a_new_support_ticket_or_task')}</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Ticket className="w-5 h-5" />{__('general.ticket_details')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.describe_the_issue_or_task_that_needs_to_be_addressed')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.ticket_subject')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.title} 
                                        onChange={e => setForm({...form, title: e.target.value})} 
                                        placeholder={__('general.invoice_transaction_double_charge_error')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Priority</label>
                                    <Select value={form.priority} onValueChange={(val) => setForm({...form, priority: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900 w-full">
                                            <SelectValue placeholder={__('general.select_priority_1')} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            <SelectItem value="Low">Low</SelectItem>
                                            <SelectItem value="Normal">Normal</SelectItem>
                                            <SelectItem value="High">High</SelectItem>
                                            <SelectItem value="Urgent">Urgent</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.priority && <p className="text-xs text-red-500">{errors.priority}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Description</label>
                                    <Textarea 
                                        value={form.description} 
                                        onChange={e => setForm({...form, description: e.target.value})} 
                                        placeholder={__('general.provide_more_details_about_this_ticket')} 
                                        className="bg-white border-slate-200 text-slate-900 min-h-[100px]"
                                    />
                                    {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'tickets' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Creating...' : 'Create Ticket'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
