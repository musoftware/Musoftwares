import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    CreditCard,
    Plus,
    Eye,
    Trash2,
    TrendingUp,
    Users,
    DollarSign,
    CheckCircle,
    XCircle,
    Percent,
} from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';

// ─── Types ────────────────────────────────────────────────────────────────────

interface ClientStats {
    total_payments: number;
    successful_count: number;
    total_volume: number;
    total_commission: number;
}

interface GatewayClient {
    id: number;
    name: string;
    client_id: string;
    website: string | null;
    status: 'active' | 'inactive';
    commission_rate: number;
    stats: ClientStats;
    created_at: string;
}

interface Totals {
    total_clients: number;
    active_clients: number;
    total_volume: number;
    total_commission: number;
    total_payments: number;
}

interface Props {
    clients: { data: GatewayClient[]; links: any[] };
    totals: Totals;
}

// ─── Empty form ───────────────────────────────────────────────────────────────
const emptyForm = {
    name: '',
    website: '',
    status: 'active',
    commission_rate: '40',
    allowed_ips: '',
};

// ─── Stat Card ────────────────────────────────────────────────────────────────
function StatCard({ icon: Icon, label, value, sub }: { icon: any; label: string; value: string; sub?: string }) {
    return (
        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-gray-400">{label}</p>
                    <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
                    {sub && <p className="mt-0.5 text-xs text-gray-400">{sub}</p>}
                </div>
                <div className="rounded-lg bg-black p-2">
                    <Icon className="h-5 w-5 text-white" />
                </div>
            </div>
        </div>
    );
}

// ─── Main Component ───────────────────────────────────────────────────────────
export default function Index({ clients, totals }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [formData, setFormData] = useState({ ...emptyForm });
    const [processing, setProcessing] = useState(false);

    const set = (key: string, value: string) =>
        setFormData(prev => ({ ...prev, [key]: value }));

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const payload: any = {
            name:            formData.name,
            website:         formData.website || null,
            status:          formData.status,
            commission_rate: parseFloat(formData.commission_rate),
        };

        if (formData.allowed_ips.trim()) {
            payload.allowed_ips = formData.allowed_ips
                .split(',')
                .map((ip: string) => ip.trim())
                .filter(Boolean);
        }

        router.post(route('admin.musoftware-clients.store'), payload, {
            onSuccess: () => { setIsCreateOpen(false); setFormData({ ...emptyForm }); },
            onFinish:  () => setProcessing(false),
        });
    };

    const handleDelete = (client: GatewayClient) => {
        if (!confirm(`Delete "${client.name}"? This cannot be undone.`)) return;
        router.delete(route('admin.musoftware-clients.destroy', client.id));
    };

    const items = clients?.data ?? [];

    return (
        <AdminSidebarLayout title="Payment Gateway" header="Payment Gateway Clients">
            <Head title="Admin — Payment Gateway" />

            {/* ── Stats Row ─────────────────────────────────────────────── */}
            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
                <StatCard icon={Users}      label="Total Clients"   value={String(totals.total_clients)}  sub={`${totals.active_clients} active`} />
                <StatCard icon={CheckCircle} label="Payments Done"  value={String(totals.total_payments)} />
                <StatCard icon={DollarSign} label="Total Volume"    value={formatCurrency(totals.total_volume, 'EGP')} />
                <StatCard icon={TrendingUp} label="Our Commission"  value={formatCurrency(totals.total_commission, 'EGP')} />
                <StatCard icon={Percent}    label="Default Rate"    value="40%" sub="Per payment" />
            </div>

            {/* ── Header bar ────────────────────────────────────────────── */}
            <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-gray-500">
                    <CreditCard className="h-4 w-4" />
                    <span>{items.length} client{items.length !== 1 ? 's' : ''}</span>
                </div>
                <Button onClick={() => setIsCreateOpen(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    New Client
                </Button>
            </div>

            {/* ── Table ─────────────────────────────────────────────────── */}
            <div className="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Client</th>
                            <th className="p-4 font-medium text-gray-600">Client ID</th>
                            <th className="p-4 font-medium text-gray-600">Commission</th>
                            <th className="p-4 font-medium text-gray-600">Payments</th>
                            <th className="p-4 font-medium text-gray-600">Volume</th>
                            <th className="p-4 font-medium text-gray-600">Our Earnings</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map(client => (
                            <tr key={client.id} className="border-b hover:bg-gray-50 transition-colors">
                                <td className="p-4">
                                    <div className="font-semibold text-gray-900">{client.name}</div>
                                    {client.website && (
                                        <div className="text-xs text-gray-400 truncate max-w-[160px]">
                                            {client.website}
                                        </div>
                                    )}
                                </td>
                                <td className="p-4">
                                    <code className="rounded bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-700">
                                        {client.client_id}
                                    </code>
                                </td>
                                <td className="p-4">
                                    <span className="inline-flex items-center gap-1 rounded-full bg-black px-2.5 py-0.5 text-xs font-semibold text-white">
                                        {client.commission_rate}%
                                    </span>
                                </td>
                                <td className="p-4 text-gray-700">
                                    {client.stats.successful_count}
                                    <span className="ml-1 text-xs text-gray-400">
                                        / {client.stats.total_payments}
                                    </span>
                                </td>
                                <td className="p-4 font-medium text-gray-900">
                                    {formatCurrency(client.stats.total_volume, 'EGP')}
                                </td>
                                <td className="p-4 font-semibold text-green-700">
                                    {formatCurrency(client.stats.total_commission, 'EGP')}
                                </td>
                                <td className="p-4">
                                    {client.status === 'active' ? (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                            <CheckCircle className="h-3 w-3" /> Active
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                            <XCircle className="h-3 w-3" /> Inactive
                                        </span>
                                    )}
                                </td>
                                <td className="p-4 text-right space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.visit(route('admin.musoftware-clients.show', client.id))}
                                    >
                                        <Eye className="h-3.5 w-3.5 mr-1" /> View
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(client)}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {items.length === 0 && (
                            <tr>
                                <td colSpan={8} className="p-12 text-center">
                                    <CreditCard className="mx-auto mb-3 h-10 w-10 text-gray-200" />
                                    <p className="font-medium text-gray-400">No clients yet</p>
                                    <p className="mt-1 text-sm text-gray-300">Create your first gateway client to get started.</p>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* ── Pagination ────────────────────────────────────────────── */}
            {clients?.links && (
                <div className="mt-4 flex justify-center gap-1">
                    {clients.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`px-3 py-1 rounded text-sm border ${
                                link.active
                                    ? 'bg-black text-white border-black'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 disabled:opacity-40'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {/* ── Create Modal ──────────────────────────────────────────── */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Create Gateway Client</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreate}>
                        <div className="space-y-4 py-2">
                            <div>
                                <Label htmlFor="name">Business / App Name *</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={e => set('name', e.target.value)}
                                    placeholder="e.g. Zara Egypt Store"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="website">Website URL</Label>
                                <Input
                                    id="website"
                                    type="url"
                                    value={formData.website}
                                    onChange={e => set('website', e.target.value)}
                                    placeholder="https://example.com"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="commission_rate">Commission Rate (%)</Label>
                                    <Input
                                        id="commission_rate"
                                        type="number"
                                        min="1"
                                        max="100"
                                        step="0.5"
                                        value={formData.commission_rate}
                                        onChange={e => set('commission_rate', e.target.value)}
                                        required
                                    />
                                    <p className="mt-1 text-xs text-gray-400">
                                        Default: 40% → Musoftware earns {formData.commission_rate}% per payment
                                    </p>
                                </div>
                                <div>
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                                        value={formData.status}
                                        onChange={e => set('status', e.target.value)}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="allowed_ips">Allowed IPs (optional)</Label>
                                <Input
                                    id="allowed_ips"
                                    value={formData.allowed_ips}
                                    onChange={e => set('allowed_ips', e.target.value)}
                                    placeholder="192.168.1.1, 10.0.0.1"
                                />
                                <p className="mt-1 text-xs text-gray-400">Comma-separated. Leave empty to allow all IPs.</p>
                            </div>
                        </div>
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating…' : 'Create Client'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
