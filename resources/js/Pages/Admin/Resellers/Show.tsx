import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { EmptyState } from '@/Components/ui/EmptyState';
import {
    Users, DollarSign, Copy, Check, ExternalLink, ArrowLeft,
    ShieldAlert, ShieldCheck, Shield, UserX, UserCheck,
    TrendingUp, TrendingDown, Store, RefreshCw, ToggleLeft, ToggleRight, Wifi
} from 'lucide-react';

function formatCurrency(amount: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
}

const statusMap: Record<string, string> = {
    active: 'success', suspended: 'danger', suspended_by_reseller: 'danger',
    sharing_flagged: 'danger', inactive: 'neutral',
};

const txTypeStyle: Record<string, { icon: any; color: string }> = {
    top_up:        { icon: TrendingUp, color: 'text-emerald-600' },
    manual_credit: { icon: TrendingUp, color: 'text-emerald-600' },
    charge:        { icon: TrendingDown, color: 'text-slate-500' },
    manual_debit:  { icon: TrendingDown, color: 'text-orange-500' },
    suspension:    { icon: ShieldAlert, color: 'text-red-500' },
};

type Tab = 'overview' | 'users' | 'flagged' | 'transactions' | 'balance';

export default function ResellersShow({ reseller, subUsers, transactions, sharingFlagged, iframeUrl }: any) {
    const [tab, setTab] = useState<Tab>('overview');
    const [copied, setCopied] = useState(false);
    const [balanceForm, setBalanceForm] = useState({ type: 'top_up', amount: '', description: '' });
    const [submittingBalance, setSubmittingBalance] = useState(false);

    function copyUrl() {
        navigator.clipboard.writeText(iframeUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    function submitBalance(e: React.FormEvent) {
        e.preventDefault();
        setSubmittingBalance(true);
        router.post(`/admin/resellers/${reseller.id}/balance`, balanceForm, {
            preserveScroll: true,
            onFinish: () => { setSubmittingBalance(false); setBalanceForm({ type: 'top_up', amount: '', description: '' }); },
        });
    }

    function suspendUser(userId: number) {
        router.post(`/admin/resellers/${reseller.id}/users/${userId}/suspend`, {}, { preserveScroll: true });
    }

    function activateUser(userId: number) {
        router.post(`/admin/resellers/${reseller.id}/users/${userId}/activate`, {}, { preserveScroll: true });
    }

    function clearFlag(userId: number) {
        if (!confirm('Clear the sharing flag for this user? Their active sessions will be reset.')) return;
        router.post(`/admin/resellers/${reseller.id}/users/${userId}/clear-flag`, {}, { preserveScroll: true });
    }

    function toggleCheck(userId: number) {
        router.post(`/admin/resellers/${reseller.id}/users/${userId}/toggle-check`, {}, { preserveScroll: true });
    }

    const tabs: { id: Tab; label: string; badge?: number }[] = [
        { id: 'overview', label: 'Overview' },
        { id: 'users', label: 'Sub-Users', badge: subUsers?.data?.length },
        { id: 'flagged', label: '🚨 Sharing Flags', badge: sharingFlagged?.length },
        { id: 'transactions', label: 'Transactions' },
        { id: 'balance', label: 'Top-Up Balance' },
    ];

    const menuItems = [
        { id: 'resellers', label: 'Resellers', icon: Store, href: '/admin/resellers', isActive: true },
    ];

    return (
        <WorkspaceLayout title={reseller.name} workspaceName="Musoftware Admin" tenantId="SYS-ADMIN" menuItems={menuItems}>
            <Head title={`Reseller: ${reseller.name}`} />
            <div className="space-y-6">
                <ModulePageHeader
                    title={reseller.name}
                    description={`Reseller account — ${reseller.user?.email}`}
                    actions={
                        <div className="flex items-center gap-2">
                            <StatusBadge status={statusMap[reseller.status] || 'neutral'} label={reseller.status} />
                            <Link href="/admin/resellers">
                                <Button variant="outline" size="sm" className="gap-1.5">
                                    <ArrowLeft className="w-3.5 h-3.5" /> Back
                                </Button>
                            </Link>
                        </div>
                    }
                />

                {/* Balance Banner */}
                <div className={`rounded-xl px-6 py-4 flex items-center justify-between border ${reseller.balance <= 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'}`}>
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-text-muted mb-1">Pre-paid Balance</p>
                        <p className={`text-3xl font-bold font-mono ${reseller.balance <= 0 ? 'text-red-600' : 'text-emerald-700'}`}>
                            {formatCurrency(reseller.balance, reseller.currency)}
                        </p>
                        {reseller.balance <= 0 && (
                            <p className="text-xs text-red-600 mt-1 font-medium">⚠️ Service suspended — balance is empty. Top up to restore access.</p>
                        )}
                    </div>
                    <div className="text-right">
                        <p className="text-xs text-text-muted">Active sub-users</p>
                        <p className="text-2xl font-bold text-text-primary">{reseller.active_users}</p>
                        <p className="text-xs text-text-muted mt-0.5">of {reseller.total_users} total</p>
                    </div>
                </div>

                {/* Tabs */}
                <div className="border-b border-border flex gap-0">
                    {tabs.map(t => (
                        <button
                            key={t.id}
                            onClick={() => setTab(t.id)}
                            className={`px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${tab === t.id ? 'border-primary text-primary' : 'border-transparent text-text-muted hover:text-text-primary'}`}
                        >
                            {t.label}
                            {t.badge != null && t.badge > 0 && (
                                <span className={`ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full ${t.id === 'flagged' ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary'}`}>
                                    {t.badge}
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                {/* ─── Overview ─── */}
                {tab === 'overview' && (
                    <div className="space-y-4">
                        {/* iFrame URL */}
                        <OperationalCard title="Portal URL (iFrame Embed)">
                            <p className="text-xs text-text-muted mb-3">
                                Share this URL with the reseller. Their sub-users access the full tools platform through this link.
                                It can also be embedded as an <code className="bg-surface-raised px-1 py-0.5 rounded text-[11px]">&lt;iframe&gt;</code> on their website.
                            </p>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 bg-surface-raised border border-border rounded-lg px-3 py-2 font-mono text-xs text-text-primary break-all">
                                    {iframeUrl}
                                </div>
                                <Button variant="outline" size="sm" onClick={copyUrl} className="shrink-0 gap-1.5">
                                    {copied ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                                    {copied ? 'Copied!' : 'Copy'}
                                </Button>
                                <a href={iframeUrl} target="_blank" rel="noopener noreferrer">
                                    <Button variant="outline" size="sm" className="shrink-0 gap-1.5">
                                        <ExternalLink className="w-3.5 h-3.5" /> Open
                                    </Button>
                                </a>
                            </div>

                            <div className="mt-4 bg-slate-900 rounded-lg p-3">
                                <p className="text-[10px] text-slate-400 font-mono mb-1">Embed code:</p>
                                <code className="text-[11px] text-emerald-400 font-mono break-all">
                                    {`<iframe src="${iframeUrl}" width="100%" height="800" frameborder="0"></iframe>`}
                                </code>
                            </div>
                        </OperationalCard>

                        {/* Notes */}
                        {reseller.notes && (
                            <OperationalCard title="Notes">
                                <p className="text-sm text-text-primary">{reseller.notes}</p>
                            </OperationalCard>
                        )}

                        {/* Anti-sharing info */}
                        <OperationalCard title="Anti-Sharing Protection">
                            <div className="flex items-start gap-3">
                                <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                    <Shield className="w-5 h-5 text-blue-500" />
                                </div>
                                <div className="text-sm text-text-muted space-y-1">
                                    <p>The platform automatically detects account sharing by monitoring <strong className="text-text-primary">concurrent active sessions</strong>.</p>
                                    <p>If the same sub-user account is detected actively using the platform from <strong className="text-text-primary">2+ different IP addresses simultaneously</strong> (within a 5-minute window), the account is immediately flagged and blocked.</p>
                                    <p className="text-xs bg-surface-raised rounded-lg p-2 border border-border mt-2">
                                        🔄 <strong>Dynamic IPs are safe</strong> — the system only flags <em>simultaneous</em> usage, not IP changes between sessions.
                                    </p>
                                </div>
                            </div>
                        </OperationalCard>
                    </div>
                )}

                {/* ─── Sub-Users ─── */}
                {tab === 'users' && (
                    <OperationalCard noPadding>
                        {!subUsers?.data?.length ? (
                            <EmptyState icon={Users} title="No sub-users yet" description="Sub-users appear here once they register through the reseller portal." />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">User</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Anti-Sharing</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Joined</th>
                                            <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {subUsers.data.map((u: any) => (
                                            <tr key={u.id} className="hover:bg-surface-raised/50 transition-colors">
                                                <td className="px-4 py-3.5">
                                                    <p className="font-semibold text-text-primary">{u.user?.name}</p>
                                                    <p className="text-xs text-text-muted">{u.user?.email}</p>
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <StatusBadge status={statusMap[u.status] || 'neutral'} label={u.status.replace('_', ' ')} size="sm" />
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <button onClick={() => toggleCheck(u.user_id)} className="flex items-center gap-1.5 text-xs">
                                                        {u.sharing_check_enabled ? (
                                                            <><ToggleRight className="w-4 h-4 text-primary" /> <span className="text-primary font-medium">Protected</span></>
                                                        ) : (
                                                            <><ToggleLeft className="w-4 h-4 text-text-muted" /> <span className="text-text-muted">Disabled</span></>
                                                        )}
                                                    </button>
                                                </td>
                                                <td className="px-4 py-3.5 text-xs text-text-muted">{u.joined_at}</td>
                                                <td className="px-4 py-3.5 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {u.status === 'active' ? (
                                                            <button onClick={() => suspendUser(u.user_id)} className="text-xs text-orange-600 hover:underline font-medium flex items-center gap-1">
                                                                <UserX className="w-3.5 h-3.5" /> Suspend
                                                            </button>
                                                        ) : (
                                                            <button onClick={() => activateUser(u.user_id)} className="text-xs text-emerald-600 hover:underline font-medium flex items-center gap-1">
                                                                <UserCheck className="w-3.5 h-3.5" /> Activate
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </OperationalCard>
                )}

                {/* ─── Sharing Flags ─── */}
                {tab === 'flagged' && (
                    <OperationalCard noPadding>
                        {!sharingFlagged?.length ? (
                            <EmptyState icon={ShieldCheck} title="No sharing violations" description="All sub-users are within policy." />
                        ) : (
                            <div className="overflow-x-auto">
                                <div className="px-4 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2 text-red-700 text-xs font-medium">
                                    <ShieldAlert className="w-4 h-4" />
                                    These accounts were caught using the platform simultaneously from multiple IPs — likely account sharing.
                                </div>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">User</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Flagged IPs</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Detected</th>
                                            <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {sharingFlagged.map((u: any) => (
                                            <tr key={u.id} className="bg-red-50/40 hover:bg-red-50/60 transition-colors">
                                                <td className="px-4 py-3.5">
                                                    <p className="font-semibold text-text-primary">{u.user?.name}</p>
                                                    <p className="text-xs text-text-muted">{u.user?.email}</p>
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <div className="flex flex-wrap gap-1">
                                                        {(u.flagged_ips || []).map((ip: string, i: number) => (
                                                            <span key={i} className="font-mono text-[11px] bg-red-100 text-red-700 px-2 py-0.5 rounded flex items-center gap-1">
                                                                <Wifi className="w-3 h-3" /> {ip}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5 text-xs text-text-muted">{u.sharing_flagged_at}</td>
                                                <td className="px-4 py-3.5 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            onClick={() => clearFlag(u.user_id)}
                                                            className="text-xs text-primary hover:underline font-semibold flex items-center gap-1"
                                                        >
                                                            <RefreshCw className="w-3.5 h-3.5" /> Clear & Restore
                                                        </button>
                                                        <button
                                                            onClick={() => suspendUser(u.user_id)}
                                                            className="text-xs text-red-600 hover:underline font-semibold flex items-center gap-1"
                                                        >
                                                            <UserX className="w-3.5 h-3.5" /> Suspend
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </OperationalCard>
                )}

                {/* ─── Transactions ─── */}
                {tab === 'transactions' && (
                    <OperationalCard noPadding>
                        {!transactions?.data?.length ? (
                            <EmptyState icon={DollarSign} title="No transactions yet" description="Balance top-ups and usage charges will appear here." />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Type</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Description</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Sub-User</th>
                                            <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Amount</th>
                                            <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Balance After</th>
                                            <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {transactions.data.map((t: any) => {
                                            const style = txTypeStyle[t.type] || { icon: DollarSign, color: 'text-text-muted' };
                                            const Icon = style.icon;
                                            return (
                                                <tr key={t.id} className="hover:bg-surface-raised/50 transition-colors">
                                                    <td className="px-4 py-3">
                                                        <span className={`inline-flex items-center gap-1 text-xs font-semibold ${style.color}`}>
                                                            <Icon className="w-3.5 h-3.5" />
                                                            {t.type.replace('_', ' ')}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-xs text-text-muted max-w-[200px] truncate">{t.description || '—'}</td>
                                                    <td className="px-4 py-3 text-xs text-text-muted">{t.user?.name || '—'}</td>
                                                    <td className="px-4 py-3 text-right font-mono text-xs">
                                                        <span className={t.amount >= 0 ? 'text-emerald-600' : 'text-red-600'}>
                                                            {t.amount >= 0 ? '+' : ''}{formatCurrency(t.amount, t.currency)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-mono text-xs text-text-muted">
                                                        {formatCurrency(t.balance_after, t.currency)}
                                                    </td>
                                                    <td className="px-4 py-3 text-right text-xs text-text-muted">{t.created_at}</td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </OperationalCard>
                )}

                {/* ─── Top-Up Balance ─── */}
                {tab === 'balance' && (
                    <div className="max-w-md">
                        <OperationalCard title="Adjust Reseller Balance">
                            <p className="text-xs text-text-muted mb-4">
                                Current balance: <strong className={`font-mono ${reseller.balance <= 0 ? 'text-red-600' : 'text-emerald-600'}`}>{formatCurrency(reseller.balance, reseller.currency)}</strong>
                            </p>
                            <form onSubmit={submitBalance} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="type">Transaction Type</Label>
                                    <select
                                        id="type"
                                        value={balanceForm.type}
                                        onChange={e => setBalanceForm(f => ({ ...f, type: e.target.value }))}
                                        className="flex h-9 w-full rounded-md border border-border bg-surface px-3 py-1 text-sm text-text-primary shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="top_up">➕ Top-Up (Add Balance)</option>
                                        <option value="manual_credit">✅ Manual Credit</option>
                                        <option value="manual_debit">➖ Manual Debit (Remove Balance)</option>
                                    </select>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="amount">Amount ({reseller.currency})</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        value={balanceForm.amount}
                                        onChange={e => setBalanceForm(f => ({ ...f, amount: e.target.value }))}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="tx_desc">Description (optional)</Label>
                                    <Textarea
                                        id="tx_desc"
                                        rows={2}
                                        placeholder="Reason for this adjustment..."
                                        value={balanceForm.description}
                                        onChange={e => setBalanceForm(f => ({ ...f, description: e.target.value }))}
                                    />
                                </div>
                                <Button type="submit" disabled={submittingBalance || !balanceForm.amount} className="w-full">
                                    {submittingBalance ? 'Processing...' : 'Apply Balance Adjustment'}
                                </Button>
                            </form>
                        </OperationalCard>
                    </div>
                )}
            </div>
        </WorkspaceLayout>
    );
}
