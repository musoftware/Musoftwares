/**
 * RuntimeStatusBanner
 *
 * Shown at the top of tool pages to communicate agent connection state.
 * Connects to the local agent and shows:
 *   - NOT_INSTALLED → "Download the agent" CTA
 *   - OFFLINE       → "Agent not running, reconnect" warning
 *   - ONLINE        → compact green badge (collapses after 3s)
 *   - plugin.installing → progress bar
 */

import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { useRuntimeStatus, AgentType } from '@/hooks/useRuntimeStatus';
import {
    Download, Wifi, WifiOff, CheckCircle2,
    Loader2, AlertCircle, Package,
} from 'lucide-react';

interface Props {
    agentType?: AgentType;
    /** slug of the tool on this page — used to show plugin install progress */
    toolSlug?:  string;
}

export function RuntimeStatusBanner({ agentType = 'nodejs', toolSlug }: Props) {
    const { status, version, plugins, lastEvent } = useRuntimeStatus(agentType);
    const [collapsed, setCollapsed]   = useState(false);
    const [installing, setInstalling] = useState<string | null>(null);

    // Auto-collapse the "online" banner after 4s
    useEffect(() => {
        if (status === 'online') {
            const t = setTimeout(() => setCollapsed(true), 4000);
            return () => clearTimeout(t);
        } else {
            setCollapsed(false);
        }
    }, [status]);

    // Track plugin install events
    useEffect(() => {
        if (!lastEvent) return;
        if (lastEvent.event === 'plugin.installing') {
            setInstalling(String(lastEvent.data.slug ?? ''));
        }
        if (lastEvent.event === 'plugin.installed' || lastEvent.event === 'plugin.install_failed') {
            setTimeout(() => setInstalling(null), 2000);
        }
    }, [lastEvent]);

    // Don't render anything if detecting or online+collapsed
    if (status === 'detecting') return null;
    if (status === 'online' && collapsed && !installing) return null;

    return (
        <div className="w-full">
            {/* ── NOT INSTALLED ─────────────────────────────────────────── */}
            {status === 'not_installed' && (
                <div className="flex items-center gap-3 px-4 py-2.5 bg-slate-900 text-sm">
                    <div className="flex items-center gap-2 flex-1 text-slate-300">
                        <AlertCircle className="h-4 w-4 text-amber-400 shrink-0" />
                        <span>
                            <span className="text-white font-medium">Musoftware Agent not detected.</span>
                            {' '}Download and install it to run tools locally.
                        </span>
                    </div>
                    <a
                        href={agentType === 'nodejs'
                            ? route('tools.download.agent', 'node')
                            : route('tools.download.agent', 'python')
                        }
                        className="shrink-0 flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-900 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors"
                    >
                        <Download className="h-3.5 w-3.5" />
                        Download Agent
                    </a>
                </div>
            )}

            {/* ── OFFLINE ───────────────────────────────────────────────── */}
            {status === 'offline' && (
                <div className="flex items-center gap-3 px-4 py-2 bg-red-950/60 border-b border-red-900/40 text-sm">
                    <WifiOff className="h-4 w-4 text-red-400 shrink-0" />
                    <span className="text-red-300 flex-1">
                        <span className="font-medium text-red-200">Agent disconnected.</span>
                        {' '}Make sure the Musoftware Agent is running on your machine.
                    </span>
                    <span className="text-red-500 text-xs shrink-0">Reconnecting…</span>
                </div>
            )}

            {/* ── ONLINE (briefly shown then collapsed) ─────────────────── */}
            {status === 'online' && !collapsed && !installing && (
                <div className="flex items-center gap-2 px-4 py-2 bg-emerald-950/50 border-b border-emerald-900/30 text-sm">
                    <CheckCircle2 className="h-4 w-4 text-emerald-400 shrink-0" />
                    <span className="text-emerald-300">
                        Agent connected
                        {version && <span className="text-emerald-500 ml-1">v{version}</span>}
                        {plugins.length > 0 && (
                            <span className="text-emerald-600 ml-2">
                                · {plugins.length} plugin{plugins.length !== 1 ? 's' : ''} installed
                            </span>
                        )}
                    </span>
                </div>
            )}

            {/* ── PLUGIN INSTALLING ─────────────────────────────────────── */}
            {installing && (
                <div className="flex items-center gap-2.5 px-4 py-2 bg-blue-950/50 border-b border-blue-900/30 text-sm">
                    <Loader2 className="h-4 w-4 text-blue-400 shrink-0 animate-spin" />
                    <span className="text-blue-300">
                        <span className="font-medium">Installing plugin:</span>
                        {' '}{installing}…
                    </span>
                    <div className="flex-1 h-1 bg-blue-900/40 rounded-full overflow-hidden ml-2">
                        <div className="h-full bg-blue-400 rounded-full animate-pulse" style={{ width: '60%' }} />
                    </div>
                </div>
            )}
        </div>
    );
}
