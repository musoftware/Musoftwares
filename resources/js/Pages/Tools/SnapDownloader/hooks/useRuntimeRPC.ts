import { useState, useEffect, useRef, useCallback } from 'react';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

export function useRuntimeRPC(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef(new Map<string, { resolve: Function; reject: Function }>());
    const onMessageCallbacks = useRef<Set<Function>>(new Set());

    useEffect(() => {
        let socket: WebSocket;
        let reconnectTimer: ReturnType<typeof setTimeout>;

        const connect = () => {
            socket = new WebSocket(getWsUrl());

            socket.onopen = () => {
                setWs(socket);
                setConnected(true);
                const pingInterval = setInterval(() => {
                    if (socket.readyState === WebSocket.OPEN) {
                        socket.send(JSON.stringify({ type: 'ping' }));
                    }
                }, 10000);
                (socket as any)._pingInterval = pingInterval;
            };

            socket.onclose = () => {
                setConnected(false);
                if ((socket as any)._pingInterval) clearInterval((socket as any)._pingInterval);
                reconnectTimer = setTimeout(connect, 3000);
            };

            socket.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const resolver = pendingRequests.current.get(msg.requestId);
                        if (resolver) {
                            if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload?.error || 'RPC Error'));
                            else resolver.resolve(msg.payload);
                            pendingRequests.current.delete(msg.requestId);
                        }
                    }
                    for (const cb of onMessageCallbacks.current) cb(msg);
                } catch (_) {}
            };
        };

        connect();

        return () => {
            if (socket) {
                socket.close();
                if ((socket as any)._pingInterval) clearInterval((socket as any)._pingInterval);
            }
            clearTimeout(reconnectTimer);
        };
    }, []);

    const callRPC = useCallback(async (action: string, data: any = {}): Promise<any> => {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            throw new Error('Runtime not connected');
        }
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(7);
            pendingRequests.current.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId,
                payload: { plugin: pluginSlug, action, data },
            }));
            setTimeout(() => {
                if (pendingRequests.current.has(requestId)) {
                    pendingRequests.current.get(requestId)!.reject(new Error('Request timed out'));
                    pendingRequests.current.delete(requestId);
                }
            }, 30000);
        });
    }, [ws, pluginSlug]);

    const subscribeToEvents = useCallback((cb: Function) => {
        onMessageCallbacks.current.add(cb);
        return () => onMessageCallbacks.current.delete(cb);
    }, []);

    return { connected, callRPC, subscribeToEvents };
}
