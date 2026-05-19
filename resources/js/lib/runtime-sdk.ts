/**
 * Musoftware Runtime SDK
 * ========================
 * Client-side SDK for communicating with the local Musoftware Runtime Agent.
 *
 * Usage:
 *   import { runtimeSDK } from '@/lib/runtime-sdk';
 *
 *   await runtimeSDK.connect();
 *   const status = await runtimeSDK.getStatus();
 *   const taskId = await runtimeSDK.runPlugin('whatsapp-sender', { contacts: [...] });
 *   runtimeSDK.onEvent(e => console.log(e));
 *
 * Or via global (for non-module scripts):
 *   window.musoftware.runtime.connect()
 */

const RUNTIME_HTTP = 'http://127.0.0.1:18400';
const RUNTIME_WS   = 'ws://127.0.0.1:18401/ws';

type RuntimeEvent = {
    event: string;
    data:  Record<string, unknown>;
    ts:    number;
};

type EventHandler = (event: RuntimeEvent) => void;

type RuntimePlugin = {
    id:      string;
    name:    string;
    slug:    string;
    version: string;
    runtime: 'nodejs' | 'python';
};

type RuntimeStatus = {
    online:      boolean;
    version:     string;
    wsPort:      number;
    plugins:     RuntimePlugin[];
    activeTasks: { taskId: string; pluginId: string; runtime: string }[];
};

class MusoftwareRuntimeSDK {
    private ws:       WebSocket | null = null;
    private handlers: Set<EventHandler> = new Set();
    private _connected = false;
    private _reconnectTimer: ReturnType<typeof setTimeout> | null = null;

    // ── Connection ─────────────────────────────────────────────────────────

    async connect(): Promise<boolean> {
        const online = await this.ping();
        if (!online) return false;
        this._openWs();
        return true;
    }

    disconnect() {
        this.ws?.close();
        this.ws = null;
        this._connected = false;
        if (this._reconnectTimer) clearTimeout(this._reconnectTimer);
    }

    private _openWs() {
        if (this.ws?.readyState === WebSocket.OPEN) return;

        this.ws = new WebSocket(RUNTIME_WS);

        this.ws.onopen = () => {
            this._connected = true;
            this._dispatch({ event: 'sdk.connected', data: {}, ts: Date.now() });
        };

        this.ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data) as RuntimeEvent;
                this._dispatch(msg);
            } catch (_) {}
        };

        this.ws.onclose = () => {
            this._connected = false;
            this._dispatch({ event: 'sdk.disconnected', data: {}, ts: Date.now() });
            // Auto-reconnect
            this._reconnectTimer = setTimeout(() => this._openWs(), 5_000);
        };

        this.ws.onerror = () => { this.ws?.close(); };
    }

    // ── Status & Discovery ─────────────────────────────────────────────────

    async ping(): Promise<boolean> {
        try {
            const res = await fetch(`${RUNTIME_HTTP}/status`, {
                signal: AbortSignal.timeout(2000),
            });
            return res.ok;
        } catch {
            return false;
        }
    }

    async getStatus(): Promise<RuntimeStatus | null> {
        try {
            const res = await fetch(`${RUNTIME_HTTP}/status`, {
                signal: AbortSignal.timeout(3000),
            });
            if (!res.ok) return null;
            return res.json();
        } catch {
            return null;
        }
    }

    async getPlugins(runtime?: 'nodejs' | 'python'): Promise<RuntimePlugin[]> {
        try {
            const url = runtime
                ? `${RUNTIME_HTTP}/plugins?runtime=${runtime}`
                : `${RUNTIME_HTTP}/plugins`;
            const res  = await fetch(url, { signal: AbortSignal.timeout(3000) });
            const data = await res.json();
            return data.plugins ?? [];
        } catch {
            return [];
        }
    }

    async getSystem(): Promise<Record<string, unknown> | null> {
        try {
            const res = await fetch(`${RUNTIME_HTTP}/system`, { signal: AbortSignal.timeout(3000) });
            return res.ok ? res.json() : null;
        } catch { return null; }
    }

    // ── Task Control ────────────────────────────────────────────────────────

    async runPlugin(slug: string, params: Record<string, unknown> = {}): Promise<string | null> {
        try {
            const res  = await fetch(`${RUNTIME_HTTP}/plugins/${slug}/run`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ params }),
            });
            const data = await res.json();
            return data.taskId ?? null;
        } catch {
            return null;
        }
    }

    async stopTask(taskId: string): Promise<void> {
        try {
            await fetch(`${RUNTIME_HTTP}/tasks/${taskId}/stop`, { method: 'POST' });
        } catch (_) {}
    }

    async getTask(taskId: string): Promise<Record<string, unknown> | null> {
        try {
            const res = await fetch(`${RUNTIME_HTTP}/tasks/${taskId}`, {
                signal: AbortSignal.timeout(3000),
            });
            return res.ok ? res.json() : null;
        } catch { return null; }
    }

    // ── Auth ────────────────────────────────────────────────────────────────

    async setToken(token: string, userId: string | number): Promise<boolean> {
        try {
            const res = await fetch(`${RUNTIME_HTTP}/auth`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ token, userId: String(userId) }),
            });
            const data = await res.json();
            return data.ok === true;
        } catch { return false; }
    }

    // ── WS send ─────────────────────────────────────────────────────────────

    send(type: string, payload?: Record<string, unknown>) {
        if (this.ws?.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({ type, payload }));
        }
    }

    // ── Event subscription ───────────────────────────────────────────────────

    onEvent(handler: EventHandler): () => void {
        this.handlers.add(handler);
        return () => this.handlers.delete(handler);
    }

    private _dispatch(event: RuntimeEvent) {
        for (const h of this.handlers) {
            try { h(event); } catch (_) {}
        }
    }

    get connected() { return this._connected; }
}

// ── Singleton ─────────────────────────────────────────────────────────────────
export const runtimeSDK = new MusoftwareRuntimeSDK();

// ── Global (for non-module scripts / window access) ───────────────────────────
if (typeof window !== 'undefined') {
    (window as any).musoftware = (window as any).musoftware || {};
    (window as any).musoftware.runtime = runtimeSDK;
}

export type { RuntimePlugin, RuntimeStatus, RuntimeEvent };
export default runtimeSDK;
