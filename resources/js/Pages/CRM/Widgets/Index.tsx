import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Plus, Code, Settings, Trash2, Globe, Activity, Link as LinkIcon, FormInput } from 'lucide-react';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { format } from 'date-fns';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';

export default function Index({ widgets }: { widgets: any }) {
    const { auth } = usePage<any>().props;
    const hasAdvancedOps = auth?.crm_features?.includes('crm-advanced-operations') ?? false;

    if (!hasAdvancedOps) {
        return (
            <CrmLayout title={__('general.web_forms')} activeMenu="widgets">
                <ModulePageHeader 
                    title={__('general.web_forms')}
                    description={__('general.create_and_manage_embeddable_forms_to_capture_leads_from_your_external_websites')}
                    icon={FormInput}
                />
                <div className="px-8 pb-8">
                    <UpgradeOverlay 
                        title={__('general.advanced_operations_required')}
                        description={__('general.to_create_and_manage_web_forms_and_capture_leads_directly_into_crm_you_need_the_advanced_operations_add_on')}
                        icon={FormInput}
                        module="crm-advanced-operations"
                        priceText={__('general.subscribe_to_advanced_operations')}
                    />
                </div>
            </CrmLayout>
        );
    }

    return (
        <CrmLayout title={__('general.web_forms')} activeMenu="widgets">
            <ModulePageHeader 
                title={__('general.web_forms')}
                description={__('general.create_and_manage_embeddable_forms_to_capture_leads_from_your_external_websites')}
                icon={FormInput}
                actions={
                    <Link href={route('crm.widgets.create')} className={buttonVariants({ className: "gap-2 bg-indigo-600 hover:bg-indigo-700 text-white" })}>
                        <Plus className="w-4 h-4" />
                        {__('general.create_new_form')}
                    </Link>
                }
            />
            <div className="space-y-6 px-8 pb-8">

                <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-start">
                            <thead className="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-4 font-medium">{__('general.form_name')}</th>
                                    <th className="px-6 py-4 font-medium">{__('general.status')}</th>
                                    <th className="px-6 py-4 font-medium">{__('general.allowed_domains')}</th>
                                    <th className="px-6 py-4 font-medium">{__('general.leads_captured')}</th>
                                    <th className="px-6 py-4 font-medium">{__('general.created_at')}</th>
                                    <th className="px-6 py-4 font-medium text-end">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(widgets.data as any).map((widget: any) => (
                                    <tr key={widget.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-slate-900">{widget.name}</div>
                                            <div className="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                                <Activity className="w-3 h-3" />
                                                {widget.embed_token.substring(0, 8)}...
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            {widget.is_active ? (
                                                <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200">
                                                    {__('general.active')}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="bg-slate-50 text-slate-600 border-slate-200">
                                                    {__('general.inactive')}
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-1.5 text-slate-600">
                                                <Globe className="w-4 h-4 text-slate-400" />
                                                {widget.allowed_domains && widget.allowed_domains.length > 0 
                                                    ? widget.allowed_domains.join(', ')
                                                    : <span className="text-slate-400 italic">{__('general.any_domain')}</span>
                                                }
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="font-semibold text-slate-900">
                                                {widget.leads_count || 0}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-slate-500">
                                            {format(new Date(widget.created_at), 'MMM dd, yyyy')}
                                        </td>
                                        <td className="px-6 py-4 text-end">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={route('crm.widgets.show', widget.id)} title={__('general.get_embed_code')} className={buttonVariants({ variant: 'ghost', size: 'sm', className: 'text-slate-500 hover:text-indigo-600' })}>
                                                    <Code className="w-4 h-4" />
                                                </Link>
                                                <Link href={route('crm.widgets.edit', widget.id)} title={__('admin.settings')} className={buttonVariants({ variant: 'ghost', size: 'sm', className: 'text-slate-500 hover:text-indigo-600' })}>
                                                    <Settings className="w-4 h-4" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {(widgets.data as any).length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center">
                                            <div className="flex flex-col items-center justify-center text-slate-500">
                                                <Globe className="w-12 h-12 text-slate-300 mb-4" />
                                                <p className="text-base font-medium text-slate-900">{__('general.no_web_forms_yet')}</p>
                                                <p className="text-sm mt-1 mb-4">{__('general.create_your_first_embeddable_lead_form')}</p>
                                                <Link href={route('crm.widgets.create')} className={buttonVariants({ variant: 'outline' })}>
                                                    <Plus className="w-4 h-4 me-2" />
                                                    {__('general.create_new_form')}
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </CrmLayout>
    );
}
