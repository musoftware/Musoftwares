import React, { useState, useEffect } from 'react';
import {
    ArrowLeft, CheckCircle2, XCircle, FileText, Download,
    RefreshCw, Clock, MessageCircle, Eye, Mail, Filter, ChevronDown, RotateCcw
} from 'lucide-react';

import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

// ── Inline SVG Donut Chart ──────────────────────────────────────────────────
interface DonutSegment {
    value: number;
    color: string;
    label: string;
}

function DonutChart({ segments, t }: { segments: DonutSegment[]; t: any }) {
    const total = segments.reduce((s, seg) => s + seg.value, 0);
    if (total === 0) return (
        <div className="w-40 h-40 rounded-full border-8 border-muted flex items-center justify-center">
            <span className="text-xs text-muted-foreground">{t.report.noContactsRecorded || 'No data'}</span>
        </div>
    );

    const radius = 60;
    const cx = 80, cy = 80;
    const circumference = 2 * Math.PI * radius;

    let cumPercent = 0;
    const slices = segments.map(seg => {
        const pct = seg.value / total;
        const dashArray = `${pct * circumference} ${circumference}`;
        const dashOffset = -cumPercent * circumference;
        cumPercent += pct;
        return { ...seg, dashArray, dashOffset, pct };
    });

    return (
        <div className="flex flex-col items-center gap-4">
            <div className="relative">
                <svg width="160" height="160" viewBox="0 0 160 160">
                    <circle cx={cx} cy={cy} r={radius} fill="none" stroke="hsl(var(--muted))" strokeWidth="20" />
                    {slices.map((s, i) => (
                        <circle
                            key={i}
                            cx={cx} cy={cy} r={radius}
                            fill="none"
                            stroke={s.color}
                            strokeWidth="20"
                            strokeDasharray={s.dashArray}
                            strokeDashoffset={s.dashOffset}
                            transform={`rotate(-90 ${cx} ${cy})`}
                            className="transition-all duration-500"
                        />
                    ))}
                    <text x={cx} y={cy - 6} textAnchor="middle" className="text-lg font-black fill-foreground" style={{ fontSize: '22px', fontWeight: 900 }}>{total}</text>
                    <text x={cx} y={cy + 12} textAnchor="middle" className="fill-muted-foreground" style={{ fontSize: '10px', fontWeight: 600 }}>{t.report.total}</text>
                </svg>
            </div>
            <div className="flex flex-wrap justify-center gap-x-4 gap-y-1.5">
                {slices.map((s, i) => (
                    <div key={i} className="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                        <div className="w-2.5 h-2.5 rounded-full" style={{ background: s.color }} />
                        {s.label} <span className="text-muted-foreground/70">({s.value})</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ── Inline Horizontal Bar Chart ─────────────────────────────────────────────
function StatBar({ label, value, total, color, icon: Icon }: any) {
    const pct = total > 0 ? Math.round((value / total) * 100) : 0;
    return (
        <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style={{ background: `${color}20` }}>
                <Icon className="w-4 h-4" style={{ color }} />
            </div>
            <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between mb-1">
                    <span className="text-xs font-bold text-start">{label}</span>
                    <span className="text-xs font-black" style={{ color }}>{value} <span className="text-muted-foreground font-normal">({pct}%)</span></span>
                </div>
                <div className="h-2 bg-secondary rounded-full overflow-hidden">
                    <div className="h-full rounded-full transition-all duration-700" style={{ width: `${pct}%`, background: color }} />
                </div>
            </div>
        </div>
    );
}

const STATUS_COLORS: Record<string, string> = {
    sent:      '#10b981',
    delivered: '#3b82f6',
    read:      '#8b5cf6',
    replied:   '#f59e0b',
    failed:    '#ef4444',
    pending:   '#94a3b8',
};

const STATUS_ICONS: Record<string, any> = {
    sent:      Mail,
    delivered: CheckCircle2,
    read:      Eye,
    replied:   MessageCircle,
    failed:    XCircle,
    pending:   Clock,
};

function formatDateForCSV(dateStr: string | null | undefined): string {
    if (!dateStr) return '';
    try {
        const isoStr = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T') + (dateStr.includes('Z') ? '' : 'Z');
        const d = new Date(isoStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleString();
    } catch { return dateStr || ''; }
}

function exportCSV(contacts: any[], campaignName: string, t: any) {
    const isAr = t.language === 'English'; // wait, in translations dictionary, ar t.language = 'English' and en t.language = 'العربية' to switch languages. So if t.language === 'English', the current language is Arabic!
    const companyHeader = isAr ? 'الشركة' : 'Company';
    const headers = [
        t.report.colPhone || 'Phone',
        t.report.colName || 'Name',
        companyHeader,
        t.report.colStatus || 'Status',
        t.report.colSent || 'Sent At',
        t.report.colDelivered || 'Delivered At',
        t.report.colRead || 'Read At',
        t.report.colReplied || 'Replied At',
        t.report.colError || 'Error'
    ];
    const rows = contacts.map(c => [
        c.phone || '',
        c.name || '',
        c.company || '',
        t.report[c.status] || c.status,
        formatDateForCSV(c.sent_at),
        formatDateForCSV(c.delivered_at),
        formatDateForCSV(c.read_at),
        formatDateForCSV(c.replied_at),
        c.error_message || ''
    ]);
    const escapeCell = (val: string, colIdx: number) => {
        const s = String(val).replace(/"/g, '""');
        // Phone column: prefix with tab so Excel treats as text (prevents 2.01E+11)
        if (colIdx === 0) return `"\t${s}"`;
        return `"${s}"`;
    };
    const csvLines = [
        headers.map(h => `"${h}"`).join(','),
        ...rows.map(r => r.map((v, i) => escapeCell(v, i)).join(','))
    ].join('\n');
    // BOM for UTF-8 in Excel
    const blob = new Blob(['\uFEFF' + csvLines], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${(campaignName || 'campaign').replace(/\s+/g, '_')}_report.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

function formatTimeSafe(dateStr: string | null | undefined) {
    if (!dateStr) return '—';
    try {
        // SQLite uses 'YYYY-MM-DD HH:MM:SS' - convert to ISO 'YYYY-MM-DDTHH:MM:SSZ' safely
        const isoStr = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T') + (dateStr.includes('Z') ? '' : 'Z');
        const date = new Date(isoStr);
        if (isNaN(date.getTime())) {
            const fallbackDate = new Date(dateStr);
            return isNaN(fallbackDate.getTime()) ? '—' : fallbackDate.toLocaleTimeString();
        }
        return date.toLocaleTimeString();
    } catch (_) {
        return '—';
    }
}

export default function CampaignReportWorkspace({ t, locale, callRPC, campaignId, campaignName, onBack, daemonConnected }: any) {
    const [contacts, setContacts]   = useState<any[]>([]);
    const [stats, setStats]         = useState<any>({});
    const [loading, setLoading]     = useState(false);
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [showFilter, setShowFilter]     = useState(false);
    const [retryingContact, setRetryingContact] = useState<string | null>(null);
    const [retryingAll, setRetryingAll] = useState(false);

    const isRtl = locale === 'ar';

    const fetchData = async () => {
        if (!daemonConnected || !campaignId) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getCampaignContacts', { campaignId, statusFilter: statusFilter !== 'all' ? statusFilter : null });
            setContacts(res.contacts || []);

            // Always fetch full stats regardless of filter
            const statsRes: any = await callRPC('getCampaignLogs', { campaignId });
            setStats(statsRes.stats || {});
        } catch (err) { console.error(err); }
        setLoading(false);
    };

    useEffect(() => {
        if (!daemonConnected || !campaignId) return;

        // Fetch immediately with loading spinner on mount/filter changes
        fetchData();

        // Establish 3-second silent background poll for real-time receipt and progress updates
        const interval = setInterval(() => {
            const fetchSilent = async () => {
                try {
                    const res: any = await callRPC('getCampaignContacts', { campaignId, statusFilter: statusFilter !== 'all' ? statusFilter : null });
                    setContacts(res.contacts || []);
                    const statsRes: any = await callRPC('getCampaignLogs', { campaignId });
                    setStats(statsRes.stats || {});
                } catch (err) {
                    console.error('Silent background fetch error:', err);
                }
            };
            fetchSilent();
        }, 3000);

        return () => clearInterval(interval);
    }, [campaignId, statusFilter, daemonConnected]);

    const totalContacts = stats.total || contacts.length;
    const rawSent      = stats.sent      || 0;
    const rawDelivered = stats.delivered || 0;
    const rawRead      = stats.read_count || 0;
    const rawReplied   = stats.replied   || 0;
    const failed       = stats.failed    || 0;
    const pending      = stats.pending   || 0;

    // ── Donut: shows current exclusive status per contact ──
    const donutSegments = [
        { value: rawSent,      color: STATUS_COLORS.sent,      label: t.report.sent },
        { value: rawDelivered, color: STATUS_COLORS.delivered,  label: t.report.delivered },
        { value: rawRead,      color: STATUS_COLORS.read,       label: t.report.read },
        { value: rawReplied,   color: STATUS_COLORS.replied,    label: t.report.replied },
        { value: failed,       color: STATUS_COLORS.failed,     label: t.report.failed },
        { value: pending,      color: STATUS_COLORS.pending,    label: t.report.pending },
    ].filter(s => s.value > 0);

    const filteredContacts = statusFilter === 'all' ? contacts : contacts.filter(c => c.status === statusFilter);

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Header */}
            <div className="flex items-center gap-4">
                <Button variant="outline" size="icon" onClick={onBack}>
                    <ArrowLeft className={`w-4 h-4 ${isRtl ? 'rotate-180' : ''}`} />
                </Button>
                <div className="flex-1 min-w-0">
                    <h2 className="text-xl font-bold tracking-tight text-start">{t.report.title}</h2>
                    <p className="text-xs text-muted-foreground mt-0.5 text-start">{campaignName} &mdash; <span className="font-mono">{campaignId}</span></p>
                </div>
                <div className="flex items-center gap-2">
                    {failed > 0 && (
                        <Button
                            onClick={async () => {
                                setRetryingAll(true);
                                try {
                                    await callRPC('retryFailedMessages', { campaignId });
                                    await fetchData();
                                } catch (e: any) { alert(`Retry failed: ${e.message}`); }
                                setRetryingAll(false);
                            }}
                            disabled={retryingAll}
                            className="bg-orange-500 hover:bg-orange-600 text-white gap-2 text-xs h-8"
                        >
                            {retryingAll ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <RotateCcw className="w-3.5 h-3.5" />}
                            {t.report.retryAllBtn} ({failed})
                        </Button>
                    )}
                    <Button variant="outline" size="icon" onClick={fetchData} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </Button>
                    <Button onClick={() => exportCSV(contacts, campaignName, t)} className="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                        <Download className="w-4 h-4" /> {t.report.exportBtn}
                    </Button>
                </div>

            </div>

            {/* Analytics Row */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {/* Donut Chart Card */}
                <Card>
                    <CardContent className="p-7 flex flex-col items-center justify-center">
                        <div className="text-center mb-6">
                            <h3 className="text-sm font-bold uppercase tracking-wider">{t.report.breakdownTitle}</h3>
                            <p className="text-[10px] text-muted-foreground mt-0.5">{t.report.breakdownSub}</p>
                        </div>
                        <DonutChart segments={donutSegments} t={t} />
                    </CardContent>
                </Card>

                {/* Bar Stats Card */}
                <Card>
                    <CardContent className="p-7">
                        <div className="mb-6 text-start">
                            <h3 className="text-sm font-bold uppercase tracking-wider">{t.report.overviewTitle}</h3>
                            <p className="text-[10px] text-muted-foreground mt-0.5">{t.report.overviewSub}</p>
                        </div>
                        <div className="space-y-4">
                            <StatBar label={t.report.sent}      value={rawSent}      total={totalContacts} color={STATUS_COLORS.sent}      icon={Mail} />
                            <StatBar label={t.report.delivered} value={rawDelivered} total={totalContacts} color={STATUS_COLORS.delivered}  icon={CheckCircle2} />
                            <StatBar label={t.report.read}      value={rawRead}      total={totalContacts} color={STATUS_COLORS.read}       icon={Eye} />
                            <StatBar label={t.report.replied}   value={rawReplied}   total={totalContacts} color={STATUS_COLORS.replied}    icon={MessageCircle} />
                            <StatBar label={t.report.failed}    value={failed}       total={totalContacts} color={STATUS_COLORS.failed}     icon={XCircle} />
                            {pending > 0 && <StatBar label={t.report.pending} value={pending} total={totalContacts} color={STATUS_COLORS.pending} icon={Clock} />}
                        </div>
                    </CardContent>
                </Card>
            </div>


            {/* Summary KPI Cards */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                {[
                    { label: t.report.total,     value: totalContacts, color: '#64748b', bg: 'bg-muted' },
                    { label: t.report.sent,      value: rawSent,       color: STATUS_COLORS.sent,      bg: 'bg-emerald-500/10' },
                    { label: t.report.delivered, value: rawDelivered,  color: STATUS_COLORS.delivered,  bg: 'bg-blue-500/10' },
                    { label: t.report.read,      value: rawRead,       color: STATUS_COLORS.read,       bg: 'bg-violet-500/10' },
                    { label: t.report.replied,   value: rawReplied,    color: STATUS_COLORS.replied,    bg: 'bg-amber-500/10' },
                    { label: t.report.failed,    value: failed,        color: STATUS_COLORS.failed,     bg: 'bg-red-500/10' },

                ].map(({ label, value, color, bg }) => (
                    <div key={label} className={`${bg} border rounded-2xl p-4 text-center`}>
                        <p className="text-2xl font-black" style={{ color }}>{value}</p>
                        <p className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground mt-0.5">{label}</p>
                    </div>
                ))}
            </div>

            {/* Contact Table with Filter */}
            <Card className="overflow-hidden">
                <div className="px-7 py-5 border-b flex items-center justify-between">
                    <h3 className="font-bold text-sm text-start">{t.report.tableTitle}</h3>
                    <div className="relative">
                        <Button variant="outline" size="sm" onClick={() => setShowFilter(f => !f)} className="gap-2 text-xs h-8">
                            <Filter className="w-3.5 h-3.5" />
                            {statusFilter === 'all' ? t.report.allStatus : (t.report[statusFilter] || statusFilter)}
                            <ChevronDown className={`w-3.5 h-3.5 transition-transform ${showFilter ? 'rotate-180' : ''}`} />
                        </Button>
                        {showFilter && (
                            <div className={`absolute ${isRtl ? 'left-0' : 'right-0'} top-full mt-1 bg-popover text-popover-foreground border rounded-xl shadow-xl z-10 min-w-[140px] py-1 overflow-hidden`}>
                                {['all', 'sent', 'delivered', 'read', 'replied', 'failed', 'pending'].map(s => (
                                    <Button variant="ghost" key={s} onClick={() => { setStatusFilter(s); setShowFilter(false); }} className={`w-full justify-start rounded-none h-auto px-4 py-2 text-xs font-bold capitalize transition-colors ${statusFilter === s ? 'bg-teal-50 text-teal-600 hover:bg-teal-100 hover:text-teal-700 dark:bg-teal-950/30' : 'hover:bg-accent'}`}>
                                        {s === 'all' ? (isRtl ? 'الكل' : 'All') : (t.report[s] || s)}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
                <div className="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table className="w-full text-start text-sm whitespace-nowrap">
                        <thead className="bg-muted/50 border-b text-muted-foreground font-semibold uppercase text-[10px] tracking-wider sticky top-0 text-start">
                            <tr>
                                <th className="px-6 py-4 text-start">{t.report.colPhone}</th>
                                <th className="px-6 py-4 text-start">{t.report.colName}</th>
                                <th className="px-6 py-4 text-start">{t.report.colStatus}</th>
                                <th className="px-6 py-4 text-start">{t.report.colSent}</th>
                                <th className="px-6 py-4 text-start">{t.report.colDelivered}</th>
                                <th className="px-6 py-4 text-start">{t.report.colRead}</th>
                                <th className="px-6 py-4 text-start">{t.report.colReplied}</th>
                                <th className="px-6 py-4 text-start">{t.report.colError}</th>
                                <th className="px-6 py-4 text-end">{t.report.colAction}</th>
                            </tr>

                        </thead>
                        <tbody className="divide-y text-start">
                            {filteredContacts.map((c: any, index: number) => {
                                const StatusIcon = STATUS_ICONS[c.status] || Clock;
                                const color      = STATUS_COLORS[c.status] || '#94a3b8';
                                return (
                                    <tr key={`${c.phone || ''}-${index}`} className="hover:bg-muted/50 transition-colors">
                                        <td className="px-6 py-3 font-mono text-muted-foreground text-xs text-start">{c.phone}</td>
                                        <td className="px-6 py-3 font-medium text-xs text-start">{c.name || '—'}</td>
                                        <td className="px-6 py-3 text-start">
                                            <Badge variant="outline" className="gap-1.5 text-[10px] uppercase tracking-wider" style={{ background: `${color}18`, color, borderColor: `${color}30` }}>
                                                <StatusIcon className="w-3 h-3" />
                                                {t.report[c.status] || c.status}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground text-xs text-start">{formatTimeSafe(c.sent_at)}</td>
                                        <td className="px-6 py-3 text-muted-foreground text-xs text-start">{formatTimeSafe(c.delivered_at)}</td>
                                        <td className="px-6 py-3 text-muted-foreground text-xs text-start">{formatTimeSafe(c.read_at)}</td>
                                        <td className="px-6 py-3 text-muted-foreground text-xs text-start">{formatTimeSafe(c.replied_at)}</td>
                                        <td className="px-6 py-3 text-destructive text-xs max-w-[200px] truncate text-start" title={c.error_message}>{c.error_message || '—'}</td>
                                        <td className="px-6 py-3 text-end">
                                            <div className="flex items-center justify-end gap-1">
                                                {c.status === 'failed' && (
                                                    <Button
                                                        size="sm"
                                                        disabled={retryingContact === c.phone}
                                                        onClick={async () => {
                                                            setRetryingContact(c.phone);
                                                            try {
                                                                await callRPC('retrySingleContact', { campaignId, phone: c.phone });
                                                                await fetchData();
                                                            } catch (e: any) { alert(`Retry failed: ${e.message}`); }
                                                            setRetryingContact(null);
                                                        }}
                                                        className="h-7 px-2.5 gap-1 bg-orange-500 hover:bg-orange-600 text-white text-[10px] font-bold"
                                                    >
                                                        {retryingContact === c.phone ? <RefreshCw className="w-3 h-3 animate-spin" /> : <RotateCcw className="w-3 h-3" />}
                                                        {t.report.retrySingleBtn}
                                                    </Button>
                                                )}
                                                {(c.status === 'sent' || c.status === 'delivered' || c.status === 'read' || c.status === 'replied') && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={retryingContact === c.phone}
                                                        onClick={async () => {
                                                            setRetryingContact(c.phone);
                                                            try {
                                                                await callRPC('retrySingleContact', { campaignId, phone: c.phone });
                                                                await fetchData();
                                                            } catch (e: any) { alert(`Resend failed: ${e.message}`); }
                                                            setRetryingContact(null);
                                                        }}
                                                        className="h-7 px-2.5 gap-1 text-[10px] font-bold"
                                                    >
                                                        {retryingContact === c.phone ? <RefreshCw className="w-3 h-3 animate-spin" /> : <RotateCcw className="w-3 h-3" />}
                                                        {t.report.resendSingleBtn}
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>

                                );
                            })}
                            {filteredContacts.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={9} className="px-6 py-12 text-center text-muted-foreground text-sm">
                                        {statusFilter !== 'all' ? `${t.report.noContactsFilter} "${t.report[statusFilter] || statusFilter}"` : t.report.noContactsRecorded}
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
