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

function formatCurrency(amount: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
}

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
        <WorkspaceLayout title="Resellers" workspaceName="Musoftware Admin" tenantId="SYS-ADMIN" menuItems={menuItems}>
            <Head title="Tool Resellers" />
            <div className="space-y-6">
                <ModulePageHeader
                    title="Tool Resellers"
                    description="Manage B2B reseller accounts — balance, sub-users, and anti-sharing protection."
                    actions={
                        <Link href="/admin/resellers/create">
                            <Button size="sm" className="gap-1.5">
                                <Plus className="w-3.5 h-3.5" /> New Reseller
                            </Button>
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
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">Total Resellers</p>
                            <p className="text-2xl font-bold text-text-primary">{filtered.length}</p>
                        </div>
                    </OperationalCard>
                    <OperationalCard className="flex items-center gap-4">
                        <div className="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                            <Users className="w-5 h-5 text-emerald-500" />
                        </div>
                        <div>
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">Active Sub-Users</p>
                            <p className="text-2xl font-bold text-text-primary">{totalUsers}</p>
                        </div>
                    </OperationalCard>
                    <OperationalCard className="flex items-center gap-4">
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${flaggedCount > 0 ? 'bg-red-500/10' : 'bg-slate-100'}`}>
                            <ShieldAlert className={`w-5 h-5 ${flaggedCount > 0 ? 'text-red-500' : 'text-slate-400'}`} />
                        </div>
                        <div>
                            <p className="text-xs text-text-muted font-medium uppercase tracking-wider">Sharing Flags</p>
                            <p className={`text-2xl font-bold ${flaggedCount > 0 ? 'text-red-500' : 'text-text-primary'}`}>{flaggedCount}</p>
                        </div>
                    </OperationalCard>
                </div>

                {/* Search */}
                <div className="relative max-w-sm">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                    <Input
                        placeholder="Search resellers..."
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        className="pl-9"
                    />
                </div>

                {/* Table */}
                <OperationalCard noPadding>
                    {filtered.length === 0 ? (
                        <EmptyState icon={Store} title="No resellers yet" description="Create your first reseller account to get started." action="/admin/resellers/create" actionLabel="New Reseller" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border/60">
                                        <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Reseller</th>
                                        <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Balance</th>
                                        <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Sub-Users</th>
                                        <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Sharing Flags</th>
                                        <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                                        <th className="text-right px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Actions</th>
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
                                                    <span className="ml-2 text-[10px] font-bold bg-red-100 text-red-600 px-1.5 py-0.5 rounded">EMPTY</span>
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
                                                        <CheckCircle className="w-3.5 h-3.5" /> Clean
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <StatusBadge status={statusMap[r.status] || 'neutral'} label={r.status} size="sm" />
                                            </td>
                                            <td className="px-4 py-3.5 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={`/admin/resellers/${r.id}`}
                                                        className="text-xs font-semibold text-primary hover:underline"
                                                    >
                                                        Manage
                                                    </Link>
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
