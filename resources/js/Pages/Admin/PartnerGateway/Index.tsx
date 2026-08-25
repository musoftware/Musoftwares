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
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
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
  Eye,
  EyeOff,
  Terminal,
  ShieldCheck,
} from 'lucide-react';

interface PartnerClientItem {
  id: number;
  user_id: number | null;
  client_name: string;
  client_key: string;
  client_secret: string;
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
  const [credentialsModalClient, setCredentialsModalClient] = useState<PartnerClientItem | null>(null);
  const [showModalSecret, setShowModalSecret] = useState(false);
  const [revealedSecrets, setRevealedSecrets] = useState<Record<number, boolean>>({});
  const [copiedSection, setCopiedSection] = useState<string | null>(null);
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  const toggleSecretReveal = (id: number) => {
    setRevealedSecrets(prev => ({ ...prev, [id]: !prev[id] }));
  };

  const handleCopyNamed = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    setCopiedSection(label);
    setTimeout(() => setCopiedSection(null), 2000);
  };

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
                  <th className="px-4 py-3">API Credentials (Key & Secret)</th>
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
                      <td className="px-4 py-3 font-mono text-xs space-y-1 min-w-[220px]">
                        {/* Client Key */}
                        <div className="flex items-center gap-1.5 bg-muted/50 px-2 py-1 rounded border">
                          <span className="text-[10px] font-bold text-muted-foreground tracking-wider shrink-0">KEY:</span>
                          <span className="truncate max-w-[130px] select-all">{client.client_key.substring(0, 14)}...</span>
                          <button
                            type="button"
                            onClick={() => handleCopyNamed(client.client_key, `key-${client.id}`)}
                            className="p-1 rounded hover:bg-background text-muted-foreground ms-auto shrink-0"
                            title="Copy Client Key (pk_live_...)"
                          >
                            {copiedSection === `key-${client.id}` ? (
                              <Check className="w-3.5 h-3.5 text-emerald-500" />
                            ) : (
                              <Copy className="w-3.5 h-3.5" />
                            )}
                          </button>
                        </div>
                        {/* Client Secret */}
                        <div className="flex items-center gap-1.5 bg-amber-500/5 px-2 py-1 rounded border border-amber-500/20">
                          <span className="text-[10px] font-bold text-amber-600 dark:text-amber-400 tracking-wider shrink-0">SECRET:</span>
                          <span className="truncate max-w-[130px] text-amber-600 dark:text-amber-400 select-all">
                            {revealedSecrets[client.id] ? client.client_secret : '••••••••••••••••'}
                          </span>
                          <div className="flex items-center gap-0.5 ms-auto shrink-0">
                            <button
                              type="button"
                              onClick={() => toggleSecretReveal(client.id)}
                              className="p-1 rounded hover:bg-background text-muted-foreground"
                              title={revealedSecrets[client.id] ? "Hide Secret" : "Reveal Secret"}
                            >
                              {revealedSecrets[client.id] ? <EyeOff className="w-3.5 h-3.5 text-amber-500" /> : <Eye className="w-3.5 h-3.5" />}
                            </button>
                            <button
                              type="button"
                              onClick={() => handleCopyNamed(client.client_secret, `secret-${client.id}`)}
                              className="p-1 rounded hover:bg-background text-muted-foreground"
                              title="Copy Secret Key (sk_live_...)"
                            >
                              {copiedSection === `secret-${client.id}` ? (
                                <Check className="w-3.5 h-3.5 text-emerald-500" />
                              ) : (
                                <Copy className="w-3.5 h-3.5" />
                              )}
                            </button>
                          </div>
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
                            className="text-primary hover:bg-primary/10"
                            onClick={() => {
                              setCredentialsModalClient(client);
                              setShowModalSecret(false);
                            }}
                            title="View Full API Credentials & .env config"
                          >
                            <Key className="w-3.5 h-3.5" />
                          </Button>
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
              <div className="space-y-1.5">
                <Label htmlFor="user_id">Select User / Client *</Label>
                <PremiumCombobox
                  value={createForm.data.user_id}
                  onChange={(val) => {
                    const strVal = val ? String(val) : '';
                    createForm.setData('user_id', strVal);
                    if (strVal && !createForm.data.client_name) {
                      const u = users.find(usr => String(usr.id) === strVal);
                      if (u) {
                        createForm.setData(prev => ({
                          ...prev,
                          user_id: strVal,
                          client_name: prev.client_name || `${u.name} Partner Client`
                        }));
                      }
                    }
                  }}
                  options={users.map((u) => ({ value: String(u.id), label: `${u.name} (${u.email})` }))}
                  placeholder="-- Choose User --"
                  searchPlaceholder="Search user by name or email..."
                />
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

      {/* View Full API Credentials Modal */}
      <Dialog open={!!credentialsModalClient} onOpenChange={(open) => !open && setCredentialsModalClient(null)}>
        <DialogContent className="sm:max-w-[580px]">
          {credentialsModalClient && (
            <div>
              <DialogHeader>
                <div className="flex items-center gap-2 text-primary">
                  <Key className="w-5 h-5" />
                  <DialogTitle>Partner API Credentials</DialogTitle>
                </div>
                <DialogDescription>
                  Credentials and .env configuration snippet for <strong>{credentialsModalClient.client_name}</strong>.
                </DialogDescription>
              </DialogHeader>

              <div className="space-y-4 py-4 text-xs font-mono">
                {/* Base URL */}
                <div className="space-y-1">
                  <div className="text-muted-foreground font-sans font-medium text-xs">Gateway API Base URL:</div>
                  <div className="flex items-center justify-between bg-muted p-2 rounded-lg border">
                    <span className="text-foreground select-all">https://musoftwares.com/api/v1/partner</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 px-2"
                      onClick={() => handleCopyNamed('https://musoftwares.com/api/v1/partner', 'env-base-url')}
                    >
                      {copiedSection === 'env-base-url' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* Client Key */}
                <div className="space-y-1">
                  <div className="text-muted-foreground font-sans font-medium text-xs">MUSOFTWARES_CLIENT_KEY (Public Key):</div>
                  <div className="flex items-center justify-between bg-muted p-2 rounded-lg border">
                    <span className="text-foreground break-all select-all">{credentialsModalClient.client_key}</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 px-2 shrink-0 ms-2"
                      onClick={() => handleCopyNamed(credentialsModalClient.client_key, 'env-client-key')}
                    >
                      {copiedSection === 'env-client-key' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* Secret Key */}
                <div className="space-y-1">
                  <div className="flex items-center justify-between text-muted-foreground font-sans font-medium text-xs">
                    <span>MUSOFTWARES_CLIENT_SECRET (Secret Key):</span>
                    <button
                      type="button"
                      onClick={() => setShowModalSecret(!showModalSecret)}
                      className="text-primary hover:underline flex items-center gap-1 font-sans text-xs"
                    >
                      {showModalSecret ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                      {showModalSecret ? 'Hide' : 'Reveal'}
                    </button>
                  </div>
                  <div className="flex items-center justify-between bg-muted p-2 rounded-lg border">
                    <span className="text-amber-500 break-all select-all">
                      {showModalSecret ? credentialsModalClient.client_secret : '••••••••••••••••••••••••••••••••••••••••••••••••'}
                    </span>
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 px-2 shrink-0 ms-2"
                      onClick={() => handleCopyNamed(credentialsModalClient.client_secret, 'env-client-secret')}
                    >
                      {copiedSection === 'env-client-secret' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* .env snippet */}
                <div className="space-y-1 pt-2">
                  <div className="flex items-center justify-between text-muted-foreground font-sans font-medium text-xs">
                    <span className="flex items-center gap-1.5"><Terminal className="w-3.5 h-3.5" /> Ready .env Snippet:</span>
                  </div>
                  <div className="relative bg-slate-950 text-emerald-400 p-3 rounded-lg border font-mono text-[11px] overflow-x-auto">
                    <pre>{`MUSOFTWARES_GATEWAY_URL=https://musoftwares.com/api/v1/partner
MUSOFTWARES_CLIENT_KEY=${credentialsModalClient.client_key}
MUSOFTWARES_CLIENT_SECRET=${credentialsModalClient.client_secret}`}</pre>
                  </div>
                </div>
              </div>

              <DialogFooter className="flex-col sm:flex-row gap-2">
                <Button
                  type="button"
                  variant="default"
                  className="w-full sm:w-auto"
                  onClick={() => {
                    const snippet = `MUSOFTWARES_GATEWAY_URL=https://musoftwares.com/api/v1/partner\nMUSOFTWARES_CLIENT_KEY=${credentialsModalClient.client_key}\nMUSOFTWARES_CLIENT_SECRET=${credentialsModalClient.client_secret}`;
                    handleCopyNamed(snippet, 'all-env');
                  }}
                >
                  {copiedSection === 'all-env' ? (
                    <>
                      <Check className="w-4 h-4 mr-1.5 text-emerald-300" /> Copied .env Configuration!
                    </>
                  ) : (
                    <>
                      <Copy className="w-4 h-4 mr-1.5" /> Copy Complete .env Snippet
                    </>
                  )}
                </Button>
                <Button type="button" variant="outline" onClick={() => setCredentialsModalClient(null)}>
                  Close
                </Button>
              </DialogFooter>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </AdminSidebarLayout>
  );
}
