import { useState, useEffect, useRef } from 'react';

export function useRuntimeWS(pluginSlug: string, onBroadcast?: ((event: string, data: any) => void) | null) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const [installingPlugin, setInstallingPlugin] = useState<boolean>(false);
    const [loginRequired, setLoginRequired] = useState<boolean>(false);
    const pending = useRef<Map<string, { resolve: ((...args: any[]) => any); reject: ((...args: any[]) => any) }>>(new Map());
    const onBroadcastRef = useRef<((event: string, data: any) => void) | null>(null);
    
    useEffect(() => {
        onBroadcastRef.current = onBroadcast || null;
    }, [onBroadcast]);

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const originalAlert = window.alert;
            window.alert = function (message) {
                if (message && typeof message === 'string' && (message.includes('RUNTIME_NOT_CONFIGURED') || message.includes('runtime_not_configured'))) {
                    console.warn('Blocked RUNTIME_NOT_CONFIGURED alert:', message);
                    return;
                }
                // eslint-disable-next-line prefer-rest-params
                return originalAlert.apply(this, arguments as any);
            };
        }
    }, []);

    useEffect(() => {
        const host = typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
        const socket = new WebSocket(`ws://${host}:18401/ws`);

        socket.onopen = () => setConnected(true);
        socket.onclose = () => setConnected(false);

        socket.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                
                // RPC response/error routing
                if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                    const r = pending.current.get(msg.requestId);
                    if (r) {
                        if (msg.type === 'plugin_rpc_error') {
                            const errMessage = msg.payload?.error || 'RPC Error';
                            if (errMessage.includes('RUNTIME_NOT_CONFIGURED')) {
                                setLoginRequired(true);
                            }
                            r.reject(new Error(errMessage));
                        } else {
                            r.resolve(msg.payload);
                        }
                        pending.current.delete(msg.requestId);
                    }
                }
                
                // Plugin installation progress events from runtime
                if (msg.event === 'plugin.installing') {
                    setInstallingPlugin(true);
                }
                if (msg.event === 'plugin.installed' || msg.event === 'plugin.install_failed') {
                    setInstallingPlugin(false);
                    if (msg.event === 'plugin.installed' && typeof window !== 'undefined') {
                        window.location.reload();
                    }
                }
                if (msg.event === 'auth.connected') {
                    setLoginRequired(false);
                    if (typeof window !== 'undefined') {
                        window.location.reload();
                    }
                }

                // General broadcast events
                if (msg.event && onBroadcastRef.current) {
                    onBroadcastRef.current(msg.event, msg.data);
                }
            } catch (_) { /* empty */ }
        };

        setWs(socket);
        return () => socket.close();
    }, []);

    const callRPC = async (action: string, data: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            throw new Error('Not connected to runtime — is the Musoftware Runtime running?');
        }
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(2, 9);
            pending.current.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: pluginSlug, action, data } }));
            setTimeout(() => {
                const r = pending.current.get(requestId);
                if (r) {
                    r.reject(new Error('RPC timeout'));
                    pending.current.delete(requestId);
                }
            }, 30_000);
        });
    };

    const emitEvent = (event: string, payload: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({ event, payload }));
    };

    return { 
        connected, 
        callRPC, 
        emitEvent, 
        installingPlugin, 
        loginRequired, 
        setLoginRequired 
    };
}
