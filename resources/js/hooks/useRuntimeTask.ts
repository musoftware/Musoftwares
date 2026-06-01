import { useState, useRef, useCallback } from 'react';

interface UseRuntimeTaskOptions {
    runtimePort: number;
}

export function useRuntimeTask(pluginSlug: string, options: UseRuntimeTaskOptions) {
    const { runtimePort } = options;
    const host = typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
    const base = `http://${host}:${runtimePort}`;

    const [status, setStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [logs, setLogs] = useState<string[]>([]);
    const [progress, setProgress] = useState(0);
    const [result, setResult] = useState<any>(null);
    const [error, setError] = useState<string | null>(null);

    const wsRef = useRef<WebSocket | null>(null);
    const pollRef = useRef<any>(null);
    const [taskId, setTaskId] = useState<string | null>(null);

    const isRunning = status === 'running';

    const stopTask = useCallback(async () => {
        if (!taskId) return;
        try {
            await fetch(`${base}/tasks/${taskId}/stop`, { method: 'POST' });
        } catch (e) {
            console.error('Failed to stop task', e);
        }
        setStatus('error');
        setError('Task stopped manually');
        if (pollRef.current) clearInterval(pollRef.current);
        if (wsRef.current) wsRef.current.close();
    }, [taskId, base]);

    const runTask = useCallback(async (params: any) => {
        setStatus('running');
        setLogs([]);
        setResult(null);
        setError(null);
        setProgress(0);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params }),
            });
            const data = await res.json();
            if (!res.ok) {
                setError(data.message || data.error || 'Error starting task');
                setStatus('error');
                return;
            }
            const tid = data.taskId;
            setTaskId(tid);

            // Connect WebSocket (using runtimePort + 1 for ws)
            const activeHost = typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
            const ws = new WebSocket(`ws://${activeHost}:${runtimePort + 1}/ws`);
            ws.onmessage = (ev) => {
                try {
                    const msg = JSON.parse(ev.data);
                    const d = msg.data ?? {};
                    if (d.taskId && d.taskId !== tid) return;
                    if (msg.event === 'task.log') setLogs(l => [...l, d.message ?? '']);
                    if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                    if (msg.event === 'task.done') {
                        setResult(d.result ?? {});
                        setStatus('done');
                        setProgress(100);
                        if (pollRef.current) clearInterval(pollRef.current);
                    }
                    if (msg.event === 'task.error') {
                        setError(d.error ?? 'Unknown error');
                        setStatus('error');
                        if (pollRef.current) clearInterval(pollRef.current);
                    }
                } catch {}
            };
            wsRef.current = ws;

            // Start Polling as fallback
            pollRef.current = setInterval(async () => {
                try {
                    const r = await fetch(`${base}/tasks/${tid}`);
                    const d = await r.json();
                    setLogs(d.logs?.map((l: any) => l.message ?? l) ?? []);
                    if (d.status === 'done') {
                        setResult(d.result ?? {});
                        setStatus('done');
                        clearInterval(pollRef.current);
                    }
                    if (d.status === 'failed') {
                        setError(d.error ?? 'Failed');
                        setStatus('error');
                        clearInterval(pollRef.current);
                    }
                } catch {}
            }, 1500);

        } catch (e) {
            setError('Cannot reach runtime');
            setStatus('error');
        }
    }, [base, pluginSlug, runtimePort]);

    return {
        runTask,
        stopTask,
        isRunning,
        status,
        logs,
        progress,
        result,
        error
    };
}
