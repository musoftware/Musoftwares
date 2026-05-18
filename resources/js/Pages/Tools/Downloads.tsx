import React from 'react';
import { Head, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Download, ArrowDownToLine, Clock, HardDrive, ShoppingBag } from 'lucide-react';

interface AvailableTool {
    tool_slug: string; tool_title: string; tool_icon_url: string | null;
    version: string; file_size: string; released_at: string;
}
interface DownloadRecord {
    id: number;
    tool: { slug: string; title: string; icon_url: string | null };
    version: string; downloaded_at: string;
}
interface Props {
    availableTools: AvailableTool[];
    downloads: { data: DownloadRecord[]; links: any[] };
}

export default function Downloads({ availableTools, downloads }: Props) {
    return (
        <ToolsPublicLayout title="Downloads" activeNav="downloads">
            <Head title="Downloads" />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

                {/* Page header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Downloads</h1>
                    <p className="text-sm text-slate-500 mt-1">Download the latest version of your subscribed tools.</p>
                </div>

                {/* Available Downloads */}
                {availableTools.length > 0 && (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Available</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {availableTools.map(t => (
                                <div
                                    key={t.tool_slug}
                                    className="bg-white border border-slate-200 rounded-xl p-5 space-y-4 hover:border-slate-300 hover:shadow-sm transition-all"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                            {t.tool_icon_url
                                                ? <img src={t.tool_icon_url} alt="" className="w-7 h-7 object-contain" />
                                                : <ArrowDownToLine className="h-5 w-5 text-slate-300" />
                                            }
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">{t.tool_title}</p>
                                            <code className="text-xs text-slate-400 font-mono">v{t.version}</code>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between text-xs text-slate-400">
                                        <span className="flex items-center gap-1">
                                            <HardDrive className="h-3 w-3" />{t.file_size}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Clock className="h-3 w-3" />{t.released_at}
                                        </span>
                                    </div>

                                    <Button
                                        className="w-full gap-2 text-sm bg-slate-900 hover:bg-slate-800 text-white h-9"
                                        onClick={() => router.visit(route('tools.download.generate', t.tool_slug))}
                                    >
                                        <Download className="h-3.5 w-3.5" /> Download
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                {/* Download History */}
                <section className="space-y-4">
                    <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">History</h2>

                    {downloads.data.length === 0 ? (
                        <div className="text-center py-14 bg-white border border-slate-200 rounded-xl">
                            <div className="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                                <ShoppingBag className="h-6 w-6 text-slate-300" />
                            </div>
                            <p className="text-sm font-semibold text-slate-700">No downloads yet</p>
                            <p className="text-xs text-slate-400 mt-1 mb-4">Subscribe to a tool to start downloading.</p>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.visit(route('tools.explore'))}
                            >
                                Browse Tools
                            </Button>
                        </div>
                    ) : (
                        <div className="bg-white border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
                            {downloads.data.map(d => (
                                <div key={d.id} className="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                            {d.tool.icon_url
                                                ? <img src={d.tool.icon_url} alt="" className="w-5 h-5 object-contain" />
                                                : <Download className="h-3.5 w-3.5 text-slate-300" />
                                            }
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-slate-900">{d.tool.title}</p>
                                            <code className="text-xs text-slate-400 font-mono">v{d.version}</code>
                                        </div>
                                    </div>
                                    <span className="text-xs text-slate-400 shrink-0 ml-4">{d.downloaded_at}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </ToolsPublicLayout>
    );
}
