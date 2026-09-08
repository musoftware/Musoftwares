import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import {
  ArrowLeft,
  Save,
  CheckCircle2,
  AlertTriangle,
  Plus,
  Trash2,
  Edit2,
  Key,
  CreditCard,
  Layers,
  PhoneCall,
  Sliders,
  Check,
  Package as PackageIcon,
  ShieldAlert,
  ShieldCheck,
} from 'lucide-react';

interface SoftwareKey {
  id: number;
  serial_software_id: number;
  key: string;
  default_value: string | null;
  description: string | null;
  created_at?: string;
}

interface SoftwarePackage {
  id: number;
  serial_software_id: number;
  name: string;
  price: number;
  reseller_price: number | null;
  currency: string;
  billing_cycle: 'lifetime' | 'monthly' | 'annual' | 'custom';
  billing_days: number | null;
  description: string | null;
  is_active: boolean;
  is_default: boolean;
  sort_order: number;
  custom_values: Record<string, string> | null;
  created_at?: string;
}

interface Software {
  id: number;
  name: string;
  is_active: boolean;
  default_status: string;
  pricing_type: 'free' | 'single' | 'packages';
  requires_payment: boolean;
  price: number | null;
  reseller_price: number | null;
  currency: string;
  billing_cycle: 'lifetime' | 'monthly' | 'annual' | 'custom';
  billing_days: number | null;
  whatsapp_number: string;
  payment_instructions: string;
  total_devices: number;
  active_count: number;
  inactive_count: number;
  blocked_count: number;
  created_at: string;
  custom_keys: SoftwareKey[];
  packages: SoftwarePackage[];
}

interface Props {
  software: Software;
  commonCurrencies: string[];
}

export default function SerialSoftwareSettings({ software, commonCurrencies }: Props) {
  // Main Settings Form State
  const [form, setForm] = useState({
    name: software.name,
    is_active: Boolean(software.is_active),
    default_status: software.default_status || 'active',
    pricing_type: software.pricing_type || (software.requires_payment ? 'single' : 'free'),
    price: software.price !== null ? String(software.price) : '',
    reseller_price: software.reseller_price !== null ? String(software.reseller_price) : '',
    currency: software.currency || 'USD',
    billing_cycle: software.billing_cycle || 'lifetime',
    billing_days: software.billing_days !== null ? String(software.billing_days) : '',
    whatsapp_number: software.whatsapp_number || '',
    payment_instructions: software.payment_instructions || '',
  });

  const [saving, setSaving] = useState(false);

  // Master Key Form State
  const [keyForm, setKeyForm] = useState({ key: '', default_value: '', description: '' });
  const [keySubmitting, setKeySubmitting] = useState(false);

  // Package Modal State
  const [packageModalOpen, setPackageModalOpen] = useState(false);
  const [editingPackage, setEditingPackage] = useState<SoftwarePackage | null>(null);
  const [packageForm, setPackageForm] = useState({
    name: '',
    price: '',
    reseller_price: '',
    currency: software.currency || 'USD',
    billing_cycle: 'monthly' as 'lifetime' | 'monthly' | 'annual' | 'custom',
    billing_days: '',
    description: '',
    is_active: true,
    is_default: false,
    sort_order: 0,
    custom_values: {} as Record<string, string>,
  });
  const [packageSubmitting, setPackageSubmitting] = useState(false);

  // Submit Main Settings
  const handleSaveSettings = (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);

    router.put(
      route('admin.serial-softwares.settings.update', software.id),
      {
        name: form.name,
        is_active: form.is_active,
        default_status: form.default_status,
        pricing_type: form.pricing_type,
        price: form.pricing_type === 'single' && form.price !== '' ? parseFloat(form.price) : null,
        reseller_price: form.pricing_type === 'single' && form.reseller_price !== '' ? parseFloat(form.reseller_price) : null,
        currency: form.currency,
        billing_cycle: form.pricing_type === 'single' ? form.billing_cycle : null,
        billing_days: form.pricing_type === 'single' && form.billing_cycle === 'custom' && form.billing_days !== '' ? parseInt(form.billing_days) : null,
        whatsapp_number: form.whatsapp_number || null,
        payment_instructions: form.payment_instructions || null,
      },
      {
        preserveScroll: true,
        onFinish: () => setSaving(false),
      }
    );
  };

  // Master Key actions
  const handleSaveKey = (e: React.FormEvent) => {
    e.preventDefault();
    if (!keyForm.key.trim()) return;
    setKeySubmitting(true);

    router.post(
      route('admin.serial-softwares.keys.store', software.id),
      keyForm,
      {
        preserveScroll: true,
        onSuccess: () => {
          setKeyForm({ key: '', default_value: '', description: '' });
        },
        onFinish: () => setKeySubmitting(false),
      }
    );
  };

  const handleDeleteKey = (keyId: number) => {
    if (!confirm(__('Are you sure you want to delete this key? Devices will lose this parameter unless overridden.', {}, 'Are you sure you want to delete this key?'))) {
      return;
    }
    router.delete(route('admin.serial-softwares.keys.destroy', [software.id, keyId]), {
      preserveScroll: true,
    });
  };

  // Package Modal Handlers
  const openNewPackageModal = () => {
    setEditingPackage(null);
    const initialOverrides: Record<string, string> = {};
    (software.custom_keys || []).forEach(k => {
      initialOverrides[k.key] = k.default_value || '';
    });

    setPackageForm({
      name: '',
      price: '',
      reseller_price: '',
      currency: form.currency || 'USD',
      billing_cycle: 'monthly',
      billing_days: '',
      description: '',
      is_active: true,
      is_default: (software.packages || []).length === 0,
      sort_order: (software.packages || []).length,
      custom_values: initialOverrides,
    });
    setPackageModalOpen(true);
  };

  const openEditPackageModal = (pkg: SoftwarePackage) => {
    setEditingPackage(pkg);
    const overrides: Record<string, string> = {};
    (software.custom_keys || []).forEach(k => {
      overrides[k.key] = pkg.custom_values && pkg.custom_values[k.key] !== undefined
        ? pkg.custom_values[k.key]
        : (k.default_value || '');
    });

    setPackageForm({
      name: pkg.name,
      price: String(pkg.price),
      reseller_price: pkg.reseller_price !== null ? String(pkg.reseller_price) : '',
      currency: pkg.currency || form.currency || 'USD',
      billing_cycle: pkg.billing_cycle,
      billing_days: pkg.billing_days !== null ? String(pkg.billing_days) : '',
      description: pkg.description || '',
      is_active: Boolean(pkg.is_active),
      is_default: Boolean(pkg.is_default),
      sort_order: pkg.sort_order || 0,
      custom_values: overrides,
    });
    setPackageModalOpen(true);
  };

  const handleSavePackage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!packageForm.name.trim() || packageForm.price === '') return;
    setPackageSubmitting(true);

    const payload = {
      name: packageForm.name,
      price: parseFloat(packageForm.price),
      reseller_price: packageForm.reseller_price !== '' ? parseFloat(packageForm.reseller_price) : null,
      currency: packageForm.currency,
      billing_cycle: packageForm.billing_cycle,
      billing_days: packageForm.billing_cycle === 'custom' && packageForm.billing_days !== '' ? parseInt(packageForm.billing_days) : null,
      description: packageForm.description || null,
      is_active: packageForm.is_active,
      is_default: packageForm.is_default,
      sort_order: packageForm.sort_order,
      custom_values: packageForm.custom_values,
    };

    if (editingPackage) {
      router.put(
        route('admin.serial-softwares.packages.update', [software.id, editingPackage.id]),
        payload,
        {
          preserveScroll: true,
          onSuccess: () => setPackageModalOpen(false),
          onFinish: () => setPackageSubmitting(false),
        }
      );
    } else {
      router.post(
        route('admin.serial-softwares.packages.store', software.id),
        payload,
        {
          preserveScroll: true,
          onSuccess: () => setPackageModalOpen(false),
          onFinish: () => setPackageSubmitting(false),
        }
      );
    }
  };

  const handleDeletePackage = (packageId: number) => {
    if (!confirm(__('Are you sure you want to delete this package?', {}, 'Are you sure you want to delete this package?'))) {
      return;
    }
    router.delete(route('admin.serial-softwares.packages.destroy', [software.id, packageId]), {
      preserveScroll: true,
    });
  };

  return (
    <AdminSidebarLayout
      title={`${software.name} - ${__('general.settings', {}, 'Settings')}`}
      header={software.name}
    >
      <Head title={`${software.name} - ${__('general.settings', {}, 'Settings')}`} />

      <div className="p-4 sm:p-6 space-y-6 w-full max-w-7xl mx-auto">
        {/* Top Header & Breadcrumbs */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-2 text-xs text-muted-foreground mb-1">
              <Link
                href={route('admin.serial-softwares.index')}
                className="hover:text-foreground flex items-center gap-1 transition-colors"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                <span>{__('general.serial_softwares', {}, 'Softwares')}</span>
              </Link>
              <span>/</span>
              <span className="font-medium text-foreground">{software.name}</span>
              <span>/</span>
              <span>{__('general.settings', {}, 'Settings')}</span>
            </div>

            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold tracking-tight">{software.name}</h1>
              {form.is_active ? (
                <Badge variant="outline" className="border-green-500/30 text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/20 text-xs gap-1 font-normal">
                  <ShieldCheck className="w-3 h-3" />
                  <span>{__('general.active', {}, 'Active')}</span>
                </Badge>
              ) : (
                <Badge variant="destructive" className="text-xs gap-1 font-normal">
                  <ShieldAlert className="w-3 h-3" />
                  <span>{__('general.deactivated', {}, 'Disabled as a Whole')}</span>
                </Badge>
              )}
            </div>
          </div>

          <div className="flex items-center gap-2.5">
            <Link
              href={route('admin.serial-softwares.index')}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-md border text-sm font-medium hover:bg-muted transition-colors"
            >
              <ArrowLeft className="w-4 h-4" />
              <span>{__('general.back_to_list', {}, 'Back to List')}</span>
            </Link>

            <Button
              type="button"
              onClick={handleSaveSettings}
              disabled={saving}
              className="gap-2"
            >
              <Save className="w-4 h-4" />
              <span>{saving ? __('general.saving', {}, 'Saving...') : __('general.save_changes', {}, 'Save Changes')}</span>
            </Button>
          </div>
        </div>

        {/* Inactive Kill Switch Warning Banner */}
        {!form.is_active && (
          <div className="p-4 rounded-xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/30 text-red-900 dark:text-red-200 flex items-start gap-3">
            <ShieldAlert className="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
            <div className="space-y-1">
              <p className="font-semibold text-sm">
                {__('general.software_master_disabled_title', {}, 'Software Is Deactivated As A Whole (Kill Switch Active)')}
              </p>
              <p className="text-xs text-red-700 dark:text-red-300">
                {__('general.software_master_disabled_desc', {}, 'All API check-ins and startups for this software will be immediately denied. No devices can connect or run while this master toggle is disabled.')}
              </p>
            </div>
          </div>
        )}

        <form onSubmit={handleSaveSettings} className="space-y-6">
          {/* SECTION 1: Master Status & General Config */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Sliders className="w-4 h-4 text-primary" />
                <span>{__('general.general_status_settings', {}, 'Master Status & Software Identity')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.general_status_desc', {}, 'Control the global availability switch and default behavior for new devices.')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Master Kill Switch */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-lg border bg-muted/30 gap-4">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    <Label htmlFor="master-active" className="text-sm font-semibold cursor-pointer">
                      {__('general.software_active_as_whole', {}, 'Software Active As A Whole (Master Switch)')}
                    </Label>
                    {form.is_active ? (
                      <span className="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-medium">
                        {__('general.running', {}, 'Running')}
                      </span>
                    ) : (
                      <span className="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium">
                        {__('general.stopped', {}, 'Completely Stopped')}
                      </span>
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {__('general.master_active_hint', {}, 'When turned off, the API returns Inactive for all devices checking in with this software, effectively stopping execution everywhere.')}
                  </p>
                </div>
                <Switch
                  id="master-active"
                  checked={form.is_active}
                  onCheckedChange={(checked) => setForm({ ...form, is_active: checked })}
                />
              </div>

              {/* Identity & Default Device Status */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="sw-name" className="text-xs font-semibold">
                    {__('general.software_program_name', {}, 'Program Name / Identifier')}
                  </Label>
                  <Input
                    id="sw-name"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    required
                    className="font-medium"
                  />
                  <p className="text-[11px] text-muted-foreground">
                    {__('general.program_name_hint', {}, 'Matches the program_name string sent by the desktop client application.')}
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="sw-default-status" className="text-xs font-semibold">
                    {__('general.default_status_for_new_devices', {}, 'Default Status for Newly Discovered Devices')}
                  </Label>
                  <Select
                    value={form.default_status}
                    onValueChange={(val) => setForm({ ...form, default_status: val || 'active' })}
                  >
                    <SelectTrigger id="sw-default-status">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">{__('general.active', {}, 'Active (Auto-activate new machines)')}</SelectItem>
                      <SelectItem value="inactive">{__('general.inactive', {}, 'Inactive (Require admin or payment to activate)')}</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-[11px] text-muted-foreground">
                    {__('general.default_status_hint', {}, 'Applies only when a completely new device checks in for the first time.')}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* SECTION 2: Pricing Strategy & Packages */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <CreditCard className="w-4 h-4 text-primary" />
                <span>{__('general.pricing_and_packages_model', {}, 'Pricing Model & Packages')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.pricing_model_desc', {}, 'Choose whether this software is Free, uses a Single Paid plan (Forever / Monthly / Annual), or provides Multi-Packages with distinct key-values.')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Pricing Mode Selector Cards */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                {/* Option 1: Free */}
                <div
                  onClick={() => setForm({ ...form, pricing_type: 'free' })}
                  className={`p-4 rounded-xl border cursor-pointer transition-all flex flex-col justify-between ${
                    form.pricing_type === 'free'
                      ? 'border-primary bg-primary/5 ring-1 ring-primary'
                      : 'border-border hover:bg-muted/40'
                  }`}
                >
                  <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                      <span className="font-semibold text-sm">{__('general.free_mode', {}, 'Free Software')}</span>
                      {form.pricing_type === 'free' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.free_mode_desc', {}, 'Direct activation for all devices. No payment required or prompted on startup.')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('general.instant_access', {}, '100% Free / Direct')}
                  </div>
                </div>

                {/* Option 2: Single Paid Plan */}
                <div
                  onClick={() => setForm({ ...form, pricing_type: 'single' })}
                  className={`p-4 rounded-xl border cursor-pointer transition-all flex flex-col justify-between ${
                    form.pricing_type === 'single'
                      ? 'border-primary bg-primary/5 ring-1 ring-primary'
                      : 'border-border hover:bg-muted/40'
                  }`}
                >
                  <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                      <span className="font-semibold text-sm">{__('general.single_paid_plan', {}, 'Single Price Plan')}</span>
                      {form.pricing_type === 'single' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.single_paid_desc', {}, 'One price with Lifetime, Monthly, Annual, or Custom Days billing cycle.')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('general.single_tier', {}, 'Single Pricing Tier')}
                  </div>
                </div>

                {/* Option 3: Multi-Packages */}
                <div
                  onClick={() => setForm({ ...form, pricing_type: 'packages' })}
                  className={`p-4 rounded-xl border cursor-pointer transition-all flex flex-col justify-between ${
                    form.pricing_type === 'packages'
                      ? 'border-primary bg-primary/5 ring-1 ring-primary'
                      : 'border-border hover:bg-muted/40'
                  }`}
                >
                  <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                      <span className="font-semibold text-sm">{__('general.multi_packages', {}, 'Multi-Packages / Tiers')}</span>
                      {form.pricing_type === 'packages' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.multi_packages_desc', {}, 'Multiple packages (e.g. Starter, Pro, Enterprise) each with distinct prices and custom key-value limits.')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('general.packages_count', { count: (software.packages || []).length }, `${(software.packages || []).length} Packages defined`)}
                  </div>
                </div>
              </div>

              {/* SINGLE PAID PLAN CONFIGURATION */}
              {form.pricing_type === 'single' && (
                <div className="p-4 rounded-xl border bg-muted/20 space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="text-sm font-semibold">{__('general.single_plan_details', {}, 'Single Plan Configuration')}</h3>
                    <Badge variant="outline" className="text-xs font-normal">
                      {__('general.requires_payment', {}, 'Requires Payment')}
                    </Badge>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="space-y-1.5">
                      <Label htmlFor="single-price" className="text-xs font-semibold">
                        {__('general.customer_price', {}, 'Customer Price (Retail)')}
                      </Label>
                      <Input
                        id="single-price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="e.g. 49.99"
                        value={form.price}
                        onChange={(e) => setForm({ ...form, price: e.target.value })}
                        required={form.pricing_type === 'single'}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="single-reseller-price" className="text-xs font-semibold">
                        {__('general.reseller_price', {}, 'Reseller Price (Cost)')}
                      </Label>
                      <Input
                        id="single-reseller-price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="e.g. 29.99"
                        value={form.reseller_price}
                        onChange={(e) => setForm({ ...form, reseller_price: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="single-currency" className="text-xs font-semibold">
                        {__('general.currency', {}, 'Currency')}
                      </Label>
                      <Input
                        id="single-currency"
                        placeholder="USD"
                        value={form.currency}
                        onChange={(e) => setForm({ ...form, currency: e.target.value.toUpperCase() })}
                        className="font-mono"
                        required={form.pricing_type === 'single'}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="single-cycle" className="text-xs font-semibold">
                        {__('general.billing_cycle', {}, 'Billing Cycle / Duration')}
                      </Label>
                      <Select
                        value={form.billing_cycle}
                        onValueChange={(val: any) => setForm({ ...form, billing_cycle: val })}
                      >
                        <SelectTrigger id="single-cycle">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="lifetime">{__('general.cycle_lifetime', {}, 'Lifetime / Forever (One-time)')}</SelectItem>
                          <SelectItem value="monthly">{__('general.cycle_monthly', {}, 'Monthly (Every Month)')}</SelectItem>
                          <SelectItem value="annual">{__('general.cycle_annual', {}, 'Annual (Every Year)')}</SelectItem>
                          <SelectItem value="custom">{__('general.cycle_custom', {}, 'Custom Days Period')}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {form.billing_cycle === 'custom' && (
                    <div className="w-full sm:w-1/3 space-y-1.5 pt-2">
                      <Label htmlFor="single-days" className="text-xs font-semibold">
                        {__('general.custom_duration_days', {}, 'Duration In Days')}
                      </Label>
                      <Input
                        id="single-days"
                        type="number"
                        min="1"
                        placeholder="e.g. 30, 90, 180"
                        value={form.billing_days}
                        onChange={(e) => setForm({ ...form, billing_days: e.target.value })}
                        required={form.billing_cycle === 'custom'}
                      />
                    </div>
                  )}
                </div>
              )}

              {/* MULTI-PACKAGES CONFIGURATION */}
              {form.pricing_type === 'packages' && (
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-semibold">{__('general.defined_packages', {}, 'Packages & Tiers')}</h3>
                      <p className="text-xs text-muted-foreground">
                        {__('general.defined_packages_desc', {}, 'Each package can have its own price, billing interval, and specific overrides for master key-values.')}
                      </p>
                    </div>

                    <Button
                      type="button"
                      size="sm"
                      onClick={openNewPackageModal}
                      className="gap-1.5"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      <span>{__('general.add_package', {}, 'Add Package')}</span>
                    </Button>
                  </div>

                  {(!software.packages || software.packages.length === 0) ? (
                    <div className="p-8 border rounded-xl text-center space-y-3 bg-muted/20">
                      <PackageIcon className="w-10 h-10 text-muted-foreground/40 mx-auto" />
                      <div className="space-y-1">
                        <p className="text-sm font-medium">{__('general.no_packages_yet', {}, 'No packages created yet')}</p>
                        <p className="text-xs text-muted-foreground">
                          {__('general.no_packages_hint', {}, 'Create your first package (e.g. Starter, Pro, Lifetime) to offer tiered pricing.')}
                        </p>
                      </div>
                      <Button type="button" variant="outline" size="sm" onClick={openNewPackageModal}>
                        <Plus className="w-3.5 h-3.5 me-1.5" />
                        <span>{__('general.create_first_package', {}, 'Create First Package')}</span>
                      </Button>
                    </div>
                  ) : (
                    <div className="border rounded-xl overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/40">
                            <TableHead>{__('general.package_name', {}, 'Package')}</TableHead>
                            <TableHead>{__('general.customer_price', {}, 'Customer Price')}</TableHead>
                            <TableHead>{__('general.reseller_price', {}, 'Reseller Price')}</TableHead>
                            <TableHead>{__('general.cycle', {}, 'Cycle')}</TableHead>
                            <TableHead>{__('general.key_overrides', {}, 'Key Values')}</TableHead>
                            <TableHead>{__('general.status', {}, 'Status')}</TableHead>
                            <TableHead className="w-24 text-end"></TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {software.packages.map((pkg) => (
                            <TableRow key={pkg.id}>
                              <TableCell>
                                <div className="space-y-0.5">
                                  <div className="flex items-center gap-2">
                                    <span className="font-semibold text-sm">{pkg.name}</span>
                                    {pkg.is_default && (
                                      <Badge variant="secondary" className="text-[10px] px-1.5 py-0 h-4">
                                        {__('general.default', {}, 'Default')}
                                      </Badge>
                                    )}
                                  </div>
                                  {pkg.description && (
                                    <p className="text-xs text-muted-foreground line-clamp-1">{pkg.description}</p>
                                  )}
                                </div>
                              </TableCell>
                              <TableCell className="font-medium">
                                {pkg.price} {pkg.currency}
                              </TableCell>
                              <TableCell className="font-semibold text-primary">
                                {pkg.reseller_price !== null ? `${pkg.reseller_price} ${pkg.currency}` : `${pkg.price} ${pkg.currency}`}
                              </TableCell>
                              <TableCell>
                                <Badge variant="outline" className="text-xs capitalize font-normal">
                                  {pkg.billing_cycle === 'lifetime'
                                    ? __('general.lifetime', {}, 'Lifetime')
                                    : pkg.billing_cycle === 'custom'
                                    ? `${pkg.billing_days || 0} ${__('general.days', {}, 'Days')}`
                                    : pkg.billing_cycle}
                                </Badge>
                              </TableCell>
                              <TableCell>
                                <div className="flex flex-wrap gap-1 max-w-xs">
                                  {pkg.custom_values && Object.keys(pkg.custom_values).length > 0 ? (
                                    Object.entries(pkg.custom_values).map(([k, v]) => (
                                      <span key={k} className="text-[11px] font-mono px-1.5 py-0.5 rounded bg-muted border">
                                        {k}: <strong>{v}</strong>
                                      </span>
                                    ))
                                  ) : (
                                    <span className="text-xs text-muted-foreground italic">
                                      {__('general.inherits_defaults', {}, 'Inherits defaults')}
                                    </span>
                                  )}
                                </div>
                              </TableCell>
                              <TableCell>
                                {pkg.is_active ? (
                                  <span className="text-xs text-green-600 font-medium">{__('general.active', {}, 'Active')}</span>
                                ) : (
                                  <span className="text-xs text-muted-foreground">{__('general.disabled', {}, 'Disabled')}</span>
                                )}
                              </TableCell>
                              <TableCell className="text-end">
                                <div className="flex items-center justify-end gap-1">
                                  <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 w-7 p-0 hover:bg-muted"
                                    onClick={() => openEditPackageModal(pkg)}
                                    title={__('general.edit', {}, 'Edit')}
                                  >
                                    <Edit2 className="w-3.5 h-3.5" />
                                  </Button>
                                  <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                    onClick={() => handleDeletePackage(pkg.id)}
                                    title={__('general.delete', {}, 'Delete')}
                                  >
                                    <Trash2 className="w-3.5 h-3.5" />
                                  </Button>
                                </div>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          {/* SECTION 3: Master Keys & Values (Key-Value Schema) */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Key className="w-4 h-4 text-primary" />
                <span>{__('general.master_custom_keys', {}, 'Master Key-Value Parameters')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.master_keys_desc', {}, 'Define custom parameter keys for this software (e.g. max_accounts, daily_limit, export_enabled). These provide default values for devices, and can be customized per package in Multi-Package mode.')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Existing Master Keys Table */}
              {(!software.custom_keys || software.custom_keys.length === 0) ? (
                <div className="p-4 border rounded-lg text-center text-sm text-muted-foreground bg-muted/20">
                  {__('general.no_master_keys_yet', {}, 'No custom parameter keys defined yet. Add the first key below.')}
                </div>
              ) : (
                <div className="border rounded-lg overflow-hidden">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/40">
                        <TableHead className="text-xs font-semibold">{__('general.key_name', {}, 'Key Identifier')}</TableHead>
                        <TableHead className="text-xs font-semibold">{__('general.default_value', {}, 'Default Value')}</TableHead>
                        <TableHead className="text-xs font-semibold">{__('general.description', {}, 'Description')}</TableHead>
                        <TableHead className="w-12 text-end"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {software.custom_keys.map((k) => (
                        <TableRow key={k.id}>
                          <TableCell className="font-mono text-xs font-semibold">
                            {k.key}
                          </TableCell>
                          <TableCell className="font-mono text-xs text-muted-foreground">
                            {k.default_value !== null && k.default_value !== '' ? (
                              k.default_value
                            ) : (
                              <span className="italic text-muted-foreground/60">(empty)</span>
                            )}
                          </TableCell>
                          <TableCell className="text-xs text-muted-foreground">
                            {k.description || '—'}
                          </TableCell>
                          <TableCell className="text-end">
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              className="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                              onClick={() => handleDeleteKey(k.id)}
                              title={__('general.delete', {}, 'Delete')}
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}

              <Separator />

              {/* Add / Update Master Key Form */}
              <div className="space-y-3">
                <Label className="text-xs font-semibold uppercase text-muted-foreground">
                  {__('general.add_new_master_key', {}, 'Add New Key Parameter')}
                </Label>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-name" className="text-xs">
                      {__('general.key_identifier', {}, 'Key Name (e.g. max_accounts)')}
                    </Label>
                    <Input
                      id="new-key-name"
                      placeholder="e.g. max_accounts"
                      value={keyForm.key}
                      onChange={(e) => setKeyForm({ ...keyForm, key: e.target.value })}
                      className="h-9 font-mono text-xs"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-default" className="text-xs">
                      {__('general.default_value', {}, 'Default Value')}
                    </Label>
                    <Input
                      id="new-key-default"
                      placeholder="e.g. 1"
                      value={keyForm.default_value}
                      onChange={(e) => setKeyForm({ ...keyForm, default_value: e.target.value })}
                      className="h-9 font-mono text-xs"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-desc" className="text-xs">
                      {__('general.description', {}, 'Description (Optional)')}
                    </Label>
                    <div className="flex gap-2">
                      <Input
                        id="new-key-desc"
                        placeholder="e.g. Maximum accounts"
                        value={keyForm.description}
                        onChange={(e) => setKeyForm({ ...keyForm, description: e.target.value })}
                        className="h-9 text-xs flex-1"
                      />
                      <Button
                        type="button"
                        onClick={handleSaveKey}
                        disabled={keySubmitting || !keyForm.key.trim()}
                        className="gap-1.5 shrink-0"
                      >
                        <Plus className="w-3.5 h-3.5" />
                        <span>{keySubmitting ? __('general.saving', {}, '...') : __('general.add', {}, 'Add')}</span>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* SECTION 4: Payment Contact & Instructions */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <PhoneCall className="w-4 h-4 text-primary" />
                <span>{__('general.payment_contact_and_instructions', {}, 'Payment Contact & Client Instructions')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.payment_contact_desc', {}, 'Configure payment instructions and WhatsApp support details presented to client desktop applications.')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="sw-whatsapp-number" className="text-xs font-semibold">
                  {__('general.whatsapp_number', {}, 'Admin WhatsApp Number (With International Country Code)')}
                </Label>
                <Input
                  id="sw-whatsapp-number"
                  placeholder="e.g. +201015218548"
                  value={form.whatsapp_number}
                  onChange={(e) => setForm({ ...form, whatsapp_number: e.target.value })}
                  className="max-w-md font-mono"
                />
                <p className="text-[11px] text-muted-foreground">
                  {__('general.whatsapp_hint', {}, 'Clients will see a direct 1-click button to contact this WhatsApp number with their Device ID.')}
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="sw-payment-notes" className="text-xs font-semibold">
                  {__('general.payment_instructions', {}, 'Payment Instructions / Notes (Shown to Client)')}
                </Label>
                <Textarea
                  id="sw-payment-notes"
                  rows={4}
                  placeholder="e.g. Transfer fee to Vodafone Cash / USDT (TRC20) / Bank Account and send transfer receipt via WhatsApp."
                  value={form.payment_instructions}
                  onChange={(e) => setForm({ ...form, payment_instructions: e.target.value })}
                  className="text-xs"
                />
                <p className="text-[11px] text-muted-foreground">
                  {__('general.instructions_hint', {}, 'Appears directly on client activation and renewal modal screens.')}
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Sticky Bottom Save Bar */}
          <div className="flex items-center justify-between p-4 rounded-xl border bg-card shadow-sm">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <span>{__('general.total_devices', {}, 'Total Devices')}: <strong>{software.total_devices}</strong></span>
              <span>•</span>
              <span className="text-green-600 font-medium">{__('general.active', {}, 'Active')}: {software.active_count}</span>
              <span>•</span>
              <span>{__('general.inactive', {}, 'Inactive')}: {software.inactive_count}</span>
            </div>

            <div className="flex items-center gap-2">
              <Link
                href={route('admin.serial-softwares.index')}
                className="px-4 py-2 text-sm font-medium rounded-md border hover:bg-muted transition-colors"
              >
                {__('general.cancel', {}, 'Cancel')}
              </Link>
              <Button
                type="submit"
                disabled={saving}
                className="gap-2 px-5"
              >
                <Save className="w-4 h-4" />
                <span>{saving ? __('general.saving', {}, 'Saving Changes...') : __('general.save_settings', {}, 'Save All Settings')}</span>
              </Button>
            </div>
          </div>
        </form>
      </div>

      {/* PACKAGE ADD / EDIT MODAL */}
      <Dialog open={packageModalOpen} onOpenChange={setPackageModalOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <PackageIcon className="w-5 h-5 text-primary" />
              <span>{editingPackage ? __('general.edit_package', {}, 'Edit Package') : __('general.add_new_package', {}, 'Add New Package')}</span>
            </DialogTitle>
            <DialogDescription>
              {__('general.package_modal_desc', {}, 'Configure package pricing, billing frequency, and key-value limits.')}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSavePackage} className="space-y-4 pt-2">
            {/* Name, Customer Price, Reseller Price, Currency */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="pkg-name" className="text-xs font-semibold">{__('general.package_name', {}, 'Package Name')}</Label>
                <Input
                  id="pkg-name"
                  placeholder="e.g. Pro Monthly"
                  value={packageForm.name}
                  onChange={(e) => setPackageForm({ ...packageForm, name: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="pkg-price" className="text-xs font-semibold">{__('general.customer_price', {}, 'Customer Price')}</Label>
                <Input
                  id="pkg-price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="29.99"
                  value={packageForm.price}
                  onChange={(e) => setPackageForm({ ...packageForm, price: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="pkg-reseller-price" className="text-xs font-semibold">{__('general.reseller_price', {}, 'Reseller Price')}</Label>
                <Input
                  id="pkg-reseller-price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="19.99"
                  value={packageForm.reseller_price}
                  onChange={(e) => setPackageForm({ ...packageForm, reseller_price: e.target.value })}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="pkg-currency" className="text-xs font-semibold">{__('general.currency', {}, 'Currency')}</Label>
                <Input
                  id="pkg-currency"
                  placeholder="USD"
                  value={packageForm.currency}
                  onChange={(e) => setPackageForm({ ...packageForm, currency: e.target.value.toUpperCase() })}
                  className="font-mono"
                  required
                />
              </div>
            </div>

            {/* Cycle and Duration */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="pkg-cycle" className="text-xs font-semibold">{__('general.billing_cycle', {}, 'Billing Cycle')}</Label>
                <Select
                  value={packageForm.billing_cycle}
                  onValueChange={(val: any) => setPackageForm({ ...packageForm, billing_cycle: val })}
                >
                  <SelectTrigger id="pkg-cycle">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="monthly">{__('general.monthly', {}, 'Monthly')}</SelectItem>
                    <SelectItem value="annual">{__('general.annual', {}, 'Annual (Yearly)')}</SelectItem>
                    <SelectItem value="lifetime">{__('general.lifetime', {}, 'Lifetime / Forever')}</SelectItem>
                    <SelectItem value="custom">{__('general.custom_days', {}, 'Custom Days')}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {packageForm.billing_cycle === 'custom' ? (
                <div className="space-y-1.5">
                  <Label htmlFor="pkg-days" className="text-xs font-semibold">{__('general.days_count', {}, 'Number of Days')}</Label>
                  <Input
                    id="pkg-days"
                    type="number"
                    min="1"
                    placeholder="e.g. 7, 30, 90"
                    value={packageForm.billing_days}
                    onChange={(e) => setPackageForm({ ...packageForm, billing_days: e.target.value })}
                    required
                  />
                </div>
              ) : (
                <div className="space-y-1.5">
                  <Label htmlFor="pkg-sort" className="text-xs font-semibold">{__('general.sort_order', {}, 'Display Order')}</Label>
                  <Input
                    id="pkg-sort"
                    type="number"
                    value={packageForm.sort_order}
                    onChange={(e) => setPackageForm({ ...packageForm, sort_order: parseInt(e.target.value) || 0 })}
                  />
                </div>
              )}
            </div>

            {/* Description */}
            <div className="space-y-1.5">
              <Label htmlFor="pkg-desc" className="text-xs font-semibold">{__('general.description', {}, 'Description / Highlights')}</Label>
              <Textarea
                id="pkg-desc"
                rows={2}
                placeholder="e.g. Unlimited bulk sending, priority support, all export formats"
                value={packageForm.description}
                onChange={(e) => setPackageForm({ ...packageForm, description: e.target.value })}
                className="text-xs"
              />
            </div>

            {/* Switches */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                <Label htmlFor="pkg-active" className="text-xs font-medium cursor-pointer">
                  {__('general.active_package', {}, 'Package Available / Active')}
                </Label>
                <Switch
                  id="pkg-active"
                  checked={packageForm.is_active}
                  onCheckedChange={(checked) => setPackageForm({ ...packageForm, is_active: checked })}
                />
              </div>

              <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                <Label htmlFor="pkg-default" className="text-xs font-medium cursor-pointer">
                  {__('general.default_recommended', {}, 'Default / Recommended')}
                </Label>
                <Switch
                  id="pkg-default"
                  checked={packageForm.is_default}
                  onCheckedChange={(checked) => setPackageForm({ ...packageForm, is_default: checked })}
                />
              </div>
            </div>

            {/* KEY-VALUE OVERRIDES FOR THIS PACKAGE */}
            <div className="space-y-2 pt-2 border-t">
              <Label className="text-xs font-semibold uppercase text-muted-foreground">
                {__('general.package_key_overrides', {}, 'Package Key-Value Limits')}
              </Label>
              <p className="text-xs text-muted-foreground">
                {__('general.key_overrides_hint', {}, 'Configure custom values for this package. Leave empty to use software default.')}
              </p>

              {(!software.custom_keys || software.custom_keys.length === 0) ? (
                <p className="text-xs text-muted-foreground italic p-2 border rounded bg-muted/10">
                  {__('general.no_master_keys_to_override', {}, 'No master keys defined yet. You can add master keys in Section 3.')}
                </p>
              ) : (
                <div className="space-y-2 border rounded-lg p-3 bg-muted/20">
                  {software.custom_keys.map((k) => (
                    <div key={k.id} className="grid grid-cols-1 sm:grid-cols-3 items-center gap-2">
                      <div className="sm:col-span-1">
                        <span className="font-mono text-xs font-semibold">{k.key}</span>
                        {k.default_value && (
                          <span className="text-[10px] text-muted-foreground block">
                            {__('general.default', {}, 'Default')}: {k.default_value}
                          </span>
                        )}
                      </div>
                      <div className="sm:col-span-2">
                        <Input
                          placeholder={k.default_value || 'Value'}
                          value={packageForm.custom_values[k.key] ?? ''}
                          onChange={(e) =>
                            setPackageForm({
                              ...packageForm,
                              custom_values: {
                                ...packageForm.custom_values,
                                [k.key]: e.target.value,
                              },
                            })
                          }
                          className="h-8 font-mono text-xs"
                        />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <DialogFooter className="pt-3">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setPackageModalOpen(false)}
              >
                {__('general.cancel', {}, 'Cancel')}
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={packageSubmitting}
                className="gap-1.5"
              >
                <Check className="w-3.5 h-3.5" />
                <span>{packageSubmitting ? __('general.saving', {}, 'Saving...') : __('general.save_package', {}, 'Save Package')}</span>
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AdminSidebarLayout>
  );
}
