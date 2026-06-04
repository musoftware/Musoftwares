import React from 'react';
import { List, RefreshCw, Trash2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { QueueJob } from '../../types/snapdownloader.types';
import { StatusBadge } from '../shared/StatusBadge';
import { EmptyState } from '../shared/EmptyState';
import { timeAgo } from '../../utils/formatters';

export function QueueWorkspace({
    queue,
    callRPC,
    loadAll
}: {
    queue: QueueJob[];
    callRPC: (action: string, data?: any) => Promise<any>;
    loadAll: () => Promise<void>;
}) {
    const handleQueueRemove = async (jobId: string) => {
        try { await callRPC('queue_remove', { jobId }); loadAll(); }
        catch (err: any) { alert(err.message); }
    };

    return (
        <div className="space-y-5 max-w-3xl mx-auto">
            <div>
                <h1 className="text-xl sm:text-2xl font-black text-white">{__('general.queue')}</h1>
                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('freelance.jobs_waiting_to_run_processed')}</p>
            </div>

            {queue.length === 0 ? (
                <EmptyState
                    icon={<List className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                    title={__('general.queue_is_empty')}
                    sub={__('general.add_profiles_from_the_download')}
                />
            ) : (
                <div className="space-y-3">
                    {queue.map((job, index) => (
                        <div key={job.id} className="flex items-center gap-3 p-4 rounded-2xl border" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                            {/* Position indicator */}
                            <div className="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-xs font-black" style={{
                                background: job.status === 'running' ? 'rgba(59,130,246,0.15)' : 'rgba(255,255,255,0.05)',
                                color: job.status === 'running' ? '#3b82f6' : 'rgba(255,255,255,0.3)',
                            }}>
                                {job.status === 'running' ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : `#${index + 1}`}
                            </div>

                            <div className="flex-1 min-w-0">
                                <div className="font-semibold text-white text-sm truncate">{job.target}</div>
                                <div className="flex items-center gap-2 mt-1 flex-wrap">
                                    <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.3)' }}>{__('general.added')} {timeAgo(job.addedAt)}</span>
                                    <StatusBadge status={job.status} />
                                    <div className="flex items-center gap-1">
                                        {Object.entries(job.filters || {}).filter(([, v]) => v).map(([k]) => (
                                            <span key={k} className="text-[9px] font-bold px-1.5 py-0.5 rounded-full capitalize" style={{ background: 'rgba(245,158,11,0.1)', color: '#f59e0b' }}>{__(k)}</span>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {job.status !== 'running' && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => handleQueueRemove(job.id)}
                                    className="h-11 w-11 shrink-0"
                                    style={{ background: 'rgba(244,63,94,0.08)', color: 'rgba(244,63,94,0.6)' }}
                                >
                                    <Trash2 className="w-4 h-4 mx-auto" />
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
