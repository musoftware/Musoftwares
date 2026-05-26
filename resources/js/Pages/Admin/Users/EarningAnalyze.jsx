import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import {
    TrendingUp, TrendingDown, Users, Clock, CheckCircle2,
    DollarSign, BarChart2, Award, ArrowUpRight, ChevronDown,
    ChevronUp, ExternalLink, Minus, AlertTriangle, Wallet,
    ArrowDownCircle, Info,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import { formatMoney } from '@/lib/utils';
import { format } from 'date-fns';

// ─────────────────────────────────────────────────────────────
// Business currency formatter — prepends symbol from backend
// ─────────────────────────────────────────────────────────────
function fmtBiz(amount, biz = null) {
    if (amount === null || amount === undefined) return '—';
    const n = parseFloat(amount);
    if (isNaN(n)) return '—';
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n);
    const symbol = biz?.symbol ?? '';
    const code   = biz?.code   ?? '';
    // Show symbol before number, code after — e.g. "$ 4,907.64" or "4,907.64 EGP"
    return symbol ? `${symbol} ${formatted}` : code ? `${formatted} ${code}` : formatted;
}

// ─────────────────────────────────────────────────────────────
// Inline SVG bar chart (no external dep)
// ─────────────────────────────────────────────────────────────
function MonthlyBarChart({ data, fmt }) {
    if (!data || data.length === 0) {
        return (
            <div className="flex items-center justify-center h-44 text-slate-400 text-sm">
                No earning data in the last 12 months.
            </div>
        );
    }

    const max = Math.max(...data.map(d => d.total), 1);
    const height = 160;

    return (
        <div className="w-full overflow-x-auto">
            <svg
                viewBox={`0 0 ${data.length * 60} ${height + 30}`}
                className="w-full"
                style={{ minWidth: data.length * 40 }}
            >
                {data.map((d, i) => {
                    const barH = Math.max(4, (d.total / max) * height);
                    const x = i * 60 + 10;
                    const y = height - barH;
                    return (
                        <g key={d.month}>
                            <rect
                                x={x} y={y} width={40} height={barH}
                                rx={4}
                                className="fill-slate-800 opacity-80 hover:opacity-100 transition-opacity"
                            />
                            <title>{d.month}: {fmt(d.total)}</title>
                            <text
                                x={x + 20} y={height + 16}
                                textAnchor="middle"
                                fontSize="9"
                                className="fill-slate-500"
                            >
                                {d.month.slice(5)}
                            </text>
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────
// Metric card — Black & White aesthetic per Admin UI skill
// ─────────────────────────────────────────────────────────────
function MetricCard({ label, value, sub, icon: Icon, tooltip, accent = false, danger = false, warning = false, success = false }) {
    const valueClass = danger
        ? 'text-red-600'
        : warning
            ? 'text-amber-600'
            : success
                ? 'text-green-600'
                : 'text-slate-900';

    return (
        <Card className="border-slate-200">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-1.5 mb-2">
                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {label}
                            </p>
                            {tooltip && (
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="h-3 w-3 text-slate-400 cursor-help flex-shrink-0" />
                                        </TooltipTrigger>
                                        <TooltipContent side="top" className="max-w-xs text-xs">
                                            {tooltip}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            )}
                        </div>
                        <p className={`text-2xl font-bold tabular-nums ${valueClass}`}>{value}</p>
                        {sub && <p className="text-xs text-slate-400 mt-1">{sub}</p>}
                    </div>
                    {Icon && (
                        <div className="rounded-lg p-2 bg-slate-50 flex-shrink-0">
                            <Icon className="h-4 w-4 text-slate-500" />
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

// ─────────────────────────────────────────────────────────────
// Section divider with label
// ─────────────────────────────────────────────────────────────
function SectionLabel({ icon: Icon, label }) {
    return (
        <div className="flex items-center gap-2 mb-3">
            <Icon className="h-4 w-4 text-slate-400" />
            <span className="text-xs font-bold uppercase tracking-widest text-slate-500">{label}</span>
            <div className="flex-1 border-t border-slate-100" />
        </div>
    );
}

// ─────────────────────────────────────────────────────────────
// Status badge
// ─────────────────────────────────────────────────────────────
function StatusBadge({ status }) {
    if (status === 'cleared') {
        return (
            <Badge className="bg-green-50 text-green-700 border-green-200 text-xs font-medium gap-1">
                <CheckCircle2 className="h-2.5 w-2.5" /> Cleared
            </Badge>
        );
    }
    if (status === 'overdue') {
        return (
            <Badge className="bg-red-50 text-red-700 border-red-200 text-xs font-medium gap-1">
                <AlertTriangle className="h-2.5 w-2.5" /> Overdue
            </Badge>
        );
    }
    return (
        <Badge className="bg-amber-50 text-amber-700 border-amber-200 text-xs font-medium gap-1">
            <Clock className="h-2.5 w-2.5" /> Pending
        </Badge>
    );
}

// ─────────────────────────────────────────────────────────────
// Main page
// ─────────────────────────────────────────────────────────────
export default function EarningAnalyze({
    business_currency,
    stats,
    annual,
    liquidity,
    settlement,
    referral_funnel,
    monthly_trend,
    top_earners,
    recent_earnings,
    currency_breakdown,
}) {
    // Shorthand formatter pre-loaded with business currency
    const biz = business_currency ?? {};
    const fmt = (amount) => {
        if (amount === null || amount === undefined) return '—';
        const n = parseFloat(amount);
        if (isNaN(n)) return '—';
        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n);
        const symbol = biz?.symbol ?? '';
        const code   = biz?.code   ?? '';
        return symbol ? `${symbol} ${formatted}` : code ? `${formatted} ${code}` : formatted;
    };
    const [sortKey, setSortKey] = useState('total_earned');
    const [sortDir, setSortDir] = useState('desc');
    const [statusFilter, setStatusFilter] = useState('all');

    // Sort top earners
    const sortedEarners = useMemo(() => {
        const copy = [...(top_earners || [])];
        copy.sort((a, b) => sortDir === 'desc' ? b[sortKey] - a[sortKey] : a[sortKey] - b[sortKey]);
        return copy;
    }, [top_earners, sortKey, sortDir]);

    const toggleSort = (key) => {
        if (sortKey === key) setSortDir(d => d === 'desc' ? 'asc' : 'desc');
        else { setSortKey(key); setSortDir('desc'); }
    };

    const SortIcon = ({ k }) => {
        if (sortKey !== k) return <ChevronDown className="h-3 w-3 opacity-30" />;
        return sortDir === 'desc'
            ? <ChevronDown className="h-3 w-3 text-slate-700" />
            : <ChevronUp className="h-3 w-3 text-slate-700" />;
    };

    // Filter recent earnings (client-side on the 50 loaded rows)
    const filteredRecent = useMemo(() => {
        if (statusFilter === 'all') return recent_earnings || [];
        return (recent_earnings || []).filter(e => e.status === statusFilter);
    }, [recent_earnings, statusFilter]);

    // Growth direction
    const growthPositive = (annual?.net_growth ?? 0) >= 0;

    // Payout ratio width (capped at 100)
    const payoutWidth = Math.min(100, settlement?.payout_ratio ?? 0);

    return (
        <AdminSidebarLayout title="Earning Analysis" header="Earning Analysis">
            <Head title="Earning Analysis — Admin" />

            {/* ── Section 1: KPIs ───────────────────────────────────────── */}
            <div className="mb-8">
                <SectionLabel icon={DollarSign} label="Summary" />
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <MetricCard
                        icon={DollarSign}
                        label="Total Commissions"
                        value={fmt(stats?.total_earnings)}
                        sub="All-time referral earnings (business currency)"
                        tooltip="Sum of all referral commission earnings, normalized to your business currency using historical exchange rates."
                    />
                    <MetricCard
                        icon={Users}
                        label="Unique Earners"
                        value={(stats?.total_earners ?? 0).toLocaleString()}
                        sub="Users who earned commissions"
                    />
                    <MetricCard
                        icon={CheckCircle2}
                        label="Cleared"
                        value={fmt(stats?.cleared_earnings)}
                        sub="Already paid to user wallets"
                        success
                        tooltip="Earnings that have been released from the clearing period and credited to user wallet balances."
                    />
                    <MetricCard
                        icon={Clock}
                        label="Pending Clearing"
                        value={fmt(stats?.pending_clearing)}
                        sub={stats?.overdue_clearing > 0
                            ? `⚠ ${fmt(stats.overdue_clearing)} overdue`
                            : `${fmt(stats?.in_window_clearing)} in holding window`}
                        warning={stats?.overdue_clearing > 0}
                        tooltip="Total earnings not yet released. Overdue = past clearing date and needs processing. In-window = still within the holding period."
                    />
                </div>

                {/* Overdue alert banner */}
                {stats?.overdue_clearing > 0 && (
                    <div className="mt-3 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <AlertTriangle className="h-4 w-4 text-red-600 flex-shrink-0" />
                        <div className="text-sm text-red-800">
                            <span className="font-semibold">{fmt(stats.overdue_clearing)}</span> in earnings are overdue for clearing.
                            The scheduler will process these automatically on the next run.
                        </div>
                    </div>
                )}
            </div>

            {/* ── Section 2: Annual Performance ─────────────────────────── */}
            <div className="mb-8">
                <SectionLabel icon={TrendingUp} label="Annual Performance" />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <MetricCard
                        icon={DollarSign}
                        label="Opening Balance"
                        value={fmt(annual?.opening_balance)}
                        sub={`Jan 1, ${new Date().getFullYear()}`}
                        tooltip="Total platform transaction balance as of January 1st this year. Serves as the fiscal year baseline."
                    />
                    <MetricCard
                        icon={Wallet}
                        label="Current Balance"
                        value={fmt(annual?.closing_balance)}
                        sub="Updated in real-time"
                        tooltip="The sum of all platform transactions (business_amount) up to today. Represents total liquid funds on the platform."
                    />
                    <Card className="border-slate-200">
                        <CardContent className="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex-1">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
                                        Net Annual Growth
                                    </p>
                                    <p className={`text-2xl font-bold tabular-nums ${growthPositive ? 'text-green-600' : 'text-red-600'}`}>
                                        {growthPositive ? '+' : ''}{fmt(annual?.net_growth)}
                                    </p>
                                    <p className="text-xs text-slate-400 mt-1">
                                        {annual?.growth_pct >= 0 ? '+' : ''}{annual?.growth_pct ?? 0}% vs opening
                                    </p>
                                </div>
                                <div className="rounded-lg p-2 bg-slate-50 flex-shrink-0">
                                    {growthPositive
                                        ? <TrendingUp className="h-4 w-4 text-green-600" />
                                        : <TrendingDown className="h-4 w-4 text-red-600" />
                                    }
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div
                                        className={`h-full rounded-full transition-all ${growthPositive ? 'bg-green-500' : 'bg-red-500'}`}
                                        style={{ width: `${Math.min(100, Math.abs(annual?.growth_pct ?? 0))}%` }}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* ── Section 3: Operational Liquidity ──────────────────────── */}
            <div className="mb-8">
                <SectionLabel icon={Wallet} label="Operational Liquidity" />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <MetricCard
                        icon={DollarSign}
                        label="Floating Cash"
                        value={fmt(liquidity?.floating_cash)}
                        sub="Total platform cash right now"
                        tooltip="All liquid funds held across all platform accounts."
                    />
                    <MetricCard
                        icon={ArrowDownCircle}
                        label="Unpaid Invoices"
                        value={fmt(liquidity?.unpaid_invoices)}
                        sub="Funds allocated to pending invoices"
                        danger={liquidity?.unpaid_invoices > 0}
                        tooltip="Total value of invoices that have been issued but not yet settled. These are immediate liabilities."
                    />
                    <MetricCard
                        icon={CheckCircle2}
                        label="Available Liquidity"
                        value={fmt(liquidity?.available_liquidity)}
                        sub="Floating cash minus obligations"
                        success={liquidity?.available_liquidity >= 0}
                        danger={liquidity?.available_liquidity < 0}
                        tooltip="The true free capital = Floating Cash − Unpaid Invoices. This is what's genuinely available for new obligations."
                    />
                </div>
            </div>

            {/* ── Section 4: Settlement & Withdrawals ───────────────────── */}
            <div className="mb-8">
                <SectionLabel icon={ArrowUpRight} label="Settlement & Withdrawals" />
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <MetricCard
                        label="Total Earning Pool"
                        value={fmt(settlement?.total_pool)}
                        sub="All-time referral commissions"
                        tooltip="The total cumulative value of all earnings ever generated through referrals."
                    />
                    <MetricCard
                        label="Paid Out"
                        value={fmt(settlement?.withdrawn)}
                        sub="Approved withdrawal payouts"
                        success
                        tooltip="Total amount that has been approved and paid out to users via withdrawal requests."
                    />
                    <MetricCard
                        label="In Clearing"
                        value={fmt(settlement?.pending_clearing)}
                        sub={settlement?.clearing_start && settlement?.clearing_end
                            ? `${settlement.clearing_start} → ${settlement.clearing_end}`
                            : 'No pending clearing dates'}
                        warning
                        tooltip="Earnings locked in the holding period. These are committed but not yet withdrawable."
                    />
                    <MetricCard
                        label="Ready to Withdraw"
                        value={fmt(settlement?.ready_for_withdrawal)}
                        sub="Net available for payout now"
                        success={settlement?.ready_for_withdrawal > 0}
                        tooltip="= Total Pool − Paid Out − In Clearing. This is the net amount users can withdraw today."
                    />
                </div>

                {/* Payout ratio bar */}
                <Card className="border-slate-200">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Effective Payout Ratio
                            </span>
                            <span className="text-sm font-bold text-slate-900 tabular-nums">
                                {settlement?.payout_ratio ?? 0}%
                            </span>
                        </div>
                        <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                className="h-full bg-slate-800 rounded-full transition-all"
                                style={{ width: `${payoutWidth}%` }}
                            />
                        </div>
                        <p className="text-xs text-slate-400 mt-1.5">
                            {fmt(settlement?.withdrawn)} paid out of {fmt(settlement?.total_pool)} total pool
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* ── Section 5: Referral Funnel ────────────────────────────── */}
            <div className="mb-8">
                <SectionLabel icon={Users} label="Referral Funnel" />
                <div className="grid grid-cols-2 gap-4">
                    <MetricCard
                        icon={ArrowUpRight}
                        label="Total Referral Visits"
                        value={(referral_funnel?.total_views ?? 0).toLocaleString()}
                        sub="Unique referral link clicks"
                    />
                    <MetricCard
                        icon={Users}
                        label="Registrations via Referral"
                        value={(referral_funnel?.total_registers ?? 0).toLocaleString()}
                        sub="Users who signed up through a referral"
                    />
                </div>
            </div>

            {/* ── Section 6: Monthly Trend + Currency Breakdown ─────────── */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <Card className="lg:col-span-2 border-slate-200">
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <BarChart2 className="h-4 w-4 text-slate-500" />
                            Monthly Earnings Trend
                        </CardTitle>
                        <CardDescription>Last 12 months — normalized to business currency</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <MonthlyBarChart data={monthly_trend} fmt={fmt} />
                    </CardContent>
                </Card>

                <Card className="border-slate-200">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-semibold text-slate-800">By Currency</CardTitle>
                        <CardDescription>Raw earning distribution per currency</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {currency_breakdown?.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        <TableHead className="text-xs">Currency</TableHead>
                                        <TableHead className="text-right text-xs">Total</TableHead>
                                        <TableHead className="text-right text-xs">Count</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {currency_breakdown.map((row) => (
                                        <TableRow key={row.currency_id}>
                                            <TableCell>
                                                <Badge variant="outline" className="font-mono text-xs">
                                                    {row.currency_code}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right font-medium text-sm tabular-nums">
                                                {formatMoney(row.total, row.currency_code)}
                                            </TableCell>
                                            <TableCell className="text-right text-slate-500 text-sm">
                                                {row.count}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="flex items-center justify-center h-32 text-slate-400 text-sm">
                                No currency data yet.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* ── Section 7: Top Earners ────────────────────────────────── */}
            <Card className="mb-6 border-slate-200">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <Award className="h-4 w-4 text-slate-500" />
                        Top Earners
                    </CardTitle>
                    <CardDescription>Ranked by referral commission (business currency)</CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    {sortedEarners.length > 0 ? (
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50">
                                    <TableHead className="w-10 text-xs">#</TableHead>
                                    <TableHead className="text-xs">User</TableHead>
                                    <TableHead
                                        className="cursor-pointer select-none hover:text-slate-900 text-xs"
                                        onClick={() => toggleSort('referral_count')}
                                    >
                                        <span className="flex items-center gap-1">
                                            Referrals <SortIcon k="referral_count" />
                                        </span>
                                    </TableHead>
                                    <TableHead
                                        className="text-right cursor-pointer select-none hover:text-slate-900 text-xs"
                                        onClick={() => toggleSort('total_earned')}
                                    >
                                        <span className="flex items-center justify-end gap-1">
                                            Earned <SortIcon k="total_earned" />
                                        </span>
                                    </TableHead>
                                    <TableHead className="w-12 text-right text-xs"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sortedEarners.map((earner, idx) => (
                                    <TableRow key={earner.user_id} className="hover:bg-slate-50/60">
                                        <TableCell>
                                            <span className={`text-xs font-bold ${idx < 3 ? 'text-amber-500' : 'text-slate-400'}`}>
                                                {idx + 1}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2.5">
                                                <div className="h-7 w-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                    {earner.name?.substring(0, 2).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="font-medium text-slate-900 text-sm">{earner.name}</div>
                                                    <div className="text-xs text-slate-400">{earner.email}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary" className="font-mono text-xs">
                                                {earner.referral_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right font-semibold text-green-700 text-sm tabular-nums">
                                            {fmt(earner.total_earned)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {earner.user_id && (
                                                <Link
                                                    href={`/admin/users/${earner.user_id}`}
                                                    className="inline-flex items-center text-slate-400 hover:text-slate-800 transition-colors"
                                                >
                                                    <ExternalLink className="h-3.5 w-3.5" />
                                                </Link>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <div className="flex flex-col items-center justify-center h-36 text-slate-400 gap-2">
                            <TrendingUp className="h-8 w-8 opacity-40" />
                            <p className="text-sm">No earners yet.</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* ── Section 8: Recent Earnings Feed ──────────────────────── */}
            <Card className="border-slate-200">
                <CardHeader className="flex flex-row items-start justify-between pb-2">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <ArrowUpRight className="h-4 w-4 text-slate-500" />
                            Recent Earnings
                        </CardTitle>
                        <CardDescription>Last 50 commission records</CardDescription>
                    </div>
                    <div className="flex gap-1.5">
                        {['all', 'pending', 'overdue', 'cleared'].map((s) => (
                            <Button
                                key={s}
                                variant={statusFilter === s ? 'default' : 'ghost'}
                                size="sm"
                                className={`text-xs h-7 px-2.5 ${statusFilter === s ? 'bg-slate-900 text-white hover:bg-slate-800' : 'text-slate-500'}`}
                                onClick={() => setStatusFilter(s)}
                            >
                                {s.charAt(0).toUpperCase() + s.slice(1)}
                            </Button>
                        ))}
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {filteredRecent.length > 0 ? (
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50">
                                    <TableHead className="w-10 text-xs">ID</TableHead>
                                    <TableHead className="text-xs">Earner</TableHead>
                                    <TableHead className="text-xs">Referred User</TableHead>
                                    <TableHead className="text-right text-xs">Amount</TableHead>
                                    <TableHead className="text-xs">Status</TableHead>
                                    <TableHead className="text-xs">Clear Date</TableHead>
                                    <TableHead className="text-xs">Created</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredRecent.map((e) => (
                                    <TableRow key={e.id} className="hover:bg-slate-50/60">
                                        <TableCell className="text-slate-400 font-mono text-xs">
                                            #{e.id}
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm font-medium text-slate-900">{e.user_name}</div>
                                            <div className="text-xs text-slate-400">{e.user_email}</div>
                                        </TableCell>
                                        <TableCell>
                                            {e.referred_user_name ? (
                                                <span className="text-sm text-slate-600">{e.referred_user_name}</span>
                                            ) : (
                                                <span className="text-xs text-slate-400 italic">No referral</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className={`font-semibold text-sm tabular-nums ${e.amount >= 0 ? 'text-green-700' : 'text-red-600'}`}>
                                                {formatMoney(e.amount, e.currency_code)}
                                                <span className="text-xs font-normal ml-1 text-slate-400">{e.currency_code}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge status={e.status} />
                                        </TableCell>
                                        <TableCell className="text-xs text-slate-500">
                                            {e.convert_to_balance_on
                                                ? format(new Date(e.convert_to_balance_on), 'MMM dd, yyyy')
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="text-xs text-slate-500">
                                            {e.created_at
                                                ? format(new Date(e.created_at), 'MMM dd, yyyy')
                                                : '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <div className="flex flex-col items-center justify-center h-36 text-slate-400 gap-2">
                            <Minus className="h-8 w-8 opacity-40" />
                            <p className="text-sm">No earnings match the selected filter.</p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminSidebarLayout>
    );
}
