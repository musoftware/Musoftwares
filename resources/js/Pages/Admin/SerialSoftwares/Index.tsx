import React, { useState, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger } from
'@/Components/ui/dropdown-menu';
import Pagination from '@/Components/Pagination';
import {
  Layers,
  Plus,
  MoreHorizontal,
  Download,
  X,
  ChevronUp,
  ChevronDown,
  Monitor,
  Trash2,
  Eye,
  ChevronsUpDown,
  Key,
  CreditCard,
  MessageSquare,
  DollarSign,
  Sliders,
  Settings as SettingsIcon,
  ShieldAlert,
  Package as PackageIcon,
} from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';

interface SoftwareKey {
  id: number;
  serial_software_id: number;
  key: string;
  default_value: string | null;
  description: string | null;
  created_at?: string;
}

interface Software {
  id: number;
  name: string;
  is_active: boolean;
  default_status: string;
  pricing_type?: 'free' | 'single' | 'packages';
  requires_payment?: boolean;
  show_price?: boolean;
  show_whatsapp?: boolean;
  price?: number | null;
  currency?: string | null;
  billing_cycle?: string;
  billing_days?: number | null;
  packages_count?: number;
  whatsapp_number?: string | null;
  payment_instructions?: string | null;
  total_devices: number;
  active_count: number;
  inactive_count: number;
  blocked_count: number;
  created_at: string;
  created_at_full: string;
  custom_keys?: SoftwareKey[];
}

interface PaginatedData {
  data: Software[];
  links: any[];
  meta?: any;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Stats {
  total_softwares: number;
  total_devices_all: number;
  active_devices_all: number;
  inactive_devices_all: number;
  blocked_devices_all: number;
}

interface Filters {
  search: string;
  default_status: string | null;
  sort_by: string;
  direction: string;
  per_page: number;
}

interface Props {
  softwares: PaginatedData;
  filters: Filters;
  stats: Stats;
}

export default function SerialSoftwaresIndex({ softwares, filters, stats }: Props) {
  const [form, setForm] = useState({ name: '', default_status: 'active' });
  const [showAddForm, setShowAddForm] = useState(false);
  const [search, setSearch] = useState(filters.search || '');

  // Check if any filter is active
  const hasActiveFilters = !!(filters.search || filters.default_status);

  const applyFilters = useCallback((newFilters: Partial<Filters>) => {
    router.get(
      route('admin.serial-softwares.index'),
      { ...filters, ...newFilters, page: 1 },
      { preserveState: true, preserveScroll: true }
    );
  }, [filters]);

  const handleSearch = useCallback((e: React.FormEvent) => {
    e.preventDefault();
    applyFilters({ search });
  }, [search, applyFilters]);

  const handleSort = useCallback((column: string) => {
    const direction = filters.sort_by === column && filters.direction === 'asc' ? 'desc' : 'asc';
    applyFilters({ sort_by: column, direction });
  }, [filters, applyFilters]);

  const clearFilters = useCallback(() => {
    setSearch('');
    router.get(route('admin.serial-softwares.index'), {}, { preserveState: true, preserveScroll: true });
  }, []);

  const [activeSoftwareForKeys, setActiveSoftwareForKeys] = useState<Software | null>(null);
  const [keyForm, setKeyForm] = useState({ key: '', default_value: '', description: '' });
  const [keySubmitting, setKeySubmitting] = useState(false);

  const openKeysModal = (sw: Software) => {
    setActiveSoftwareForKeys(sw);
    setKeyForm({ key: '', default_value: '', description: '' });
  };

  const handleSaveKey = (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeSoftwareForKeys || !keyForm.key.trim()) return;
    setKeySubmitting(true);
    router.post(
      route('admin.serial-softwares.keys.store', activeSoftwareForKeys.id),
      keyForm,
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page: any) => {
          setKeyForm({ key: '', default_value: '', description: '' });
          const updatedList = page.props.softwares?.data as Software[];
          const updatedSw = updatedList?.find(s => s.id === activeSoftwareForKeys.id);
          if (updatedSw) {
            setActiveSoftwareForKeys(updatedSw);
          }
        },
        onFinish: () => setKeySubmitting(false),
      }
    );
  };

  const handleDeleteKey = (keyId: number) => {
    if (!activeSoftwareForKeys) return;
    if (!confirm(__('general.confirm_delete_software_key'))) return;
    router.delete(
      route('admin.serial-softwares.keys.destroy', [activeSoftwareForKeys.id, keyId]),
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page: any) => {
          const updatedList = page.props.softwares?.data as Software[];
          const updatedSw = updatedList?.find(s => s.id === activeSoftwareForKeys.id);
          if (updatedSw) {
            setActiveSoftwareForKeys(updatedSw);
          }
        },
      }
    );
  };

  const [activeSoftwareForPayment, setActiveSoftwareForPayment] = useState<Software | null>(null);
  const [paymentForm, setPaymentForm] = useState({
    requires_payment: false,
    show_price: true,
    show_whatsapp: true,
    price: '',
    currency: 'USD',
    whatsapp_number: '',
    payment_instructions: '',
  });
  const [paymentSubmitting, setPaymentSubmitting] = useState(false);

  const openPaymentModal = (sw: Software) => {
    setActiveSoftwareForPayment(sw);
    setPaymentForm({
      requires_payment: !!sw.requires_payment,
      show_price: sw.show_price !== undefined ? !!sw.show_price : true,
      show_whatsapp: sw.show_whatsapp !== undefined ? !!sw.show_whatsapp : true,
      price: sw.price !== null && sw.price !== undefined ? String(sw.price) : '',
      currency: sw.currency || '',
      whatsapp_number: sw.whatsapp_number || '',
      payment_instructions: sw.payment_instructions || '',
    });
  };

  const handleSavePayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeSoftwareForPayment) return;
    setPaymentSubmitting(true);
    router.patch(
      route('admin.serial-softwares.payment', activeSoftwareForPayment.id),
      {
        requires_payment: paymentForm.requires_payment,
        show_price: paymentForm.show_price,
        show_whatsapp: paymentForm.show_whatsapp,
        price: paymentForm.price !== '' ? parseFloat(paymentForm.price) : null,
        currency: paymentForm.currency || null,
        whatsapp_number: paymentForm.whatsapp_number || null,
        payment_instructions: paymentForm.payment_instructions || null,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          setActiveSoftwareForPayment(null);
        },
        onFinish: () => setPaymentSubmitting(false),
      }
    );
  };

  const store = (e: React.FormEvent) => {
    e.preventDefault();
    router.post(route('admin.serial-softwares.store'), form, {
      onSuccess: () => setForm({ name: '', default_status: 'active' })
    });
  };

  const updateStatus = (sw: Software, status: string) => {
    router.patch(route('admin.serial-softwares.status', sw.id), { status }, { preserveScroll: true });
  };

  const destroy = (sw: Software) => {
    if (!confirm(__('general.confirm_delete_software_modal', { name: sw.name }))) return;
    router.delete(route('admin.serial-softwares.destroy', sw.id), { preserveScroll: true });
  };

  const SortHeader = ({ column, children }: {column: string;children: React.ReactNode;}) => {
    const isActive = filters.sort_by === column;
    return (
      <button
        onClick={() => handleSort(column)}
        className="inline-flex items-center gap-1 hover:text-foreground transition-colors text-xs font-medium uppercase tracking-wide">
        
                {children}
                {isActive ?
        filters.direction === 'asc' ?
        <ChevronUp className="w-3 h-3" /> :

        <ChevronDown className="w-3 h-3" /> :


        <ChevronsUpDown className="w-3 h-3 opacity-40" />
        }
            </button>);

  };

  return (
    <AdminSidebarLayout title={__('general.serial_softwares')} header={__('general.serial_softwares')}>
            <Head title={__('general.serial_softwares')} />

            <div className="p-4 sm:p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{__('general.serial_softwares')}</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        {__('general.software_registry_auto_created_on_first_api_check_in')}
                    </p>
                </div>

                <Separator />

                {/* Stats Cards */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground font-medium">{__('general.total_softwares')}</p>
                            <p className="text-2xl font-bold mt-1">{stats.total_softwares}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground font-medium">{__('general.total_devices')}</p>
                            <p className="text-2xl font-bold mt-1">{stats.total_devices_all}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground font-medium">{__('general.active_devices')}</p>
                            <p className="text-2xl font-bold mt-1 text-green-600">{stats.active_devices_all}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground font-medium">{__('general.inactive_devices')}</p>
                            <p className="text-2xl font-bold mt-1">{stats.inactive_devices_all}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground font-medium">{__('general.blocked_devices')}</p>
                            <p className="text-2xl font-bold mt-1 text-red-600">{stats.blocked_devices_all}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Add Software (Collapsible) */}
                <Card>
                    <CardHeader
            className="pb-3 cursor-pointer select-none"
            onClick={() => setShowAddForm(!showAddForm)}>
            
                        <div className="flex items-center justify-end gap-4">
                            <CardTitle className="me-auto text-sm font-semibold">{__('general.add_software_manually')}</CardTitle>
                            <Button variant="ghost" size="sm" className="h-6 w-6 p-0">
                                {showAddForm ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                            </Button>
                        </div>
                    </CardHeader>
                    {showAddForm &&
          <CardContent>
                            <form onSubmit={store} className="flex flex-col sm:flex-row flex-wrap gap-3 items-end">
                                <div className="flex-1 min-w-48 w-full sm:w-auto">
                                    <Label className="text-xs mb-1 block">{__('general.software_name')}</Label>
                                    <Input
                  placeholder={__('general.e_g_myapp_exe')}
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  required />
                
                                </div>
                                <div className="w-full sm:w-40">
                                    <Label className="text-xs mb-1 block">{__('general.default_status')}</Label>
                                    <Select
                  value={form.default_status}
                  onValueChange={(v) => setForm((f) => ({ ...f, default_status: v || 'active' }))}>
                  
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">{__('general.active')}</SelectItem>
                                            <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="submit" className="gap-2 w-full sm:w-auto">
                                    <Plus className="w-4 h-4" /> {__('general.add')}
                                </Button>
                            </form>
                        </CardContent>
          }
                </Card>

                {/* Filter Bar */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-col sm:flex-row flex-wrap gap-3 items-end">
                            {/* Search */}
                            <form onSubmit={handleSearch} className="flex-1 min-w-48 w-full sm:w-auto">
                                <Label className="text-xs mb-1 block">{__('general.search')}</Label>
                                <div className="flex gap-2">
                                    <Input
                    placeholder={__('general.search_by_software_name')}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="flex-1" />
                  
                                    <Button type="submit" variant="outline" size="sm" className="px-3 shrink-0">
                                        {__('general.go')}
                                    </Button>
                                </div>
                            </form>

                            {/* Status Filter */}
                            <div className="w-full sm:w-36">
                                <Label className="text-xs mb-1 block">{__('general.default_status')}</Label>
                                <Select
                  value={filters.default_status || 'all'}
                  onValueChange={(v) => applyFilters({ default_status: v === 'all' ? null : v })}>
                  
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{__('general.all')}</SelectItem>
                                        <SelectItem value="active">{__('general.active')}</SelectItem>
                                        <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Per Page */}
                            <div className="w-full sm:w-28">
                                <Label className="text-xs mb-1 block">{__('general.per_page')}</Label>
                                <Select
                  value={String(filters.per_page)}
                  onValueChange={(v) => applyFilters({ per_page: parseInt(v ?? '20') })}>
                  
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="20">20</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex gap-2 w-full sm:w-auto">
                                <a
                  href={route('admin.serial-softwares.export')}
                  className="inline-flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-muted transition-colors w-full sm:w-auto">
                  
                                    <Download className="w-4 h-4" />
                                    {__('general.export_csv')}
                                </a>

                                {hasActiveFilters &&
                <Button variant="ghost" size="sm" onClick={clearFilters} className="gap-1 shrink-0">
                                        <X className="w-3 h-3" />
                                        {__('general.clear')}
                                    </Button>
                }
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <SortHeader column="name">{__('general.software')}</SortHeader>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <SortHeader column="total_devices">{__('general.total')}</SortHeader>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <SortHeader column="active_count">{__('general.active')}</SortHeader>
                                        </TableHead>
                                        <TableHead className="text-center hidden sm:table-cell">{__('general.inactive')}</TableHead>
                                        <TableHead className="text-center hidden sm:table-cell">{__('general.blocked')}</TableHead>
                                        <TableHead className="text-center">{__('general.custom_keys', {}, 'Keys')}</TableHead>
                                        <TableHead className="text-center">{__('general.pricing', {}, 'Payment / Pricing')}</TableHead>
                                        <TableHead className="hidden md:table-cell">
                                            <SortHeader column="default_status">{__('general.default_status')}</SortHeader>
                                        </TableHead>
                                        <TableHead className="hidden lg:table-cell">
                                            <SortHeader column="created_at">{__('general.registered')}</SortHeader>
                                        </TableHead>
                                        <TableHead className="w-10"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(softwares.data as any).length === 0 &&
                  <TableRow>
                                            <TableCell colSpan={10} className="text-center py-16 text-muted-foreground">
                                                <div className="flex flex-col items-center gap-2">
                                                    <Layers className="w-8 h-8 opacity-30" />
                                                    <p>{__('general.no_software_registered_yet')}</p>
                                                    {hasActiveFilters &&
                        <Button variant="link" size="sm" onClick={clearFilters}>
                                                            {__('general.clear_filters')}
                                                        </Button>
                        }
                                                </div>
                                            </TableCell>
                                        </TableRow>
                  }
                                    {(softwares.data as any).map((sw) =>
                  <TableRow key={sw.id}>
                                            {/* Software Name - links to devices */}
                                            <TableCell>
                                                <Link
                        href={route('admin.serial-devices.index', { software_id: sw.id })}
                        className="flex items-center gap-3 hover:underline">
                        
                                                    <div className="w-8 h-8 rounded-lg border flex items-center justify-center bg-muted shrink-0">
                                                        <Layers className="w-4 h-4 text-muted-foreground" />
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="font-medium">{sw.name}</span>
                                                        {sw.is_active === false && (
                                                            <span className="text-[10px] text-red-600 dark:text-red-400 font-semibold flex items-center gap-0.5">
                                                                <ShieldAlert className="w-3 h-3" />
                                                                {__('general.disabled_as_whole', {}, 'Disabled as whole')}
                                                            </span>
                                                        )}
                                                    </div>
                                                </Link>
                                            </TableCell>

                                            {/* Total Devices */}
                                            <TableCell className="text-center font-medium">
                                                {sw.total_devices}
                                            </TableCell>

                                            {/* Active */}
                                            <TableCell className="text-center">
                                                <span className="text-green-600 font-medium">{sw.active_count}</span>
                                            </TableCell>

                                            {/* Inactive */}
                                            <TableCell className="text-center text-muted-foreground hidden sm:table-cell">
                                                {sw.inactive_count}
                                            </TableCell>

                                            {/* Blocked */}
                                            <TableCell className="text-center hidden sm:table-cell">
                                                <span className={sw.blocked_count > 0 ? 'text-red-600 font-medium' : 'text-muted-foreground'}>
                                                    {sw.blocked_count}
                                                </span>
                                            </TableCell>

                                            {/* Keys button */}
                                            <TableCell className="text-center">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => openKeysModal(sw)}
                                                    className="h-7 text-xs gap-1.5 font-normal hover:bg-muted"
                                                    title={__('general.manage_keys', {}, 'Manage Keys')}
                                                >
                                                    <Key className="w-3.5 h-3.5 text-muted-foreground" />
                                                    <span>{sw.custom_keys?.length ?? 0}</span>
                                                </Button>
                                            </TableCell>

                                            {/* Pricing / Payment (Links to full Settings page) */}
                                            <TableCell className="text-center">
                                                <Link
                                                    href={route('admin.serial-softwares.settings', sw.id)}
                                                    className="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs hover:bg-muted transition-colors"
                                                    title={__('general.edit_settings', {}, 'Configure full software settings')}
                                                >
                                                    {sw.pricing_type === 'packages' ? (
                                                        <span className="inline-flex items-center gap-1 text-primary font-medium">
                                                            <PackageIcon className="w-3.5 h-3.5" />
                                                            <span>{sw.packages_count ? `${sw.packages_count} Plans` : __('general.packages', {}, 'Packages')}</span>
                                                        </span>
                                                    ) : sw.requires_payment ? (
                                                        <span className="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-medium">
                                                            <CreditCard className="w-3.5 h-3.5" />
                                                            <span>{sw.price ? `${sw.price} ${sw.currency || ''}`.trim() : __('general.paid', {}, 'Paid')}</span>
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 text-muted-foreground">
                                                            <span>{__('general.free_instant', {}, 'Free / Direct')}</span>
                                                        </span>
                                                    )}
                                                </Link>
                                            </TableCell>

                                            {/* Default Status (inline select) */}
                                            <TableCell className="hidden md:table-cell">
                                                <Select
                        value={sw.default_status}
                        onValueChange={(v) => updateStatus(sw, v || 'active')}>
                        
                                                    <SelectTrigger className="w-28 h-7 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="active">{__('general.active')}</SelectItem>
                                                        <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>

                                            {/* Registered */}
                                            <TableCell className="text-muted-foreground text-xs hidden lg:table-cell" title={sw.created_at_full}>
                                                {sw.created_at}
                                            </TableCell>

                                            {/* Actions Dropdown */}
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger render={<Button variant="ghost" className="h-8 w-8 p-0" />}>
                                                        <span className="sr-only">{__('general.open_menu')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" side="bottom">
                                                        <DropdownMenuItem
                                                            onClick={() => router.visit(route('admin.serial-softwares.settings', sw.id))}>
                                                            <Sliders className="w-4 h-4 me-2 text-primary" />
                                                            {__('general.settings_and_pricing', {}, 'Settings & Pricing')}
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem
                            onClick={() => router.visit(route('admin.serial-devices.index', { software_id: sw.id }))}>
                            
                                                            <Eye className="w-4 h-4 me-2" />
                                                            {__('general.view_devices')}
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem
                            onClick={() => openKeysModal(sw)}>
                            
                                                            <Key className="w-4 h-4 me-2" />
                                                            {__('general.manage_keys', {}, 'Manage Custom Keys')}
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem
                            onClick={() => openPaymentModal(sw)}>
                            
                                                            <CreditCard className="w-4 h-4 me-2" />
                                                            {__('general.payment_settings', {}, 'Payment & Pricing')}
                                                        </DropdownMenuItem>

                                                        {/* Mobile: show status changer in dropdown */}
                                                        <div className="md:hidden px-1.5 py-1">
                                                            <p className="text-xs text-muted-foreground mb-1">{__('general.default_status')}</p>
                                                            <Select
                              value={sw.default_status}
                              onValueChange={(v) => updateStatus(sw, v || 'active')}>
                              
                                                                <SelectTrigger className="w-full h-7 text-xs">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="active">{__('general.active')}</SelectItem>
                                                                    <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>

                                                        <DropdownMenuSeparator />

                                                        <DropdownMenuItem
                            variant="destructive"
                            onClick={() => destroy(sw)}>
                            
                                                            <Trash2 className="w-4 h-4 me-2" />
                                                            {__('general.delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                  )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Pagination */}
                <Pagination links={softwares.links} />
            </div>

            {/* Software Custom Keys Dialog */}
            <Dialog open={activeSoftwareForKeys !== null} onOpenChange={(open) => !open && setActiveSoftwareForKeys(null)}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Key className="w-5 h-5 text-muted-foreground" />
                            <span>{__('general.software_keys', {}, 'Software Keys')}: {activeSoftwareForKeys?.name}</span>
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.software_keys_desc', {}, 'Configure default key-value parameters for this software. Devices can inherit or override these settings.')}
                        </DialogDescription>
                    </DialogHeader>

                    {activeSoftwareForKeys && (
                        <div className="space-y-6 pt-2">
                            {/* Existing Keys Table */}
                            <div className="space-y-2">
                                <Label className="text-xs font-semibold uppercase text-muted-foreground">
                                    {__('general.existing_keys', {}, 'Defined Keys')} ({activeSoftwareForKeys.custom_keys?.length ?? 0})
                                </Label>

                                {(!activeSoftwareForKeys.custom_keys || activeSoftwareForKeys.custom_keys.length === 0) ? (
                                    <div className="p-4 border rounded-lg text-center text-sm text-muted-foreground bg-muted/30">
                                        {__('general.no_keys_defined_yet', {}, 'No keys defined yet. Add the first key below (e.g. max_accounts).')}
                                    </div>
                                ) : (
                                    <div className="border rounded-lg overflow-hidden">
                                        <Table>
                                            <TableHeader>
                                                <TableRow className="bg-muted/40">
                                                    <TableHead className="text-xs font-semibold">{__('general.key_name', {}, 'Key')}</TableHead>
                                                    <TableHead className="text-xs font-semibold">{__('general.default_value', {}, 'Default Value')}</TableHead>
                                                    <TableHead className="text-xs font-semibold">{__('general.description', {}, 'Description')}</TableHead>
                                                    <TableHead className="w-12 text-end"></TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {activeSoftwareForKeys.custom_keys.map((k) => (
                                                    <TableRow key={k.id}>
                                                        <TableCell className="font-mono text-xs font-medium">
                                                            {k.key}
                                                        </TableCell>
                                                        <TableCell className="font-mono text-xs text-muted-foreground">
                                                            {k.default_value !== null && k.default_value !== '' ? k.default_value : <span className="italic text-xs text-muted-foreground/60">(empty)</span>}
                                                        </TableCell>
                                                        <TableCell className="text-xs text-muted-foreground max-w-xs truncate" title={k.description ?? ''}>
                                                            {k.description || '—'}
                                                        </TableCell>
                                                        <TableCell className="text-end">
                                                            <Button
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
                            </div>

                            <Separator />

                            {/* Add / Update Key Form */}
                            <form onSubmit={handleSaveKey} className="space-y-4">
                                <Label className="text-xs font-semibold uppercase text-muted-foreground">
                                    {__('general.add_or_update_key', {}, 'Add or Update Key')}
                                </Label>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="sw-key-name" className="text-xs">{__('general.key_name', {}, 'Key Name')}</Label>
                                        <Input
                                            id="sw-key-name"
                                            placeholder="e.g. max_accounts"
                                            value={keyForm.key}
                                            onChange={(e) => setKeyForm({ ...keyForm, key: e.target.value })}
                                            className="h-8 font-mono text-xs"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="sw-default-val" className="text-xs">{__('general.default_value', {}, 'Default Value')}</Label>
                                        <Input
                                            id="sw-default-val"
                                            placeholder="e.g. 1"
                                            value={keyForm.default_value}
                                            onChange={(e) => setKeyForm({ ...keyForm, default_value: e.target.value })}
                                            className="h-8 font-mono text-xs"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="sw-key-desc" className="text-xs">{__('general.description', {}, 'Description (Optional)')}</Label>
                                    <Input
                                        id="sw-key-desc"
                                        placeholder="e.g. Maximum allowed WhatsApp accounts"
                                        value={keyForm.description}
                                        onChange={(e) => setKeyForm({ ...keyForm, description: e.target.value })}
                                        className="h-8 text-xs"
                                    />
                                </div>

                                <div className="flex justify-end gap-2 pt-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setActiveSoftwareForKeys(null)}
                                    >
                                        {__('general.close', {}, 'Close')}
                                    </Button>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={keySubmitting || !keyForm.key.trim()}
                                        className="gap-1.5"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        <span>{keySubmitting ? __('general.saving', {}, 'Saving...') : __('general.save_key', {}, 'Save Key')}</span>
                                    </Button>
                                </div>
                            </form>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* Payment & Pricing Settings Modal */}
            <Dialog open={!!activeSoftwareForPayment} onOpenChange={(open) => !open && setActiveSoftwareForPayment(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <CreditCard className="w-5 h-5 text-primary" />
                            <span>{__('general.payment_settings_title', {}, 'Payment & Pricing Settings')}</span>
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.payment_settings_desc', {}, 'Configure required payment and payment instructions before new devices can be activated for')} <span className="font-semibold text-foreground">{activeSoftwareForPayment?.name}</span>.
                        </DialogDescription>
                    </DialogHeader>

                    {activeSoftwareForPayment && (
                        <form onSubmit={handleSavePayment} className="space-y-4 pt-1">
                            {/* Toggle Requires Payment */}
                            <div className="flex items-center justify-between p-3.5 rounded-lg border bg-muted/30">
                                <div className="space-y-0.5 pe-4">
                                    <Label htmlFor="sw-requires-payment" className="text-sm font-medium cursor-pointer">
                                        {__('general.require_payment_before_activation', {}, 'Require Payment Before Activation')}
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        {__('general.require_payment_hint', {}, 'When enabled, new clients/devices will remain inactive and prompted to pay first.')}
                                    </p>
                                </div>
                                <Switch
                                    id="sw-requires-payment"
                                    checked={paymentForm.requires_payment}
                                    onCheckedChange={(checked) => setPaymentForm({ ...paymentForm, requires_payment: checked })}
                                />
                            </div>

                            {/* Price & Currency */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="sw-price" className="text-xs">
                                        {__('general.price', {}, 'Software / Subscription Price')}
                                    </Label>
                                    <Input
                                        id="sw-price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="e.g. 49.99"
                                        value={paymentForm.price}
                                        onChange={(e) => setPaymentForm({ ...paymentForm, price: e.target.value })}
                                        className="h-9"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="sw-currency" className="text-xs">
                                        {__('general.currency', {}, 'Currency')}
                                    </Label>
                                    <Input
                                        id="sw-currency"
                                        placeholder="e.g. USD, EGP, SAR"
                                        value={paymentForm.currency}
                                        onChange={(e) => setPaymentForm({ ...paymentForm, currency: e.target.value.toUpperCase() })}
                                        className="h-9 font-mono"
                                    />
                                </div>
                            </div>

                            {/* WhatsApp Number */}
                            <div className="space-y-1.5">
                                <Label htmlFor="sw-whatsapp" className="text-xs flex items-center gap-1.5">
                                    <MessageSquare className="w-3.5 h-3.5 text-muted-foreground" />
                                    <span>{__('general.whatsapp_number', {}, 'Admin WhatsApp Number (With Country Code)')}</span>
                                </Label>
                                <Input
                                    id="sw-whatsapp"
                                    placeholder="e.g. +201012345678"
                                    value={paymentForm.whatsapp_number}
                                    onChange={(e) => setPaymentForm({ ...paymentForm, whatsapp_number: e.target.value })}
                                    className="h-9"
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    {__('general.whatsapp_hint', {}, 'Clients will see a direct 1-click button to contact this WhatsApp number with their Device ID.')}
                                </p>
                            </div>

                            {/* Payment Instructions / Notes */}
                            <div className="space-y-1.5">
                                <Label htmlFor="sw-instructions" className="text-xs">
                                    {__('general.payment_instructions', {}, 'Payment Instructions / Notes (Shown to Client)')}
                                </Label>
                                <Textarea
                                    id="sw-instructions"
                                    rows={3}
                                    placeholder="e.g. Transfer fee to Vodafone Cash / USDT / Bank Account and send transfer receipt via WhatsApp."
                                    value={paymentForm.payment_instructions}
                                    onChange={(e) => setPaymentForm({ ...paymentForm, payment_instructions: e.target.value })}
                                    className="text-xs resize-none"
                                />
                            </div>

                            {/* Activation Window Display Toggles */}
                            <div className="pt-2 border-t space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="sw-show-price" className="text-xs font-medium cursor-pointer">
                                            {__('general.show_price_in_dialog', {}, 'إظهار السعر في نافذة التفعيل')}
                                        </Label>
                                        <p className="text-[11px] text-muted-foreground">
                                            {__('general.show_price_in_dialog_desc', {}, 'التحكم في ظهور أو إخفاء صف السعر والعملة داخل نافذة التفعيل للعميل.')}
                                        </p>
                                    </div>
                                    <Switch
                                        id="sw-show-price"
                                        checked={paymentForm.show_price}
                                        onCheckedChange={(checked) => setPaymentForm({ ...paymentForm, show_price: checked })}
                                    />
                                </div>

                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="sw-show-wa" className="text-xs font-medium cursor-pointer">
                                            {__('general.show_whatsapp_in_dialog', {}, 'إظهار زر الواتساب في نافذة التفعيل')}
                                        </Label>
                                        <p className="text-[11px] text-muted-foreground">
                                            {__('general.show_whatsapp_in_dialog_desc', {}, 'التحكم في ظهور أو إخفاء زر المراسلة المباشرة عبر واتساب.')}
                                        </p>
                                    </div>
                                    <Switch
                                        id="sw-show-wa"
                                        checked={paymentForm.show_whatsapp}
                                        onCheckedChange={(checked) => setPaymentForm({ ...paymentForm, show_whatsapp: checked })}
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setActiveSoftwareForPayment(null)}
                                >
                                    {__('general.cancel', {}, 'Cancel')}
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={paymentSubmitting}
                                >
                                    <span>{paymentSubmitting ? __('general.saving', {}, 'Saving...') : __('general.save_changes', {}, 'Save Settings')}</span>
                                </Button>
                            </div>
                        </form>
                    )}
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>);
}