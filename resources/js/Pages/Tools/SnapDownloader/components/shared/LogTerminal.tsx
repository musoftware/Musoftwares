import React, { useEffect, useRef } from 'react';
import { Button } from '@/Components/ui/button';
import { XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export function LogTerminal({ logs, target, onClose }: {
    logs: { ts: number; level: string; message: string }[];
    target: string;
    onClose: () => void;
}) {
    const logsEndRef = useRef<HTMLDivElement>(null);
    useEffect(() => {
        logsEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [logs]);

    return (
        <div className="rounded-2xl border overflow-hidden" style={{ background: '#0a0c13', borderColor: 'rgba(255,255,255,0.06)' }}>
            <div className="flex items-center justify-between px-4 py-2.5 border-b" style={{ borderColor: 'rgba(255,255,255,0.04)' }}>
                <div className="flex gap-1.5">
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f43f5e' }} />
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f59e0b' }} />
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#10b981' }} />
                </div>
                <span className="text-[10px] font-mono truncate max-w-[120px] sm:max-w-xs" style={{ color: 'rgba(255,255,255,0.2)' }}>
                    {target}
                </span>
                <Button variant="ghost" size="icon" onClick={onClose} className="h-6 w-6 hover:bg-transparent" style={{ color: 'rgba(255,255,255,0.3)' }}>
                    <XCircle className="w-3.5 h-3.5" />
                </Button>
            </div>
            <div className="h-48 sm:h-64 overflow-y-auto p-4 font-mono text-[11px] space-y-1">
                {logs.length === 0 ? (
                    <div style={{ color: 'rgba(255,255,255,0.2)' }}>{__('general.awaiting_output')}</div>
                ) : logs.map((log, i) => {
                    const color =
                        log.level === 'error' ? '#f43f5e' :
                        log.message?.includes('[+]') ? '#10b981' :
                        log.message?.includes('[*]') ? '#60a5fa' :
                        log.message?.includes('[!]') ? '#f59e0b' :
                        'rgba(255,255,255,0.5)';
                    return (
                        <div key={i} className="leading-relaxed break-all" style={{ color }}>
                            <span style={{ color: 'rgba(255,255,255,0.15)', marginRight: '8px' }}>
                                {new Date(log.ts).toLocaleTimeString()}
                            </span>
                            {log.message}
                        </div>
                    );
                })}
                <div ref={logsEndRef} />
            </div>
        </div>
    );
}
