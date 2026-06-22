import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { EmptyState } from '@/Components/ui/EmptyState';
import {
    Users, DollarSign, Plus, Search, Store, AlertTriangle,
    CheckCircle, XCircle, BarChart3, Settings, Building2,
    ShieldAlert, Wifi
} from 'lucide-react';

const statusMap: Record<string, string> = {
    active: 'success',
    suspended: 'danger',
    inactive: 'neutral',
};

import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: BarChart3, href: '/admin', isActive: false },
    { id: 'clients', label: 'Clients', icon: Users, href: '/admin/clients', isActive: false },
    { id: 'resellers', label: 'Resellers', icon: Store, href: '/admin/resellers', isActive: true },
    { id: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings', isActive: false },
];

export default function ResellersIndex({ resellers, meta }: any) {
    const [search, setSearch] = useState('');

    const filtered = (resellers?.data ?? []).filter((r: any) =>
        r.name.toLowerCase().includes(search.toLowerCase()) ||
        r.user?.email?.toLowerCase().includes(search.toLowerCase())
    );

    const totalBalance = filtered.reduce((s: number, r: any) => s + (r.balance || 0), 0);
    const totalUsers   = filtered.reduce((s: number, r: any) => s + (r.active_users || 0), 0);
    const flaggedCount = filtered.reduce((s: number, r: any) => s + (r.sharing_flagged || 0), 0);

    function handleSuspend(id: number) {
        router.put(`/admin/resellers/${id}`, { status: 'suspended' }, { preserveScroll: true });
    }

    function handleActivate(id: number) {
        router.put(`/admin/resellers/${id}`, { status: 'active' }, { preserveScroll: true });
    }

    return (
        <WorkspaceLayout title={__('general.resellers')} workspaceName="Musoftware Admin" tenantId="SYS-ADMIN" menuItems={menuItems}>
            <Head title={__('general.tool_resellers')} />
            <div className="space-y-6">
                <ModulePageHeader
                    title={__('general.tool_resellers')}
                    description={__('general.manage_b2b_reseller_accounts_balance_sub_users_and_anti_sharing_protection')}
                    actions={
                        <Link href="/admin/resellers/create">
                            <Button size="sm" className="gap-1.5">
                                <Plus className="w-3.5 h-3.5" />{__('general.new_reseller')}</Button>
                        </Link>
                    }
                />

                {/* Stats Row */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <OperationalCard className="flex items-center gap-4">
                        <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <Store className="w-5 h-5 text-primary" />
                        </div>
                        <div>
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">{__('general.total_resellers')}</p>
                            <p className="text-2xl font-bold text-text-primary">{filtered.length}</p>
                        </div>
                    </OperationalCard>
                    <OperationalCard className="flex items-center gap-4">
                        <div className="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                            <Users className="w-5 h-5 text-emerald-500" />
                        </div>
                        <div>
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">{__('general.active_sub_users')}</p>
                            <p className="text-2xl font-bold text-text-primary">{totalUsers}</p>
                        </div>
                    </OperationalCard>
                    <OperationalCard className="flex items-center gap-4">
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${flaggedCount > 0 ? 'bg-red-500/10' : 'bg-slate-100'}`}>
                            <ShieldAlert className={`w-5 h-5 ${flaggedCount > 0 ? 'text-red-500' : 'text-slate-400'}`} />
                        </div>
                        <div>
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">{__('general.sharing_flags')}</p>
                            <p className={`text-2xl font-bold ${flaggedCount > 0 ? 'text-red-500' : 'text-text-primary'}`}>{flaggedCount}</p>
                        </div>
                    </OperationalCard>
                </div>

                {/* Search */}
                <div className="relative max-w-sm">
                    <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                    <Input
                        placeholder={__('general.search_resellers')}
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        className="ps-9"
                    />
                </div>

                {/* Table */}
                <OperationalCard noPadding>
                    {filtered.length === 0 ? (
                        <EmptyState icon={Store} title={__('general.no_resellers_yet')} description={__('general.create_your_first_reseller_account_to_get_started')} action="/admin/resellers/create" actionLabel="New Reseller" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border/60">
                                        <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.reseller')}</th>
                                        <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.balance')}</th>
                                        <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.sub_users')}</th>
                                        <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.sharing_flags')}</th>
                                        <th className="text-start px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.status')}</th>
                                        <th className="text-end px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">{__('general.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/40">
                                    {filtered.map((r: any) => (
                                        <tr key={r.id} className="hover:bg-surface-raised/50 transition-colors">
                                            <td className="px-4 py-3.5">
                                                <p className="font-semibold text-text-primary">{r.name}</p>
                                                <p className="text-xs text-text-muted mt-0.5">{r.user?.email}</p>
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <span className={`font-mono font-semibold text-sm ${r.balance <= 0 ? 'text-red-500' : 'text-emerald-600'}`}>
                                                    {formatCurrency(r.balance, r.currency)}
                                                </span>
                                                {r.balance <= 0 && (
                                                    <span className="ms-2 text-[10px] font-bold bg-red-100 text-red-600 px-1.5 py-0.5 rounded">{__('general.empty')}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <div className="flex items-center gap-1.5">
                                                    <Users className="w-3.5 h-3.5 text-text-muted" />
                                                    <span className="font-medium">{r.active_users}</span>
                                                    <span className="text-text-muted">/ {r.total_users}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3.5">
                                                {r.sharing_flagged > 0 ? (
                                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">
                                                        <ShieldAlert className="w-3 h-3" /> {r.sharing_flagged} flagged
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 text-xs text-emerald-600">
                                                        <CheckCircle className="w-3.5 h-3.5" /> {__('general.clean')}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <StatusBadge status={statusMap[r.status] || 'neutral'} label={r.status} size="sm" />
                                            </td>
                                            <td className="px-4 py-3.5 text-end">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={`/admin/resellers/${r.id}`}
                                                        className="text-xs font-semibold text-primary hover:underline"
                                                    >
                                                        {__('general.manage')}</Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </OperationalCard>
            </div>
        </WorkspaceLayout>
    );
}
