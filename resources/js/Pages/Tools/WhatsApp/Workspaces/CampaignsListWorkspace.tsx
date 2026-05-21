import React, { useState, useEffect } from 'react';
import {
    LayoutDashboard, Play, CheckCircle2, XCircle, RefreshCw, Eye,
    Pause, Square, Trash2, MoreVertical, X, Users, Layers, Clock
} from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; pulse?: boolean }> = {
    created:   { label: 'Ready',     color: 'text-slate-500',  bg: 'bg-slate-100'  },
    running:   { label: 'Running',   color: 'text-blue-600',   bg: 'bg-blue-50',  pulse: true },
    paused:    { label: 'Paused',    color: 'text-amber-600',  bg: 'bg-amber-50'  },
    stopped:   { label: 'Stopped',   color: 'text-slate-500',  bg: 'bg-slate-100' },
    completed: { label: 'Completed', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    failed:    { label: 'Failed',    color: 'text-destructive',   bg: 'bg-destructive/10'   },
};

function CampaignActions({ campaign, onStart, onPause, onResume, onStop, onDelete, onViewReport }: any) {
    const [open, setOpen] = useState(false);
    const { status } = campaign;

    return (
        <div className="flex items-center gap-1.5 justify-end">
            {/* Primary action */}
            {status === 'created' && (
                <Button size="icon" onClick={() => onStart(campaign.id)} title="Start" className="h-8 w-8 bg-teal-600 hover:bg-teal-700 text-white">
                    <Play className="w-3.5 h-3.5 fill-white" />
                </Button>
            )}
            {status === 'running' && (
                <Button size="icon" onClick={() => onPause(campaign.id)} title="Pause" className="h-8 w-8 bg-amber-500 hover:bg-amber-600 text-white">
                    <Pause className="w-3.5 h-3.5 fill-white" />
                </Button>
            )}
            {status === 'paused' && (
                <Button size="icon" onClick={() => onResume(campaign.id)} title="Resume" className="h-8 w-8 bg-teal-600 hover:bg-teal-700 text-white">
                    <Play className="w-3.5 h-3.5 fill-white" />
                </Button>
            )}

            {/* View Report */}
            <Button size="icon" variant="ghost" onClick={() => onViewReport(campaign.id, campaign.name)} title="View Report" className="h-8 w-8 bg-muted hover:bg-muted/80 text-muted-foreground">
                <Eye className="w-3.5 h-3.5" />
            </Button>

            {/* More menu */}
            <div className="relative">
                <Button size="icon" variant="ghost" onClick={() => setOpen(o => !o)} className="h-8 w-8 bg-muted hover:bg-muted/80 text-muted-foreground">
                    <MoreVertical className="w-3.5 h-3.5" />
                </Button>
                {open && (
                    <div className="absolute right-0 top-full mt-1 bg-popover border text-popover-foreground rounded-xl shadow-xl z-10 min-w-[140px] py-1 overflow-hidden" onClick={e => e.stopPropagation()}>
                        {(status === 'running' || status === 'paused') && (
                            <Button variant="ghost" onClick={() => { onStop(campaign.id); setOpen(false); }} className="w-full justify-start rounded-none h-auto px-4 py-2 text-xs font-bold hover:bg-accent hover:text-accent-foreground flex items-center gap-2">
                                <Square className="w-3.5 h-3.5 text-muted-foreground" /> Stop Campaign
                            </Button>
                        )}
                        <Button variant="ghost" onClick={() => { onDelete(campaign.id); setOpen(false); }} className="w-full justify-start rounded-none h-auto px-4 py-2 text-xs font-bold text-destructive hover:bg-destructive/10 hover:text-destructive flex items-center gap-2">
                            <Trash2 className="w-3.5 h-3.5" /> Delete
                        </Button>
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
                    <h2 className="text-xl font-bold tracking-tight">Campaigns</h2>
                    <p className="text-sm text-muted-foreground mt-1">Manage all your campaigns — start, pause, stop, or review reports.</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="icon" onClick={fetchCampaigns} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </Button>
                </div>
            </div>

            {/* Table */}
            <Card className="overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-muted/50 border-b text-muted-foreground font-semibold uppercase text-[10px] tracking-wider">
                            <tr>
                                <th className="px-6 py-4">Campaign</th>
                                <th className="px-6 py-4">Type</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 w-44 text-center">Progress</th>
                                <th className="px-6 py-4 text-center">Sent</th>
                                <th className="px-6 py-4 text-center">Failed</th>
                                <th className="px-6 py-4 text-center">Account</th>
                                <th className="px-6 py-4 text-center">Created</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {mergedCampaigns.map(c => {
                                const processed = (c.sent_count || 0) + (c.failed_count || 0);
                                const percent = c.total_contacts ? Math.round((processed / c.total_contacts) * 100) : 0;
                                const statusCfg = STATUS_CONFIG[c.status] || STATUS_CONFIG.created;
                                const isProcessing = actionLoading === c.id;

                                return (
                                    <tr key={c.id} className={`hover:bg-muted/50 transition-colors ${isProcessing ? 'opacity-60' : ''}`}>
                                        <td className="px-6 py-4">
                                            <div>
                                                <p className="font-bold text-sm">{c.name}</p>
                                                <p className="text-[10px] font-mono text-muted-foreground mt-0.5">{c.id}</p>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={`text-[10px] gap-1 ${c.type === 'group' ? 'bg-violet-50 text-violet-600' : 'bg-muted text-muted-foreground'}`}>
                                                {c.type === 'group' ? <Users className="w-3 h-3" /> : <Layers className="w-3 h-3" />}
                                                {c.type || 'bulk'}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="secondary" className={`text-[10px] ${statusCfg.color} ${statusCfg.bg} ${statusCfg.pulse ? 'animate-pulse' : ''}`}>
                                                {statusCfg.label}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-center gap-2">
                                                <div className="w-24 h-1.5 bg-secondary rounded-full overflow-hidden">
                                                    <div className="h-full rounded-full transition-all duration-500" style={{
                                                        width: `${percent}%`,
                                                        background: c.status === 'completed' ? '#10b981' : c.status === 'failed' ? '#ef4444' : '#0ea5e9'
                                                    }} />
                                                </div>
                                                <span className="text-[10px] font-black text-muted-foreground">{percent}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-teal-600 font-black text-center tabular-nums">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                                {c.sent_count || 0}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-destructive font-black text-center tabular-nums">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <XCircle className="w-3.5 h-3.5" />
                                                {c.failed_count || 0}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-muted-foreground text-xs font-medium text-center">{c.account_id || '—'}</td>
                                        <td className="px-6 py-4 text-muted-foreground text-xs text-center tabular-nums">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <Clock className="w-3 h-3" />
                                                {c.created_at ? new Date(c.created_at.replace(' ', 'T') + (c.created_at.includes('Z') ? '' : 'Z')).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'}
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
                                            <div className="w-12 h-12 rounded-2xl bg-muted flex items-center justify-center">
                                                <LayoutDashboard className="w-6 h-6 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <p className="font-bold text-muted-foreground">No campaigns yet</p>
                                                <p className="text-xs text-muted-foreground mt-1">Create your first campaign to get started.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    );
}
