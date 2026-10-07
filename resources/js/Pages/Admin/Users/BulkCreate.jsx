import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Info, CheckCircle2, AlertTriangle, XCircle, Users } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

const PROCESSING_RULES = [
    { titleKey: 'admin.bulk_rule_formats_title', bodyKey: 'admin.bulk_rule_formats_body' },
    { titleKey: 'admin.bulk_rule_last_name_title', bodyKey: 'admin.bulk_rule_last_name_body' },
    { titleKey: 'admin.bulk_rule_duplicates_title', bodyKey: 'admin.bulk_rule_duplicates_body' },
    { titleKey: 'admin.bulk_rule_currency_title', bodyKey: 'admin.bulk_rule_currency_body' },
    { titleKey: 'admin.bulk_rule_credentials_title', bodyKey: 'admin.bulk_rule_credentials_body' },
];

export default function BulkCreate({ bulk_results = null, success = null }) {
    const { data, setData, post, processing, errors } = useForm({
        entries: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/users/bulk-create', {
            preserveState: true,
        });
    };

    return (
        <AdminSidebarLayout title={__('general.bulk_create')} header={__('admin.platform_users')}>
            <Head title={__('general.bulk_create')} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <p className="text-sm text-gray-500 font-medium tracking-wider uppercase">{__('whatsapp.ui.system')}</p>
                        <h1 className="text-3xl font-bold text-gray-900">{__('general.bulk_create_accounts')}</h1>
                        <p className="text-gray-500 mt-1">{__('admin.bulk_create_description')}</p>
                    </div>
                    <Link href="/admin/users">
                        <Button variant="outline">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            {__('general.back')}
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left: Input Form */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <form onSubmit={handleSubmit} className="p-6 md:p-8 space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="entries" className="text-base font-semibold text-slate-900">
                                        {__('admin.bulk_account_entries')}
                                    </Label>
                                    <p className="text-xs text-gray-500 mb-2">
                                        {__('admin.bulk_one_account_per_line')} <code className="bg-slate-100 px-1 py-0.5 rounded text-red-600 font-mono">Name, Email</code>
                                    </p>
                                    <textarea
                                        id="entries"
                                        rows={12}
                                        value={data.entries}
                                        onChange={(e) => setData('entries', e.target.value)}
                                        placeholder="John Doe, john@example.com&#10;Jane Smith, jane@example.com"
                                        className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"
                                        required
                                    />
                                    {errors.entries && <p className="text-sm text-red-600 font-medium">{errors.entries}</p>}
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing} className="w-full sm:w-auto px-6">
                                        <Save className="me-2 h-4 w-4" />
                                        {processing ? __('general.processing') : __('general.bulk_create_accounts')}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Right: Instructions & Help */}
                    <div className="space-y-6">
                        <div className="bg-slate-50 border border-slate-200/60 rounded-xl p-6 space-y-4">
                            <div className="flex items-center gap-2 text-slate-800 font-semibold">
                                <Info className="h-5 w-5 text-slate-600" />
                                <h3>{__('admin.bulk_processing_rules')}</h3>
                            </div>
                            <ul className="text-sm text-slate-600 space-y-3 list-disc ps-5">
                                {PROCESSING_RULES.map((rule) => (
                                    <li key={rule.titleKey}>
                                        <strong>{__(rule.titleKey)}:</strong> {__(rule.bodyKey)}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Bottom: Results Section */}
                {bulk_results && (
                    <div className="mt-12 space-y-6">
                        <div className="flex items-center justify-between">
                            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                                <Users className="h-5 w-5 text-slate-600" />
                                {__('admin.bulk_processing_summary')}
                            </h2>
                            <span className="px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-semibold">
                                {success || __('general.status_completed')}
                            </span>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead className="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-start">
                                        <tr>
                                            <th className="px-6 py-4 w-16">{__('admin.bulk_line')}</th>
                                            <th className="px-6 py-4">{__('admin.bulk_input_entered')}</th>
                                            <th className="px-6 py-4">{__('admin.bulk_parsed_name')}</th>
                                            <th className="px-6 py-4">{__('admin.bulk_parsed_email')}</th>
                                            <th className="px-6 py-4 w-32">{__('general.status')}</th>
                                            <th className="px-6 py-4">{__('admin.bulk_result_detail')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 text-slate-700 bg-white">
                                        {bulk_results.map((res, idx) => (
                                            <tr key={idx} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4 font-mono text-slate-400">#{res.line}</td>
                                                <td className="px-6 py-4 font-mono text-xs max-w-xs truncate">{res.input}</td>
                                                <td className="px-6 py-4 font-medium text-slate-900">{res.name}</td>
                                                <td className="px-6 py-4 font-mono text-xs">{res.email}</td>
                                                <td className="px-6 py-4">
                                                    {res.status === 'created' && (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                                            <CheckCircle2 className="h-3 w-3" />
                                                            {__('admin.bulk_status_created')}
                                                        </span>
                                                    )}
                                                    {res.status === 'skipped' && (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                            <AlertTriangle className="h-3 w-3" />
                                                            {__('admin.bulk_status_skipped')}
                                                        </span>
                                                    )}
                                                    {res.status === 'failed' && (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                            <XCircle className="h-3 w-3" />
                                                            {__('admin.bulk_status_failed')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-slate-500 text-xs">{res.reason}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
