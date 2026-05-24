import React, { useState, useEffect } from 'react';
import {
    LayoutDashboard, Play, CheckCircle2, XCircle, RefreshCw, Eye,
    Pause, Square, Trash2, MoreVertical, X, Users, Layers, Clock, Timer, RotateCcw,
    LayoutGrid, List, Rocket, TrendingUp, AlertTriangle, Send, ChevronRight,
    CalendarClock, Reply
} from 'lucide-react';

import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; textColor: string; borderColor: string; pulse?: boolean; icon: any }> = {
    created:    { label: 'Ready',     color: 'text-slate-600',    bg: 'bg-slate-100 dark:bg-slate-900/40',   textColor: 'text-slate-700 dark:text-slate-300',   borderColor: 'border-slate-200 dark:border-slate-800',   icon: Rocket },
    running:    { label: 'Running',   color: 'text-blue-600',     bg: 'bg-blue-50 dark:bg-blue-950/40',      textColor: 'text-blue-700 dark:text-blue-300',      borderColor: 'border-blue-200 dark:border-blue-800',     pulse: true, icon: Play },
    processing: { label: 'Running',   color: 'text-blue-600',     bg: 'bg-blue-50 dark:bg-blue-950/40',      textColor: 'text-blue-700 dark:text-blue-300',      borderColor: 'border-blue-200 dark:border-blue-800',     pulse: true, icon: Play },
    paused:     { label: 'Paused',    color: 'text-amber-600',    bg: 'bg-amber-50 dark:bg-amber-950/40',    textColor: 'text-amber-700 dark:text-amber-300',    borderColor: 'border-amber-200 dark:border-amber-800',   icon: Pause },
    stopped:    { label: 'Stopped',   color: 'text-slate-500',    bg: 'bg-slate-100 dark:bg-slate-900/40',   textColor: 'text-slate-600 dark:text-slate-400',    borderColor: 'border-slate-200 dark:border-slate-800',   icon: Square },
    completed:  { label: 'Completed', color: 'text-emerald-600',  bg: 'bg-emerald-50 dark:bg-emerald-950/40', textColor: 'text-emerald-700 dark:text-emerald-300', borderColor: 'border-emerald-200 dark:border-emerald-800', icon: CheckCircle2 },
    failed:     { label: 'Failed',    color: 'text-red-600',      bg: 'bg-red-50 dark:bg-red-950/40',        textColor: 'text-red-700 dark:text-red-300',        borderColor: 'border-red-200 dark:border-red-800',       icon: XCircle },
    scheduled:  { label: 'Scheduled', color: 'text-orange-600',   bg: 'bg-orange-50 dark:bg-orange-950/40',  textColor: 'text-orange-700 dark:text-orange-300',  borderColor: 'border-orange-200 dark:border-orange-800', icon: CalendarClock },
};

function CampaignCardActions({ campaign, onStart, onRetry, onPause, onResume, onStop, onDelete, onViewReport, onFollowUp, t, locale }: any) {
    const { status } = campaign;

    return (
        <div className="flex items-center gap-1.5">
            {/* Start — for fresh / stopped campaigns */}
            {(status === 'created' || status === 'stopped') && (
                <Button size="sm" onClick={() => onStart(campaign.id)} title={locale === 'ar' ? "بدء" : "Start"} className="h-8 px-3 gap-1.5 bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-bold rounded-xl">
                    <Play className="w-3 h-3 fill-white rtl:rotate-180" />
                    {locale === 'ar' ? 'بدء' : 'Start'}
                </Button>
            )}

            {/* Retry — for stuck / failed / stopped campaigns */}
            {(status === 'failed' || status === 'stopped' || status === 'paused') && (
                <Button
                    onClick={() => onRetry(campaign.id)}
                    title={t.history.retryBtn}
                    className="h-8 px-3 gap-1.5 bg-orange-500 hover:bg-orange-600 text-white text-[11px] font-bold rounded-xl"
                >
                    <RotateCcw className="w-3 h-3" />
                    {t.history.retryBtn}
                </Button>
            )}
            {(status === 'running' || status === 'processing') && (
                <Button size="sm" onClick={() => onPause(campaign.id)} title={locale === 'ar' ? "إيقاف مؤقت" : "Pause"} className="h-8 px-3 gap-1.5 bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold rounded-xl">
                    <Pause className="w-3 h-3 fill-white" />
                    {locale === 'ar' ? 'إيقاف' : 'Pause'}
                </Button>
            )}
            {status === 'paused' && (
                <Button size="sm" onClick={() => onResume(campaign.id)} title={locale === 'ar' ? "استئناف" : "Resume"} className="h-8 px-3 gap-1.5 bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-bold rounded-xl">
                    <Play className="w-3 h-3 fill-white rtl:rotate-180" />
                    {locale === 'ar' ? 'استئناف' : 'Resume'}
                </Button>
            )}

            {/* View Report */}
            <Button size="sm" variant="outline" onClick={() => onViewReport(campaign.id, campaign.name)} title={locale === 'ar' ? "عرض التقرير" : "View Report"} className="h-8 px-3 gap-1.5 text-[11px] font-bold rounded-xl border-muted">
                <Eye className="w-3 h-3" />
                {locale === 'ar' ? 'التقرير' : 'Report'}
            </Button>

            {/* More menu */}
            <DropdownMenu>
                <DropdownMenuTrigger className="inline-flex items-center justify-center rounded-xl h-8 w-8 bg-muted/50 hover:bg-muted text-muted-foreground transition-colors duration-150 outline-none select-none cursor-pointer focus:ring-0">
                    <MoreVertical className="w-3.5 h-3.5" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align={locale === 'ar' ? 'start' : 'end'} className="min-w-[140px]">
                    {(status === 'running' || status === 'processing' || status === 'paused') && (
                        <DropdownMenuItem onClick={() => onStop(campaign.id)} className="font-bold flex items-center gap-2 cursor-pointer text-start justify-start w-full">
                            <Square className="w-3.5 h-3.5 text-muted-foreground" />
                            <span>{t.history.stopCampaign}</span>
                        </DropdownMenuItem>
                    )}
                    {(status === 'stopped' || status === 'failed') && (
                        <DropdownMenuItem onClick={() => onRetry(campaign.id)} className="font-bold flex items-center gap-2 cursor-pointer text-orange-600 text-start justify-start w-full">
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>{t.history.retryBtn}</span>
                        </DropdownMenuItem>
                    )}
                    {(status === 'completed' || status === 'stopped' || status === 'failed') && campaign.sent_count > 0 && (
                        <DropdownMenuItem onClick={() => onFollowUp?.(campaign)} className="font-bold flex items-center gap-2 cursor-pointer text-blue-600 text-start justify-start w-full">
                            <Reply className="w-3.5 h-3.5" />
                            <span>{locale === 'ar' ? 'متابعة غير الرادّين' : 'Follow-up Non-Replied'}</span>
                        </DropdownMenuItem>
                    )}
                    <DropdownMenuItem onClick={() => onDelete(campaign.id)} variant="destructive" className="font-bold flex items-center gap-2 cursor-pointer text-start justify-start w-full">
                        <Trash2 className="w-3.5 h-3.5" />
                        <span>{locale === 'ar' ? "حذف" : "Delete"}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}

// ── Circular progress ring ───────────────────────────────────────────
function ProgressRing({ percent, size = 52, strokeWidth = 4, color = '#0d9488' }: { percent: number; size?: number; strokeWidth?: number; color?: string }) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;
    return (
        <svg width={size} height={size} className="shrink-0 -rotate-90">
            <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="hsl(var(--muted))" strokeWidth={strokeWidth} />
            <circle
                cx={size / 2} cy={size / 2} r={radius} fill="none"
                stroke={color}
                strokeWidth={strokeWidth}
                strokeDasharray={circumference}
                strokeDashoffset={offset}
                strokeLinecap="round"
                className="transition-all duration-700"
            />
            <text
                x={size / 2} y={size / 2}
                textAnchor="middle"
                dominantBaseline="central"
                className="fill-foreground rotate-90"
                style={{ fontSize: '11px', fontWeight: 900, transformOrigin: 'center' }}
            >
                {percent}%
            </text>
        </svg>
    );
}

export default function CampaignsListWorkspace({ t, locale, callRPC, onViewReport, onCreateCampaign, activeCampaigns, campaignDelays, daemonConnected, selectedAccount }: any) {
    const isRtl = locale === 'ar';

    const getStatusLabel = (status: string) => {
        const mapping: Record<string, string> = {
            created: t.history.ready,
            running: t.history.running,
            processing: t.history.running,
            paused: t.history.paused,
            stopped: t.history.stopped,
            completed: t.history.completed,
            failed: t.history.failedStatus,
            scheduled: isRtl ? 'مجدولة' : 'Scheduled',
        };
        return mapping[status] || status;
    };

    const [campaigns, setCampaigns]   = useState<any[]>([]);
    const [loading, setLoading]       = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [viewMode, setViewMode] = useState<'grid' | 'table'>('grid');

    const fetchCampaigns = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getCampaigns');
            setCampaigns(res.campaigns || []);
        } catch (err) { console.error('Failed to fetch campaigns', err); }
        setLoading(false);
    };

    useEffect(() => {
        if (daemonConnected) {
            fetchCampaigns();
        }
    }, [daemonConnected]);

    // Auto-refresh running campaigns
    useEffect(() => {
        if (!daemonConnected) return;
        const hasRunning = campaigns.some(c => c.status === 'running' || c.status === 'processing');
        if (!hasRunning) return;
        const interval = setInterval(fetchCampaigns, 5000);
        return () => clearInterval(interval);
    }, [campaigns, daemonConnected]);

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

    const handleRetry = async (campaignId: string) => {
        setActionLoading(campaignId);
        try {
            // Try retrying failed messages first
            const res: any = await callRPC('retryFailedMessages', { campaignId });
            console.log(`Retrying ${res?.retriedCount || 0} failed messages`);
            await fetchCampaigns();
        } catch (e: any) {
            // If no failed messages, just resume the campaign
            if (e.message?.includes('No failed messages')) {
                try {
                    await callRPC('resumeCampaign', { campaignId });
                    await fetchCampaigns();
                } catch (e2: any) {
                    alert(`Resume failed: ${e2.message}`);
                }
            } else {
                alert(`Retry failed: ${e.message}`);
            }
        }
        setActionLoading(null);
    };

    // ── Follow-up Campaign ──────────────────────────────────────────────
    const [followUpModal, setFollowUpModal] = useState<{ campaign: any; contacts: any[]; loading: boolean } | null>(null);

    const handleFollowUp = async (campaign: any) => {
        setFollowUpModal({ campaign, contacts: [], loading: true });
        try {
            const res: any = await callRPC('getNonRepliedContacts', { campaignId: campaign.id });
            setFollowUpModal({ campaign, contacts: res.contacts || [], loading: false });
        } catch (err: any) {
            alert(`Failed: ${err.message}`);
            setFollowUpModal(null);
        }
    };

    const handleCreateFollowUp = () => {
        if (!followUpModal) return;
        const { campaign, contacts } = followUpModal;
        // Navigate to campaign workspace with pre-filled contacts
        onCreateCampaign?.({
            followUp: true,
            originalCampaignId: campaign.id,
            originalCampaignName: campaign.name,
            contacts: contacts.map((c: any) => ({ phone: c.phone, name: c.name || '', company: c.company || '' })),
        });
        setFollowUpModal(null);
    };

    // ── KPI calculations ───────────────────────────────────────────────
    const totalCampaigns = mergedCampaigns.length;
    const activeCampaignCount = mergedCampaigns.filter(c => c.status === 'running' || c.status === 'processing').length;
    const completedCount = mergedCampaigns.filter(c => c.status === 'completed').length;
    const failedCount = mergedCampaigns.filter(c => c.status === 'failed').length;
    const pausedCount = mergedCampaigns.filter(c => c.status === 'paused').length;

    // ── Filtered campaigns ─────────────────────────────────────────────
    let filteredCampaigns = statusFilter === 'all'
        ? mergedCampaigns
        : mergedCampaigns.filter(c => {
            if (statusFilter === 'active') return c.status === 'running' || c.status === 'processing';
            return c.status === statusFilter;
        });

    if (selectedAccount) {
        filteredCampaigns = filteredCampaigns.filter(c => c.account_id === selectedAccount);
    }

    // ── KPI config ─────────────────────────────────────────────────────
    const kpiCards = [
        {
            label: isRtl ? 'إجمالي الحملات' : 'Total Campaigns',
            value: totalCampaigns,
            color: 'text-violet-600',
        },
        {
            label: isRtl ? 'حملات نشطة' : 'Active Now',
            value: activeCampaignCount,
            color: 'text-blue-600',
        },
        {
            label: isRtl ? 'مكتملة' : 'Completed',
            value: completedCount,
            color: 'text-emerald-600',
        },
        {
            label: isRtl ? 'فاشلة' : 'Failed',
            value: failedCount,
            color: 'text-red-600',
        },
    ];

    // ── Filter tabs ────────────────────────────────────────────────────
    const filterTabs = [
        { id: 'all',       label: isRtl ? 'الكل' : 'All',        count: totalCampaigns },
        { id: 'active',    label: isRtl ? 'نشطة' : 'Active',     count: activeCampaignCount },
        { id: 'completed', label: isRtl ? 'مكتملة' : 'Completed', count: completedCount },
        { id: 'paused',    label: isRtl ? 'موقوفة' : 'Paused',   count: pausedCount },
        { id: 'failed',    label: isRtl ? 'فاشلة' : 'Failed',    count: failedCount },
    ];

    return (
        <div className="space-y-6 animate-in fade-in duration-300" onClick={() => {}}>
            {/* ── Header ───────────────────────────────────────────────── */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight">{t.history.title}</h2>
                    <p className="text-sm text-muted-foreground mt-1">{t.history.subtitle}</p>
                </div>
                <div className="flex items-center gap-2">
                    {/* View mode toggle */}
                    <div className="hidden sm:flex items-center gap-0.5 p-0.5 bg-muted rounded-xl">
                        <button
                            onClick={() => setViewMode('grid')}
                            className={`p-1.5 rounded-lg transition-all ${viewMode === 'grid' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            <LayoutGrid className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('table')}
                            className={`p-1.5 rounded-lg transition-all ${viewMode === 'table' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            <List className="w-4 h-4" />
                        </button>
                    </div>
                    <Button variant="outline" size="icon" onClick={fetchCampaigns} disabled={loading} className="rounded-xl">
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </Button>
                </div>
            </div>

            {/* ── KPI Cards ────────────────────────────────────────────── */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {kpiCards.map((kpi) => {
                    return (
                        <Card key={kpi.label} className="rounded-2xl">
                            <CardContent className="p-4 text-center">
                                <div className={`text-2xl font-black ${kpi.color}`}>{kpi.value}</div>
                                <p className="text-[11px] text-muted-foreground font-medium mt-1">
                                    {kpi.label}
                                </p>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {/* ── Filter Tabs ──────────────────────────────────────────── */}
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-hide">
                {filterTabs.map((tab) => (
                    <button
                        key={tab.id}
                        onClick={() => setStatusFilter(tab.id)}
                        className={`shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-1.5 select-none ${
                            statusFilter === tab.id
                                ? 'bg-teal-600 text-white shadow-md shadow-teal-500/15'
                                : 'bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                        }`}
                    >
                        {tab.label}
                        {tab.count > 0 && (
                            <span className={`text-[10px] font-black px-1.5 py-0.5 rounded-md leading-none ${
                                statusFilter === tab.id ? 'bg-white/20 text-white' : 'bg-muted-foreground/10 text-muted-foreground'
                            }`}>
                                {tab.count}
                            </span>
                        )}
                    </button>
                ))}
            </div>

            {/* ── Campaign Grid View ───────────────────────────────────── */}
            {viewMode === 'grid' ? (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    {filteredCampaigns.map(c => {
                        const processed = (c.sent_count || 0) + (c.failed_count || 0);
                        const percent = c.total_contacts ? Math.round((processed / c.total_contacts) * 100) : 0;
                        const statusCfg = STATUS_CONFIG[c.status] || STATUS_CONFIG.created;
                        const StatusIcon = statusCfg.icon;
                        const isProcessing = actionLoading === c.id;
                        const isLive = c.status === 'running' || c.status === 'processing';
                        const progressColor = c.status === 'completed' ? '#10b981' : c.status === 'failed' ? '#ef4444' : '#0d9488';

                        return (
                            <Card
                                key={c.id}
                                className={`rounded-2xl border overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01] group relative ${
                                    isProcessing ? 'opacity-60 pointer-events-none' : ''
                                } ${isLive ? `${statusCfg.borderColor} shadow-md` : 'border-muted'}`}
                            >
                                {/* Live pulse indicator */}
                                {isLive && (
                                    <div className="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-blue-500 via-teal-500 to-blue-500 animate-pulse" />
                                )}

                                <CardContent className="p-5 space-y-4">
                                    {/* Top row: Name + Status */}
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1 text-start">
                                            <h3 className="font-bold text-sm truncate">{c.name}</h3>
                                            <p className="text-[10px] font-mono text-muted-foreground mt-0.5 truncate">{c.id}</p>
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            className={`shrink-0 text-[10px] font-bold gap-1 px-2.5 py-1 rounded-lg ${statusCfg.bg} ${statusCfg.textColor} ${statusCfg.pulse ? 'animate-pulse' : ''}`}
                                        >
                                            <StatusIcon className="w-3 h-3" />
                                            {getStatusLabel(c.status)}
                                        </Badge>
                                    </div>

                                    {/* Progress Ring + Stats */}
                                    <div className="flex items-center gap-4">
                                        <ProgressRing percent={percent} color={progressColor} />
                                        <div className="flex-1 space-y-2.5">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="flex items-center gap-1.5 text-emerald-600 font-bold">
                                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                                    {isRtl ? 'ناجح' : 'Sent'}
                                                </span>
                                                <span className="font-black tabular-nums">{c.sent_count || 0}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="flex items-center gap-1.5 text-red-500 font-bold">
                                                    <XCircle className="w-3.5 h-3.5" />
                                                    {isRtl ? 'فاشل' : 'Failed'}
                                                </span>
                                                <span className="font-black tabular-nums">{c.failed_count || 0}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                <span className="flex items-center gap-1.5 font-medium">
                                                    <Users className="w-3.5 h-3.5" />
                                                    {isRtl ? 'الإجمالي' : 'Total'}
                                                </span>
                                                <span className="font-black tabular-nums">{c.total_contacts || 0}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Live delay countdown */}
                                    {campaignDelays?.[c.id] && isLive && (
                                        <div className="flex items-center gap-2 p-2.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30">
                                            <Timer className="w-3.5 h-3.5 text-amber-500 animate-pulse shrink-0" />
                                            <span className="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                                {t.history.nextIn} {campaignDelays[c.id]}{isRtl ? ' ثانية' : 's'}
                                            </span>
                                        </div>
                                    )}

                                    {/* Meta info row */}
                                    <div className="flex items-center justify-between text-[10px] text-muted-foreground pt-2 border-t border-dashed border-muted/60">
                                        <div className="flex items-center gap-1.5">
                                            <Badge variant="outline" className={`text-[9px] gap-0.5 px-1.5 py-0.5 rounded-md ${c.type === 'group' ? 'bg-violet-50 text-violet-600 dark:bg-violet-950/30 dark:text-violet-400' : 'bg-muted/50 text-muted-foreground'}`}>
                                                {c.type === 'group' ? <Users className="w-2.5 h-2.5" /> : <Layers className="w-2.5 h-2.5" />}
                                                {c.type === 'group' ? (isRtl ? 'مجموعات' : 'Group') : (isRtl ? 'أرقام' : 'Bulk')}
                                            </Badge>
                                            {c.account_id && (
                                                <span className="font-medium truncate max-w-[80px]">{c.account_id}</span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Clock className="w-3 h-3" />
                                            <span className="tabular-nums">
                                                {c.created_at ? new Date(c.created_at.replace(' ', 'T') + (c.created_at.includes('Z') ? '' : 'Z')).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : '—'}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex items-center justify-end pt-1">
                                        <CampaignCardActions
                                            campaign={c}
                                            onStart={(id: string)   => rpc('startCampaign', id)}
                                            onRetry={(id: string)   => handleRetry(id)}
                                            onPause={(id: string)   => rpc('pauseCampaign', id)}
                                            onResume={(id: string)  => rpc('resumeCampaign', id)}
                                            onStop={(id: string)    => rpc('stopCampaign', id)}
                                            onDelete={(id: string)  => { if (confirm(t.history.deleteConfirm)) rpc('deleteCampaign', id); }}
                                            onViewReport={onViewReport}
                                            onFollowUp={handleFollowUp}
                                            t={t}
                                            locale={locale}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            ) : (
                /* ── Table View (preserved) ──────────────────────────────── */
                <Card className="overflow-hidden rounded-2xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-start text-sm whitespace-nowrap">
                            <thead className="bg-muted/50 border-b text-muted-foreground font-semibold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th className="px-6 py-4 text-start">{t.history.colCampaign}</th>
                                    <th className="px-6 py-4 text-start">{t.history.colType}</th>
                                    <th className="px-6 py-4 text-start">{t.history.colStatus}</th>
                                    <th className="px-6 py-4 w-44 text-center">{t.history.colProgress}</th>
                                    <th className="px-6 py-4 text-center">{t.history.colSent}</th>
                                    <th className="px-6 py-4 text-center">{t.history.colFailed}</th>
                                    <th className="px-6 py-4 text-center">{t.history.colAccount}</th>
                                    <th className="px-6 py-4 text-center">{t.history.colCreated}</th>
                                    <th className="px-6 py-4 text-end">{t.history.colActions}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y text-start">
                                {filteredCampaigns.map(c => {
                                    const processed = (c.sent_count || 0) + (c.failed_count || 0);
                                    const percent = c.total_contacts ? Math.round((processed / c.total_contacts) * 100) : 0;
                                    const statusCfg = STATUS_CONFIG[c.status] || STATUS_CONFIG.created;
                                    const isProcessing = actionLoading === c.id;

                                    return (
                                        <tr key={c.id} className={`hover:bg-muted/50 transition-colors ${isProcessing ? 'opacity-60' : ''}`}>
                                            <td className="px-6 py-4 text-start">
                                                <div>
                                                    <p className="font-bold text-sm text-start">{c.name}</p>
                                                    <p className="text-[10px] font-mono text-muted-foreground mt-0.5 text-start">{c.id}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-start">
                                                <Badge variant="outline" className={`text-[10px] gap-1 ${c.type === 'group' ? 'bg-violet-50 text-violet-600' : 'bg-muted text-muted-foreground'}`}>
                                                    {c.type === 'group' ? <Users className="w-3 h-3" /> : <Layers className="w-3 h-3" />}
                                                    {c.type === 'group' ? (locale === 'ar' ? 'مجموعات' : 'Group') : (locale === 'ar' ? 'أرقام' : 'Bulk')}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 text-start">
                                                <div className="flex flex-col items-start gap-1.5">
                                                    <Badge variant="secondary" className={`text-[10px] ${statusCfg.color} ${statusCfg.bg} ${statusCfg.pulse ? 'animate-pulse' : ''}`}>
                                                        {getStatusLabel(c.status)}
                                                    </Badge>
                                                    {campaignDelays?.[c.id] && (c.status === 'running' || c.status === 'processing') && (
                                                        <div className="flex items-center gap-1 text-[10px] font-mono text-amber-600 dark:text-amber-400 animate-pulse">
                                                            <Timer className="w-3 h-3" />
                                                            <span>{t.history.nextIn} {campaignDelays[c.id]}{locale === 'ar' ? ' ثانية' : 's'}</span>
                                                        </div>
                                                    )}
                                                </div>
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
                                            <td className="px-6 py-4 text-end">
                                                <CampaignCardActions
                                                    campaign={c}
                                                    onStart={(id: string)   => rpc('startCampaign', id)}
                                                    onRetry={(id: string)   => handleRetry(id)}
                                                    onPause={(id: string)   => rpc('pauseCampaign', id)}
                                                    onResume={(id: string)  => rpc('resumeCampaign', id)}
                                                    onStop={(id: string)    => rpc('stopCampaign', id)}
                                                    onDelete={(id: string)  => { if (confirm(t.history.deleteConfirm)) rpc('deleteCampaign', id); }}
                                                    onViewReport={onViewReport}
                                            onFollowUp={handleFollowUp}
                                                    t={t}
                                                    locale={locale}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* ── Empty State ──────────────────────────────────────────── */}
            {filteredCampaigns.length === 0 && !loading && (
                <div className="flex flex-col items-center justify-center py-20 px-6">
                    <div className="size-20 rounded-3xl bg-muted/50 flex items-center justify-center mb-5">
                        <Send className="w-8 h-8 text-muted-foreground/50" />
                    </div>
                    <h3 className="font-bold text-lg text-foreground">
                        {statusFilter !== 'all'
                            ? (isRtl ? 'لا توجد حملات بهذه الحالة' : 'No campaigns with this status')
                            : t.history.emptyTitle
                        }
                    </h3>
                    <p className="text-sm text-muted-foreground mt-2 max-w-sm text-center leading-relaxed">
                        {statusFilter !== 'all'
                            ? (isRtl ? 'جرب تغيير الفلتر لعرض حملات أخرى.' : 'Try changing the filter to see other campaigns.')
                            : t.history.emptySub
                        }
                    </p>
                    {statusFilter !== 'all' && (
                        <Button
                            variant="outline"
                            onClick={() => setStatusFilter('all')}
                            className="mt-5 rounded-xl gap-2 font-bold"
                        >
                            {isRtl ? 'عرض جميع الحملات' : 'Show All Campaigns'}
                        </Button>
                    )}
                </div>
            )}
            {/* ── Follow-up Modal ──────────────────────────────────────── */}
            {followUpModal && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={() => setFollowUpModal(null)}>
                    <div className="bg-background rounded-2xl max-w-md w-full shadow-2xl animate-in zoom-in-95 duration-300" onClick={e => e.stopPropagation()}>
                        <div className="p-6 border-b flex items-center gap-3">
                            <div className="size-10 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center">
                                <Reply className="w-5 h-5 text-blue-600" />
                            </div>
                            <div className="text-start">
                                <h3 className="font-bold text-sm">{isRtl ? 'حملة متابعة' : 'Follow-up Campaign'}</h3>
                                <p className="text-xs text-muted-foreground">{followUpModal.campaign.name}</p>
                            </div>
                            <Button variant="ghost" size="icon" className="h-8 w-8 rounded-lg ml-auto" onClick={() => setFollowUpModal(null)}>
                                <X className="w-4 h-4" />
                            </Button>
                        </div>
                        <div className="p-6 text-center space-y-4">
                            {followUpModal.loading ? (
                                <div className="py-8 flex flex-col items-center gap-3">
                                    <span className="size-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin" />
                                    <p className="text-xs text-muted-foreground">{isRtl ? 'جارٍ تحليل الردود...' : 'Analyzing responses...'}</p>
                                </div>
                            ) : (
                                <>
                                    <div className="flex justify-center gap-6">
                                        <div>
                                            <div className="text-3xl font-black text-blue-600">{followUpModal.contacts.length}</div>
                                            <p className="text-[10px] text-muted-foreground font-bold mt-0.5">{isRtl ? 'لم يردّوا' : 'Non-Replied'}</p>
                                        </div>
                                        <div className="w-px bg-border" />
                                        <div>
                                            <div className="text-3xl font-black text-muted-foreground">{(followUpModal.campaign.total_contacts || 0) - followUpModal.contacts.length}</div>
                                            <p className="text-[10px] text-muted-foreground font-bold mt-0.5">{isRtl ? 'ردّوا' : 'Replied'}</p>
                                        </div>
                                    </div>
                                    {followUpModal.contacts.length > 0 ? (
                                        <div className="space-y-3">
                                            <p className="text-xs text-muted-foreground">
                                                {isRtl
                                                    ? `سيتم إنشاء حملة متابعة لـ ${followUpModal.contacts.length} جهة اتصال لم تردّ`
                                                    : `A follow-up campaign will target ${followUpModal.contacts.length} non-replied contacts`
                                                }
                                            </p>
                                            <Button onClick={handleCreateFollowUp} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl gap-2 h-11">
                                                <Reply className="w-4 h-4" />
                                                {isRtl ? 'إنشاء حملة متابعة' : 'Create Follow-up Campaign'}
                                            </Button>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-emerald-600 font-bold py-4">
                                            {isRtl ? '🎉 جميع جهات الاتصال ردّوا!' : '🎉 All contacts have replied!'}
                                        </p>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
