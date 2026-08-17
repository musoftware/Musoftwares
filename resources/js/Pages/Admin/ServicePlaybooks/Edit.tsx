import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import MDEditor from '@uiw/react-md-editor';
import { ArrowRight, Save, Copy, Check, Info, Megaphone, DollarSign, ListChecks, Wrench, HeartHandshake } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { __ } from '@/lib/i18n';

interface Service {
    id: number;
    title: string;
}

interface Playbook {
    id: number;
    title: string;
    service_id?: number | string;
    marketing_message?: string;
    pricing_info?: string;
    client_requirements?: string;
    execution_workflow?: string;
    thank_you_message?: string;
    notes?: string;
}

interface EditProps {
    playbook: Playbook;
    services: Service[];
}

export default function Edit({ playbook, services }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: playbook.title || '',
        service_id: playbook.service_id || '',
        marketing_message: playbook.marketing_message || '',
        pricing_info: playbook.pricing_info || '',
        client_requirements: playbook.client_requirements || '',
        execution_workflow: playbook.execution_workflow || '',
        thank_you_message: playbook.thank_you_message || '',
        notes: playbook.notes || '',
    });

    const [isLoadingPricing, setIsLoadingPricing] = useState(false);
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const handleServiceChange = async (serviceId: string) => {
        setData('service_id', serviceId);

        if (!serviceId) return;

        try {
            setIsLoadingPricing(true);
            const res = await axios.get(`/admin/marketplace/service-playbooks/service-details/${serviceId}`);
            if (res.data && res.data.pricing_template) {
                setData('pricing_info', res.data.pricing_template);
                toast.success(__('service_playbooks.pricing_generated'));
            }
        } catch (e) {
            console.error(e);
        } finally {
            setIsLoadingPricing(false);
        }
    };

    const handleCopy = (text: string, fieldName: string) => {
        if (!text) {
            toast.error(__('service_playbooks.copy_empty'));
            return;
        }
        navigator.clipboard.writeText(text);
        setCopiedField(fieldName);
        toast.success(__('service_playbooks.copy_success'));
        setTimeout(() => setCopiedField(null), 2000);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/marketplace/service-playbooks/${playbook.id}`, {
            onSuccess: () => toast.success(__('service_playbooks.updated_success')),
        });
    };

    return (
        <AdminSidebarLayout header={`${__('service_playbooks.title')}: ${playbook.title}`}>
            <Head title={`${__('service_playbooks.title')}: ${playbook.title} - ${__('service_playbooks.admin_title')}`} />

            <div className="max-w-5xl mx-auto space-y-6 pb-12">
                {/* Navigation Back */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/admin/marketplace/service-playbooks"
                        className="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 font-medium gap-1.5"
                    >
                        <ArrowRight className="w-4 h-4" />
                        {__('service_playbooks.back_to_list')}
                    </Link>
                    <Link href={`/admin/marketplace/service-playbooks/${playbook.id}`}>
                        <Button variant="outline" size="sm" className="text-xs">
                            {__('service_playbooks.preview')}
                        </Button>
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Card 1: Main Info */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <Info className="w-5 h-5 text-sky-500" />
                                {__('service_playbooks.basic_info')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-slate-700">{__('service_playbooks.field_title')} <span className="text-red-500">*</span></Label>
                                    <Input
                                        value={data.title}
                                        onChange={e => setData('title', e.target.value)}
                                        required
                                        className="bg-white"
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-slate-700">{__('service_playbooks.field_service')}</Label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                        value={data.service_id}
                                        onChange={e => handleServiceChange(e.target.value)}
                                    >
                                        <option value="">{__('service_playbooks.field_service_none')}</option>
                                        {services.map(s => (
                                            <option key={s.id} value={s.id}>
                                                {s.title}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.service_id && <p className="text-xs text-red-500">{errors.service_id}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 2: Marketing Message */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Megaphone className="w-5 h-5 text-amber-500" />
                                    {__('service_playbooks.marketing_section')}
                                </CardTitle>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.marketing_message, 'marketing_message')}
                                className="text-xs gap-1"
                            >
                                {copiedField === 'marketing_message' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'marketing_message' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <Textarea
                                rows={4}
                                value={data.marketing_message}
                                onChange={e => setData('marketing_message', e.target.value)}
                                className="bg-white font-sans text-sm"
                            />
                            {errors.marketing_message && <p className="text-xs text-red-500 mt-1">{errors.marketing_message}</p>}
                        </CardContent>
                    </Card>

                    {/* Card 3: Pricing Info (Markdown) */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <DollarSign className="w-5 h-5 text-emerald-500" />
                                    {__('service_playbooks.pricing_section')}
                                </CardTitle>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.pricing_info, 'pricing_info')}
                                className="text-xs gap-1"
                            >
                                {copiedField === 'pricing_info' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'pricing_info' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 space-y-2" data-color-mode="light">
                            <MDEditor
                                value={data.pricing_info}
                                onChange={val => setData('pricing_info', val || '')}
                                height={240}
                                preview="edit"
                                className="rounded-md overflow-hidden border border-slate-200"
                            />
                            {errors.pricing_info && <p className="text-xs text-red-500">{errors.pricing_info}</p>}
                        </CardContent>
                    </Card>

                    {/* Card 4: Client Requirements */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <ListChecks className="w-5 h-5 text-indigo-500" />
                                    {__('service_playbooks.reqs_section')}
                                </CardTitle>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.client_requirements, 'client_requirements')}
                                className="text-xs gap-1"
                            >
                                {copiedField === 'client_requirements' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'client_requirements' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 space-y-2" data-color-mode="light">
                            <MDEditor
                                value={data.client_requirements}
                                onChange={val => setData('client_requirements', val || '')}
                                height={200}
                                preview="edit"
                                className="rounded-md overflow-hidden border border-slate-200"
                            />
                            {errors.client_requirements && <p className="text-xs text-red-500">{errors.client_requirements}</p>}
                        </CardContent>
                    </Card>

                    {/* Card 5: Execution Workflow (SOP) */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Wrench className="w-5 h-5 text-purple-500" />
                                    {__('service_playbooks.workflow_section')}
                                </CardTitle>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.execution_workflow, 'execution_workflow')}
                                className="text-xs gap-1"
                            >
                                {copiedField === 'execution_workflow' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'execution_workflow' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 space-y-2" data-color-mode="light">
                            <MDEditor
                                value={data.execution_workflow}
                                onChange={val => setData('execution_workflow', val || '')}
                                height={240}
                                preview="edit"
                                className="rounded-md overflow-hidden border border-slate-200"
                            />
                            {errors.execution_workflow && <p className="text-xs text-red-500">{errors.execution_workflow}</p>}
                        </CardContent>
                    </Card>

                    {/* Card 6: Thank You Message */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <HeartHandshake className="w-5 h-5 text-rose-500" />
                                    {__('service_playbooks.thankyou_section')}
                                </CardTitle>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.thank_you_message, 'thank_you_message')}
                                className="text-xs gap-1"
                            >
                                {copiedField === 'thank_you_message' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'thank_you_message' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <Textarea
                                rows={3}
                                value={data.thank_you_message}
                                onChange={e => setData('thank_you_message', e.target.value)}
                                className="bg-white text-sm"
                            />
                            {errors.thank_you_message && <p className="text-xs text-red-500 mt-1">{errors.thank_you_message}</p>}
                        </CardContent>
                    </Card>

                    {/* Card 7: Internal Notes */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50">
                            <CardTitle className="text-base font-bold text-slate-900">
                                {__('service_playbooks.notes_section')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6">
                            <Textarea
                                rows={3}
                                value={data.notes}
                                onChange={e => setData('notes', e.target.value)}
                                className="bg-white text-sm"
                            />
                            {errors.notes && <p className="text-xs text-red-500 mt-1">{errors.notes}</p>}
                        </CardContent>
                    </Card>

                    {/* Bottom Actions */}
                    <div className="flex items-center justify-end gap-3 pt-4">
                        <Link href="/admin/marketplace/service-playbooks">
                            <Button type="button" variant="outline" className="border-slate-300">
                                {__('service_playbooks.cancel')}
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="bg-slate-900 hover:bg-slate-800 text-white gap-2 font-medium px-6">
                            <Save className="w-4 h-4" />
                            {processing ? __('common.saving') : __('service_playbooks.save_changes')}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
