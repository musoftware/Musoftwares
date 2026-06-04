import React, { useState, useEffect } from 'react';
import { Activity, Download, RefreshCw, CheckCircle, XCircle, Square, Terminal, FolderOpen, StopCircle } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { Process, WorkspaceType } from '../../types/snapdownloader.types';
import { StatusBadge } from '../shared/StatusBadge';
import { LogTerminal } from '../shared/LogTerminal';
import { EmptyState } from '../shared/EmptyState';
import { timeAgo } from '../../utils/formatters';

export function ActiveProcessesWorkspace({
    activeProcesses,
    callRPC,
    loadAll,
    setActiveWorkspace
}: {
    activeProcesses: Process[];
    callRPC: (action: string, data?: any) => Promise<any>;
    loadAll: () => Promise<void>;
    setActiveWorkspace: (ws: WorkspaceType) => void;
}) {
    const [selectedProcessId, setSelectedProcessId] = useState<string | null>(null);
    const [processLogs, setProcessLogs] = useState<{ ts: number; level: string; message: string }[]>([]);
    const [logsExpanded, setLogsExpanded] = useState(false);

    useEffect(() => {
        if (!selectedProcessId) return;
        callRPC('get_logs', { processId: selectedProcessId })
            .then(res => setProcessLogs(res.logs || []))
            .catch(() => {});
    }, [selectedProcessId, activeProcesses, callRPC]);

    const handleStopProcess = async (processId: string) => {
        try { await callRPC('stop_process', { processId }); loadAll(); }
        catch (err: any) { alert(err.message); }
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
            <div>
                <h1 className="text-xl sm:text-2xl font-black text-white">{__('general.active_downloads')}</h1>
                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('freelance.monitor_and_control_running_jobs')}</p>
            </div>

            {activeProcesses.length === 0 && (
                <EmptyState
                    icon={<Activity className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                    title={__('general.no_active_downloads')}
                    sub={__('general.start_a_download_from_the')}
                    cta={
                        <Button onClick={() => setActiveWorkspace('new')} className="gap-2 h-11 text-xs font-bold" style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b' }}>
                            <Download className="w-3.5 h-3.5" /> {__('general.new_download')}
                        </Button>
                    }
                />
            )}

            <div className="space-y-4">
                {activeProcesses.map(proc => (
                    <div key={proc.id} className="rounded-2xl border overflow-hidden" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                        <div className="p-4 sm:p-5">
                            <div className="flex items-start gap-3">
                                {/* Status Icon */}
                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{
                                    background: proc.status === 'running' ? 'rgba(59,130,246,0.15)' :
                                               proc.status === 'completed' ? 'rgba(16,185,129,0.15)' :
                                               proc.status === 'error' ? 'rgba(244,63,94,0.15)' : 'rgba(255,255,255,0.05)',
                                }}>
                                    {proc.status === 'running' && <RefreshCw className="w-4.5 h-4.5 animate-spin" style={{ color: '#3b82f6' }} />}
                                    {proc.status === 'completed' && <CheckCircle className="w-4.5 h-4.5" style={{ color: '#10b981' }} />}
                                    {proc.status === 'error' && <XCircle className="w-4.5 h-4.5" style={{ color: '#f43f5e' }} />}
                                    {proc.status === 'stopped' && <Square className="w-4.5 h-4.5" style={{ color: 'rgba(255,255,255,0.4)' }} />}
                                </div>

                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-bold text-white text-sm truncate max-w-[160px] sm:max-w-xs">{proc.target}</span>
                                        <StatusBadge status={proc.status} />
                                    </div>
                                    <div className="text-[11px] mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>
                                        {__('general.started')} {timeAgo(proc.startTime)}
                                        {proc.totalItems > 0 && ` · ${proc.successCount}/${proc.totalItems} ${__('general.items')}`}
                                    </div>

                                    {/* Progress */}
                                    {proc.status === 'running' && (
                                        <div className="mt-3 space-y-1.5">
                                            <div className="flex items-center justify-between text-[10px]">
                                                <span style={{ color: 'rgba(255,255,255,0.4)' }}>{proc.progressMsg}</span>
                                                <span className="font-bold" style={{ color: '#3b82f6' }}>{proc.progress}%</span>
                                            </div>
                                            <div className="h-1.5 rounded-full overflow-hidden" style={{ background: 'rgba(255,255,255,0.06)' }}>
                                                <div className="h-full rounded-full transition-all duration-500" style={{ width: `${proc.progress}%`, background: 'linear-gradient(90deg, #3b82f6, #6366f1)' }} />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Actions Row */}
                            <div className="flex items-center gap-2 mt-4 flex-wrap">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => selectProcess(proc)}
                                    className="gap-1.5 h-9 text-[11px] font-semibold transition-all"
                                    style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)' }}
                                >
                                    <Terminal className="w-3 h-3" /> {__('general.logs')}
                                </Button>
                                {proc.outputDir && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleOpenFolder(proc.target.replace(/^@/, '').replace(/.*\//, ''))}
                                        className="gap-1.5 h-9 text-[11px] font-semibold transition-all"
                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)' }}
                                    >
                                        <FolderOpen className="w-3 h-3" /> {__('general.folder')}
                                    </Button>
                                )}
                                {proc.status === 'running' && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleStopProcess(proc.id)}
                                        className="gap-1.5 h-9 text-[11px] font-bold transition-all ml-auto"
                                        style={{ background: 'rgba(244,63,94,0.15)', color: '#f43f5e', border: '1px solid rgba(244,63,94,0.25)' }}
                                    >
                                        <StopCircle className="w-3 h-3" /> {__('general.stop')}
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Inline Terminal */}
                        {selectedProcessId === proc.id && logsExpanded && (
                            <div className="border-t" style={{ borderColor: 'rgba(255,255,255,0.05)', background: '#0a0c13' }}>
                                <LogTerminal logs={processLogs} target={proc.target} onClose={closeLogs} />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
