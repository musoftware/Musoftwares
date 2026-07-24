import React from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import {
    BarChart3,
    TrendingUp,
    Users,
    MousePointer,
    FileText,
    ArrowLeft,
    Sparkles,
    CheckCircle2
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

interface AnalyticsProps {
    service: {
        id: number;
        title: string;
    };
    landingPage: {
        id: number;
        slug: string;
        ab_testing_enabled: boolean;
        hero_title: string;
    };
    analytics: {
        total_visits?: number;
        total_cta_clicks?: number;
        total_form_submissions?: number;
        conversion_rate?: number;
        variants_performance?: any[];
        events?: any[];
    };
}

export default function Analytics({ service, landingPage, analytics }: AnalyticsProps) {
    const totalVisits = analytics?.total_visits || 0;
    const ctaClicks = analytics?.total_cta_clicks || 0;
    const formSubmissions = analytics?.total_form_submissions || 0;
    const conversionRate = analytics?.conversion_rate || (totalVisits > 0 ? ((formSubmissions / totalVisits) * 100).toFixed(1) : 0);

    return (
        <MarketplaceLayout>
            <Head title={__('general.landing_page_analytics') || 'Landing Page Analytics'} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex items-center justify-between">
                    <Link
                        href="/marketplace/landing-pages"
                        className="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-900 transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        {__('general.back_to_landing_pages') || 'Back to Landing Pages'}
                    </Link>
                </div>

                <ModulePageHeader
                    title={`${service?.title || 'Service'} — ${__('general.analytics') || 'Performance Analytics'}`}
                    description={`Conversion tracking & A/B testing insights for /s/${landingPage?.slug || ''}`}
                />

                <SellerNav />

                <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <MetricCard
                        label={__('general.total_page_views') || 'Total Page Visits'}
                        value={totalVisits}
                        icon={Users}
                    />
                    <MetricCard
                        label={__('general.cta_clicks') || 'CTA Button Clicks'}
                        value={ctaClicks}
                        icon={MousePointer}
                    />
                    <MetricCard
                        label={__('general.form_leads') || 'Form Submissions'}
                        value={formSubmissions}
                        icon={FileText}
                    />
                    <MetricCard
                        label={__('general.conversion_rate') || 'Conversion Rate'}
                        value={`${conversionRate}%`}
                        icon={TrendingUp}
                    />
                </div>

                {landingPage?.ab_testing_enabled && analytics?.variants_performance && (
                    <OperationalCard
                        title={__('general.ab_test_variants') || 'A/B Test Variant Performance'}
                        description={__('general.ab_test_sub') || 'Comparing conversion efficiency across test variants.'}
                    >
                        <div className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {analytics.variants_performance.map((variant: any, idx: number) => (
                                <div key={idx} className="border border-slate-200 rounded-xl p-5 bg-white space-y-3 shadow-xs">
                                    <div className="flex items-center justify-between">
                                        <span className="font-bold text-slate-900 text-sm flex items-center gap-2">
                                            <Sparkles className="w-4 h-4 text-indigo-600" />
                                            Variant {variant.name || (idx === 0 ? 'A (Original)' : 'B')}
                                        </span>
                                        {variant.is_winner && (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                🏆 Winner
                                            </span>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-3 gap-2 text-center pt-2 border-t border-slate-100">
                                        <div>
                                            <div className="text-xs text-slate-400">Visits</div>
                                            <div className="font-bold text-slate-900 text-base">{variant.visits || 0}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-slate-400">Leads</div>
                                            <div className="font-bold text-slate-900 text-base">{variant.submissions || 0}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-slate-400">Rate</div>
                                            <div className="font-bold text-indigo-600 text-base">{variant.conversion_rate || 0}%</div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </OperationalCard>
                )}

                <OperationalCard
                    title={__('general.recent_activity_logs') || 'Recent Visitor Activity Events'}
                    description={__('general.activity_logs_sub') || 'Real-time tracking of visitor interactions on your landing page.'}
                >
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                <tr>
                                    <th className="px-6 py-3">Event Type</th>
                                    <th className="px-6 py-3">Visitor IP</th>
                                    <th className="px-6 py-3">Scroll Depth</th>
                                    <th className="px-6 py-3 text-right">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(!analytics?.events || analytics.events.length === 0) ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-8 text-center text-slate-400 text-xs">
                                            No conversion activity recorded yet.
                                        </td>
                                    </tr>
                                ) : (
                                    analytics.events.map((evt: any, i: number) => (
                                        <tr key={i} className="hover:bg-slate-50/50 transition">
                                            <td className="px-6 py-3.5 font-medium text-slate-900">
                                                {evt.event_type || 'page_view'}
                                            </td>
                                            <td className="px-6 py-3.5 font-mono text-xs text-slate-500">
                                                {evt.visitor_ip || 'Anonymous'}
                                            </td>
                                            <td className="px-6 py-3.5 text-slate-700">
                                                {evt.scroll_depth ? `${evt.scroll_depth}%` : '—'}
                                            </td>
                                            <td className="px-6 py-3.5 text-right text-xs text-slate-400">
                                                {evt.created_at || 'Just now'}
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
