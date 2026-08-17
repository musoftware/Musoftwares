import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { ArrowRight, Edit3, Copy, Check, Megaphone, DollarSign, ListChecks, Wrench, HeartHandshake, Info } from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Service {
    id: number;
    title: string;
    tagline?: string;
    thumbnail?: string;
}

interface Playbook {
    id: number;
    title: string;
    marketing_message?: string;
    pricing_info?: string;
    client_requirements?: string;
    execution_workflow?: string;
    thank_you_message?: string;
    notes?: string;
    service?: Service;
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    playbook: Playbook;
}

export default function Show({ playbook }: ShowProps) {
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const handleCopy = (text: string | undefined, fieldName: string) => {
        if (!text) {
            toast.error(__('service_playbooks.copy_empty'));
            return;
        }
        navigator.clipboard.writeText(text);
        setCopiedField(fieldName);
        toast.success(__('service_playbooks.copy_success'));
        setTimeout(() => setCopiedField(null), 2000);
    };

    return (
        <AdminSidebarLayout header={`${__('service_playbooks.title')}: ${playbook.title}`}>
            <Head title={`${playbook.title} - ${__('service_playbooks.admin_title')}`} />

            <div className="max-w-5xl mx-auto space-y-6 pb-12">
                {/* Header Actions */}
                <div className="flex items-center justify-between gap-4">
                    <Link
                        href="/admin/marketplace/service-playbooks"
                        className="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 font-medium gap-1.5"
                    >
                        <ArrowRight className="w-4 h-4" />
                        {__('service_playbooks.back_to_list')}
                    </Link>
                    <Link href={`/admin/marketplace/service-playbooks/${playbook.id}/edit`}>
                        <Button className="bg-sky-600 hover:bg-sky-700 text-white gap-2 font-medium">
                            <Edit3 className="w-4 h-4" />
                            {__('service_playbooks.edit_this')}
                        </Button>
                    </Link>
                </div>

                {/* Banner */}
                <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
                    <CardHeader className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="space-y-2">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <h1 className="text-xl font-bold text-white">{playbook.title}</h1>
                                    {playbook.service ? (
                                        <Badge className="bg-sky-500/20 text-sky-300 border-sky-400/30">
                                            🔗 {playbook.service.title}
                                        </Badge>
                                    ) : (
                                        <Badge className="bg-slate-800 text-slate-300 border-slate-700">
                                            {__('service_playbooks.custom_service')}
                                        </Badge>
                                    )}
                                </div>
                                {playbook.notes && (
                                    <p className="text-xs text-slate-300 bg-slate-800/70 p-2.5 rounded-md border border-slate-700/50">
                                        <span className="font-semibold text-amber-400">{__('service_playbooks.internal_notes')}:</span> {playbook.notes}
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Section 1: Marketing Message */}
                {playbook.marketing_message && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4 px-6">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <Megaphone className="w-4 h-4 text-amber-500" />
                                {__('service_playbooks.marketing_section')}
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleCopy(playbook.marketing_message, 'marketing')}
                                className="h-8 text-xs gap-1"
                            >
                                {copiedField === 'marketing' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'marketing' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed bg-amber-50/10">
                            {playbook.marketing_message}
                        </CardContent>
                    </Card>
                )}

                {/* Section 2: Pricing Info (Markdown) */}
                {playbook.pricing_info && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4 px-6">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <DollarSign className="w-4 h-4 text-emerald-500" />
                                {__('service_playbooks.pricing_section')}
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleCopy(playbook.pricing_info, 'pricing')}
                                className="h-8 text-xs gap-1"
                            >
                                {copiedField === 'pricing' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'pricing' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 prose prose-slate max-w-none text-right" data-color-mode="light">
                            <MDEditor.Markdown source={playbook.pricing_info} />
                        </CardContent>
                    </Card>
                )}

                {/* Section 3: Client Requirements (Markdown) */}
                {playbook.client_requirements && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4 px-6">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <ListChecks className="w-4 h-4 text-indigo-500" />
                                {__('service_playbooks.reqs_section')}
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleCopy(playbook.client_requirements, 'requirements')}
                                className="h-8 text-xs gap-1"
                            >
                                {copiedField === 'requirements' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'requirements' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 prose prose-slate max-w-none text-right" data-color-mode="light">
                            <MDEditor.Markdown source={playbook.client_requirements} />
                        </CardContent>
                    </Card>
                )}

                {/* Section 4: Execution Workflow (Markdown) */}
                {playbook.execution_workflow && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4 px-6">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <Wrench className="w-4 h-4 text-purple-500" />
                                {__('service_playbooks.workflow_section')}
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleCopy(playbook.execution_workflow, 'workflow')}
                                className="h-8 text-xs gap-1"
                            >
                                {copiedField === 'workflow' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'workflow' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 prose prose-slate max-w-none text-right" data-color-mode="light">
                            <MDEditor.Markdown source={playbook.execution_workflow} />
                        </CardContent>
                    </Card>
                )}

                {/* Section 5: Thank You Message */}
                {playbook.thank_you_message && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4 px-6">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <HeartHandshake className="w-4 h-4 text-rose-500" />
                                {__('service_playbooks.thankyou_section')}
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleCopy(playbook.thank_you_message, 'thank_you')}
                                className="h-8 text-xs gap-1"
                            >
                                {copiedField === 'thank_you' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'thank_you' ? __('service_playbooks.copied') : __('service_playbooks.copy')}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed bg-rose-50/10">
                            {playbook.thank_you_message}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
