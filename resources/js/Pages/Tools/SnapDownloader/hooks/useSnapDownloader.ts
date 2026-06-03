import { useState, useEffect, useRef, useCallback } from 'react';
import { useRuntimeRPC } from './useRuntimeRPC';
import { Process, QueueJob, SavedFolder, WorkspaceType } from '../types/snapdownloader.types';

export function useSnapDownloader() {
    const { connected, callRPC, subscribeToEvents } = useRuntimeRPC('snapdownloader');

    const [activeWorkspace, setActiveWorkspace] = useState<WorkspaceType>('new');

    // Data state
    const [activeProcesses, setActiveProcesses] = useState<Process[]>([]);
    const [queue, setQueue] = useState<QueueJob[]>([]);
    const [automations, setAutomations] = useState<any[]>([]);
    const [folders, setFolders] = useState<SavedFolder[]>([]);
    const [history, setHistory] = useState<Process[]>([]);

    // Polling
    const pollTimer = useRef<ReturnType<typeof setInterval> | null>(null);

    const loadAll = useCallback(async () => {
        try {
            const [activeRes, queueRes, histRes, autoRes] = await Promise.all([
                callRPC('get_active', {}),
                callRPC('get_queue', {}),
                callRPC('get_history', {}),
                callRPC('get_automations', {}).catch(() => ({ automations: [] }))
            ]);
            setActiveProcesses(activeRes.processes || []);
            setQueue(queueRes.queue || []);
            setHistory(histRes.history || []);
            setAutomations(autoRes.automations || []);
        } catch (_) { /* empty */ }
    }, [callRPC]);

    const loadFolders = useCallback(async () => {
        try {
            const res = await callRPC('get_folders', {});
            setFolders(res.folders || []);
        } catch (_) { /* empty */ }
    }, [callRPC]);

    useEffect(() => {
        if (!connected) return;
        loadAll();
        pollTimer.current = setInterval(loadAll, 3000);
        return () => { if (pollTimer.current) clearInterval(pollTimer.current); };
    }, [connected, loadAll]);

    useEffect(() => {
        if (activeWorkspace === 'folders') loadFolders();
    }, [activeWorkspace, loadFolders]);

    useEffect(() => {
        const unsub = subscribeToEvents((msg: any) => {
            if (!msg.event?.startsWith('snapdownloader.')) return;
            if (['snapdownloader.process_started', 'snapdownloader.process_completed',
                 'snapdownloader.process_error', 'snapdownloader.process_stopped',
                 'snapdownloader.progress', 'snapdownloader.log'].includes(msg.event)) {
                loadAll();
            }
        });
        return () => { unsub(); };
    }, [subscribeToEvents, loadAll]);

    return {
        connected,
        callRPC,
        activeWorkspace,
        setActiveWorkspace,
        activeProcesses,
        queue,
        automations,
        folders,
        history,
        loadAll,
        loadFolders
    };
}
