import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { __ } from '@/lib/i18n';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        event_trigger: 'App\\Events\\LeadStageChanged',
        is_active: true,
        conditions: {},
        actions: [{ type: 'send_email', target: '', template: '' }],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('settings.automations.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">{__('general.create_automation_rule')}</h2>}>
            <Head title={__('general.create_automation')} />

            <div className="py-12 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="bg-slate-50 border-b border-slate-100">
                        <CardTitle>{__('general.rule_details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">{__('general.rule_name')}</Label>
                                <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Welcome Email to New Lead" />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="event_trigger">{__('general.trigger_event')}</Label>
                                <Select value={data.event_trigger} onValueChange={v => setData('event_trigger', v as string)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={__('general.select_event')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="App\Events\LeadStageChanged">{__('general.lead_stage_changed')}</SelectItem>
                                        <SelectItem value="App\Events\InvoicePaid">{__('general.invoice_paid')}</SelectItem>
                                        <SelectItem value="App\Events\ProposalAccepted">{__('general.proposal_accepted')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.event_trigger && <p className="text-sm text-red-500">{errors.event_trigger}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>{__('general.status')}</Label>
                                <Select value={data.is_active ? '1' : '0'} onValueChange={v => setData('is_active', v === '1')}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={__('general.status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">{__('general.active')}</SelectItem>
                                        <SelectItem value="0">{__('general.inactive')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="pt-4 border-t border-slate-100">
                                <h4 className="text-sm font-medium text-slate-800 mb-4">Conditions (Optional)</h4>
                                <div className="space-y-4 p-4 border border-slate-200 rounded-lg bg-slate-50">
                                    <div className="space-y-2">
                                        <Label>Condition Key (e.g., new_stage)</Label>
                                        <Input value={Object.keys(data.conditions || {})[0] || ''} onChange={e => {
                                            const key = e.target.value;
                                            const val = Object.values(data.conditions || {})[0] || '';
                                            setData('conditions', key ? { [key]: val } : {});
                                        }} placeholder="new_stage" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Expected Value (e.g., WON)</Label>
                                        <Input value={(Object.values(data.conditions || {})[0] as string) || ''} onChange={e => {
                                            const key = Object.keys(data.conditions || {})[0] || 'new_stage';
                                            setData('conditions', { [key]: e.target.value });
                                        }} placeholder="WON" />
                                    </div>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-slate-100">
                                <h4 className="text-sm font-medium text-slate-800 mb-4">{__('general.actions')}</h4>
                                <div className="space-y-4 p-4 border border-slate-200 rounded-lg bg-slate-50">
                                    <div className="space-y-2">
                                        <Label>{__('general.action_type')}</Label>
                                        <Select value={data.actions[0]?.type || 'send_email'} onValueChange={v => setData('actions', [{...(data.actions[0] || {}), type: v as string}])}>
                                            <SelectTrigger><SelectValue placeholder={__('general.action')} /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="send_email">{__('general.send_email')}</SelectItem>
                                                <SelectItem value="update_tag">{__('general.update_tag')}</SelectItem>
                                                <SelectItem value="webhook">{__('general.send_webhook')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Target {data.actions[0]?.type === 'send_email' ? '(Email Address, leave blank for Lead Email)' : data.actions[0]?.type === 'update_tag' ? '(Tag Name)' : '(Webhook URL)'}</Label>
                                        <Input value={data.actions[0]?.target || ''} onChange={e => setData('actions', [{...(data.actions[0] || {}), target: e.target.value}])} placeholder={data.actions[0]?.type === 'webhook' ? 'https://...' : ''} />
                                    </div>
                                    {data.actions[0]?.type === 'send_email' && (
                                    <div className="space-y-2">
                                        <Label>Template / Message</Label>
                                        <Input value={data.actions[0]?.template || ''} onChange={e => setData('actions', [{...(data.actions[0] || {}), template: e.target.value}])} placeholder={__('general.welcome_to_our_service')} />
                                    </div>
                                    )}
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Link href={route('settings.automations.index')}>
                                    <Button type="button" variant="outline">{__('general.cancel')}</Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700">
                                    {__('general.save_rule')}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
