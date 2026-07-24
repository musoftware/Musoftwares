import React, { useState } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import {
    FileText,
    Download,
    ArrowLeft,
    Search,
    User,
    Mail,
    Calendar
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface SubmissionsProps {
    service: {
        id: number;
        title: string;
    };
    landingPage: {
        id: number;
        slug: string;
    };
    submissions: {
        data: any[];
        links: any[];
    };
}

export default function Submissions({ service, landingPage, submissions }: SubmissionsProps) {
    const [searchTerm, setSearchTerm] = useState('');

    const filteredSubmissions = (submissions?.data || []).filter((sub: any) => {
        const query = searchTerm.toLowerCase();
        return (
            (sub.name && sub.name.toLowerCase().includes(query)) ||
            (sub.email && sub.email.toLowerCase().includes(query))
        );
    });

    return (
        <MarketplaceLayout>
            <Head title={`Leads & Inquiries — ${service?.title}`} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex items-center justify-between">
                    <Link
                        href="/marketplace/landing-pages"
                        className="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-900 transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Landing Pages
                    </Link>

                    <a
                        href={`/marketplace/landing-pages/${service.id}/submissions/export`}
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition shadow-sm"
                    >
                        <Download className="w-3.5 h-3.5" />
                        Export Leads CSV
                    </a>
                </div>

                <ModulePageHeader
                    title={`Captured Leads: "${service?.title}"`}
                    description={`Client form submissions collected from /s/${landingPage?.slug || ''}`}
                />

                <SellerNav />

                <OperationalCard
                    title="Form Submissions & Inquiry Records"
                    description="Client questionnaires and lead contact details."
                >
                    <div className="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="relative w-full sm:w-80">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search by name or email..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                <tr>
                                    <th className="px-6 py-3">Date</th>
                                    <th className="px-6 py-3">Lead Contact</th>
                                    <th className="px-6 py-3">Submitted Questionnaire Data</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredSubmissions.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-12 text-center text-slate-400">
                                            <div className="flex flex-col items-center justify-center space-y-2">
                                                <FileText className="w-8 h-8 text-slate-300 stroke-[1.5]" />
                                                <p className="text-sm">No form submissions received yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    filteredSubmissions.map((sub: any) => (
                                        <tr key={sub.id} className="hover:bg-slate-50/50 transition">
                                            <td className="px-6 py-4 text-xs font-medium text-slate-500 whitespace-nowrap">
                                                {formatDate(sub.created_at)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-semibold text-slate-900">{sub.name || 'Anonymous Lead'}</div>
                                                <div className="text-xs text-indigo-600 font-mono">{sub.email || 'No email provided'}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {sub.data && typeof sub.data === 'object' ? (
                                                    <div className="bg-slate-50 rounded-lg p-3 border border-slate-200/80 space-y-1 text-xs">
                                                        {Object.entries(sub.data).map(([k, v]: [string, any]) => (
                                                            <div key={k} className="flex flex-col sm:flex-row sm:gap-2">
                                                                <span className="font-semibold text-slate-700">{k}:</span>
                                                                <span className="text-slate-600">{String(v)}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
