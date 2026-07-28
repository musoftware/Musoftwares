import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Info, CheckCircle2, AlertTriangle, XCircle, Users } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

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
        <AdminSidebarLayout title={__('general.bulk_create') || 'Bulk Create Accounts'} header="Platform Users">
            <Head title={__('general.bulk_create') || 'Bulk Create Accounts'} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <p className="text-sm text-gray-500 font-medium tracking-wider uppercase">{__('whatsapp.ui.system') || 'System'}</p>
                        <h1 className="text-3xl font-bold text-gray-900">{__('general.bulk_create_accounts') || 'Bulk Create Accounts'}</h1>
                        <p className="text-gray-500 mt-1">Create multiple platform client accounts at once by entering names and emails.</p>
                    </div>
                    <Link href="/admin/users">
                        <Button variant="outline">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            {__('general.back') || 'Back'}
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
                                        Account Entries
                                    </Label>
                                    <p className="text-xs text-gray-500 mb-2">
                                        Enter one account per line. Format: <code className="bg-slate-100 px-1 py-0.5 rounded text-red-600 font-mono">Name, Email</code>
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
                                        {processing ? 'Processing...' : 'Bulk Create Accounts'}
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
                                <h3>Processing Rules</h3>
                            </div>
                            <ul className="text-sm text-slate-600 space-y-3 list-disc pl-5">
                                <li>
                                    <strong>Formats Supported:</strong> Comma separated (<code className="font-mono">Name,Email</code>), semicolon separated (<code className="font-mono">Name;Email</code>), or space separated (<code className="font-mono">Name Email</code>).
                                </li>
                                <li>
                                    <strong>Last Name Compliance:</strong> System accounts require a first and last name. Single-word names (e.g. <code className="font-mono">JohnDoe</code>) will be split by case (<code className="font-mono">John Doe</code>). If they cannot be split, <code className="font-mono">"Account"</code> is added as the default surname (e.g. <code className="font-mono">John</code> &rarr; <code className="font-mono">John Account</code>).
                                </li>
                                <li>
                                    <strong>Duplicate & Alias Handling:</strong> Checks primary emails AND alias emails. If the email is registered anywhere, that line is skipped.
                                </li>
                                <li>
                                    <strong>Default Currency:</strong> All successfully created client accounts will be configured with <strong>EGP</strong> currency.
                                </li>
                                <li>
                                    <strong>Credentials:</strong> Secure random passwords will be generated for every created account.
                                </li>
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
                                Processing Summary
                            </h2>
                            <span className="px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-semibold">
                                {success || 'Completed'}
                            </span>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead className="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                        <tr>
                                            <th className="px-6 py-4 w-16">Line</th>
                                            <th className="px-6 py-4">Input Entered</th>
                                            <th className="px-6 py-4">Parsed Name</th>
                                            <th className="px-6 py-4">Parsed Email</th>
                                            <th className="px-6 py-4 w-32">Status</th>
                                            <th className="px-6 py-4">Result Detail</th>
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
                                                            Created
                                                        </span>
                                                    )}
                                                    {res.status === 'skipped' && (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                            <AlertTriangle className="h-3 w-3" />
                                                            Skipped
                                                        </span>
                                                    )}
                                                    {res.status === 'failed' && (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                            <XCircle className="h-3 w-3" />
                                                            Failed
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
