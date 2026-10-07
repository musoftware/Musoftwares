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
import { useConfirm } from '@/hooks/useConfirm';
import { __ } from '@/lib/i18n';
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
  const { confirm, confirmDialog } = useConfirm();

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

  const handleRotateSecret = async (client: PartnerClientItem) => {
    const accepted = await confirm({
      title: __('admin.partner_gateway_rotate_confirm_title'),
      description: __('admin.partner_gateway_rotate_confirm_body', { name: client.client_name }),
      variant: 'danger',
      confirmLabel: __('admin.partner_gateway_rotate_confirm_button'),
    });
    if (!accepted) return;
    router.post(route('admin.partner-gateway.regenerate-secret', client.id));
  };

  const handleDelete = async (client: PartnerClientItem) => {
    const accepted = await confirm({
      title: __('admin.partner_gateway_delete_confirm_title'),
      description: __('admin.partner_gateway_delete_confirm_body', { name: client.client_name }),
      variant: 'danger',
      confirmLabel: __('general.delete'),
    });
    if (!accepted) return;
    router.delete(route('admin.partner-gateway.destroy', client.id));
  };

  return (
    <AdminSidebarLayout>
      <Head title={__('admin.partner_gateway_page_title')} />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">{__('admin.partner_gateway_heading')}</h1>
            <p className="text-sm text-muted-foreground mt-1">
              {__('admin.partner_gateway_subheading')}
            </p>
          </div>
          <Button onClick={() => setCreateModalOpen(true)} className="gap-2">
            <Plus className="w-4 h-4" />
            {__('admin.partner_gateway_activate_account')}
          </Button>
        </div>

        {/* Metric Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">{__('admin.partner_gateway_total_partners')}</span>
              <Users className="w-4 h-4" />
            </div>
            <div className="text-2xl font-bold">{totals.total_clients}</div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">{__('admin.partner_gateway_active_partners')}</span>
              <CheckCircle className="w-4 h-4 text-emerald-500" />
            </div>
            <div className="text-2xl font-bold">{totals.active_clients}</div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">{__('admin.partner_gateway_total_balances_usd')}</span>
              <DollarSign className="w-4 h-4 text-primary" />
            </div>
            <div className="text-2xl font-bold">
              ${totals.total_balance_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
          </div>

          <div className="p-4 rounded-xl border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">{__('admin.partner_gateway_active_credit_leases')}</span>
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
              placeholder={__('admin.partner_gateway_search_placeholder')}
              className="pl-9"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <Button type="submit" variant="secondary">
            {__('general.search')}
          </Button>
        </form>

        {/* Table */}
        <div className="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="bg-muted/50 text-muted-foreground text-xs uppercase tracking-wider border-b">
                <tr>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_partner_client')}</th>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_owner_user')}</th>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_api_credentials')}</th>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_rate_per_msg')}</th>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_wallet_balance')}</th>
                  <th className="px-4 py-3">{__('admin.partner_gateway_col_active_leases')}</th>
                  <th className="px-4 py-3">{__('general.status')}</th>
                  <th className="px-4 py-3 text-right">{__('general.actions')}</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {clients.data.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-4 py-8 text-center text-muted-foreground">
                      {__('admin.partner_gateway_empty_state')}
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
                          <span className="text-xs text-muted-foreground italic">{__('admin.partner_gateway_system_direct')}</span>
                        )}
                      </td>
                      <td className="px-4 py-3 font-mono text-xs space-y-1 min-w-[220px]">
                        {/* Client Key */}
                        <div className="flex items-center gap-1.5 bg-muted/50 px-2 py-1 rounded border">
                          <span className="text-[10px] font-bold text-muted-foreground tracking-wider shrink-0">{__('admin.partner_gateway_key_label')}</span>
                          <span className="truncate max-w-[130px] select-all">{client.client_key.substring(0, 14)}...</span>
                          <button
                            type="button"
                            onClick={() => handleCopyNamed(client.client_key, `key-${client.id}`)}
                            className="p-1 rounded hover:bg-background text-muted-foreground ms-auto shrink-0"
                            title={__('admin.partner_gateway_copy_client_key')}
                            aria-label={__('admin.partner_gateway_copy_client_key')}
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
                          <span className="text-[10px] font-bold text-amber-600 dark:text-amber-400 tracking-wider shrink-0">{__('admin.partner_gateway_secret_label')}</span>
                          <span className="truncate max-w-[130px] text-amber-600 dark:text-amber-400 select-all">
                            {revealedSecrets[client.id] ? client.client_secret : '••••••••••••••••'}
                          </span>
                          <div className="flex items-center gap-0.5 ms-auto shrink-0">
                            <button
                              type="button"
                              onClick={() => toggleSecretReveal(client.id)}
                              className="p-1 rounded hover:bg-background text-muted-foreground"
                              title={revealedSecrets[client.id] ? __('admin.partner_gateway_hide_secret') : __('admin.partner_gateway_reveal_secret')}
                              aria-label={revealedSecrets[client.id] ? __('admin.partner_gateway_hide_secret') : __('admin.partner_gateway_reveal_secret')}
                            >
                              {revealedSecrets[client.id] ? <EyeOff className="w-3.5 h-3.5 text-amber-500" /> : <Eye className="w-3.5 h-3.5" />}
                            </button>
                            <button
                              type="button"
                              onClick={() => handleCopyNamed(client.client_secret, `secret-${client.id}`)}
                              className="p-1 rounded hover:bg-background text-muted-foreground"
                              title={__('admin.partner_gateway_copy_secret_key')}
                              aria-label={__('admin.partner_gateway_copy_secret_key')}
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
                          {__('admin.partner_gateway_active_leases_count', { count: client.active_leases_count || 0 })}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        {client.is_active ? (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-500">
                            <CheckCircle className="w-3 h-3" /> {__('general.active')}
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-destructive/10 text-destructive">
                            <XCircle className="w-3 h-3" /> {__('general.inactive')}
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
                            title={__('admin.partner_gateway_view_credentials')}
                            aria-label={__('admin.partner_gateway_view_credentials')}
                          >
                            <Key className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => openAdjustModal(client)}
                            title={__('admin.partner_gateway_adjust_balance_action')}
                            aria-label={__('admin.partner_gateway_adjust_balance_action')}
                          >
                            <DollarSign className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => openEditModal(client)}
                            title={__('admin.edit_settings')}
                            aria-label={__('admin.edit_settings')}
                          >
                            <Edit2 className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleRotateSecret(client)}
                            title={__('admin.partner_gateway_rotate_secret')}
                            aria-label={__('admin.partner_gateway_rotate_secret')}
                          >
                            <RefreshCw className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDelete(client)}
                            title={__('admin.partner_gateway_delete_client')}
                            aria-label={__('admin.partner_gateway_delete_client')}
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
              <DialogTitle>{__('admin.partner_gateway_activate_account')}</DialogTitle>
              <DialogDescription>
                {__('admin.partner_gateway_create_description')}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div className="space-y-1.5">
                <Label htmlFor="user_id">{__('admin.partner_gateway_select_user')}</Label>
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
                          client_name: prev.client_name || __('admin.partner_gateway_default_client_name', { name: u.name })
                        }));
                      }
                    }
                  }}
                  options={users.map((u) => ({ value: String(u.id), label: `${u.name} (${u.email})` }))}
                  placeholder={__('admin.partner_gateway_choose_user')}
                  searchPlaceholder={__('admin.partner_gateway_search_user')}
                />
                {createForm.errors.user_id && (
                  <p className="text-xs text-destructive mt-1">{createForm.errors.user_id}</p>
                )}
              </div>

              <div>
                <Label htmlFor="client_name">{__('admin.partner_gateway_app_client_name')}</Label>
                <Input
                  id="client_name"
                  placeholder={__('admin.partner_gateway_app_client_name_placeholder')}
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
                  <Label htmlFor="cost_per_message">{__('admin.partner_gateway_rate_per_msg_usd')}</Label>
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
                  <Label htmlFor="initial_balance">{__('admin.partner_gateway_initial_balance')}</Label>
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
                  <Label htmlFor="pricing_model">{__('admin.partner_gateway_pricing_model')}</Label>
                  <select
                    id="pricing_model"
                    className="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    value={createForm.data.pricing_model}
                    onChange={(e) => createForm.setData('pricing_model', e.target.value)}
                  >
                    <option value="PAYG_PER_MSG">{__('admin.partner_gateway_pricing_payg')}</option>
                    <option value="SUBSCRIPTION">{__('admin.partner_gateway_pricing_subscription')}</option>
                    <option value="HYBRID">{__('admin.partner_gateway_pricing_hybrid')}</option>
                  </select>
                </div>

                <div>
                  <Label htmlFor="low_balance_threshold">{__('admin.partner_gateway_low_balance_alert')}</Label>
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
                {__('general.cancel')}
              </Button>
              <Button type="submit" disabled={createForm.processing}>
                {__('admin.partner_gateway_generate_activate')}
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
              <DialogTitle>{__('admin.partner_gateway_edit_title')}</DialogTitle>
              <DialogDescription>
                {__('admin.partner_gateway_edit_description')}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div>
                <Label htmlFor="edit_client_name">{__('admin.partner_gateway_client_name_required')}</Label>
                <Input
                  id="edit_client_name"
                  value={editForm.data.client_name}
                  onChange={(e) => editForm.setData('client_name', e.target.value)}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="edit_cost">{__('admin.partner_gateway_rate_per_msg')}</Label>
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
                  <Label htmlFor="edit_threshold">{__('admin.partner_gateway_low_balance_alert')}</Label>
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
                <Label htmlFor="edit_pricing">{__('admin.partner_gateway_pricing_model')}</Label>
                <select
                  id="edit_pricing"
                  className="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                  value={editForm.data.pricing_model}
                  onChange={(e) => editForm.setData('pricing_model', e.target.value)}
                >
                  <option value="PAYG_PER_MSG">{__('admin.partner_gateway_pricing_payg')}</option>
                  <option value="SUBSCRIPTION">{__('admin.partner_gateway_pricing_subscription')}</option>
                  <option value="HYBRID">{__('admin.partner_gateway_pricing_hybrid')}</option>
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
                  {__('admin.partner_gateway_active_checkbox')}
                </Label>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditModalOpen(false)}>
                {__('general.cancel')}
              </Button>
              <Button type="submit" disabled={editForm.processing}>
                {__('general.save_changes')}
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
              <DialogTitle>{__('admin.partner_gateway_adjust_title')}</DialogTitle>
              <DialogDescription>
                {__('admin.partner_gateway_adjust_description', { name: selectedClient?.client_name ?? '' })}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div>
                <Label htmlFor="adjust_amount">{__('admin.partner_gateway_adjust_amount')}</Label>
                <Input
                  id="adjust_amount"
                  type="number"
                  step="0.0001"
                  placeholder={__('admin.partner_gateway_adjust_amount_placeholder')}
                  value={adjustForm.data.amount}
                  onChange={(e) => adjustForm.setData('amount', e.target.value)}
                  required
                />
                <p className="text-xs text-muted-foreground mt-1">
                  {__('admin.partner_gateway_current_balance', { amount: `$${Number(selectedClient?.wallet_balance || 0).toFixed(4)}` })}
                </p>
              </div>

              <div>
                <Label htmlFor="adjust_reason">{__('admin.partner_gateway_adjust_reason')}</Label>
                <Input
                  id="adjust_reason"
                  placeholder={__('admin.partner_gateway_adjust_reason_placeholder')}
                  value={adjustForm.data.reason}
                  onChange={(e) => adjustForm.setData('reason', e.target.value)}
                  required
                />
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAdjustModalOpen(false)}>
                {__('general.cancel')}
              </Button>
              <Button type="submit" disabled={adjustForm.processing}>
                {__('admin.partner_gateway_apply_adjustment')}
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
                  <DialogTitle>{__('admin.partner_gateway_credentials_title')}</DialogTitle>
                </div>
                <DialogDescription>
                  {__('admin.partner_gateway_credentials_description')} <strong>{credentialsModalClient.client_name}</strong>.
                </DialogDescription>
              </DialogHeader>

              <div className="space-y-4 py-4 text-xs font-mono">
                {/* Base URL */}
                <div className="space-y-1">
                  <div className="text-muted-foreground font-sans font-medium text-xs">{__('admin.partner_gateway_base_url_label')}</div>
                  <div className="flex items-center justify-between bg-muted p-2 rounded-lg border">
                    <span className="text-foreground select-all">https://musoftwares.com/api/v1/partner</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 px-2"
                      aria-label={__('admin.partner_gateway_copy_base_url')}
                      onClick={() => handleCopyNamed('https://musoftwares.com/api/v1/partner', 'env-base-url')}
                    >
                      {copiedSection === 'env-base-url' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* Client Key */}
                <div className="space-y-1">
                  <div className="text-muted-foreground font-sans font-medium text-xs">MUSOFTWARES_CLIENT_KEY ({__('admin.partner_gateway_public_key')}):</div>
                  <div className="flex items-center justify-between bg-muted p-2 rounded-lg border">
                    <span className="text-foreground break-all select-all">{credentialsModalClient.client_key}</span>
                    <Button
                      size="sm"
                      variant="ghost"
                      className="h-7 px-2 shrink-0 ms-2"
                      aria-label={__('admin.partner_gateway_copy_client_key')}
                      onClick={() => handleCopyNamed(credentialsModalClient.client_key, 'env-client-key')}
                    >
                      {copiedSection === 'env-client-key' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* Secret Key */}
                <div className="space-y-1">
                  <div className="flex items-center justify-between text-muted-foreground font-sans font-medium text-xs">
                    <span>MUSOFTWARES_CLIENT_SECRET ({__('admin.partner_gateway_secret_key')}):</span>
                    <button
                      type="button"
                      onClick={() => setShowModalSecret(!showModalSecret)}
                      className="text-primary hover:underline flex items-center gap-1 font-sans text-xs"
                    >
                      {showModalSecret ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                      {showModalSecret ? __('general.hide') : __('admin.partner_gateway_reveal')}
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
                      aria-label={__('admin.partner_gateway_copy_secret_key')}
                      onClick={() => handleCopyNamed(credentialsModalClient.client_secret, 'env-client-secret')}
                    >
                      {copiedSection === 'env-client-secret' ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                    </Button>
                  </div>
                </div>

                {/* .env snippet */}
                <div className="space-y-1 pt-2">
                  <div className="flex items-center justify-between text-muted-foreground font-sans font-medium text-xs">
                    <span className="flex items-center gap-1.5"><Terminal className="w-3.5 h-3.5" /> {__('admin.partner_gateway_ready_env_snippet')}</span>
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
                      <Check className="w-4 h-4 mr-1.5 text-emerald-300" /> {__('admin.partner_gateway_copied_env')}
                    </>
                  ) : (
                    <>
                      <Copy className="w-4 h-4 mr-1.5" /> {__('admin.partner_gateway_copy_env_snippet')}
                    </>
                  )}
                </Button>
                <Button type="button" variant="outline" onClick={() => setCredentialsModalClient(null)}>
                  {__('general.close')}
                </Button>
              </DialogFooter>
            </div>
          )}
        </DialogContent>
      </Dialog>
      {confirmDialog}
    </AdminSidebarLayout>
  );
}
