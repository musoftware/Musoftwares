import React, { useState, useEffect } from 'react';
import {
    LayoutDashboard, Play, CheckCircle2, XCircle, RefreshCw, Eye,
    Pause, Square, Trash2, MoreVertical, X, Users, Layers, Clock
} from 'lucide-react';

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; pulse?: boolean }> = {
    created:   { label: 'Ready',     color: 'text-slate-500',  bg: 'bg-slate-100'  },
    running:   { label: 'Running',   color: 'text-blue-600',   bg: 'bg-blue-50',  pulse: true },
    paused:    { label: 'Paused',    color: 'text-amber-600',  bg: 'bg-amber-50'  },
    stopped:   { label: 'Stopped',   color: 'text-slate-500',  bg: 'bg-slate-100' },
    completed: { label: 'Completed', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    failed:    { label: 'Failed',    color: 'text-rose-600',   bg: 'bg-rose-50'   },
};

function CampaignActions({ campaign, onStart, onPause, onResume, onStop, onDelete, onViewReport }: any) {
    const [open, setOpen] = useState(false);
    const { status } = campaign;

    return (
        <div className="flex items-center gap-1.5 justify-end">
            {/* Primary action */}
            {status === 'created' && (
                <button onClick={() => onStart(campaign.id)} title="Start" className="p-2 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl hover:from-teal-400 hover:to-emerald-500 transition-all active:scale-95 shadow-[0_4px_8px_rgb(52,211,153,0.3)]">
                    <Play className="w-3.5 h-3.5 fill-white" />
                </button>
            )}
            {status === 'running' && (
                <button onClick={() => onPause(campaign.id)} title="Pause" className="p-2 bg-amber-500 text-white rounded-xl hover:bg-amber-400 transition-all active:scale-95">
                    <Pause className="w-3.5 h-3.5 fill-white" />
                </button>
            )}
            {status === 'paused' && (
                <button onClick={() => onResume(campaign.id)} title="Resume" className="p-2 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl hover:from-teal-400 hover:to-emerald-500 transition-all active:scale-95">
                    <Play className="w-3.5 h-3.5 fill-white" />
                </button>
            )}

            {/* View Report */}
            <button onClick={() => onViewReport(campaign.id, campaign.name)} title="View Report" className="p-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                <Eye className="w-3.5 h-3.5" />
            </button>

            {/* More menu */}
            <div className="relative">
                <button onClick={() => setOpen(o => !o)} className="p-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                    <MoreVertical className="w-3.5 h-3.5" />
                </button>
                {open && (
                    <div className="absolute right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-10 min-w-[140px] py-1 overflow-hidden" onClick={e => e.stopPropagation()}>
                        {(status === 'running' || status === 'paused') && (
                            <button onClick={() => { onStop(campaign.id); setOpen(false); }} className="w-full text-left px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 flex items-center gap-2">
                                <Square className="w-3.5 h-3.5 text-slate-400" /> Stop Campaign
                            </button>
                        )}
                        <button onClick={() => { onDelete(campaign.id); setOpen(false); }} className="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                            <Trash2 className="w-3.5 h-3.5" /> Delete
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}

export default function CampaignsListWorkspace({ t, callRPC, onViewReport, onCreateCampaign, activeCampaigns }: any) {
    const [campaigns, setCampaigns]   = useState<any[]>([]);
    const [loading, setLoading]       = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const fetchCampaigns = async () => {
        setLoading(true);
        try {
            const res: any = await callRPC('getCampaigns');
            setCampaigns(res.campaigns || []);
        } catch (err) { console.error('Failed to fetch campaigns', err); }
        setLoading(false);
    };

    useEffect(() => { fetchCampaigns(); }, []);

    // Auto-refresh running campaigns
    useEffect(() => {
        const hasRunning = campaigns.some(c => c.status === 'running');
        if (!hasRunning) return;
        const interval = setInterval(fetchCampaigns, 5000);
        return () => clearInterval(interval);
    }, [campaigns]);

    // Merge live progress from activeCampaigns
    const mergedCampaigns = campaigns.map(c => {
        const live = activeCampaigns?.[c.id];
        if (live) return { ...c, sent_count: live.sent, failed_count: live.failed };
        return c;
    });

    const rpc = async (action: string, campaignId: string) => {
        setActionLoading(campaignId);
        try {
            await callRPC(action, { campaignId });
            await fetchCampaigns();
        } catch (e: any) {
            alert(`Error: ${e.message}`);
        }
        setActionLoading(null);
    };

    return (
        <div className="space-y-6 animate-in fade-in duration-300" onClick={() => {}}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800">Campaigns</h2>
                    <p className="text-xs text-slate-400 mt-1">Manage all your campaigns — start, pause, stop, or review reports.</p>
                </div>
                <div className="flex items-center gap-2">
                    <button onClick={fetchCampaigns} disabled={loading} className="p-2.5 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all">
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Table */}
            <div className="bg-white/70 backdrop-blur-xl border border-white/60 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                            <tr>
                                <th className="px-6 py-4">Campaign</th>
                                <th className="px-6 py-4">Type</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 w-44">Progress</th>
                                <th className="px-6 py-4">Sent</th>
                                <th className="px-6 py-4">Failed</th>
                                <th className="px-6 py-4">Account</th>
                                <th className="px-6 py-4">Created</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {mergedCampaigns.map(c => {
                                const processed = (c.sent_count || 0) + (c.failed_count || 0);
                                const percent = c.total_contacts ? Math.round((processed / c.total_contacts) * 100) : 0;
                                const statusCfg = STATUS_CONFIG[c.status] || STATUS_CONFIG.created;
                                const isProcessing = actionLoading === c.id;

                                return (
                                    <tr key={c.id} className={`hover:bg-slate-50/60 transition-colors ${isProcessing ? 'opacity-60' : ''}`}>
                                        <td className="px-6 py-4">
                                            <div>
                                                <p className="font-bold text-slate-800 text-sm">{c.name}</p>
                                                <p className="text-[10px] font-mono text-slate-400 mt-0.5">{c.id}</p>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 w-fit ${c.type === 'group' ? 'bg-violet-50 text-violet-600' : 'bg-slate-100 text-slate-500'}`}>
                                                {c.type === 'group' ? <Users className="w-3 h-3" /> : <Layers className="w-3 h-3" />}
                                                {c.type || 'bulk'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusCfg.color} ${statusCfg.bg} ${statusCfg.pulse ? 'animate-pulse' : ''}`}>
                                                {statusCfg.label}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <div className="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div className="h-full rounded-full transition-all duration-500" style={{
                                                        width: `${percent}%`,
                                                        background: c.status === 'completed' ? '#10b981' : c.status === 'failed' ? '#ef4444' : '#0ea5e9'
                                                    }} />
                                                </div>
                                                <span className="text-[10px] font-black text-slate-500">{percent}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-emerald-600 font-black flex items-center gap-1.5">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            {c.sent_count || 0}
                                        </td>
                                        <td className="px-6 py-4 text-rose-500 font-black">
                                            <div className="flex items-center gap-1.5">
                                                <XCircle className="w-3.5 h-3.5" />
                                                {c.failed_count || 0}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-slate-400 text-xs font-medium">{c.account_id || '—'}</td>
                                        <td className="px-6 py-4 text-slate-400 text-xs">
                                            <div className="flex items-center gap-1.5">
                                                <Clock className="w-3 h-3" />
                                                {new Date(c.created_at).toLocaleDateString()}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <CampaignActions
                                                campaign={c}
                                                onStart={id   => rpc('startCampaign', id)}
                                                onPause={id   => rpc('pauseCampaign', id)}
                                                onResume={id  => rpc('resumeCampaign', id)}
                                                onStop={id    => rpc('stopCampaign', id)}
                                                onDelete={id  => { if (confirm('Delete this campaign? This cannot be undone.')) rpc('deleteCampaign', id); }}
                                                onViewReport={onViewReport}
                                            />
                                        </td>
                                    </tr>
                                );
                            })}
                            {campaigns.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={9} className="px-6 py-16 text-center">
                                        <div className="flex flex-col items-center gap-3">
                                            <div className="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center">
                                                <LayoutDashboard className="w-6 h-6 text-slate-300" />
                                            </div>
                                            <div>
                                                <p className="font-bold text-slate-500">No campaigns yet</p>
                                                <p className="text-xs text-slate-400 mt-1">Create your first campaign to get started.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
