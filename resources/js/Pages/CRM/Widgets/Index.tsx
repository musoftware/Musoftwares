import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CRMLayout';
import { __ } from '@/lib/i18n';
import { Plus, Code, Settings, Trash2, Globe, Activity, Link as LinkIcon } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { format } from 'date-fns';

export default function Index({ widgets }: { widgets: any }) {
    return (
        <CrmLayout title={__('Web Forms')} activeMenu="widgets">
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{__('Web Forms')}</h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('Create and manage embeddable forms to capture leads from your external websites.')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('crm.widgets.create')} className="gap-2">
                            <Plus className="w-4 h-4" />
                            {__('Create New Form')}
                        </Link>
                    </Button>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-4 font-medium">{__('Form Name')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Status')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Allowed Domains')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Leads Captured')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Created At')}</th>
                                    <th className="px-6 py-4 font-medium text-right">{__('Actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {widgets.data.map((widget: any) => (
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
                                                    {__('Active')}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="bg-slate-50 text-slate-600 border-slate-200">
                                                    {__('Inactive')}
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-1.5 text-slate-600">
                                                <Globe className="w-4 h-4 text-slate-400" />
                                                {widget.allowed_domains && widget.allowed_domains.length > 0 
                                                    ? widget.allowed_domains.join(', ')
                                                    : <span className="text-slate-400 italic">{__('Any domain (*)')}</span>
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
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button variant="ghost" size="sm" asChild className="text-slate-500 hover:text-indigo-600">
                                                    <Link href={route('crm.widgets.show', widget.id)} title={__('Get Embed Code')}>
                                                        <Code className="w-4 h-4" />
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" asChild className="text-slate-500 hover:text-indigo-600">
                                                    <Link href={route('crm.widgets.edit', widget.id)} title={__('Settings')}>
                                                        <Settings className="w-4 h-4" />
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {widgets.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center">
                                            <div className="flex flex-col items-center justify-center text-slate-500">
                                                <Globe className="w-12 h-12 text-slate-300 mb-4" />
                                                <p className="text-base font-medium text-slate-900">{__('No Web Forms yet')}</p>
                                                <p className="text-sm mt-1 mb-4">{__('Create your first embeddable lead form.')}</p>
                                                <Button asChild variant="outline">
                                                    <Link href={route('crm.widgets.create')}>
                                                        <Plus className="w-4 h-4 mr-2" />
                                                        {__('Create New Form')}
                                                    </Link>
                                                </Button>
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
