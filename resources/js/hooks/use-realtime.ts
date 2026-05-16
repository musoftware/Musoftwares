import { useEffect } from 'react';

// Simplified hook for polling-based realtime data (no WebSockets allowed)
export function useRealtime(callback: () => void, interval = 10000) {
    useEffect(() => {
        const timer = setInterval(callback, interval);
        return () => clearInterval(timer);
    }, [callback, interval]);
}
