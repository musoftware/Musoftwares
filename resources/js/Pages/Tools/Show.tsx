import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { ToolHeader } from '@/Components/Tools/ToolHeader';
import { ScreenshotGallery } from '@/Components/Tools/ScreenshotGallery';
import { FeatureList } from '@/Components/Tools/FeatureList';
import { ReleaseNotes } from '@/Components/Tools/ReleaseNotes';
import { PricingPanel } from '@/Components/Tools/PricingPanel';
import { DownloadPanel } from '@/Components/Tools/DownloadPanel';
import { PlatformBadges } from '@/Components/Tools/PlatformBadge';
import { Cpu } from 'lucide-react';
import { Card } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';

interface PricingPlan {
    id: number; name: string; price_monthly: number; price_yearly: number;
    features: string[]; is_popular: boolean; yearly_savings: number;
}
interface ToolVersion {
    version: string; changelog: string; is_latest: boolean;
    is_beta: boolean; file_size: string; released_at: string;
}
interface Props {
    tool: {
        id: number; slug: string; title: string; description: string;
        short_description: string; icon_url: string | null; category: string;
        category_label: string; supported_os: string[]; current_version: string;
        is_featured: boolean; features: string[];
        requirements: string[];
        screenshots: { id: number; url: string; caption: string | null }[];
        pricing_plans: PricingPlan[];
        versions: ToolVersion[];
    };
    userSubscription: {
        id: number; plan_name: string; billing_cycle: string;
        status: string; expires_at: string;
    } | null;
}

export default function Show({ tool, userSubscription }: Props) {
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;
    const isSubscribed = !!userSubscription && userSubscription.status === 'active';

    const safeFeatures = tool.features ?? [];
    const safeRequirements = tool.requirements ?? [];

    const latestVersion = (tool.versions ?? []).find(v => v.is_latest) ?? tool.versions?.[0] ?? null;

    return (
        <ToolsPublicLayout title={tool.title} activeNav="explore">
            <Head title={tool.title} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

                {/* Tool Header */}
                <ToolHeader tool={tool} isSubscribed={isSubscribed} />

                {/* Screenshots (full-width) */}
                {(tool.screenshots ?? []).length > 0 && (
                    <ScreenshotGallery screenshots={tool.screenshots} />
                )}

                {/* Two-column layout */}
                <div className="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8 items-start">

                    {/* LEFT — Content */}
                    <div className="space-y-10 min-w-0">

                        {/* About */}
                        {tool.description && (
                            <section>
                                <h2 className="text-base font-semibold text-slate-900 mb-3 pb-2 border-b border-slate-100">{__('general.about_this_tool')}</h2>
                                <div className="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">
                                    {tool.description}
                                </div>
                            </section>
                        )}

                        {/* Features */}
                        {safeFeatures.length > 0 && (
                            <section>
                                <h2 className="text-base font-semibold text-slate-900 mb-4 pb-2 border-b border-slate-100">
                                    {__('general.features')}</h2>
                                <FeatureList features={safeFeatures} columns={2} />
                            </section>
                        )}

                        {/* Release Notes */}
                        {(tool.versions ?? []).length > 0 && (
                            <section>
                                <h2 className="text-base font-semibold text-slate-900 mb-4 pb-2 border-b border-slate-100">{__('general.release_notes')}</h2>
                                <ReleaseNotes
                                    versions={tool.versions}
                                    defaultExpanded={tool.versions[0]?.version ?? null}
                                />
                            </section>
                        )}

                        {/* System Requirements */}
                        {safeRequirements.length > 0 && (
                            <section>
                                <h2 className="text-base font-semibold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                                    <Cpu className="h-4 w-4 text-slate-400" />{__('general.system_requirements')}</h2>
                                <ul className="space-y-2">
                                    {safeRequirements.map((req: string, i: number) => (
                                        <li key={i} className="flex items-start gap-2.5 text-sm text-slate-600">
                                            <span className="text-slate-300 mt-0.5 font-medium">—</span>
                                            {req}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </div>

                    {/* RIGHT — Sticky panel */}
                    <div className="lg:sticky lg:top-[72px] space-y-4">
                        {/* Download panel (subscribed state) */}
                        {isSubscribed && userSubscription && (
                            <DownloadPanel
                                toolSlug={tool.slug}
                                toolTitle={tool.title}
                                userSubscription={userSubscription}
                                latestVersion={latestVersion}
                            />
                        )}

                        {/* Pricing panel (not subscribed) */}
                        {!isSubscribed && (tool.pricing_plans ?? []).length > 0 && (
                            <Card className="p-5">
                                <PricingPanel
                                    plans={tool.pricing_plans}
                                    toolSlug={tool.slug}
                                    isAuthed={isAuthed}
                                />
                            </Card>
                        )}

                        {/* Info meta card */}
                        <Card className="p-4 space-y-3">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{__('general.details')}</p>
                            <div className="space-y-2">
                                <div className="flex justify-between text-xs">
                                    <span className="text-muted-foreground">{__('general.version')}</span>
                                    <code className="font-mono text-foreground font-medium">v{tool.current_version}</code>
                                </div>
                                <div className="flex justify-between text-xs">
                                    <span className="text-muted-foreground">{__('general.category')}</span>
                                    <span className="text-foreground capitalize">{tool.category_label}</span>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
