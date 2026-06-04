import React, { useState, useEffect } from 'react';
import { History, Trash2, CheckCircle, XCircle, Square, Eye, FolderOpen } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { Process } from '../../types/snapdownloader.types';
import { StatusBadge } from '../shared/StatusBadge';
import { LogTerminal } from '../shared/LogTerminal';
import { EmptyState } from '../shared/EmptyState';
import { timeAgo } from '../../utils/formatters';

export function HistoryWorkspace({
    history,
    callRPC,
    setHistory
}: {
    history: Process[];
    callRPC: (action: string, data?: any) => Promise<any>;
    setHistory: (history: Process[]) => void;
}) {
    const [selectedProcessId, setSelectedProcessId] = useState<string | null>(null);
    const [processLogs, setProcessLogs] = useState<{ ts: number; level: string; message: string }[]>([]);
    const [logsExpanded, setLogsExpanded] = useState(false);

    useEffect(() => {
        if (!selectedProcessId) return;
        callRPC('get_logs', { processId: selectedProcessId })
            .then(res => setProcessLogs(res.logs || []))
            .catch(() => {});
    }, [selectedProcessId, callRPC, history]);

    const handleClearHistory = async () => {
        try { await callRPC('clear_history', {}); setHistory([]); }
        catch (_) { /* empty */ }
    };

    const handleOpenFolder = async (subdir?: string) => {
        try { await callRPC('open_folder', { subdir }); }
        catch (err: any) { alert(err.message); }
    };

    const selectProcess = async (proc: Process) => {
        setSelectedProcessId(proc.id);
        setLogsExpanded(true);
        const res = await callRPC('get_logs', { processId: proc.id }).catch(() => ({ logs: [] }));
        setProcessLogs(res.logs || []);
    };

    const closeLogs = () => { setLogsExpanded(false); setSelectedProcessId(null); };

    return (
        <div className="space-y-5 max-w-3xl mx-auto">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-white">{__('general.history')}</h1>
                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('general.past_completed_and_stopped_downloads')}</p>
                </div>
                {history.length > 0 && (
                    <Button variant="ghost" onClick={handleClearHistory} className="gap-2 h-11 text-xs font-semibold" style={{ background: 'rgba(244,63,94,0.1)', color: '#f43f5e' }}>
                        <Trash2 className="w-3.5 h-3.5" /> {__('general.clear')}
                    </Button>
                )}
            </div>

            {history.length === 0 ? (
                <EmptyState
                    icon={<History className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                    title={__('general.no_history_yet')}
                    sub={__('general.completed_downloads_will_appear_here')}
                />
            ) : (
                <div className="space-y-3">
                    {history.map(item => (
                        <div key={item.id} className="rounded-2xl border overflow-hidden" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                            <div className="flex items-center gap-3 p-4">
                                <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{
                                    background: item.status === 'completed' ? 'rgba(16,185,129,0.1)' :
                                               item.status === 'error' ? 'rgba(244,63,94,0.1)' : 'rgba(255,255,255,0.05)',
                                }}>
                                    {item.status === 'completed' && <CheckCircle className="w-4 h-4" style={{ color: '#10b981' }} />}
                                    {item.status === 'error' && <XCircle className="w-4 h-4" style={{ color: '#f43f5e' }} />}
                                    {item.status === 'stopped' && <Square className="w-4 h-4" style={{ color: 'rgba(255,255,255,0.3)' }} />}
                                </div>

                                <div className="flex-1 min-w-0">
                                    <div className="font-semibold text-white text-sm truncate">{item.target}</div>
                                    <div className="flex items-center gap-2 mt-1 flex-wrap">
                                        <StatusBadge status={item.status} />
                                        {item.successCount !== undefined && (
                                            <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.35)' }}>{item.successCount} {__('general.saved')}</span>
                                        )}
                                        {item.endTime && (
                                            <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.25)' }}>{timeAgo(item.endTime)}</span>
                                        )}
                                    </div>
                                </div>

                                {/* Actions */}
                                <div className="flex items-center gap-2 shrink-0">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => selectProcess(item)}
                                        className="h-9 w-9 transition-all"
                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleOpenFolder(item.target.replace(/^@/, '').replace(/.*\//, ''))}
                                        className="h-9 w-9 transition-all"
                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}
                                    >
                                        <FolderOpen className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>

                            {/* Log drawer */}
                            {selectedProcessId === item.id && logsExpanded && (
                                <div className="border-t" style={{ borderColor: 'rgba(255,255,255,0.05)' }}>
                                    <LogTerminal logs={processLogs} target={item.target} onClose={closeLogs} />
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
