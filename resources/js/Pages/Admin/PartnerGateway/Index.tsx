import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Key,
  Plus,
  Edit2,
  Trash2,
  DollarSign,
  Users,
  Layers,
  RefreshCw,
  Copy,
  Check,
  Search,
  CheckCircle,
  XCircle,
  TrendingUp,
} from 'lucide-react';

interface PartnerClientItem {
  id: number;
  user_id: number | null;
  client_name: string;
  client_key: string;
  wallet_balance: number;
  pricing_model: string;
  cost_per_message: number;
  low_balance_threshold: number;
  is_active: boolean;
  active_leases_count?: number;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  } | null;
}

interface UserOption {
  id: number;
  name: string;
  email: string;
}

interface Totals {
  total_clients: number;
  active_clients: number;
  total_balance_usd: number;
  active_leases_count: number;
}

interface Props {
  clients: {
    data: PartnerClientItem[];
    links: any[];
    total: number;
    current_page: number;
    last_page: number;
  };
  totals: Totals;
  users: UserOption[];
  filters: {
    search?: string;
  };
}

export default function AdminPartnerGatewayIndex({ clients, totals, users, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [adjustModalOpen, setAdjustModalOpen] = useState(false);
  const [selectedClient, setSelectedClient] = useState<PartnerClientItem | null>(null);
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  // Form for creating a new partner client
  const createForm = useForm({
    user_id: '',
    client_name: '',
    initial_balance: '0',
    cost_per_message: '0.0100',
    pricing_model: 'PAYG_PER_MSG',
    low_balance_threshold: '10.00',
  });

  // Form for editing partner settings
  const editForm = useForm({
    client_name: '',
    cost_per_message: '0.0100',
    pricing_model: 'PAYG_PER_MSG',
    low_balance_threshold: '10.00',
    is_active: true,
  });

  // Form for adjusting balance
  const adjustForm = useForm({
    amount: '',
    reason: '',
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route('admin.partner-gateway.index'), { search }, { preserveState: true });
  };

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedKey(text);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  const openEditModal = (client: PartnerClientItem) => {
    setSelectedClient(client);
    editForm.setData({
      client_name: client.client_name,
      cost_per_message: String(client.cost_per_message),
      pricing_model: client.pricing_model,
      low_balance_threshold: String(client.low_balance_threshold),
      is_active: client.is_active,
    });
    setEditModalOpen(true);
  };

  const openAdjustModal = (client: PartnerClientItem) => {
    setSelectedClient(client);
    adjustForm.setData({
      amount: '',
      reason: '',
    });
    setAdjustModalOpen(true);
  };

  const submitCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post(route('admin.partner-gateway.store'), {
      onSuccess: () => {
        setCreateModalOpen(false);
        createForm.reset();
      },
    });
  };

  const submitEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedClient) return;
    editForm.put(route('admin.partner-gateway.update', selectedClient.id), {
      onSuccess: () => {
        setEditModalOpen(false);
      },
    });
  };

  const submitAdjust = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedClient) return;
    adjustForm.post(route('admin.partner-gateway.adjust-balance', selectedClient.id), {
      onSuccess: () => {
        setAdjustModalOpen(false);
        adjustForm.reset();
      },
    });
  };

  const handleRotateSecret = (client: PartnerClientItem) => {
    if (confirm(`Are you sure you want to regenerate the API secret for "${client.client_name}"? Existing integrations will need to update their secret.`)) {
      router.post(route('admin.partner-gateway.regenerate-secret', client.id));
    }
  };

  const handleDelete = (client: PartnerClientItem) => {
    if (confirm(`Are you sure you want to delete "${client.client_name}"?`)) {
      router.delete(route('admin.partner-gateway.destroy', client.id));
    }
  };

  return (
    <AdminSidebarLayout>
      <Head title="Partner Gateway (B2B SaaS Metering)" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Partner Gateway Management</h1>
            <p className="text-sm text-muted-foreground mt-1">
              Activate and manage B2B partner API credentials, credit leases, and message metering.
            </p>
          </div>
          <Button onClick={() => setCreateModalOpen(true)} className="gap-2">
            <Plus className="w-4 h-4" />
            Activate Partner Account
          </Button>
        </div>

        {/* Metric Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Total Partners</span>
              <Users className="w-4 h-4" />
            </div>
            <div className="text-2xl font-bold">{totals.total_clients}</div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Active Partners</span>
              <CheckCircle className="w-4 h-4 text-emerald-500" />
            </div>
            <div className="text-2xl font-bold">{totals.active_clients}</div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Total Balances (USD)</span>
              <DollarSign className="w-4 h-4 text-primary" />
            </div>
            <div className="text-2xl font-bold">
              ${totals.total_balance_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Active Credit Leases</span>
              <Layers className="w-4 h-4 text-blue-500" />
            </div>
            <div className="text-2xl font-bold">{totals.active_leases_count}</div>
          </div>
        </div>

        {/* Search Bar */}
        <form onSubmit={handleSearch} className="flex gap-2 max-w-md">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Search by client name, key, user..."
              className="pl-9"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <Button type="submit" variant="secondary">
            Search
          </Button>
        </form>

        {/* Table */}
        <div className="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wider border-b">
                <tr>
                  <th className="px-4 py-3">Partner Client</th>
                  <th className="px-4 py-3">Owner User</th>
                  <th className="px-4 py-3">Client Key</th>
                  <th className="px-4 py-3">Rate / Msg</th>
                  <th className="px-4 py-3">Wallet Balance</th>
                  <th className="px-4 py-3">Active Leases</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {clients.data.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-4 py-8 text-center text-muted-foreground">
                      No partner clients found. Click "Activate Partner Account" to create one.
                    </td>
                  </tr>
                ) : (
                  clients.data.map((client) => (
                    <tr key={client.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-4 py-3 font-medium">
                        <div className="font-semibold">{client.client_name}</div>
                        <div className="text-xs text-muted-foreground">{client.pricing_model}</div>
                      </td>
                      <td className="px-4 py-3">
                        {client.user ? (
                          <div>
                            <div className="font-medium text-foreground">{client.user.name}</div>
                            <div className="text-xs text-muted-foreground">{client.user.email}</div>
                          </div>
                        ) : (
                          <span className="text-xs text-muted-foreground italic">System / Direct</span>
                        )}
                      </td>
                      <td className="px-4 py-3 font-mono text-xs">
                        <div className="flex items-center gap-1.5">
                          <span>{client.client_key.substring(0, 16)}...</span>
                          <button
                            type="button"
                            onClick={() => handleCopy(client.client_key)}
                            className="p-1 rounded hover:bg-muted text-muted-foreground"
                            title="Copy Client Key"
                          >
                            {copiedKey === client.client_key ? (
                              <Check className="w-3.5 h-3.5 text-emerald-500" />
                            ) : (
                              <Copy className="w-3.5 h-3.5" />
                            )}
                          </button>
                        </div>
                      </td>
                      <td className="px-4 py-3 font-mono">
                        ${Number(client.cost_per_message).toFixed(4)}
                      </td>
                      <td className="px-4 py-3 font-bold font-mono">
                        <span className={Number(client.wallet_balance) <= Number(client.low_balance_threshold) ? 'text-amber-500' : 'text-emerald-500'}>
                          ${Number(client.wallet_balance).toFixed(4)}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-500/10 text-blue-500">
                          {client.active_leases_count || 0} active
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        {client.is_active ? (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-500">
                            <CheckCircle className="w-3 h-3" /> Active
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-destructive/10 text-destructive">
                            <XCircle className="w-3 h-3" /> Inactive
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => openAdjustModal(client)}
                            title="Adjust / Top-Up Balance"
                          >
                            <DollarSign className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => openEditModal(client)}
                            title="Edit Settings"
                          >
                            <Edit2 className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleRotateSecret(client)}
                            title="Rotate Secret Key"
                          >
                            <RefreshCw className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDelete(client)}
                            title="Delete Partner Client"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Create Partner Modal */}
      <Dialog open={createModalOpen} onOpenChange={setCreateModalOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <form onSubmit={submitCreate}>
            <DialogHeader>
              <DialogTitle>Activate Partner Account</DialogTitle>
              <DialogDescription>
                Generate API credentials and set pricing parameters for a client user.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div>
                <Label htmlFor="user_id">Select User / Client *</Label>
                <select
                  id="user_id"
                  className="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                  value={createForm.data.user_id}
                  onChange={(e) => createForm.setData('user_id', e.target.value)}
                  required
                >
                  <option value="">-- Choose User --</option>
                  {users.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} ({u.email})
                    </option>
                  ))}
                </select>
                {createForm.errors.user_id && (
                  <p className="text-xs text-destructive mt-1">{createForm.errors.user_id}</p>
                )}
              </div>

              <div>
                <Label htmlFor="client_name">Application / Client Name *</Label>
                <Input
                  id="client_name"
                  placeholder="e.g. Trenz Agency CRM"
                  value={createForm.data.client_name}
                  onChange={(e) => createForm.setData('client_name', e.target.value)}
                  required
                />
                {createForm.errors.client_name && (
                  <p className="text-xs text-destructive mt-1">{createForm.errors.client_name}</p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="cost_per_message">Rate per Msg (USD) *</Label>
                  <Input
                    id="cost_per_message"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    value={createForm.data.cost_per_message}
                    onChange={(e) => createForm.setData('cost_per_message', e.target.value)}
                    required
                  />
                </div>

                <div>
                  <Label htmlFor="initial_balance">Initial Balance ($)</Label>
                  <Input
                    id="initial_balance"
                    type="number"
                    step="0.01"
                    min="0"
                    value={createForm.data.initial_balance}
                    onChange={(e) => createForm.setData('initial_balance', e.target.value)}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="pricing_model">Pricing Model</Label>
                  <select
                    id="pricing_model"
                    className="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    value={createForm.data.pricing_model}
                    onChange={(e) => createForm.setData('pricing_model', e.target.value)}
                  >
                    <option value="PAYG_PER_MSG">Pay As You Go</option>
                    <option value="SUBSCRIPTION">Subscription</option>
                    <option value="HYBRID">Hybrid</option>
                  </select>
                </div>

                <div>
                  <Label htmlFor="low_balance_threshold">Low Balance Alert ($)</Label>
                  <Input
                    id="low_balance_threshold"
                    type="number"
                    step="0.01"
                    min="0"
                    value={createForm.data.low_balance_threshold}
                    onChange={(e) => createForm.setData('low_balance_threshold', e.target.value)}
                  />
                </div>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCreateModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={createForm.processing}>
                Generate & Activate
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Edit Partner Modal */}
      <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <form onSubmit={submitEdit}>
            <DialogHeader>
              <DialogTitle>Edit Partner Settings</DialogTitle>
              <DialogDescription>
                Update rate per message, pricing model, and active state.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div>
                <Label htmlFor="edit_client_name">Client Name *</Label>
                <Input
                  id="edit_client_name"
                  value={editForm.data.client_name}
                  onChange={(e) => editForm.setData('client_name', e.target.value)}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="edit_cost">Rate per Msg ($)</Label>
                  <Input
                    id="edit_cost"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    value={editForm.data.cost_per_message}
                    onChange={(e) => editForm.setData('cost_per_message', e.target.value)}
                    required
                  />
                </div>

                <div>
                  <Label htmlFor="edit_threshold">Low Balance Alert ($)</Label>
                  <Input
                    id="edit_threshold"
                    type="number"
                    step="0.01"
                    min="0"
                    value={editForm.data.low_balance_threshold}
                    onChange={(e) => editForm.setData('low_balance_threshold', e.target.value)}
                    required
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="edit_pricing">Pricing Model</Label>
                <select
                  id="edit_pricing"
                  className="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                  value={editForm.data.pricing_model}
                  onChange={(e) => editForm.setData('pricing_model', e.target.value)}
                >
                  <option value="PAYG_PER_MSG">Pay As You Go</option>
                  <option value="SUBSCRIPTION">Subscription</option>
                  <option value="HYBRID">Hybrid</option>
                </select>
              </div>

              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="edit_is_active"
                  className="h-4 w-4 rounded border-gray-300 text-primary"
                  checked={editForm.data.is_active}
                  onChange={(e) => editForm.setData('is_active', e.target.checked)}
                />
                <Label htmlFor="edit_is_active" className="cursor-pointer">
                  Active (Allow API Requests & Credit Leases)
                </Label>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={editForm.processing}>
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Adjust Balance Modal */}
      <Dialog open={adjustModalOpen} onOpenChange={setAdjustModalOpen}>
        <DialogContent className="sm:max-w-[450px]">
          <form onSubmit={submitAdjust}>
            <DialogHeader>
              <DialogTitle>Adjust Partner Balance</DialogTitle>
              <DialogDescription>
                Credit or debit balance manually for {selectedClient?.client_name}. (Use negative amount to deduct).
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div>
                <Label htmlFor="adjust_amount">Adjustment Amount ($ USD) *</Label>
                <Input
                  id="adjust_amount"
                  type="number"
                  step="0.0001"
                  placeholder="e.g. 50.00 or -10.00"
                  value={adjustForm.data.amount}
                  onChange={(e) => adjustForm.setData('amount', e.target.value)}
                  required
                />
                <p className="text-xs text-muted-foreground mt-1">
                  Current Balance: ${Number(selectedClient?.wallet_balance || 0).toFixed(4)}
                </p>
              </div>

              <div>
                <Label htmlFor="adjust_reason">Reason / Reference Note *</Label>
                <Input
                  id="adjust_reason"
                  placeholder="e.g. Manual bank transfer deposit, promotional credit"
                  value={adjustForm.data.reason}
                  onChange={(e) => adjustForm.setData('reason', e.target.value)}
                  required
                />
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAdjustModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={adjustForm.processing}>
                Apply Adjustment
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AdminSidebarLayout>
  );
}
