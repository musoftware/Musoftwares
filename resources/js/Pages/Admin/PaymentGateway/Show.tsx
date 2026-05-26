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
    ArrowLeft,
    Copy,
    Check,
    RefreshCw,
    DollarSign,
    TrendingUp,
    CreditCard,
    Activity,
    Code,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Clock,
} from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface GatewayClient {
    id: number;
    name: string;
    client_id: string;
    client_secret: string;
    webhook_secret: string;
    website: string | null;
    status: string;
    commission_rate: number;
    allowed_ips: string[];
    created_at: string;
}

interface GatewayPayment {
    id: number;
    internal_order_id: string;
    external_order_id: string;
    amount: number;
    currency: string;
    commission_rate: number;
    commission_amount: number;
    net_amount: number;
    description: string | null;
    customer_name: string | null;
    customer_email: string | null;
    status: string;
    status_badge: { label: string; color: string };
    kashier_transaction_id: string | null;
    created_at: string;
}

interface Stats {
    total_payments: number;
    successful_count: number;
    pending_count: number;
    failed_count: number;
    total_volume: number;
    total_commission: number;
    total_net: number;
    commission_rate: number;
}

interface Props {
    client: GatewayClient;
    payments: { data: GatewayPayment[]; links: any[] };
    stats: Stats;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function useCopy(timeout = 2000) {
    const [copied, setCopied] = useState<string | null>(null);
    const copy = (text: string, key: string) => {
        navigator.clipboard.writeText(text);
        setCopied(key);
        setTimeout(() => setCopied(null), timeout);
    };
    return { copied, copy };
}

function StatCard({ icon: Icon, label, value, sub, accent }: {
    icon: any; label: string; value: string; sub?: string; accent?: string;
}) {
    return (
        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-gray-400">{label}</p>
                    <p className={`mt-1 text-2xl font-bold ${accent ?? 'text-gray-900'}`}>{value}</p>
                    {sub && <p className="mt-0.5 text-xs text-gray-400">{sub}</p>}
                </div>
                <div className="rounded-lg bg-black p-2">
                    <Icon className="h-5 w-5 text-white" />
                </div>
            </div>
        </div>
    );
}

function SecretField({ label, value, copyKey, copied, onCopy }: {
    label: string; value: string; copyKey: string;
    copied: string | null; onCopy: (text: string, key: string) => void;
}) {
    const [visible, setVisible] = useState(false);
    const masked = '•'.repeat(Math.min(value.length, 32));

    return (
        <div>
            <Label className="text-xs text-gray-500">{label}</Label>
            <div className="mt-1 flex items-center gap-2">
                <code className="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-800 break-all">
                    {visible ? value : masked}
                </code>
                <button
                    type="button"
                    onClick={() => setVisible(v => !v)}
                    className="rounded px-2 py-1 text-xs text-gray-400 hover:text-gray-700 border border-gray-200"
                >
                    {visible ? 'Hide' : 'Show'}
                </button>
                <button
                    type="button"
                    onClick={() => onCopy(value, copyKey)}
                    className="rounded p-1.5 text-gray-400 hover:text-black border border-gray-200"
                >
                    {copied === copyKey
                        ? <Check className="h-3.5 w-3.5 text-green-600" />
                        : <Copy className="h-3.5 w-3.5" />
                    }
                </button>
            </div>
        </div>
    );
}

function StatusBadge({ status }: { status: string }) {
    const map: Record<string, { label: string; cls: string; icon: any }> = {
        success:   { label: 'Success',   cls: 'bg-green-100 text-green-700',  icon: CheckCircle },
        failed:    { label: 'Failed',    cls: 'bg-red-100 text-red-700',      icon: XCircle },
        cancelled: { label: 'Cancelled', cls: 'bg-gray-100 text-gray-500',    icon: XCircle },
        pending:   { label: 'Pending',   cls: 'bg-yellow-100 text-yellow-700', icon: Clock },
    };
    const { label, cls, icon: Icon } = map[status] ?? map.pending;
    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${cls}`}>
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}

// ─── Integration Code Snippet ─────────────────────────────────────────────────
function CodeSnippet({ clientId, clientSecret }: { clientId: string; clientSecret: string }) {
    const code = `// Musoftware Payment Gateway — PHP Integration Example
$response = Http::withHeaders([
    'X-Client-Id'     => '${clientId}',
    'X-Client-Secret' => '${clientSecret}',
])->post('${window.location.origin}/api/payment-gateway/initiate', [
    'order_id'    => 'ORDER_' . uniqid(),
    'amount'      => 150.00,
    'currency'    => 'EGP',
    'description' => 'Product purchase',
    'success_url' => 'https://yoursite.com/payment/success',
    'failure_url' => 'https://yoursite.com/payment/failure',
    'webhook_url' => 'https://yoursite.com/webhooks/payment',
    'customer'    => [
        'name'  => 'Ahmed Ali',
        'email' => 'ahmed@example.com',
        'phone' => '01012345678',
    ],
]);

$paymentUrl = $response->json('payment_url');
return redirect()->away($paymentUrl); // → Redirect customer to Kashier`;

    return (
        <pre className="overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-green-400 leading-relaxed">
            <code>{code}</code>
        </pre>
    );
}

// ─── Main Component ───────────────────────────────────────────────────────────
export default function Show({ client, payments, stats }: Props) {
    const { copied, copy } = useCopy();
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isRegenOpen, setIsRegenOpen] = useState(false);
    const [isDocsOpen, setIsDocsOpen] = useState(false);
    const [editForm, setEditForm] = useState({
        name:            client.name,
        website:         client.website ?? '',
        status:          client.status,
        commission_rate: String(client.commission_rate),
        allowed_ips:     (client.allowed_ips ?? []).join(', '),
    });

    const setEdit = (key: string, value: string) =>
        setEditForm(prev => ({ ...prev, [key]: value }));

    const handleEdit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('admin.musoftware-clients.update', client.id), {
            name:            editForm.name,
            website:         editForm.website || null,
            status:          editForm.status,
            commission_rate: parseFloat(editForm.commission_rate),
            allowed_ips:     editForm.allowed_ips
                ? editForm.allowed_ips.split(',').map(ip => ip.trim()).filter(Boolean)
                : null,
        }, {
            onSuccess: () => setIsEditOpen(false),
        });
    };

    const handleRegenerate = () => {
        router.post(route('admin.musoftware-clients.regenerate-secret', client.id), {}, {
            onSuccess: () => setIsRegenOpen(false),
        });
    };

    const items = payments?.data ?? [];

    return (
        <AdminSidebarLayout title={client.name} header="Payment Gateway">
            <Head title={`Gateway — ${client.name}`} />

            {/* ── Back + Header ──────────────────────────────────────────── */}
            <div className="mb-6 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" onClick={() => router.visit(route('admin.musoftware-clients.index'))}>
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold text-gray-900">{client.name}</h1>
                        <p className="text-sm text-gray-400">{client.website ?? 'No website'}</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => setIsDocsOpen(true)}>
                        <Code className="h-4 w-4 mr-1" /> Integration Docs
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => setIsEditOpen(true)}>
                        Edit Client
                    </Button>
                    <Button variant="destructive" size="sm" onClick={() => setIsRegenOpen(true)}>
                        <RefreshCw className="h-4 w-4 mr-1" /> Regenerate Secrets
                    </Button>
                </div>
            </div>

            {/* ── Stats Row ─────────────────────────────────────────────── */}
            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    icon={DollarSign}
                    label="Total Volume"
                    value={`EGP ${stats.total_volume.toLocaleString('en', { minimumFractionDigits: 2 })}`}
                    sub={`${stats.successful_count} successful payments`}
                />
                <StatCard
                    icon={TrendingUp}
                    label={`Our Commission (${stats.commission_rate}%)`}
                    value={`EGP ${stats.total_commission.toLocaleString('en', { minimumFractionDigits: 2 })}`}
                    accent="text-green-700"
                />
                <StatCard
                    icon={CreditCard}
                    label="Client Net (60%)"
                    value={`EGP ${stats.total_net.toLocaleString('en', { minimumFractionDigits: 2 })}`}
                />
                <StatCard
                    icon={Activity}
                    label="Payments"
                    value={String(stats.total_payments)}
                    sub={`${stats.pending_count} pending · ${stats.failed_count} failed`}
                />
            </div>

            {/* ── Credentials Card ──────────────────────────────────────── */}
            <div className="mb-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <h2 className="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wider">
                    API Credentials
                </h2>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <SecretField
                        label="Client ID"
                        value={client.client_id}
                        copyKey="client_id"
                        copied={copied}
                        onCopy={copy}
                    />
                    <SecretField
                        label="Client Secret"
                        value={client.client_secret}
                        copyKey="client_secret"
                        copied={copied}
                        onCopy={copy}
                    />
                    <SecretField
                        label="Webhook Secret"
                        value={client.webhook_secret}
                        copyKey="webhook_secret"
                        copied={copied}
                        onCopy={copy}
                    />
                </div>
                <div className="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3 flex items-start gap-2">
                    <AlertTriangle className="h-4 w-4 text-amber-500 mt-0.5 flex-shrink-0" />
                    <p className="text-xs text-amber-700">
                        Keep these secrets confidential. Use the webhook secret to verify incoming payment notifications from Musoftware.
                    </p>
                </div>
            </div>

            {/* ── Payments Table ────────────────────────────────────────── */}
            <div className="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                <div className="border-b bg-gray-50 px-5 py-3">
                    <h2 className="text-sm font-semibold text-gray-700">Payment History</h2>
                </div>
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Order ID</th>
                            <th className="p-4 font-medium text-gray-600">Customer</th>
                            <th className="p-4 font-medium text-gray-600">Amount</th>
                            <th className="p-4 font-medium text-gray-600">Commission</th>
                            <th className="p-4 font-medium text-gray-600">Net to Client</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map(p => (
                            <tr key={p.id} className="border-b hover:bg-gray-50 transition-colors">
                                <td className="p-4">
                                    <div className="font-mono text-xs text-gray-700">{p.internal_order_id}</div>
                                    <div className="text-xs text-gray-400">ext: {p.external_order_id}</div>
                                </td>
                                <td className="p-4">
                                    <div className="text-sm text-gray-800">{p.customer_name ?? '—'}</div>
                                    <div className="text-xs text-gray-400">{p.customer_email ?? ''}</div>
                                </td>
                                <td className="p-4 font-semibold text-gray-900">
                                    {p.amount.toLocaleString('en', { minimumFractionDigits: 2 })}
                                    <span className="ml-1 text-xs font-normal text-gray-400">{p.currency}</span>
                                </td>
                                <td className="p-4 font-medium text-green-700">
                                    {p.commission_amount.toLocaleString('en', { minimumFractionDigits: 2 })}
                                    <span className="ml-1 text-xs text-gray-400">({p.commission_rate}%)</span>
                                </td>
                                <td className="p-4 text-gray-700">
                                    {p.net_amount.toLocaleString('en', { minimumFractionDigits: 2 })}
                                </td>
                                <td className="p-4">
                                    <StatusBadge status={p.status} />
                                </td>
                                <td className="p-4 text-xs text-gray-500">
                                    {new Date(p.created_at).toLocaleDateString('en', {
                                        day: '2-digit', month: 'short', year: 'numeric',
                                    })}
                                </td>
                            </tr>
                        ))}
                        {items.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-10 text-center text-gray-400">
                                    No payments yet for this client.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* ── Pagination ────────────────────────────────────────────── */}
            {payments?.links && (
                <div className="mt-4 flex justify-center gap-1">
                    {payments.links.map((link, i) => (
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

            {/* ── Edit Modal ────────────────────────────────────────────── */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Edit Client</DialogTitle></DialogHeader>
                    <form onSubmit={handleEdit}>
                        <div className="space-y-4 py-2">
                            <div>
                                <Label>Name</Label>
                                <Input value={editForm.name} onChange={e => setEdit('name', e.target.value)} required />
                            </div>
                            <div>
                                <Label>Website</Label>
                                <Input type="url" value={editForm.website} onChange={e => setEdit('website', e.target.value)} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Commission Rate (%)</Label>
                                    <Input
                                        type="number" min="1" max="100" step="0.5"
                                        value={editForm.commission_rate}
                                        onChange={e => setEdit('commission_rate', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label>Status</Label>
                                    <select
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                        value={editForm.status}
                                        onChange={e => setEdit('status', e.target.value)}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <Label>Allowed IPs</Label>
                                <Input
                                    value={editForm.allowed_ips}
                                    onChange={e => setEdit('allowed_ips', e.target.value)}
                                    placeholder="192.168.1.1, 10.0.0.1"
                                />
                            </div>
                        </div>
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>Cancel</Button>
                            <Button type="submit">Save Changes</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ── Regenerate Secrets Confirm ────────────────────────────── */}
            <Dialog open={isRegenOpen} onOpenChange={setIsRegenOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Regenerate API Secrets?</DialogTitle></DialogHeader>
                    <div className="py-2">
                        <div className="rounded-lg bg-red-50 border border-red-200 p-3 flex items-start gap-2">
                            <AlertTriangle className="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" />
                            <p className="text-sm text-red-700">
                                The current <strong>client_secret</strong> and <strong>webhook_secret</strong> will be invalidated immediately.
                                You must update your integration code with the new credentials.
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsRegenOpen(false)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleRegenerate}>
                            <RefreshCw className="h-4 w-4 mr-1" /> Yes, Regenerate
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Integration Docs Modal ────────────────────────────────── */}
            <Dialog open={isDocsOpen} onOpenChange={setIsDocsOpen}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader><DialogTitle>Integration Guide — {client.name}</DialogTitle></DialogHeader>
                    <div className="max-h-[70vh] space-y-4 overflow-y-auto py-2 pr-1">
                        <div className="rounded-lg border border-gray-200 p-4 text-sm space-y-2">
                            <p className="font-semibold text-gray-700">1. Endpoint</p>
                            <code className="block rounded bg-gray-100 px-3 py-2 text-xs">
                                POST {window.location.origin}/api/payment-gateway/initiate
                            </code>
                            <p className="font-semibold text-gray-700 pt-2">2. Authentication Headers</p>
                            <code className="block rounded bg-gray-100 px-3 py-2 text-xs whitespace-pre">
                                {`X-Client-Id:     ${client.client_id}\nX-Client-Secret: ${client.client_secret}`}
                            </code>
                            <p className="font-semibold text-gray-700 pt-2">3. Status Check</p>
                            <code className="block rounded bg-gray-100 px-3 py-2 text-xs">
                                GET {window.location.origin}/api/payment-gateway/status/{'{order_id}'}
                            </code>
                        </div>
                        <CodeSnippet clientId={client.client_id} clientSecret={client.client_secret} />
                        <div className="rounded-lg border border-gray-200 p-4 text-sm">
                            <p className="font-semibold text-gray-700 mb-2">Webhook Verification (PHP)</p>
                            <pre className="overflow-x-auto rounded bg-gray-950 p-3 text-xs text-green-400">
{`$signature = hash_hmac('sha256', json_encode($payload), '${client.webhook_secret}');
$isValid = hash_equals($signature, $request->header('X-Gateway-Signature'));`}
                            </pre>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={() => setIsDocsOpen(false)}>Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
