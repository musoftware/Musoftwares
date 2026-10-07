import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
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
  Shield,
  Eye,
  EyeOff,
  Copy,
  Check,
  RefreshCw,
  Wallet,
  CreditCard,
  Layers,
  Clock,
  ArrowUpRight,
  TrendingDown,
  TrendingUp,
  AlertCircle,
  Sparkles,
  Zap,
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

interface PartnerClient {
  id: number;
  client_name: string;
  client_key: string;
  client_secret: string;
  wallet_balance: number;
  cost_per_message: number;
  pricing_model: string;
  low_balance_threshold: number;
  is_active: boolean;
  created_at: string;
}

interface CreditLease {
  id: number;
  lease_id: string;
  granted_messages: number;
  settled_messages: number;
  reserved_amount: number;
  final_charged_amount: number;
  status: string;
  expires_at: string | null;
  created_at: string;
}

interface UsageLog {
  id: number;
  type: string;
  amount: number;
  balance_after: number;
  description: string | null;
  created_at: string;
}

interface Props {
  partnerClient: PartnerClient | null;
  activeLeases: CreditLease[];
  usageLogs: UsageLog[];
  userWalletBalance: number;
  userCurrency: string;
  userCurrencySymbol: string;
}

export default function ClientPartnerGatewayIndex({
  partnerClient,
  activeLeases = [],
  usageLogs = [],
  userWalletBalance = 0,
  userCurrency = 'USD',
  userCurrencySymbol = '$',
}: Props) {
  const { confirm, confirmDialog } = useConfirm();
  const [showSecret, setShowSecret] = useState(false);
  const [copiedField, setCopiedField] = useState<string | null>(null);
  const [topUpModalOpen, setTopUpModalOpen] = useState(false);
  const [topUpTab, setTopUpTab] = useState<'wallet' | 'online'>('wallet');

  const walletTopUpForm = useForm({
    amount_usd: '25.00',
  });

  const onlineTopUpForm = useForm({
    amount_usd: '25.00',
  });

  const handleCopy = (text: string, field: string) => {
    navigator.clipboard.writeText(text);
    setCopiedField(field);
    setTimeout(() => setCopiedField(null), 2000);
  };

  const handleRegenerateSecret = async () => {
    const accepted = await confirm({
      title: __('client.partner_regenerate_title'),
      description: __('client.partner_regenerate_confirm'),
      variant: 'danger',
    });
    if (!accepted) return;
    router.post(route('client.partner-gateway.regenerate-secret'));
  };

  const handleWalletTopUp = (e: React.FormEvent) => {
    e.preventDefault();
    walletTopUpForm.post(route('client.partner-gateway.topup-wallet'), {
      onSuccess: () => {
        setTopUpModalOpen(false);
      },
    });
  };

  const handleOnlineTopUp = (e: React.FormEvent) => {
    e.preventDefault();
    onlineTopUpForm.post(route('client.partner-gateway.topup-online'), {
      onSuccess: () => {
        setTopUpModalOpen(false);
      },
    });
  };

  if (!partnerClient) {
    return (
      <WorkspaceLayout title={__('general.partner_gateway_api')}>
        <Head title={__('client.partner_page_title')} />
        <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6 text-center space-y-4">
          <div className="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto">
            <Zap className="w-8 h-8" />
          </div>
          <h2 className="text-2xl font-bold">{__('client.partner_not_activated')}</h2>
          <p className="text-muted-foreground max-w-md mx-auto text-sm">
            {__('client.partner_not_activated_desc')}
          </p>
        </div>
      </WorkspaceLayout>
    );
  }

  const isLowBalance = Number(partnerClient.wallet_balance) <= Number(partnerClient.low_balance_threshold);

  return (
    <WorkspaceLayout title={__('general.partner_gateway_api')}>
      <Head title={__('client.partner_page_title')} />

      <div className="max-w-6xl mx-auto py-6 px-4 sm:px-6 space-y-8">
        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-6">
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-2xl font-bold tracking-tight">{__('general.partner_gateway_api')}</h1>
              <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-500">
                {__('client.partner_live_production')}
              </span>
            </div>
            <p className="text-sm text-muted-foreground mt-1">
              {__('client.partner_subtitle')}
            </p>
          </div>

          <Button onClick={() => setTopUpModalOpen(true)} className="gap-2 shadow-md">
            <Wallet className="w-4 h-4" />
            {__('client.partner_top_up_credits')}
          </Button>
        </div>

        {/* Low Balance Alert Banner */}
        {isLowBalance && (
          <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
            <div className="flex-1 text-sm">
              <div className="font-semibold text-amber-600 dark:text-amber-400">{__('client.partner_low_balance_title')}</div>
              <div className="text-muted-foreground mt-0.5">
                {__('client.partner_low_balance_desc', { threshold: `$${Number(partnerClient.low_balance_threshold).toFixed(2)}` })}
              </div>
            </div>
            <Button size="sm" variant="outline" className="border-amber-500/40 text-amber-600 hover:bg-amber-500/20" onClick={() => setTopUpModalOpen(true)}>
              {__('client.partner_recharge_now')}
            </Button>
          </div>
        )}

        {/* Metrics Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
          {/* Partner Balance Card */}
          <div className="rounded-2xl border bg-card p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between text-muted-foreground text-xs font-semibold uppercase tracking-wider">
              <span>{__('client.partner_wallet_balance')}</span>
              <Wallet className="w-4 h-4 text-emerald-500" />
            </div>
            <div className="text-3xl font-extrabold font-mono text-emerald-500">
              ${Number(partnerClient.wallet_balance).toFixed(4)} <span className="text-xs text-muted-foreground font-normal">USD</span>
            </div>
            <div className="text-xs text-muted-foreground flex items-center justify-between pt-1 border-t">
              <span>{__('client.partner_rate_per_message')}</span>
              <span className="font-mono font-semibold text-foreground">${Number(partnerClient.cost_per_message).toFixed(4)}</span>
            </div>
          </div>

          {/* User Main Balance Card */}
          <div className="rounded-2xl border bg-card p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between text-muted-foreground text-xs font-semibold uppercase tracking-wider">
              <span>{__('client.partner_main_balance')}</span>
              <CreditCard className="w-4 h-4 text-primary" />
            </div>
            <div className="text-3xl font-extrabold font-mono text-foreground">
              {userCurrencySymbol}{Number(userWalletBalance).toFixed(2)} <span className="text-xs text-muted-foreground font-normal">{userCurrency}</span>
            </div>
            <div className="text-xs text-muted-foreground flex items-center justify-between pt-1 border-t">
              <span>{__('client.partner_instant_transfer_available')}</span>
              <span className="text-emerald-500 font-medium">{__('client.partner_ready')}</span>
            </div>
          </div>

          {/* Active Leases Card */}
          <div className="rounded-2xl border bg-card p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between text-muted-foreground text-xs font-semibold uppercase tracking-wider">
              <span>{__('client.partner_active_leases')}</span>
              <Layers className="w-4 h-4 text-blue-500" />
            </div>
            <div className="text-3xl font-extrabold font-mono text-blue-500">
              {activeLeases.filter((l) => l.status === 'ACTIVE').length} <span className="text-xs text-muted-foreground font-normal">{__('general.active')}</span>
            </div>
            <div className="text-xs text-muted-foreground flex items-center justify-between pt-1 border-t">
              <span>{__('client.partner_pricing_model')}</span>
              <span className="font-semibold text-foreground">{partnerClient.pricing_model}</span>
            </div>
          </div>
        </div>

        {/* API Credentials Card */}
        <div className="rounded-2xl border bg-card p-6 shadow-sm space-y-5">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Key className="w-5 h-5 text-primary" />
              <h2 className="text-lg font-bold">{__('client.partner_api_credentials')}</h2>
            </div>
            <Button
              size="sm"
              variant="outline"
              onClick={handleRegenerateSecret}
              className="gap-1.5 text-xs text-muted-foreground hover:text-foreground"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              {__('client.partner_regenerate_secret')}
            </Button>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {/* Client Key */}
            <div className="space-y-1.5">
              <Label className="text-xs text-muted-foreground">{__('client.partner_client_key')}</Label>
              <div className="flex items-center gap-2">
                <Input
                  readOnly
                  aria-label={__('client.partner_client_key')}
                  value={partnerClient.client_key}
                  className="font-mono text-xs bg-muted/40 cursor-text"
                />
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => handleCopy(partnerClient.client_key, 'key')}
                  className="shrink-0"
                  aria-label={__('client.partner_copy_key')}
                >
                  {copiedField === 'key' ? (
                    <Check className="w-4 h-4 text-emerald-500" />
                  ) : (
                    <Copy className="w-4 h-4" />
                  )}
                </Button>
              </div>
            </div>

            {/* Client Secret */}
            <div className="space-y-1.5">
              <Label className="text-xs text-muted-foreground">{__('client.partner_client_secret')}</Label>
              <div className="flex items-center gap-2">
                <Input
                  readOnly
                  type={showSecret ? 'text' : 'password'}
                  aria-label={__('client.partner_client_secret')}
                  value={partnerClient.client_secret}
                  className="font-mono text-xs bg-muted/40 cursor-text"
                />
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => setShowSecret(!showSecret)}
                  className="shrink-0"
                  title={showSecret ? __('client.partner_hide_secret') : __('client.partner_reveal_secret')}
                  aria-label={showSecret ? __('client.partner_hide_secret') : __('client.partner_reveal_secret')}
                >
                  {showSecret ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => handleCopy(partnerClient.client_secret, 'secret')}
                  className="shrink-0"
                  aria-label={__('client.partner_copy_secret')}
                >
                  {copiedField === 'secret' ? (
                    <Check className="w-4 h-4 text-emerald-500" />
                  ) : (
                    <Copy className="w-4 h-4" />
                  )}
                </Button>
              </div>
            </div>
          </div>

          <div className="p-3.5 rounded-xl bg-muted/40 border text-xs text-muted-foreground leading-relaxed">
            <span className="font-semibold text-foreground">{__('client.partner_auth_protocol')}</span>{' '}
            {__('client.partner_auth_protocol_desc', {
              key: 'x-partner-key',
              timestamp: 'x-partner-timestamp',
              signature: 'x-partner-signature',
              payload: 'timestamp.body',
            })}
          </div>
        </div>

        {/* Leases & Usage Logs Tabs */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Active / Recent Leases */}
          <div className="rounded-2xl border bg-card p-5 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm flex items-center gap-2">
                <Layers className="w-4 h-4 text-primary" />
                {__('client.partner_leases_history')}
              </h3>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-xs text-left">
                <thead className="bg-muted/50 text-muted-foreground uppercase border-b">
                  <tr>
                    <th className="px-3 py-2">{__('client.partner_lease_id')}</th>
                    <th className="px-3 py-2">{__('general.messages')}</th>
                    <th className="px-3 py-2">{__('client.partner_reserved')}</th>
                    <th className="px-3 py-2">{__('general.status')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {activeLeases.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-3 py-6 text-center text-muted-foreground">
                        {__('client.partner_no_leases')}
                      </td>
                    </tr>
                  ) : (
                    activeLeases.slice(0, 8).map((lease) => (
                      <tr key={lease.id} className="hover:bg-muted/20">
                        <td className="px-3 py-2.5 font-mono">{lease.lease_id.substring(0, 14)}...</td>
                        <td className="px-3 py-2.5 font-mono">
                          {lease.settled_messages} / {lease.granted_messages}
                        </td>
                        <td className="px-3 py-2.5 font-mono font-semibold">
                          ${Number(lease.reserved_amount).toFixed(2)}
                        </td>
                        <td className="px-3 py-2.5">
                          <span
                            className={`inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold ${
                              lease.status === 'ACTIVE'
                                ? 'bg-blue-500/10 text-blue-500'
                                : 'bg-muted text-muted-foreground'
                            }`}
                          >
                            {lease.status}
                          </span>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Usage / Audit Logs */}
          <div className="rounded-2xl border bg-card p-5 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm flex items-center gap-2">
                <Clock className="w-4 h-4 text-primary" />
                {__('client.partner_audit_log')}
              </h3>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-xs text-left">
                <thead className="bg-muted/50 text-muted-foreground uppercase border-b">
                  <tr>
                    <th className="px-3 py-2">{__('general.type')}</th>
                    <th className="px-3 py-2">{__('general.amount')}</th>
                    <th className="px-3 py-2">{__('client.partner_balance_after')}</th>
                    <th className="px-3 py-2">{__('general.date')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {usageLogs.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-3 py-6 text-center text-muted-foreground">
                        {__('client.partner_no_transactions')}
                      </td>
                    </tr>
                  ) : (
                    usageLogs.slice(0, 8).map((log) => (
                      <tr key={log.id} className="hover:bg-muted/20">
                        <td className="px-3 py-2.5 font-medium">
                          <span
                            className={`inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold ${
                              log.type === 'TOP_UP'
                                ? 'bg-emerald-500/10 text-emerald-500'
                                : log.type === 'LEASE_SETTLE'
                                ? 'bg-purple-500/10 text-purple-500'
                                : 'bg-muted text-muted-foreground'
                            }`}
                          >
                            {log.type}
                          </span>
                        </td>
                        <td className="px-3 py-2.5 font-mono font-semibold">
                          <span className={Number(log.amount) >= 0 ? 'text-emerald-500' : 'text-foreground'}>
                            {Number(log.amount) >= 0 ? '+' : ''}${Number(log.amount).toFixed(4)}
                          </span>
                        </td>
                        <td className="px-3 py-2.5 font-mono text-muted-foreground">
                          ${Number(log.balance_after).toFixed(4)}
                        </td>
                        <td className="px-3 py-2.5 text-muted-foreground whitespace-nowrap">
                          {new Date(log.created_at).toLocaleDateString()}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {/* Top Up Modal */}
      <Dialog open={topUpModalOpen} onOpenChange={setTopUpModalOpen}>
        <DialogContent className="sm:max-w-[480px]">
          <DialogHeader>
            <DialogTitle>{__('client.partner_top_up_title')}</DialogTitle>
            <DialogDescription>
              {__('client.partner_top_up_desc')}
            </DialogDescription>
          </DialogHeader>

          {/* Method Tabs */}
          <div className="grid grid-cols-2 gap-2 p-1 bg-muted rounded-xl">
            <button
              type="button"
              onClick={() => setTopUpTab('wallet')}
              className={`py-2 text-xs font-semibold rounded-lg transition-all ${
                topUpTab === 'wallet'
                  ? 'bg-card text-foreground shadow-sm'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              {__('client.partner_tab_wallet')}
            </button>
            <button
              type="button"
              onClick={() => setTopUpTab('online')}
              className={`py-2 text-xs font-semibold rounded-lg transition-all ${
                topUpTab === 'online'
                  ? 'bg-card text-foreground shadow-sm'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              {__('client.partner_tab_online')}
            </button>
          </div>

          {/* Wallet Transfer Form */}
          {topUpTab === 'wallet' ? (
            <form onSubmit={handleWalletTopUp} className="space-y-4 py-3">
              <div className="p-3 rounded-xl bg-muted/40 border text-xs space-y-1">
                <div className="flex justify-between text-muted-foreground">
                  <span>{__('client.partner_available_balance')}</span>
                  <span className="font-bold text-foreground font-mono">
                    {userCurrencySymbol}{Number(userWalletBalance).toFixed(2)} {userCurrency}
                  </span>
                </div>
                <div className="flex justify-between text-muted-foreground">
                  <span>{__('client.partner_current_balance')}</span>
                  <span className="font-bold text-emerald-500 font-mono">
                    ${Number(partnerClient.wallet_balance).toFixed(4)} USD
                  </span>
                </div>
              </div>

              <div>
                <Label htmlFor="wallet_amount_usd">{__('client.partner_recharge_amount')}</Label>
                <Input
                  id="wallet_amount_usd"
                  type="number"
                  step="1"
                  min="1"
                  max="50000"
                  value={walletTopUpForm.data.amount_usd}
                  onChange={(e) => walletTopUpForm.setData('amount_usd', e.target.value)}
                  required
                />
                {walletTopUpForm.errors.amount_usd && (
                  <p className="text-xs text-destructive mt-1">{walletTopUpForm.errors.amount_usd}</p>
                )}
              </div>

              {/* Quick Amount Buttons */}
              <div className="flex gap-2">
                {['10', '25', '50', '100'].map((amt) => (
                  <Button
                    key={amt}
                    type="button"
                    size="sm"
                    variant="outline"
                    className="flex-1 text-xs"
                    onClick={() => walletTopUpForm.setData('amount_usd', amt)}
                  >
                    ${amt}
                  </Button>
                ))}
              </div>

              <DialogFooter className="pt-2">
                <Button type="button" variant="outline" onClick={() => setTopUpModalOpen(false)}>
                  {__('general.cancel')}
                </Button>
                <Button type="submit" disabled={walletTopUpForm.processing} className="gap-2">
                  <Check className="w-4 h-4" />
                  {__('client.partner_instant_transfer', { amount: `$${walletTopUpForm.data.amount_usd}` })}
                </Button>
              </DialogFooter>
            </form>
          ) : (
            /* Online Direct Checkout Form */
            <form onSubmit={handleOnlineTopUp} className="space-y-4 py-3">
              <div className="p-3 rounded-xl bg-muted/40 border text-xs text-muted-foreground">
                {__('client.partner_online_desc')}
              </div>

              <div>
                <Label htmlFor="online_amount_usd">{__('client.partner_recharge_amount')}</Label>
                <Input
                  id="online_amount_usd"
                  type="number"
                  step="1"
                  min="1"
                  max="50000"
                  value={onlineTopUpForm.data.amount_usd}
                  onChange={(e) => onlineTopUpForm.setData('amount_usd', e.target.value)}
                  required
                />
                {onlineTopUpForm.errors.amount_usd && (
                  <p className="text-xs text-destructive mt-1">{onlineTopUpForm.errors.amount_usd}</p>
                )}
              </div>

              {/* Quick Amount Buttons */}
              <div className="flex gap-2">
                {['25', '50', '100', '250'].map((amt) => (
                  <Button
                    key={amt}
                    type="button"
                    size="sm"
                    variant="outline"
                    className="flex-1 text-xs"
                    onClick={() => onlineTopUpForm.setData('amount_usd', amt)}
                  >
                    ${amt}
                  </Button>
                ))}
              </div>

              <DialogFooter className="pt-2">
                <Button type="button" variant="outline" onClick={() => setTopUpModalOpen(false)}>
                  {__('general.cancel')}
                </Button>
                <Button type="submit" disabled={onlineTopUpForm.processing} className="gap-2">
                  <CreditCard className="w-4 h-4" />
                  {__('client.partner_proceed_checkout', { amount: `$${onlineTopUpForm.data.amount_usd}` })}
                </Button>
              </DialogFooter>
            </form>
          )}
        </DialogContent>
      </Dialog>
      {confirmDialog}
    </WorkspaceLayout>
  );
}
