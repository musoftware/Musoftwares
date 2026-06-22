/**
 * useRuntimeStatus — React hook
 *
 * Connects to the unified Musoftware Runtime Agent via the SDK.
 * Uses HTTP :18400 for API and WS :18401 for real-time events.
 *
 * Usage:
 *   const { status, plugins, runPlugin, stopTask, lastEvent } = useRuntimeStatus();
 *
 * Status values:
 *   'detecting'     → initial probe
 *   'not_installed' → runtime not running
 *   'online'        → connected and ready
 *   'offline'       → was connected, lost connection
 */

import { useEffect, useRef, useState, useCallback } from 'react';
import { runtimeSDK } from '@/lib/runtime-sdk';
import type { RuntimePlugin, RuntimeEvent, RuntimeStatus } from '@/lib/runtime-sdk';
import { __ } from '@/lib/i18n';

export type { RuntimePlugin, RuntimeEvent };

export type ConnectionStatus = 'detecting' | 'not_installed' | 'online' | 'offline';

export interface UseRuntimeStatusReturn {
    status:      ConnectionStatus;
    version:     string | null;
    plugins:     RuntimePlugin[];
    activeTasks: { taskId: string; pluginId: string; runtime: string }[];
    error:       string | null;
    lastEvent:   RuntimeEvent | null;
    runPlugin:   (slug: string, params?: Record<string, unknown>) => Promise<string | null>;
    stopTask:    (taskId: string) => void;
    send:        (type: string, payload?: Record<string, unknown>) => void;
    connected:   boolean;
}

const PROBE_INTERVAL_MS   = 10_000; // when offline — re-probe every 10s
const REFRESH_INTERVAL_MS = 30_000; // when online — refresh status every 30s

export function useRuntimeStatus(): UseRuntimeStatusReturn {
    const [status, setStatus]       = useState<ConnectionStatus>('detecting');
    const [version, setVersion]     = useState<string | null>(null);
    const [plugins, setPlugins]     = useState<RuntimePlugin[]>([]);
    const [activeTasks, setTasks]   = useState<{ taskId: string; pluginId: string; runtime: string }[]>([]);
    const [error, setError]         = useState<string | null>(null);
    const [lastEvent, setLastEvent] = useState<RuntimeEvent | null>(null);

    const mountedRef   = useRef(true);
    const probeRef     = useRef<ReturnType<typeof setInterval> | null>(null);
    const refreshRef   = useRef<ReturnType<typeof setInterval> | null>(null);

    const applyStatus = useCallback((s: RuntimeStatus) => {
        if (!mountedRef.current) return;
        setStatus('online');
        setVersion(s.version ?? null);
        setPlugins(s.plugins ?? []);
        setTasks(s.activeTasks ?? []);
        setError(null);
    }, []);

    const probe = useCallback(async (): Promise<boolean> => {
        const s = await runtimeSDK.getStatus();
        if (!mountedRef.current) return false;
        if (s) {
            applyStatus(s);
            return true;
        } else {
            setStatus(prev => prev === 'online' ? 'offline' : 'not_installed');
            const host = typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
            setError(`Runtime agent not reachable at ${host}:18400`);
            return false;
        }
    }, [applyStatus]);

    // Handle all WS events
    const handleEvent = useCallback((event: RuntimeEvent) => {
        if (!mountedRef.current) return;
        setLastEvent(event);

        switch (event.event) {
            case 'runtime.ready':
                setStatus('online');
                setPlugins((event.data as any).plugins ?? []);
                setVersion((event.data as any).version ?? null);
                break;

            case 'plugin.installed':
            case 'plugin.installing':
                // Refresh plugin list
                probe();
                break;

            case 'worker.crashed':
                setError(`Worker crashed: ${(event.data as any).pluginId}`);
                break;

            case 'sdk.disconnected':
                if (mountedRef.current) setStatus('offline');
                break;

            case 'sdk.connected':
                if (mountedRef.current) setStatus('online');
                break;

            case 'task.done':
            case 'task.error':
                // Refresh active tasks
                setTasks(prev => prev.filter(t => t.taskId !== (event.data as any).taskId));
                break;

            case 'worker.started':
                setTasks(prev => [
                    ...prev,
                    {
                        taskId:   (event.data as any).taskId as string,
                        pluginId: (event.data as any).pluginId as string,
                        runtime:  (event.data as any).runtime as string,
                    },
                ]);
                break;
        }
    }, [probe]);

    useEffect(() => {
        mountedRef.current = true;
        const unsub = runtimeSDK.onEvent(handleEvent);

        // Initial probe
        probe().then(online => {
            if (online) {
                // Connect WS
                runtimeSDK.connect();
                // Periodic status refresh
                refreshRef.current = setInterval(probe, REFRESH_INTERVAL_MS);
            } else {
                // Poll until runtime comes online
                probeRef.current = setInterval(async () => {
                    const ok = await probe();
                    if (ok && mountedRef.current) {
                        clearInterval(probeRef.current!);
                        runtimeSDK.connect();
                        refreshRef.current = setInterval(probe, REFRESH_INTERVAL_MS);
                    }
                }, PROBE_INTERVAL_MS);
            }
        });

        return () => {
            mountedRef.current = false;
            unsub();
            clearInterval(probeRef.current!);
            clearInterval(refreshRef.current!);
        };
    }, []);  // eslint-disable-line react-hooks/exhaustive-deps

    const runPlugin = useCallback(async (slug: string, params?: Record<string, unknown>) => {
        return runtimeSDK.runPlugin(slug, params);
    }, []);

    const stopTask = useCallback((taskId: string) => {
        runtimeSDK.stopTask(taskId);
        setTasks(prev => prev.filter(t => t.taskId !== taskId));
    }, []);

    const send = useCallback((type: string, payload?: Record<string, unknown>) => {
        runtimeSDK.send(type, payload);
    }, []);

    return {
        status,
        version,
        plugins,
        activeTasks,
        error,
        lastEvent,
        runPlugin,
        stopTask,
        send,
        connected: status === 'online',
    };
}

