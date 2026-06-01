/**
 * RuntimeStatusBanner
 *
 * Shown at the top of tool pages — communicates unified runtime state.
 *   - NOT_INSTALLED → "Download the runtime" CTA
 *   - OFFLINE       → reconnecting warning
 *   - ONLINE        → compact green badge (collapses after 4s)
 *   - plugin.installing → animated progress bar
 *   - runtime.update_available → soft nudge
 */

import React, { useEffect, useState } from 'react';
import { useRuntimeStatus } from '@/hooks/useRuntimeStatus';
import {
    Download, WifiOff, CheckCircle2,
    Loader2, AlertCircle, RefreshCw,
    Play, Power
} from 'lucide-react';

interface Props {
    /** slug of the tool on this page — used to track install progress */
    toolSlug?: string;
}

export function RuntimeStatusBanner({ toolSlug }: Props) {
    const { status, version, plugins, lastEvent, send } = useRuntimeStatus();
    const [installing, setInstalling] = useState<string | null>(null);
    const [updateAvail, setUpdateAvail] = useState<string | null>(null);

    // Handle WS events
    useEffect(() => {
        if (!lastEvent) return;
        const { event, data } = lastEvent;

        if (event === 'plugin.installing') {
            setInstalling(String(data.toolSlug ?? data.slug ?? ''));
        }
        if (event === 'plugin.installed' || event === 'plugin.install_failed') {
            setTimeout(() => setInstalling(null), 2000);
        }
        if (event === 'runtime.update_available') {
            setUpdateAvail(String(data.latest ?? ''));
        }
    }, [lastEvent]);

    const renderControls = () => (
        <div className="w-full flex items-center justify-between px-4 py-1.5 bg-slate-950 border-b border-slate-900 z-50">
            <div className="flex items-center gap-2">
                <div className={`h-1.5 w-1.5 rounded-full ${status === 'online' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]' : 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.8)]'}`} />
                <span className="text-xs font-medium text-slate-400">
                    Runtime {status === 'online' ? 'Connected' : (status === 'detecting' ? 'Detecting...' : 'Disconnected')}
                </span>
            </div>
            <div className="flex items-center gap-1.5">
                {status !== 'online' ? (
                    <button
                        onClick={() => { window.location.href = 'musoftware://launch'; }}
                        className="flex items-center gap-1.5 px-3 py-1 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 hover:text-emerald-300 rounded text-xs font-semibold transition-colors"
                    >
                        <Play className="h-3 w-3" />{__('general.run_runtime')}</button>
                ) : (
                    <>
                        <button
                            onClick={() => { send('runtime.restart'); }}
                            title={__('general.restart_runtime')}
                            className="flex items-center gap-1.5 px-3 py-1 bg-slate-800/50 text-slate-300 hover:bg-slate-700 hover:text-white rounded text-xs font-medium transition-colors"
                        >
                            <RefreshCw className="h-3 w-3" />
                            Restart
                        </button>
                        <button
                            onClick={() => { send('runtime.close'); }}
                            title={__('general.close_runtime')}
                            className="flex items-center gap-1.5 px-3 py-1 bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 rounded text-xs font-medium transition-colors"
                        >
                            <Power className="h-3 w-3" />
                            Close
                        </button>
                    </>
                )}
            </div>
        </div>
    );

    if (status === 'detecting') return null;
    if (status === 'online' && !installing && !updateAvail) return renderControls();

    return (
        <>
            {renderControls()}
            <div className="w-full">
            {/* ── NOT INSTALLED ─────────────────────────────────────────────── */}
            {status === 'not_installed' && (
                <div className="flex items-center gap-3 px-4 py-2.5 bg-slate-900/80 border-b border-slate-800 text-sm">
                    <AlertCircle className="h-4 w-4 text-amber-400 shrink-0" />
                    <span className="text-slate-300 flex-1">
                        <span className="text-white font-medium">{__('general.musoftware_runtime_not_detected')}</span>
                        {' '}Install the runtime agent to execute tools locally on your machine.
                    </span>
                    <a
                        href={route('tools.download.agent', 'node')}
                        className="shrink-0 flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-900 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors"
                    >
                        <Download className="h-3.5 w-3.5" />{__('general.download_runtime')}</a>
                </div>
            )}

            {/* ── OFFLINE ───────────────────────────────────────────────────── */}
            {status === 'offline' && (
                <div className="flex items-center gap-3 px-4 py-2 bg-red-950/60 border-b border-red-900/40 text-sm">
                    <WifiOff className="h-4 w-4 text-red-400 shrink-0" />
                    <span className="text-red-300 flex-1">
                        <span className="font-medium text-red-200">{__('general.runtime_disconnected')}</span>
                        {' '}Make sure the Musoftware Runtime is running on your machine.
                    </span>
                    <span className="text-red-500 text-xs shrink-0 flex items-center gap-1">
                        <RefreshCw className="h-3 w-3 animate-spin" /> Reconnecting…
                    </span>
                </div>
            )}


            {/* ── PLUGIN INSTALLING ─────────────────────────────────────────── */}
            {installing && (
                <div className="flex items-center gap-2.5 px-4 py-2 bg-blue-950/50 border-b border-blue-900/30 text-sm">
                    <Loader2 className="h-4 w-4 text-blue-400 shrink-0 animate-spin" />
                    <span className="text-blue-300">
                        <span className="font-medium">Installing:</span> {installing}…
                    </span>
                    <div className="flex-1 h-1 bg-blue-900/40 rounded-full overflow-hidden ml-2">
                        <div className="h-full bg-blue-400 rounded-full animate-[pulse_1.5s_ease-in-out_infinite]" style={{ width: '65%' }} />
                    </div>
                </div>
            )}

            {/* ── UPDATE AVAILABLE ─────────────────────────────────────────── */}
            {updateAvail && status === 'online' && (
                <div className="flex items-center gap-2 px-4 py-1.5 bg-violet-950/40 border-b border-violet-900/30 text-xs">
                    <span className="text-violet-300">
                        Runtime update available → <span className="font-mono font-semibold">v{updateAvail}</span>
                    </span>
                    <a
                        href={route('tools.download.agent', 'node')}
                        className="ml-auto text-violet-400 hover:text-violet-200 underline"
                    >
                        Update
                    </a>
                </div>
            )}
        </div>
        </>
    );
}
