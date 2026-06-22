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
    TrendingUp, TrendingDown, Store, RefreshCw, Wifi
} from 'lucide-react';
import { Switch } from '@/Components/ui/switch';

import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const statusMap: Record<string, string> = {
    active: 'success', suspended: 'danger', suspended_by_reseller: 'danger',
    sharing_flagged: 'danger', inactive: 'neutral',
};

const txTypeStyle: Record<string, { icon: any; color: string }> = {
    top_up:        { icon: TrendingUp, color: 'text-slate-900' },
    manual_credit: { icon: TrendingUp, color: 'text-slate-900' },
    charge:        { icon: TrendingDown, color: 'text-slate-500' },
    manual_debit:  { icon: TrendingDown, color: 'text-yellow-600' },
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
                                    <ArrowLeft className="w-3.5 h-3.5" /> {__('general.back')}</Button>
                            </Link>
                        </div>
                    }
                />

                {/* Balance Banner */}
                <div className={`rounded-xl px-6 py-4 flex items-center justify-between border ${reseller.balance <= 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'}`}>
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-text-muted mb-1">{__('general.pre_paid_balance')}</p>
                        <p className={`text-3xl font-bold font-mono ${reseller.balance <= 0 ? 'text-red-600' : 'text-slate-900'}`}>
                            {formatCurrency(reseller.balance, reseller.currency)}
                        </p>
                        {reseller.balance <= 0 && (
                            <p className="text-xs text-red-600 mt-1 font-medium">⚠️ Service suspended — balance is empty. Top up to restore access.</p>
                        )}
                    </div>
                    <div className="text-end">
                        <p className="text-xs text-text-muted">{__('general.active_sub_users_1')}</p>
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
                                <span className={`ms-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full ${t.id === 'flagged' ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary'}`}>
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
                        <OperationalCard title={__('general.portal_url_iframe_embed')}>
                            <p className="text-xs text-text-muted mb-3">
                                {__('general.share_this_url_with_the_reseller_their_s')}<code className="bg-surface-raised px-1 py-0.5 rounded text-[11px]">&lt;iframe&gt;</code>{__('general.on_their_website')}</p>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 bg-surface-raised border border-border rounded-lg px-3 py-2 font-mono text-xs text-text-primary break-all">
                                    {iframeUrl}
                                </div>
                                <Button variant="outline" size="sm" onClick={copyUrl} className="shrink-0 gap-1.5">
                                    {copied ? <Check className="w-3.5 h-3.5 text-slate-900" /> : <Copy className="w-3.5 h-3.5" />}
                                    {copied ? 'Copied!' : 'Copy'}
                                </Button>
                                <a href={iframeUrl} target="_blank" rel="noopener noreferrer">
                                    <Button variant="outline" size="sm" className="shrink-0 gap-1.5">
                                        <ExternalLink className="w-3.5 h-3.5" /> {__('general.open')}</Button>
                                </a>
                            </div>

                            <div className="mt-4 bg-slate-900 rounded-lg p-3">
                                <p className="text-[10px] text-slate-400 font-mono mb-1">Embed code:</p>
                                <code className="text-[11px] text-slate-900 font-mono break-all">
                                    {`<iframe src="${iframeUrl}" width="100%" height="800" frameborder="0"></iframe>`}
                                </code>
                            </div>
                        </OperationalCard>

                        {/* Notes */}
                        {reseller.notes && (
                            <OperationalCard title={__('general.notes')}>
                                <p className="text-sm text-text-primary">{reseller.notes}</p>
                            </OperationalCard>
                        )}

                        {/* Anti-sharing info */}
                        <OperationalCard title={__('general.anti_sharing_protection')}>
                            <div className="flex items-start gap-3">
                                <div className="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
                                    <Shield className="w-5 h-5 text-slate-900" />
                                </div>
                                <div className="text-sm text-text-muted space-y-1">
                                    <p>{__('general.the_platform_automatically_detects_account_sharing_by_monitoring')}<strong className="text-text-primary">{__('general.concurrent_active_sessions')}</strong>.</p>
                                    <p>{__('general.if_the_same_sub_user_account_is_detected_actively_using_the_platform_from')}<strong className="text-text-primary">2+ different IP addresses simultaneously</strong> (within a 5-minute window), the account is immediately flagged and blocked.</p>
                                    <p className="text-xs bg-surface-raised rounded-lg p-2 border border-border mt-2">
                                        🔄 <strong>{__('general.dynamic_ips_are_safe')}</strong> — the system only flags <em>simultaneous</em>{__('general.usage_not_ip_changes_between_sessions')}</p>
                                </div>
                            </div>
                        </OperationalCard>
                    </div>
                )}

                {/* ─── Sub-Users ─── */}
                {tab === 'users' && (
                    <OperationalCard noPadding>
                        {!subUsers?.data?.length ? (
                            <EmptyState icon={Users} title={__('general.no_sub_users_yet')} description={__('general.sub_users_appear_here_once_they_register_through_the_reseller_portal')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.user')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.status')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.anti_sharing')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.joined')}</th>
                                            <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {(subUsers.data as any).map((u: any) => (
                                            <tr key={u.id} className="hover:bg-surface-raised/50 transition-colors">
                                                <td className="px-4 py-3.5">
                                                    <p className="font-semibold text-text-primary">{u.user?.name}</p>
                                                    <p className="text-xs text-text-muted">{u.user?.email}</p>
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <StatusBadge status={statusMap[u.status] || 'neutral'} label={u.status.replace('_', ' ')} size="sm" />
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <div className="flex items-center gap-2">
                                                        <Switch
                                                            checked={u.sharing_check_enabled}
                                                            onCheckedChange={() => toggleCheck(u.user_id)}
                                                        />
                                                        <span className={`text-xs ${u.sharing_check_enabled ? 'text-primary font-medium' : 'text-text-muted'}`}>
                                                            {u.sharing_check_enabled ? 'Protected' : 'Disabled'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5 text-xs text-text-muted">{u.joined_at}</td>
                                                <td className="px-4 py-3.5 text-end">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {u.status === 'active' ? (
                                                            <button onClick={() => suspendUser(u.user_id)} className="text-xs text-yellow-600 hover:underline font-medium flex items-center gap-1">
                                                                <UserX className="w-3.5 h-3.5" /> {__('general.suspend')}</button>
                                                        ) : (
                                                            <button onClick={() => activateUser(u.user_id)} className="text-xs text-slate-900 hover:underline font-medium flex items-center gap-1">
                                                                <UserCheck className="w-3.5 h-3.5" /> {__('general.activate')}</button>
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
                            <EmptyState icon={ShieldCheck} title={__('general.no_sharing_violations')} description={__('general.all_sub_users_are_within_policy')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <div className="px-4 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2 text-red-700 text-xs font-medium">
                                    <ShieldAlert className="w-4 h-4" />{__('general.these_accounts_were_caught_using_the_platform_simultaneously_from_multiple_ips_likely_account_sharing')}</div>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.user')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.flagged_ips')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.detected')}</th>
                                            <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.actions')}</th>
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
                                                <td className="px-4 py-3.5 text-end">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            onClick={() => clearFlag(u.user_id)}
                                                            className="text-xs text-primary hover:underline font-semibold flex items-center gap-1"
                                                        >
                                                            <RefreshCw className="w-3.5 h-3.5" />{__('general.clear_restore')}</button>
                                                        <button
                                                            onClick={() => suspendUser(u.user_id)}
                                                            className="text-xs text-red-600 hover:underline font-semibold flex items-center gap-1"
                                                        >
                                                            <UserX className="w-3.5 h-3.5" /> {__('general.suspend')}</button>
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
                            <EmptyState icon={DollarSign} title={__('general.no_transactions_yet')} description={__('general.balance_top_ups_and_usage_charges_will_appear_here')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60">
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.type')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.description')}</th>
                                            <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.sub_user')}</th>
                                            <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.amount')}</th>
                                            <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.balance_after')}</th>
                                            <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {(transactions.data as any).map((t: any) => {
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
                                                    <td className="px-4 py-3 text-end font-mono text-xs">
                                                        <span className={t.amount >= 0 ? 'text-slate-900' : 'text-red-600'}>
                                                            {t.amount >= 0 ? '+' : ''}{formatCurrency(t.amount, t.currency)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-end font-mono text-xs text-text-muted">
                                                        {formatCurrency(t.balance_after, t.currency)}
                                                    </td>
                                                    <td className="px-4 py-3 text-end text-xs text-text-muted">{t.created_at}</td>
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
                        <OperationalCard title={__('general.adjust_reseller_balance')}>
                            <p className="text-xs text-text-muted mb-4">
                                Current balance: <strong className={`font-mono ${reseller.balance <= 0 ? 'text-red-600' : 'text-slate-900'}`}>{formatCurrency(reseller.balance, reseller.currency)}</strong>
                            </p>
                            <form onSubmit={submitBalance} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="type">{__('general.transaction_type')}</Label>
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
                                        placeholder={__('general.reason_for_this_adjustment')}
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
