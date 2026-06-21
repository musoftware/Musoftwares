import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function Edit({ auth, rule }) {
    const { data, setData, put, processing, errors } = useForm({
        name: rule.name || '',
        event_trigger: rule.event_trigger || 'App\\Events\\LeadStageChanged',
        is_active: rule.is_active,
        conditions: rule.conditions || {},
        actions: rule.actions && rule.actions.length > 0 ? rule.actions : [{ type: 'send_email', target: '', template: '' }],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('settings.automations.update', rule.id));
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Edit Automation Rule</h2>}>
            <Head title="Edit Automation" />

            <div className="py-12 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="bg-slate-50 border-b border-slate-100">
                        <CardTitle>Edit Rule: {rule.name}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">Rule Name</Label>
                                <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Welcome Email to New Lead" />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="event_trigger">Trigger Event</Label>
                                <Select value={data.event_trigger} onValueChange={v => setData('event_trigger', v)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Event" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="App\Events\LeadStageChanged">Lead Stage Changed</SelectItem>
                                        <SelectItem value="App\Events\InvoicePaid">Invoice Paid</SelectItem>
                                        <SelectItem value="App\Events\ProposalAccepted">Proposal Accepted</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.event_trigger && <p className="text-sm text-red-500">{errors.event_trigger}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>Status</Label>
                                <Select value={data.is_active ? '1' : '0'} onValueChange={v => setData('is_active', v === '1')}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Active</SelectItem>
                                        <SelectItem value="0">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="pt-4 border-t border-slate-100">
                                <h4 className="text-sm font-medium text-slate-800 mb-4">Actions</h4>
                                <div className="space-y-4 p-4 border border-slate-200 rounded-lg bg-slate-50">
                                    <div className="space-y-2">
                                        <Label>Action Type</Label>
                                        <Select value={data.actions[0].type} onValueChange={v => setData('actions', [{...data.actions[0], type: v}])}>
                                            <SelectTrigger><SelectValue placeholder="Action" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="send_email">Send Email</SelectItem>
                                                <SelectItem value="update_tag">Update Tag</SelectItem>
                                                <SelectItem value="webhook">Send Webhook</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Link href={route('settings.automations.index')}>
                                    <Button type="button" variant="outline">Cancel</Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700">
                                    Update Rule
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
