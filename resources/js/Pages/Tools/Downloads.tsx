import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ExternalLink, Zap, Clock, ShoppingBag, CheckCircle2, Monitor, Download, Globe } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface AvailableTool {
    tool_slug: string;
    tool_title: string;
    tool_icon_url: string | null;
    version: string;
    file_size: string;
    released_at: string;
}

interface DownloadRecord {
    id: number;
    tool: { slug: string; title: string; icon_url: string | null };
    version: string;
    downloaded_at: string;
}

interface Props {
    availableTools: AvailableTool[];
    downloads: { data: DownloadRecord[]; links: any[] };
}

export default function Downloads({ availableTools, downloads }: Props) {
    const host = typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';

    return (
        <ToolsPublicLayout title={__('general.my_tools')} activeNav="downloads">
            <Head title={__('general.my_tools_musoftware')} />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

                {/* Page header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{__('general.my_tools')}</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Tools run directly in your browser — no download needed.
                        Make sure the{' '}
                        <Link
                            href={`http://${host}:18400/setup`}
                            className="text-indigo-600 hover:underline"
                            target="_blank"
                        >{__('general.musoftware_runtime')}</Link>{' '}
                        is running on your computer.
                    </p>
                </div>

                {/* Available Tools */}
                {availableTools.length > 0 ? (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.your_subscribed_tools')}</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {availableTools.map(t => (
                                <div
                                    key={t.tool_slug}
                                    className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4 hover:border-slate-300 hover:shadow-sm transition-all"
                                >
                                    {/* Tool header */}
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                                            {t.tool_icon_url
                                                ? <img src={t.tool_icon_url} alt="" className="w-7 h-7 object-contain" />
                                                : <Zap className="h-5 w-5 text-indigo-400" />
                                            }
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                {t.tool_title}
                                            </p>
                                            <div className="flex items-center gap-1.5 mt-0.5">
                                                <span className="text-xs text-slate-400 font-mono">v{t.version}</span>
                                                <span className="w-1 h-1 rounded-full bg-emerald-400 inline-block" />
                                                <span className="text-xs text-emerald-600 font-medium">Active</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Meta */}
                                    <div className="flex items-center gap-1 text-xs text-slate-400">
                                        <Clock className="h-3 w-3" />
                                        <span>Updated {t.released_at}</span>
                                    </div>

                                    {/* CTA — Open tool, not download */}
                                    <Link href={route('tools.show', t.tool_slug)} className="block">
                                        <Button
                                            className="w-full gap-2 text-sm bg-indigo-600 hover:bg-indigo-500 text-white h-9"
                                        >
                                            <ExternalLink className="h-3.5 w-3.5" />{__('general.open_tool')}</Button>
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </section>
                ) : (
                    <div className="text-center py-16 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl">
                        <div className="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center mx-auto mb-4">
                            <ShoppingBag className="h-6 w-6 text-slate-300" />
                        </div>
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">{__('general.no_tools_yet')}</p>
                        <p className="text-xs text-slate-400 mt-1 mb-5">{__('general.subscribe_to_a_tool_to_get_started_many_are_free')}</p>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('tools.explore'))}
                        >{__('general.browse_tools')}</Button>
                    </div>
                )}

                {/* Infrastructure Downloads */}
                <section className="space-y-4">
                    <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.infrastructure_runtimes')}</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        {/* Desktop Runtime */}
                        <Card className="flex flex-col border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-3 text-sm">
                                    <Monitor className="h-5 w-5 text-indigo-500" />{__('general.desktop_runtime')}</CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1">
                                <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{__('general.the_powerful_background_agent_that_executes_tools_directly_on_your_computer_required_for_heavy_automation_workflows')}</p>
                            </CardContent>
                            <CardFooter className="flex items-center gap-2 pt-0">
                                <a href={route('tools.download.agent', 'node')}>
                                    <Button size="sm" variant="outline" className="text-xs h-8 bg-white dark:bg-slate-900">
                                        <Download className="h-3 w-3 mr-1.5" />{__('general.download_app')}</Button>
                                </a>
                                <a href={`http://${host}:18400/setup`} target="_blank" className="text-xs text-indigo-600 hover:underline" rel="noreferrer">
                                    Check Status →
                                </a>
                            </CardFooter>
                        </Card>

                        {/* Browser Extension */}
                        <Card className="flex flex-col border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 shadow-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-3 text-sm">
                                    <Globe className="h-5 w-5 text-emerald-500" />{__('general.browser_extension')}<Badge className="ml-2 text-[10px] uppercase tracking-wider bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-800 shadow-none">
                                        New
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1">
                                <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{__('general.a_lightweight_execution_layer_that_runs_tools_securely_inside_your_browser_used_for_social_media_and_web_based_tools')}</p>
                            </CardContent>
                            <CardFooter className="pt-0">
                                <a href={route('tools.download.agent', 'extension')}>
                                    <Button size="sm" className="text-xs h-8 bg-emerald-600 hover:bg-emerald-500 text-white border-0">
                                        <Download className="h-3 w-3 mr-1.5" />{__('general.download_extension')}</Button>
                                </a>
                            </CardFooter>
                        </Card>

                    </div>
                </section>

                {/* Usage History */}
                {(downloads.data as any).length > 0 && (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.recent_activity')}</h2>
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            {(downloads.data as any).map(d => (
                                <div key={d.id} className="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                                            {d.tool.icon_url
                                                ? <img src={d.tool.icon_url} alt="" className="w-5 h-5 object-contain" />
                                                : <CheckCircle2 className="h-3.5 w-3.5 text-emerald-400" />
                                            }
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-slate-900 dark:text-white">
                                                {d.tool.title}
                                            </p>
                                            <code className="text-xs text-slate-400 font-mono">v{d.version}</code>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3 shrink-0 ml-4">
                                        <span className="text-xs text-slate-400">{d.downloaded_at}</span>
                                        <Link href={route('tools.show', d.tool.slug)}>
                                            <Button variant="ghost" size="sm" className="h-7 text-xs gap-1 text-indigo-600">
                                                <ExternalLink className="h-3 w-3" /> Open
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

            </div>
        </ToolsPublicLayout>
    );
}
