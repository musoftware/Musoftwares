/**
 * useRuntimeStatus — React hook
 *
 * Detects whether the local agent is running and subscribes to its
 * WebSocket for real-time events (task logs, progress, plugin updates).
 *
 * Usage:
 *   const { status, plugins, send, lastEvent } = useRuntimeStatus('nodejs');
 *
 * Status:
 *   'detecting'    → initial probe in progress
 *   'not_installed'→ agent not running / not installed
 *   'online'       → agent connected and ready
 *   'offline'      → was connected, now lost connection
 */

import { useEffect, useRef, useState, useCallback } from 'react';

export type AgentType = 'nodejs' | 'python';

export interface AgentPlugin {
    id:      string;
    name:    string;
    slug:    string;
    version: string;
}

export interface ActiveTask {
    taskId:   string;
    pluginId: string;
}

export interface AgentStatus {
    status:      'detecting' | 'not_installed' | 'online' | 'offline';
    version:     string | null;
    plugins:     AgentPlugin[];
    activeTasks: ActiveTask[];
    error:       string | null;
}

export interface RuntimeEvent {
    event: string;
    data:  Record<string, unknown>;
    ts:    number;
}

const AGENT_PORTS: Record<AgentType, number> = {
    nodejs: 18400,
    python: 18401,
};

const POLL_INTERVAL_MS    = 10_000; // re-check every 10s when offline
const WS_RECONNECT_MS     = 5_000;

interface UseRuntimeStatusReturn extends AgentStatus {
    /** Send a command to the agent via WebSocket */
    send: (type: string, payload?: Record<string, unknown>) => void;
    /** Run a plugin on the agent */
    runPlugin: (slug: string, params?: Record<string, unknown>) => Promise<string | null>;
    /** Stop a task */
    stopTask: (taskId: string) => void;
    /** Last received WS event */
    lastEvent: RuntimeEvent | null;
}

export function useRuntimeStatus(agentType: AgentType = 'nodejs'): UseRuntimeStatusReturn {
    const port    = AGENT_PORTS[agentType];
    const baseUrl = `http://127.0.0.1:${port}`;
    const wsUrl   = `ws://127.0.0.1:${port}/ws`;

    const [state, setState] = useState<AgentStatus>({
        status:      'detecting',
        version:     null,
        plugins:     [],
        activeTasks: [],
        error:       null,
    });
    const [lastEvent, setLastEvent] = useState<RuntimeEvent | null>(null);

    const wsRef      = useRef<WebSocket | null>(null);
    const pollRef    = useRef<ReturnType<typeof setInterval> | null>(null);
    const mountedRef = useRef(true);

    // ── HTTP probe ─────────────────────────────────────────────────────────
    const probe = useCallback(async () => {
        try {
            const res = await fetch(`${baseUrl}/status`, {
                signal: AbortSignal.timeout(2000),
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!mountedRef.current) return;

            setState({
                status:      'online',
                version:     data.version ?? null,
                plugins:     data.plugins ?? [],
                activeTasks: data.activeTasks ?? [],
                error:       null,
            });
            return true;
        } catch {
            if (!mountedRef.current) return false;
            setState(prev => ({
                ...prev,
                status: prev.status === 'online' ? 'offline' : 'not_installed',
                error:  'Agent not reachable',
            }));
            return false;
        }
    }, [baseUrl]);

    // ── WebSocket connection ────────────────────────────────────────────────
    const connectWs = useCallback(() => {
        if (wsRef.current?.readyState === WebSocket.OPEN) return;

        const ws = new WebSocket(wsUrl);
        wsRef.current = ws;

        ws.onopen = () => {
            if (!mountedRef.current) return;
            setState(prev => ({ ...prev, status: 'online', error: null }));
        };

        ws.onmessage = (e) => {
            if (!mountedRef.current) return;
            try {
                const msg: RuntimeEvent = JSON.parse(e.data);
                setLastEvent(msg);

                // Update plugin list when plugin is installed
                if (msg.event === 'plugin.installed' || msg.event === 'agent.ready') {
                    probe();
                }
            } catch (_) {}
        };

        ws.onclose = () => {
            if (!mountedRef.current) return;
            setState(prev => ({ ...prev, status: 'offline' }));
            // Reconnect after delay
            setTimeout(() => {
                if (mountedRef.current) connectWs();
            }, WS_RECONNECT_MS);
        };

        ws.onerror = () => {
            ws.close();
        };
    }, [wsUrl, probe]);

    // ── Bootstrap ──────────────────────────────────────────────────────────
    useEffect(() => {
        mountedRef.current = true;

        probe().then(online => {
            if (online) connectWs();
            else {
                // Poll until agent comes online
                pollRef.current = setInterval(async () => {
                    const ok = await probe();
                    if (ok) {
                        clearInterval(pollRef.current!);
                        connectWs();
                    }
                }, POLL_INTERVAL_MS);
            }
        });

        return () => {
            mountedRef.current = false;
            clearInterval(pollRef.current!);
            wsRef.current?.close();
        };
    }, [agentType]); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Public API ─────────────────────────────────────────────────────────
    const send = useCallback((type: string, payload?: Record<string, unknown>) => {
        if (wsRef.current?.readyState === WebSocket.OPEN) {
            wsRef.current.send(JSON.stringify({ type, payload }));
        }
    }, []);

    const runPlugin = useCallback(async (slug: string, params?: Record<string, unknown>): Promise<string | null> => {
        try {
            const res = await fetch(`${baseUrl}/plugins/${slug}/run`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ params }),
            });
            const data = await res.json();
            return data.taskId ?? null;
        } catch {
            return null;
        }
    }, [baseUrl]);

    const stopTask = useCallback((taskId: string) => {
        send('stop', { taskId });
    }, [send]);

    return { ...state, send, runPlugin, stopTask, lastEvent };
}
