import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { 
    Download, 
    Chrome, 
    BookOpen, 
    ArrowLeft, 
    Play, 
    CheckCircle2, 
    Monitor, 
    Layers, 
    Puzzle, 
    MousePointerClick, 
    ExternalLink 
} from 'lucide-react';

interface Props {
    tool: {
        slug: string;
        title: string;
        icon_url: string | null;
        short_description: string;
        is_browser_tool: boolean;
    };
}

export default function Tutorial({ tool }: Props) {
    const isBrowser = tool.is_browser_tool;

    return (
        <ToolsPublicLayout title={`${tool.title} — Quick Start Guide`} activeNav="explore">
            <Head title={`${tool.title} — Quick Start Guide`} />

            <div className="max-w-3xl mx-auto py-10 px-4 sm:px-6 space-y-8">
                {/* Back button */}
                <div>
                    <Link
                        href={route('tools.show', tool.slug)}
                        className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to {tool.title}
                    </Link>
                </div>

                {/* Header card with glassmorphism feel */}
                <div className="relative overflow-hidden rounded-3xl bg-slate-900 text-white p-8 md:p-10 shadow-xl">
                    <div className="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-3xl" />
                    <div className="absolute -left-10 -bottom-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl" />
                    
                    <div className="relative z-10 space-y-4">
                        <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/50 text-xs font-semibold text-indigo-300">
                            {isBrowser ? <Puzzle className="h-3.5 w-3.5" /> : <Monitor className="h-3.5 w-3.5" />}
                            {isBrowser ? 'Browser Extension Required' : 'Desktop Agent Required'}
                        </div>
                        <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight">
                            Quick Start: {tool.title}
                        </h1>
                        <p className="text-slate-400 text-sm md:text-base max-w-xl leading-relaxed">
                            {tool.short_description}
                        </p>
                    </div>
                </div>

                {/* Steps Section */}
                <div className="bg-white border border-slate-200/80 rounded-3xl p-6 md:p-10 shadow-sm space-y-8">
                    <div>
                        <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <BookOpen className="h-5 w-5 text-indigo-500" /> Setup Instructions
                        </h2>
                        <p className="text-xs text-slate-400 mt-1">Follow these simple steps to activate and run your tool.</p>
                    </div>

                    <div className="relative border-l border-slate-100 pl-6 md:pl-8 ml-4 space-y-10">
                        {isBrowser ? (
                            <>
                                {/* Step 1: Download Extension */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        1
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Download the Browser Extension</h3>
                                        <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                            The browser extension acts as the visual bridge to intercept network requests securely directly in your browser.
                                        </p>
                                        <div className="pt-1">
                                            <a href={route('tools.download.agent', 'extension')}>
                                                <Button size="sm" className="bg-slate-900 hover:bg-slate-800 text-white gap-2">
                                                    <Download className="h-3.5 w-3.5" /> Download Extension ZIP
                                                </Button>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 2: Enable Developer Mode */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        2
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Install the Extension in your browser</h3>
                                        <div className="text-xs text-slate-500 leading-relaxed max-w-xl space-y-2">
                                            <p>1. Open your browser extension management tab: <b>chrome://extensions</b> (for Chrome/Edge/Brave).</p>
                                            <p>2. Toggle <b>Developer Mode</b> on in the top-right corner.</p>
                                            <p>3. Extract the downloaded ZIP file to a local folder.</p>
                                            <p>4. Click <b>Load unpacked</b> in the top-left and select the extracted folder.</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 3: Pin the Extension */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        3
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Pin and Authorize</h3>
                                        <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                            Pin the Musoftware extension in your browser toolbar. Click the extension icon and ensure the connection indicator is active.
                                        </p>
                                    </div>
                                </div>

                                {/* Step 4: Open Target Platform */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        4
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Open the platform</h3>
                                        <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                            Go to the target automation site (e.g. Facebook for the Facebook Extractor) and log in. The extension will automatically coordinate background handshakes.
                                        </p>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <>
                                {/* Step 1: Make sure runtime is running */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        1
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Download the Desktop Runtime Agent</h3>
                                        <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                            This tool executes tasks locally on your operating system. Make sure you have the Musoftware Runtime running.
                                        </p>
                                        <div className="pt-1">
                                            <a href={route('tools.download.agent', 'node')}>
                                                <Button size="sm" className="bg-slate-900 hover:bg-slate-800 text-white gap-2">
                                                    <Download className="h-3.5 w-3.5" /> Download Agent Installer
                                                </Button>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 2: Run and Authorize */}
                                <div className="relative">
                                    <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-indigo-50 border-4 border-white flex items-center justify-center text-xs font-bold text-indigo-600 shadow-sm">
                                        2
                                    </div>
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-bold text-slate-900">Launch and handshake</h3>
                                        <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                            Install and start the desktop agent. Once running, go to the connection screen or let it sync your subscription licenses in the background automatically.
                                        </p>
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Final Step: Run */}
                        <div className="relative">
                            <div className="absolute -left-[45px] md:-left-[53px] top-0 w-8 h-8 rounded-full bg-emerald-50 border-4 border-white flex items-center justify-center text-xs font-bold text-emerald-600 shadow-sm">
                                ✓
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-sm font-bold text-emerald-950">You're ready!</h3>
                                <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                    Launch the workspace to initiate scraper templates, configure delays, and monitor active workflows.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex flex-col sm:flex-row gap-3 pt-2">
                    <Link href={route('tools.run', tool.slug)} className="flex-1">
                        <Button className="w-full h-11 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold gap-2 shadow-sm">
                            <Play className="h-4 w-4 fill-current" /> Open {tool.title} Workspace
                        </Button>
                    </Link>
                    <Link href={route('tools.explore')} className="sm:w-48">
                        <Button variant="outline" className="w-full h-11 border-slate-200 text-slate-700 hover:bg-slate-50">
                            Explore Marketplace
                        </Button>
                    </Link>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
