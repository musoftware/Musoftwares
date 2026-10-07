import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';
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
  Upload,
  Image as ImageIcon,
  Clock,
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
  logo_url?: string | null;
  is_active: boolean;
  default_status: string;
  pricing_type: 'free' | 'single' | 'packages';
  requires_payment: boolean;
  show_price?: boolean;
  show_whatsapp?: boolean;
  trial_enabled?: boolean;
  trial_days?: number;
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
  const { confirm, confirmDialog } = useConfirm();
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
    show_price: software.show_price !== undefined ? Boolean(software.show_price) : true,
    show_whatsapp: software.show_whatsapp !== undefined ? Boolean(software.show_whatsapp) : true,
    trial_enabled: Boolean(software.trial_enabled),
    trial_days: software.trial_days !== null && software.trial_days !== undefined ? String(software.trial_days) : '1',
  });

  const [saving, setSaving] = useState(false);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreview, setLogoPreview] = useState<string | null>(software.logo_url || null);
  const [removeLogo, setRemoveLogo] = useState(false);

  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setLogoFile(file);
      setRemoveLogo(false);
      const reader = new FileReader();
      reader.onload = () => {
        setLogoPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleRemoveLogo = () => {
    setLogoFile(null);
    setLogoPreview(null);
    setRemoveLogo(true);
  };

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

    const payload: Record<string, any> = {
      _method: 'PUT',
      name: form.name,
      is_active: form.is_active ? 1 : 0,
      default_status: form.default_status,
      pricing_type: form.pricing_type,
      price: form.pricing_type === 'single' && form.price !== '' ? parseFloat(form.price) : null,
      reseller_price: form.pricing_type === 'single' && form.reseller_price !== '' ? parseFloat(form.reseller_price) : null,
      currency: form.currency || 'USD',
      billing_cycle: form.billing_cycle || 'lifetime',
      billing_days: form.pricing_type === 'single' && form.billing_cycle === 'custom' && form.billing_days !== '' ? parseInt(form.billing_days) : null,
      whatsapp_number: form.whatsapp_number || null,
      payment_instructions: form.payment_instructions || null,
      show_price: form.show_price ? 1 : 0,
      show_whatsapp: form.show_whatsapp ? 1 : 0,
      trial_enabled: form.trial_enabled ? 1 : 0,
      trial_days: form.trial_days !== '' ? parseInt(form.trial_days) : 1,
      remove_logo: removeLogo ? 1 : 0,
    };

    if (logoFile) {
      payload.logo = logoFile;
    }

    router.post(
      route('admin.serial-softwares.settings.update', software.id),
      payload,
      {
        forceFormData: true,
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

  const handleDeleteKey = async (keyId: number) => {
    const accepted = await confirm({
      title: __('general.are_you_sure'),
      description: __('general.confirm_delete_software_key'),
      variant: 'danger',
      confirmLabel: __('general.delete'),
    });
    if (!accepted) return;
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

  const handleDeletePackage = async (packageId: number) => {
    const accepted = await confirm({
      title: __('general.are_you_sure'),
      description: __('general.confirm_delete_package'),
      variant: 'danger',
      confirmLabel: __('general.delete'),
    });
    if (!accepted) return;
    router.delete(route('admin.serial-softwares.packages.destroy', [software.id, packageId]), {
      preserveScroll: true,
    });
  };

  return (
    <AdminSidebarLayout
      title={`${software.name} - ${__('general.settings')}`}
      header={software.name}
    >
      <Head title={`${software.name} - ${__('general.settings')}`} />

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
                <span>{__('general.serial_softwares')}</span>
              </Link>
              <span>/</span>
              <span className="font-medium text-foreground">{software.name}</span>
              <span>/</span>
              <span>{__('general.settings')}</span>
            </div>

            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold tracking-tight">{software.name}</h1>
              {form.is_active ? (
                <Badge variant="outline" className="border-green-500/30 text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/20 text-xs gap-1 font-normal">
                  <ShieldCheck className="w-3 h-3" />
                  <span>{__('general.active')}</span>
                </Badge>
              ) : (
                <Badge variant="destructive" className="text-xs gap-1 font-normal">
                  <ShieldAlert className="w-3 h-3" />
                  <span>{__('general.deactivated')}</span>
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
              <span>{__('general.back_to_list')}</span>
            </Link>

            <Button
              type="button"
              onClick={handleSaveSettings}
              disabled={saving}
              className="gap-2"
            >
              <Save className="w-4 h-4" />
              <span>{saving ? __('general.saving') : __('general.save_changes')}</span>
            </Button>
          </div>
        </div>

        {/* Inactive Kill Switch Warning Banner */}
        {!form.is_active && (
          <div className="p-4 rounded-xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/30 text-red-900 dark:text-red-200 flex items-start gap-3">
            <ShieldAlert className="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
            <div className="space-y-1">
              <p className="font-semibold text-sm">
                {__('general.software_master_disabled_title')}
              </p>
              <p className="text-xs text-red-700 dark:text-red-300">
                {__('general.software_master_disabled_desc')}
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
                <span>{__('general.general_status_settings')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.general_status_desc')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Master Kill Switch */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-lg border bg-muted/30 gap-4">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    <Label htmlFor="master-active" className="text-sm font-semibold cursor-pointer">
                      {__('general.software_active_as_whole')}
                    </Label>
                    {form.is_active ? (
                      <span className="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-medium">
                        {__('general.running')}
                      </span>
                    ) : (
                      <span className="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium">
                        {__('general.stopped')}
                      </span>
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {__('general.master_active_hint')}
                  </p>
                </div>
                <Switch
                  id="master-active"
                  checked={form.is_active}
                  onCheckedChange={(checked) => setForm({ ...form, is_active: checked })}
                />
              </div>

              {/* Program Logo Upload */}
              <div className="p-4 rounded-xl border bg-muted/20 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label className="text-xs font-semibold">
                      {__('general.program_logo')}
                    </Label>
                    <p className="text-[11px] text-muted-foreground">
                      {__('general.program_logo_hint')}
                    </p>
                  </div>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                  {logoPreview ? (
                    <div className="relative group shrink-0">
                      <img
                        src={logoPreview}
                        alt={__('admin.serial_software_logo_alt')}
                        className="w-20 h-20 object-contain rounded-xl border bg-background p-1.5 shadow-xs"
                      />
                    </div>
                  ) : (
                    <div className="w-20 h-20 rounded-xl border border-dashed flex flex-col items-center justify-center bg-muted/30 text-muted-foreground shrink-0">
                      <ImageIcon className="w-8 h-8 opacity-40 mb-1" />
                      <span className="text-[10px]">{__('general.no_logo')}</span>
                    </div>
                  )}

                  <div className="flex flex-wrap items-center gap-2">
                    <input
                      type="file"
                      id="software-logo-input"
                      accept="image/png,image/jpeg,image/webp,image/svg+xml"
                      className="hidden"
                      onChange={handleLogoChange}
                    />
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      className="gap-1.5 text-xs"
                      onClick={() => document.getElementById('software-logo-input')?.click()}
                    >
                      <Upload className="w-3.5 h-3.5" />
                      <span>{logoPreview ? __('general.change_logo') : __('general.upload_logo')}</span>
                    </Button>

                    {logoPreview && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="gap-1 text-xs text-destructive hover:bg-destructive/10"
                        onClick={handleRemoveLogo}
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                        <span>{__('general.remove_logo')}</span>
                      </Button>
                    )}
                  </div>
                </div>
              </div>

              {/* Identity & Default Device Status */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="sw-name" className="text-xs font-semibold">
                    {__('general.software_program_name')}
                  </Label>
                  <Input
                    id="sw-name"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    required
                    className="font-medium"
                  />
                  <p className="text-[11px] text-muted-foreground">
                    {__('general.program_name_hint')}
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="sw-default-status" className="text-xs font-semibold">
                    {__('general.default_status_for_new_devices')}
                  </Label>
                  <Select
                    value={form.default_status}
                    onValueChange={(val) => setForm({ ...form, default_status: val || 'active' })}
                  >
                    <SelectTrigger id="sw-default-status">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">{__('general.active')}</SelectItem>
                      <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-[11px] text-muted-foreground">
                    {__('general.default_status_hint')}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* SECTION 1.5: Free Trial Configuration */}
          <Card className="w-full border-border/80 shadow-xs">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Clock className="w-4 h-4 text-emerald-600" />
                    <span>{__('general.free_trial_configuration')}</span>
                  </CardTitle>
                  <CardDescription>
                    {__('general.free_trial_desc')}
                  </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Switch
                    checked={form.trial_enabled}
                    onCheckedChange={(checked) => setForm({ ...form, trial_enabled: checked })}
                    id="sw-trial-enabled"
                  />
                  <Label htmlFor="sw-trial-enabled" className="text-xs font-semibold cursor-pointer">
                    {form.trial_enabled ? __('general.enabled') : __('general.disabled')}
                  </Label>
                </div>
              </div>
            </CardHeader>
            {form.trial_enabled && (
              <CardContent className="pt-0">
                <div className="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 space-y-3">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="sw-trial-days" className="text-xs font-semibold">
                        {__('general.trial_duration_days')}
                      </Label>
                      <Input
                        id="sw-trial-days"
                        type="number"
                        min="1"
                        max="365"
                        value={form.trial_days}
                        onChange={(e) => setForm({ ...form, trial_days: e.target.value })}
                        placeholder="1"
                        className="h-9 text-xs"
                      />
                      <p className="text-[11px] text-muted-foreground">
                        {__('general.trial_duration_hint')}
                      </p>
                    </div>
                    <div className="p-3 bg-background/80 rounded-lg border border-border/60 text-xs text-muted-foreground flex flex-col justify-center">
                      <span className="font-semibold text-foreground mb-1">
                        {__('general.trial_claim_rule')}
                      </span>
                      <span>
                        {__('general.trial_claim_rule_desc')}
                      </span>
                    </div>
                  </div>
                </div>
              </CardContent>
            )}
          </Card>

          {/* SECTION 2: Pricing Strategy & Packages */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <CreditCard className="w-4 h-4 text-primary" />
                <span>{__('general.pricing_and_packages_model')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.pricing_model_desc')}
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
                      <span className="font-semibold text-sm">{__('general.free_mode')}</span>
                      {form.pricing_type === 'free' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.free_mode_desc')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('general.instant_access')}
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
                      <span className="font-semibold text-sm">{__('general.single_paid_plan')}</span>
                      {form.pricing_type === 'single' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.single_paid_desc')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('general.single_tier')}
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
                      <span className="font-semibold text-sm">{__('general.multi_packages')}</span>
                      {form.pricing_type === 'packages' && <CheckCircle2 className="w-4 h-4 text-primary" />}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {__('general.multi_packages_desc')}
                    </p>
                  </div>
                  <div className="pt-3 mt-2 border-t text-xs font-medium text-muted-foreground">
                    {__('admin.serial_software_packages_defined', { count: (software.packages || []).length })}
                  </div>
                </div>
              </div>

              {/* SINGLE PAID PLAN CONFIGURATION */}
              {form.pricing_type === 'single' && (
                <div className="p-4 rounded-xl border bg-muted/20 space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="text-sm font-semibold">{__('general.single_plan_details')}</h3>
                    <Badge variant="outline" className="text-xs font-normal">
                      {__('general.requires_payment')}
                    </Badge>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="space-y-1.5">
                      <Label htmlFor="single-price" className="text-xs font-semibold">
                        {__('general.customer_price')}
                      </Label>
                      <Input
                        id="single-price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder={__('admin.serial_software_price_placeholder')}
                        value={form.price}
                        onChange={(e) => setForm({ ...form, price: e.target.value })}
                        required={form.pricing_type === 'single'}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="single-reseller-price" className="text-xs font-semibold">
                        {__('general.reseller_price')}
                      </Label>
                      <Input
                        id="single-reseller-price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder={__('admin.serial_software_reseller_price_placeholder')}
                        value={form.reseller_price}
                        onChange={(e) => setForm({ ...form, reseller_price: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="single-currency" className="text-xs font-semibold">
                        {__('general.currency')}
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
                        {__('general.billing_cycle')}
                      </Label>
                      <Select
                        value={form.billing_cycle}
                        onValueChange={(val: any) => setForm({ ...form, billing_cycle: val })}
                      >
                        <SelectTrigger id="single-cycle">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="lifetime">{__('general.cycle_lifetime')}</SelectItem>
                          <SelectItem value="monthly">{__('general.cycle_monthly')}</SelectItem>
                          <SelectItem value="annual">{__('general.cycle_annual')}</SelectItem>
                          <SelectItem value="custom">{__('general.cycle_custom')}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {form.billing_cycle === 'custom' && (
                    <div className="w-full sm:w-1/3 space-y-1.5 pt-2">
                      <Label htmlFor="single-days" className="text-xs font-semibold">
                        {__('general.custom_duration_days')}
                      </Label>
                      <Input
                        id="single-days"
                        type="number"
                        min="1"
                        placeholder={__('admin.serial_software_billing_days_placeholder')}
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
                      <h3 className="text-sm font-semibold">{__('general.defined_packages')}</h3>
                      <p className="text-xs text-muted-foreground">
                        {__('general.defined_packages_desc')}
                      </p>
                    </div>

                    <Button
                      type="button"
                      size="sm"
                      onClick={openNewPackageModal}
                      className="gap-1.5"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      <span>{__('general.add_package')}</span>
                    </Button>
                  </div>

                  {(!software.packages || software.packages.length === 0) ? (
                    <div className="p-8 border rounded-xl text-center space-y-3 bg-muted/20">
                      <PackageIcon className="w-10 h-10 text-muted-foreground/40 mx-auto" />
                      <div className="space-y-1">
                        <p className="text-sm font-medium">{__('general.no_packages_yet')}</p>
                        <p className="text-xs text-muted-foreground">
                          {__('general.no_packages_hint')}
                        </p>
                      </div>
                      <Button type="button" variant="outline" size="sm" onClick={openNewPackageModal}>
                        <Plus className="w-3.5 h-3.5 me-1.5" />
                        <span>{__('general.create_first_package')}</span>
                      </Button>
                    </div>
                  ) : (
                    <div className="border rounded-xl overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/40">
                            <TableHead>{__('general.package_name')}</TableHead>
                            <TableHead>{__('general.customer_price')}</TableHead>
                            <TableHead>{__('general.reseller_price')}</TableHead>
                            <TableHead>{__('general.cycle')}</TableHead>
                            <TableHead>{__('general.key_overrides')}</TableHead>
                            <TableHead>{__('general.status')}</TableHead>
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
                                        {__('general.default')}
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
                                    ? __('general.lifetime')
                                    : pkg.billing_cycle === 'custom'
                                    ? `${pkg.billing_days || 0} ${__('general.days')}`
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
                                      {__('general.inherits_defaults')}
                                    </span>
                                  )}
                                </div>
                              </TableCell>
                              <TableCell>
                                {pkg.is_active ? (
                                  <span className="text-xs text-green-600 font-medium">{__('general.active')}</span>
                                ) : (
                                  <span className="text-xs text-muted-foreground">{__('general.disabled')}</span>
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
                                    title={__('general.edit')}
                                    aria-label={__('general.edit')}
                                  >
                                    <Edit2 className="w-3.5 h-3.5" />
                                  </Button>
                                  <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                    onClick={() => handleDeletePackage(pkg.id)}
                                    title={__('general.delete')}
                                    aria-label={__('general.delete')}
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

              {/* CONTACT & ACTIVATION DIALOG SETTINGS */}
              <div className="pt-6 border-t space-y-4">
                <div className="flex items-center gap-2">
                  <PhoneCall className="w-4 h-4 text-primary" />
                  <div>
                    <h3 className="text-sm font-semibold">{__('general.activation_contact_settings')}</h3>
                    <p className="text-xs text-muted-foreground">
                      {__('general.activation_contact_settings_desc')}
                    </p>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="software-whatsapp" className="text-xs font-semibold">
                      {__('general.whatsapp_number')}
                    </Label>
                    <Input
                      id="software-whatsapp"
                      placeholder={__('admin.serial_software_whatsapp_placeholder')}
                      value={form.whatsapp_number}
                      onChange={(e) => setForm({ ...form, whatsapp_number: e.target.value })}
                    />
                    <p className="text-[11px] text-muted-foreground">
                      {__('general.whatsapp_hint')}
                    </p>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="software-instructions" className="text-xs font-semibold">
                      {__('general.payment_instructions')}
                    </Label>
                    <Textarea
                      id="software-instructions"
                      rows={3}
                      placeholder={__('admin.serial_software_payment_instructions_placeholder')}
                      value={form.payment_instructions}
                      onChange={(e) => setForm({ ...form, payment_instructions: e.target.value })}
                      className="text-xs resize-none"
                    />
                  </div>
                </div>

                {/* Display Toggles */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl border bg-muted/10">
                  <div className="flex items-center justify-between gap-3">
                    <div className="space-y-0.5">
                      <Label htmlFor="setting-show-price" className="text-xs font-medium cursor-pointer">
                        {__('general.show_price_in_dialog')}
                      </Label>
                      <p className="text-[11px] text-muted-foreground">
                        {__('general.show_price_in_dialog_desc')}
                      </p>
                    </div>
                    <Switch
                      id="setting-show-price"
                      checked={form.show_price}
                      onCheckedChange={(checked) => setForm({ ...form, show_price: checked })}
                    />
                  </div>

                  <div className="flex items-center justify-between gap-3">
                    <div className="space-y-0.5">
                      <Label htmlFor="setting-show-wa" className="text-xs font-medium cursor-pointer">
                        {__('general.show_whatsapp_in_dialog')}
                      </Label>
                      <p className="text-[11px] text-muted-foreground">
                        {__('general.show_whatsapp_in_dialog_desc')}
                      </p>
                    </div>
                    <Switch
                      id="setting-show-wa"
                      checked={form.show_whatsapp}
                      onCheckedChange={(checked) => setForm({ ...form, show_whatsapp: checked })}
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* SECTION 3: Master Keys & Values (Key-Value Schema) */}
          <Card className="w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Key className="w-4 h-4 text-primary" />
                <span>{__('general.master_custom_keys')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.master_keys_desc')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Existing Master Keys Table */}
              {(!software.custom_keys || software.custom_keys.length === 0) ? (
                <div className="p-4 border rounded-lg text-center text-sm text-muted-foreground bg-muted/20">
                  {__('general.no_master_keys_yet')}
                </div>
              ) : (
                <div className="border rounded-lg overflow-hidden">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/40">
                        <TableHead className="text-xs font-semibold">{__('general.key_name')}</TableHead>
                        <TableHead className="text-xs font-semibold">{__('general.default_value')}</TableHead>
                        <TableHead className="text-xs font-semibold">{__('general.description')}</TableHead>
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
                              <span className="italic text-muted-foreground/60">{__('general.empty')}</span>
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
                              title={__('general.delete')}
                              aria-label={__('general.delete')}
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
                  {__('general.add_new_master_key')}
                </Label>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-name" className="text-xs">
                      {__('general.key_identifier')}
                    </Label>
                    <Input
                      id="new-key-name"
                      placeholder={__('admin.serial_software_key_placeholder')}
                      value={keyForm.key}
                      onChange={(e) => setKeyForm({ ...keyForm, key: e.target.value })}
                      className="h-9 font-mono text-xs"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-default" className="text-xs">
                      {__('general.default_value')}
                    </Label>
                    <Input
                      id="new-key-default"
                      placeholder={__('admin.serial_software_default_value_placeholder')}
                      value={keyForm.default_value}
                      onChange={(e) => setKeyForm({ ...keyForm, default_value: e.target.value })}
                      className="h-9 font-mono text-xs"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="new-key-desc" className="text-xs">
                      {__('general.description')}
                    </Label>
                    <div className="flex gap-2">
                      <Input
                        id="new-key-desc"
                        placeholder={__('admin.serial_software_key_description_placeholder')}
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
                        <span>{keySubmitting ? __('general.saving') : __('general.add')}</span>
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
                <span>{__('general.payment_contact_and_instructions')}</span>
              </CardTitle>
              <CardDescription>
                {__('general.payment_contact_desc')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="sw-whatsapp-number" className="text-xs font-semibold">
                  {__('general.whatsapp_number')}
                </Label>
                <Input
                  id="sw-whatsapp-number"
                  placeholder={__('admin.serial_software_whatsapp_placeholder')}
                  value={form.whatsapp_number}
                  onChange={(e) => setForm({ ...form, whatsapp_number: e.target.value })}
                  className="max-w-md font-mono"
                />
                <p className="text-[11px] text-muted-foreground">
                  {__('general.whatsapp_hint')}
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="sw-payment-notes" className="text-xs font-semibold">
                  {__('general.payment_instructions')}
                </Label>
                <Textarea
                  id="sw-payment-notes"
                  rows={4}
                  placeholder={__('admin.serial_software_payment_instructions_placeholder')}
                  value={form.payment_instructions}
                  onChange={(e) => setForm({ ...form, payment_instructions: e.target.value })}
                  className="text-xs"
                />
                <p className="text-[11px] text-muted-foreground">
                  {__('general.instructions_hint')}
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Sticky Bottom Save Bar */}
          <div className="flex items-center justify-between p-4 rounded-xl border bg-card shadow-sm">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <span>{__('general.total_devices')}: <strong>{software.total_devices}</strong></span>
              <span>•</span>
              <span className="text-green-600 font-medium">{__('general.active')}: {software.active_count}</span>
              <span>•</span>
              <span>{__('general.inactive')}: {software.inactive_count}</span>
            </div>

            <div className="flex items-center gap-2">
              <Link
                href={route('admin.serial-softwares.index')}
                className="px-4 py-2 text-sm font-medium rounded-md border hover:bg-muted transition-colors"
              >
                {__('general.cancel')}
              </Link>
              <Button
                type="submit"
                disabled={saving}
                className="gap-2 px-5"
              >
                <Save className="w-4 h-4" />
                <span>{saving ? __('general.saving') : __('general.save_settings')}</span>
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
              <span>{editingPackage ? __('general.edit_package') : __('general.add_new_package')}</span>
            </DialogTitle>
            <DialogDescription>
              {__('general.package_modal_desc')}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSavePackage} className="space-y-4 pt-2">
            {/* Name, Customer Price, Reseller Price, Currency */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="pkg-name" className="text-xs font-semibold">{__('general.package_name')}</Label>
                <Input
                  id="pkg-name"
                  placeholder={__('admin.serial_software_package_name_placeholder')}
                  value={packageForm.name}
                  onChange={(e) => setPackageForm({ ...packageForm, name: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="pkg-price" className="text-xs font-semibold">{__('general.customer_price')}</Label>
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
                <Label htmlFor="pkg-reseller-price" className="text-xs font-semibold">{__('general.reseller_price')}</Label>
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
                <Label htmlFor="pkg-currency" className="text-xs font-semibold">{__('general.currency')}</Label>
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
                <Label htmlFor="pkg-cycle" className="text-xs font-semibold">{__('general.billing_cycle')}</Label>
                <Select
                  value={packageForm.billing_cycle}
                  onValueChange={(val: any) => setPackageForm({ ...packageForm, billing_cycle: val })}
                >
                  <SelectTrigger id="pkg-cycle">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="monthly">{__('general.monthly')}</SelectItem>
                    <SelectItem value="annual">{__('general.annual')}</SelectItem>
                    <SelectItem value="lifetime">{__('general.lifetime')}</SelectItem>
                    <SelectItem value="custom">{__('general.custom_days')}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {packageForm.billing_cycle === 'custom' ? (
                <div className="space-y-1.5">
                  <Label htmlFor="pkg-days" className="text-xs font-semibold">{__('general.days_count')}</Label>
                  <Input
                    id="pkg-days"
                    type="number"
                    min="1"
                    placeholder={__('admin.serial_software_billing_days_placeholder')}
                    value={packageForm.billing_days}
                    onChange={(e) => setPackageForm({ ...packageForm, billing_days: e.target.value })}
                    required
                  />
                </div>
              ) : (
                <div className="space-y-1.5">
                  <Label htmlFor="pkg-sort" className="text-xs font-semibold">{__('general.sort_order')}</Label>
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
              <Label htmlFor="pkg-desc" className="text-xs font-semibold">{__('general.description')}</Label>
              <Textarea
                id="pkg-desc"
                rows={2}
                placeholder={__('admin.serial_software_package_description_placeholder')}
                value={packageForm.description}
                onChange={(e) => setPackageForm({ ...packageForm, description: e.target.value })}
                className="text-xs"
              />
            </div>

            {/* Switches */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                <Label htmlFor="pkg-active" className="text-xs font-medium cursor-pointer">
                  {__('general.active_package')}
                </Label>
                <Switch
                  id="pkg-active"
                  checked={packageForm.is_active}
                  onCheckedChange={(checked) => setPackageForm({ ...packageForm, is_active: checked })}
                />
              </div>

              <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                <Label htmlFor="pkg-default" className="text-xs font-medium cursor-pointer">
                  {__('general.default_recommended')}
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
                {__('general.package_key_overrides')}
              </Label>
              <p className="text-xs text-muted-foreground">
                {__('general.key_overrides_hint')}
              </p>

              {(!software.custom_keys || software.custom_keys.length === 0) ? (
                <p className="text-xs text-muted-foreground italic p-2 border rounded bg-muted/10">
                  {__('general.no_master_keys_to_override')}
                </p>
              ) : (
                <div className="space-y-2 border rounded-lg p-3 bg-muted/20">
                  {software.custom_keys.map((k) => (
                    <div key={k.id} className="grid grid-cols-1 sm:grid-cols-3 items-center gap-2">
                      <div className="sm:col-span-1">
                        <span className="font-mono text-xs font-semibold">{k.key}</span>
                        {k.default_value && (
                          <span className="text-[10px] text-muted-foreground block">
                            {__('general.default')}: {k.default_value}
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
                {__('general.cancel')}
              </Button>
              <Button
                type="submit"
                size="sm"
                disabled={packageSubmitting}
                className="gap-1.5"
              >
                <Check className="w-3.5 h-3.5" />
                <span>{packageSubmitting ? __('general.saving') : __('general.save_package')}</span>
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
      {confirmDialog}
    </AdminSidebarLayout>
  );
}
