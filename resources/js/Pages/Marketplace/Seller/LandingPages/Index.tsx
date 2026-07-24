import React from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Globe,
    Plus,
    BarChart3,
    Edit3,
    ExternalLink,
    FileText,
    Copy,
    Sparkles,
    CheckCircle2
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

interface LandingPagesIndexProps {
    servicesWithLandingPages: {
        data: any[];
        links: any[];
    };
}

export default function Index({ servicesWithLandingPages }: LandingPagesIndexProps) {
    return (
        <MarketplaceLayout>
            <Head title={__('general.my_landing_pages') || 'My Service Landing Pages'} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <ModulePageHeader
                        title={__('general.my_landing_pages') || 'Custom Service Landing Pages'}
                        description={__('general.landing_pages_sub') || 'High-converting, A/B tested landing pages for your marketplace service listings.'}
                    />
                </div>

                <SellerNav />

                <OperationalCard
                    title={__('general.active_landing_pages') || 'Service Landing Pages & Funnels'}
                    description={__('general.manage_funnels_sub') || 'Track conversions, customize themes, and manage lead submissions for your services.'}
                    noPadding
                >
                    <div className="divide-y divide-slate-100">
                        {servicesWithLandingPages.data.length === 0 ? (
                            <div className="p-12 text-center text-slate-400 space-y-3">
                                <Globe className="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
                                <p className="text-sm font-medium text-slate-700">
                                    {__('general.no_landing_pages_yet') || 'No custom landing pages created yet.'}
                                </p>
                                <p className="text-xs text-slate-400 max-w-md mx-auto">
                                    Create high-converting landing pages with A/B testing and AI FAQ generation for your service listings.
                                </p>
                            </div>
                        ) : (
                            servicesWithLandingPages.data.map((service: any) => {
                                const page = service.landing_page;
                                if (!page) return null;

                                return (
                                    <div key={service.id} className="p-6 hover:bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div className="space-y-1.5">
                                            <div className="flex items-center gap-2">
                                                <h4 className="font-semibold text-slate-900 text-base">
                                                    {service.title}
                                                </h4>
                                                {page.is_active ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {__('general.published') || 'Published'}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                        {__('general.draft') || 'Draft'}
                                                    </span>
                                                )}
                                                {page.template && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] uppercase font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                        {page.template}
                                                    </span>
                                                )}
                                            </div>

                                            <div className="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                                                <span>Slug: <code className="text-slate-700 font-mono bg-slate-100 px-1.5 py-0.5 rounded">/s/{page.slug}</code></span>
                                                <span>•</span>
                                                <span>Form Submissions: <strong>{page.form_submissions?.length || 0}</strong></span>
                                                {page.ab_testing_enabled && (
                                                    <>
                                                        <span>•</span>
                                                        <span className="text-indigo-600 font-semibold flex items-center gap-1">
                                                            <Sparkles className="w-3.5 h-3.5" /> A/B Testing Active
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 pt-2 md:pt-0">
                                            <Link
                                                href={`/s/${page.slug}`}
                                                target="_blank"
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-medium transition"
                                            >
                                                <ExternalLink className="w-3.5 h-3.5" />
                                                {__('general.preview') || 'Preview'}
                                            </Link>
                                            <Link
                                                href={`/marketplace/landing-pages/${service.id}/analytics`}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-medium transition"
                                            >
                                                <BarChart3 className="w-3.5 h-3.5" />
                                                {__('general.analytics') || 'Analytics'}
                                            </Link>
                                            <Link
                                                href={`/marketplace/landing-pages/${service.id}/submissions`}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-medium transition"
                                            >
                                                <FileText className="w-3.5 h-3.5" />
                                                {__('general.leads') || 'Leads'}
                                            </Link>
                                            <Link
                                                href={`/marketplace/landing-pages/${service.id}/edit/${page.id}`}
                                                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-xs font-medium transition"
                                            >
                                                <Edit3 className="w-3.5 h-3.5" />
                                                {__('general.edit') || 'Edit Builder'}
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
