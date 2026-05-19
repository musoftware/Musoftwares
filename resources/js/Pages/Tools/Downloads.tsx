import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { ExternalLink, Zap, Clock, ShoppingBag, CheckCircle2, Monitor } from 'lucide-react';

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
    return (
        <ToolsPublicLayout title="My Tools" activeNav="downloads">
            <Head title="My Tools — Musoftware" />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

                {/* Page header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        My Tools
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Tools run directly in your browser — no download needed.
                        Make sure the{' '}
                        <Link
                            href="http://127.0.0.1:18400/setup"
                            className="text-indigo-600 hover:underline"
                            target="_blank"
                        >
                            Musoftware Runtime
                        </Link>{' '}
                        is running on your computer.
                    </p>
                </div>

                {/* Available Tools */}
                {availableTools.length > 0 ? (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Your Subscribed Tools
                        </h2>
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
                                            <ExternalLink className="h-3.5 w-3.5" />
                                            Open Tool
                                        </Button>
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
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">No tools yet</p>
                        <p className="text-xs text-slate-400 mt-1 mb-5">
                            Subscribe to a tool to get started — many are free.
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('tools.explore'))}
                        >
                            Browse Tools
                        </Button>
                    </div>
                )}

                {/* Runtime notice */}
                <div className="flex items-start gap-3 rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20 p-4">
                    <Monitor className="h-4 w-4 text-indigo-500 mt-0.5 shrink-0" />
                    <div className="text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed">
                        <strong>How it works:</strong> Tools run locally on your computer via the Musoftware Runtime.
                        The website is your control panel — no separate app needed.{' '}
                        <Link
                            href="http://127.0.0.1:18400/setup"
                            target="_blank"
                            className="underline underline-offset-2"
                        >
                            Check runtime status →
                        </Link>
                    </div>
                </div>

                {/* Usage History */}
                {downloads.data.length > 0 && (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Recent Activity
                        </h2>
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            {downloads.data.map(d => (
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
