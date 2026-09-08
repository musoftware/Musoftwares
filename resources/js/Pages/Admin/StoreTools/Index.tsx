import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Switch } from '@/Components/ui/switch';
import Pagination from '@/Components/Pagination';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  Package,
  Plus,
  Search,
  MoreHorizontal,
  Edit2,
  Trash2,
  ExternalLink,
  Layers,
  DollarSign,
  CheckCircle2,
  Eye,
  EyeOff,
  Link as LinkIcon,
  Download,
  Sparkles,
} from 'lucide-react';

interface SerialSoftwareOption {
  id: number;
  name: string;
}

interface StoreToolItem {
  id: number;
  name: string;
  slug?: string;
  tagline: string | null;
  description: string | null;
  version: string | null;
  download_url: string | null;
  category: string | null;
  price: number;
  currency: string;
  requires_payment: boolean;
  whatsapp_number: string | null;
  payment_instructions: string | null;
  features: string[] | null;
  serial_software_id: number | null;
  serial_software?: { id: number; name: string } | null;
  is_published: boolean;
  sort_order: number;
  created_at?: string;
}

interface PageProps {
  tools: {
    data: StoreToolItem[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
  };
  filters: {
    search: string;
    status: string;
    type: string;
  };
  stats: {
    total_tools: number;
    published_tools: number;
    draft_tools: number;
    paid_tools: number;
    linked_softwares: number;
  };
  serialSoftwares: SerialSoftwareOption[];
}

export default function AdminStoreToolsIndex({
  tools,
  filters,
  stats,
  serialSoftwares = [],
}: PageProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || 'all');
  const [type, setType] = useState(filters.type || 'all');

  // Modal states
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingTool, setEditingTool] = useState<StoreToolItem | null>(null);
  const [deletingTool, setDeletingTool] = useState<StoreToolItem | null>(null);

  // Form handling
  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    tagline: '',
    description: '',
    version: '',
    download_url: '',
    category: '',
    price: 0,
    currency: 'USD',
    requires_payment: false,
    whatsapp_number: '',
    payment_instructions: '',
    features_text: '',
    serial_software_id: '' as string | number,
    is_published: true,
    sort_order: 0,
  });

  const handleFilter = (newSearch?: string, newStatus?: string, newType?: string) => {
    router.get(
      route('admin.store-tools.index'),
      {
        search: newSearch !== undefined ? newSearch : search,
        status: newStatus !== undefined ? newStatus : status,
        type: newType !== undefined ? newType : type,
      },
      { preserveState: true, replace: true }
    );
  };

  const openCreateModal = () => {
    reset();
    setData({
      name: '',
      tagline: '',
      description: '',
      version: '1.0.0',
      download_url: '',
      category: 'Automation',
      price: 0,
      currency: 'USD',
      requires_payment: false,
      whatsapp_number: '',
      payment_instructions: '',
      features_text: '',
      serial_software_id: '',
      is_published: true,
      sort_order: 0,
    });
    setIsCreateOpen(true);
  };

  const openEditModal = (tool: StoreToolItem) => {
    setEditingTool(tool);
    setData({
      name: tool.name,
      tagline: tool.tagline || '',
      description: tool.description || '',
      version: tool.version || '',
      download_url: tool.download_url || '',
      category: tool.category || '',
      price: tool.price || 0,
      currency: tool.currency || 'USD',
      requires_payment: tool.requires_payment,
      whatsapp_number: tool.whatsapp_number || '',
      payment_instructions: tool.payment_instructions || '',
      features_text: Array.isArray(tool.features) ? tool.features.join('\n') : '',
      serial_software_id: tool.serial_software_id ? String(tool.serial_software_id) : '',
      is_published: tool.is_published,
      sort_order: tool.sort_order || 0,
    });
  };

  const handleSaveCreate = (e: React.FormEvent) => {
    e.preventDefault();
    const features = data.features_text
      .split('\n')
      .map(f => f.trim())
      .filter(f => f.length > 0);

    post(route('admin.store-tools.store'), {
      data: {
        ...data,
        features,
        serial_software_id: data.serial_software_id ? Number(data.serial_software_id) : null,
      } as any,
      preserveScroll: true,
      onSuccess: () => {
        setIsCreateOpen(false);
        reset();
      },
    });
  };

  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingTool) return;

    const features = data.features_text
      .split('\n')
      .map(f => f.trim())
      .filter(f => f.length > 0);

    put(route('admin.store-tools.update', editingTool.id), {
      data: {
        ...data,
        features,
        serial_software_id: data.serial_software_id ? Number(data.serial_software_id) : null,
      } as any,
      preserveScroll: true,
      onSuccess: () => {
        setEditingTool(null);
        reset();
      },
    });
  };

  const handleToggleStatus = (tool: StoreToolItem) => {
    router.patch(
      route('admin.store-tools.toggle-status', tool.id),
      {},
      { preserveScroll: true }
    );
  };

  const handleDelete = () => {
    if (!deletingTool) return;
    router.delete(route('admin.store-tools.destroy', deletingTool.id), {
      preserveScroll: true,
      onSuccess: () => setDeletingTool(null),
    });
  };

  return (
    <AdminSidebarLayout>
      <Head title="Software & Tools Store Management" />

      <div className="space-y-6 pb-12">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/10 dark:border-white/10 pb-5">
          <div>
            <div className="flex items-center gap-2">
              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-black text-white dark:bg-white dark:text-black">
                <Package className="w-3.5 h-3.5" />
                Store Catalog
              </span>
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-black dark:text-white mt-1">
              Software & Tools Store
            </h1>
            <p className="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
              Control the customer-facing tools catalog. Only tools added and published here appear in the store.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <a
              href={route('store.tools.index')}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-black/20 dark:border-white/20 text-black dark:text-white hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
            >
              <ExternalLink className="w-3.5 h-3.5" />
              View Public Store
            </a>
            <Button
              onClick={openCreateModal}
              className="bg-black text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90 gap-1.5 text-xs font-semibold rounded-lg px-4"
            >
              <Plus className="w-4 h-4" />
              Add Tool to Store
            </Button>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
          <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <CardHeader className="pb-1 pt-3 px-4">
              <CardTitle className="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                Total Tools
              </CardTitle>
            </CardHeader>
            <CardContent className="pb-3 px-4">
              <div className="text-2xl font-bold text-black dark:text-white">{stats.total_tools}</div>
            </CardContent>
          </Card>

          <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <CardHeader className="pb-1 pt-3 px-4">
              <CardTitle className="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                Published
              </CardTitle>
            </CardHeader>
            <CardContent className="pb-3 px-4">
              <div className="text-2xl font-bold text-black dark:text-white">{stats.published_tools}</div>
            </CardContent>
          </Card>

          <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <CardHeader className="pb-1 pt-3 px-4">
              <CardTitle className="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                Drafts
              </CardTitle>
            </CardHeader>
            <CardContent className="pb-3 px-4">
              <div className="text-2xl font-bold text-black dark:text-white">{stats.draft_tools}</div>
            </CardContent>
          </Card>

          <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <CardHeader className="pb-1 pt-3 px-4">
              <CardTitle className="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                Paid Items
              </CardTitle>
            </CardHeader>
            <CardContent className="pb-3 px-4">
              <div className="text-2xl font-bold text-black dark:text-white">{stats.paid_tools}</div>
            </CardContent>
          </Card>

          <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950 col-span-2 md:col-span-1">
            <CardHeader className="pb-1 pt-3 px-4">
              <CardTitle className="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                Linked to Serials
              </CardTitle>
            </CardHeader>
            <CardContent className="pb-3 px-4">
              <div className="text-2xl font-bold text-black dark:text-white">{stats.linked_softwares}</div>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-zinc-950 p-3 rounded-xl border border-black/10 dark:border-white/10">
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <Input
              type="text"
              placeholder="Search by tool name, tagline..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                handleFilter(e.target.value, status, type);
              }}
              className="pl-9 text-xs h-9 border-black/10 dark:border-white/10 rounded-lg"
            />
          </div>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Select
              value={status}
              onValueChange={(val) => {
                setStatus(val);
                handleFilter(search, val, type);
              }}
            >
              <SelectTrigger className="text-xs h-9 w-[130px] border-black/10 dark:border-white/10">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="published">Published</SelectItem>
                <SelectItem value="draft">Drafts</SelectItem>
              </SelectContent>
            </Select>

            <Select
              value={type}
              onValueChange={(val) => {
                setType(val);
                handleFilter(search, status, val);
              }}
            >
              <SelectTrigger className="text-xs h-9 w-[130px] border-black/10 dark:border-white/10">
                <SelectValue placeholder="Pricing Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem value="free">Free Tools</SelectItem>
                <SelectItem value="paid">Paid Tools</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Table */}
        <Card className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950 overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow className="border-b border-black/10 dark:border-white/10 hover:bg-transparent">
                <TableHead className="text-xs font-semibold text-black dark:text-white">Tool Details</TableHead>
                <TableHead className="text-xs font-semibold text-black dark:text-white">Linked Serial App</TableHead>
                <TableHead className="text-xs font-semibold text-black dark:text-white">Price / Access</TableHead>
                <TableHead className="text-xs font-semibold text-black dark:text-white">Download</TableHead>
                <TableHead className="text-xs font-semibold text-black dark:text-white">Published</TableHead>
                <TableHead className="text-xs font-semibold text-black dark:text-white text-end">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {tools.data.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-12 text-zinc-500">
                    <Package className="w-8 h-8 mx-auto mb-2 text-zinc-400" />
                    <p className="text-sm font-medium">No store tools found</p>
                    <p className="text-xs text-zinc-400 mt-0.5">Click "Add Tool to Store" to create your first catalog item.</p>
                  </TableCell>
                </TableRow>
              ) : (
                tools.data.map((tool) => (
                  <TableRow
                    key={tool.id}
                    className="border-b border-black/5 dark:border-white/5 hover:bg-black/[0.02] dark:hover:bg-white/[0.02]"
                  >
                    <TableCell>
                      <div className="flex flex-col">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-sm text-black dark:text-white">
                            {tool.name}
                          </span>
                          {tool.version && (
                            <span className="text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                              {tool.version}
                            </span>
                          )}
                          {tool.category && (
                            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded border border-black/10 dark:border-white/10 text-zinc-600 dark:text-zinc-400">
                              {tool.category}
                            </span>
                          )}
                        </div>
                        {tool.tagline && (
                          <span className="text-xs text-zinc-500 mt-0.5 line-clamp-1">
                            {tool.tagline}
                          </span>
                        )}
                      </div>
                    </TableCell>

                    <TableCell>
                      {tool.serial_software ? (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-black dark:text-white">
                          <LinkIcon className="w-3 h-3 text-zinc-400" />
                          {tool.serial_software.name}
                        </span>
                      ) : (
                        <span className="text-xs text-zinc-400">Standalone Tool</span>
                      )}
                    </TableCell>

                    <TableCell>
                      {tool.requires_payment && tool.price > 0 ? (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-md bg-black text-white dark:bg-white dark:text-black text-xs font-medium">
                          {tool.price} {tool.currency}
                        </span>
                      ) : (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-md border border-black/20 text-black dark:border-white/20 dark:text-white text-xs font-medium">
                          Free License
                        </span>
                      )}
                    </TableCell>

                    <TableCell>
                      {tool.download_url ? (
                        <a
                          href={tool.download_url}
                          target="_blank"
                          rel="noreferrer"
                          className="inline-flex items-center gap-1 text-xs text-black dark:text-white hover:underline"
                        >
                          <Download className="w-3.5 h-3.5" />
                          Link
                        </a>
                      ) : (
                        <span className="text-xs text-zinc-400">-</span>
                      )}
                    </TableCell>

                    <TableCell>
                      <button
                        onClick={() => handleToggleStatus(tool)}
                        className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium transition-colors ${
                          tool.is_published
                            ? 'bg-black text-white dark:bg-white dark:text-black'
                            : 'border border-black/30 text-zinc-600 dark:border-white/30 dark:text-zinc-400'
                        }`}
                      >
                        {tool.is_published ? (
                          <>
                            <Eye className="w-3 h-3" /> Published
                          </>
                        ) : (
                          <>
                            <EyeOff className="w-3 h-3" /> Draft
                          </>
                        )}
                      </button>
                    </TableCell>

                    <TableCell className="text-end">
                      <div className="flex items-center justify-end gap-1">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openEditModal(tool)}
                          className="h-8 w-8 p-0 text-zinc-600 dark:text-zinc-300 hover:text-black dark:hover:text-white"
                        >
                          <Edit2 className="w-3.5 h-3.5" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setDeletingTool(tool)}
                          className="h-8 w-8 p-0 text-zinc-400 hover:text-red-600"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>

          {tools.links && tools.links.length > 3 && (
            <div className="p-4 border-t border-black/10 dark:border-white/10">
              <Pagination links={tools.links} />
            </div>
          )}
        </Card>

        {/* Create / Add Modal */}
        <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
          <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <DialogHeader>
              <DialogTitle className="text-lg font-bold text-black dark:text-white">
                Add Tool to Store Catalog
              </DialogTitle>
              <DialogDescription className="text-xs text-zinc-500">
                Create a new product listing in the Software & Tools Store.
              </DialogDescription>
            </DialogHeader>

            <form onSubmit={handleSaveCreate} className="space-y-4 pt-2">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Tool Name *</Label>
                  <Input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g. WhatsApp Bulk Sender Pro"
                    required
                    className="text-xs h-9"
                  />
                  {errors.name && <span className="text-xs text-red-500">{errors.name}</span>}
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Tagline / Short Summary</Label>
                  <Input
                    value={data.tagline}
                    onChange={(e) => setData('tagline', e.target.value)}
                    placeholder="e.g. Extract contacts from groups with one click"
                    className="text-xs h-9"
                  />
                </div>

                <div className="space-y-1">
                  <Label className="text-xs font-semibold">Category</Label>
                  <Input
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                    placeholder="e.g. Automation, Marketing"
                    className="text-xs h-9"
                  />
                </div>

                <div className="space-y-1">
                  <Label className="text-xs font-semibold">Version</Label>
                  <Input
                    value={data.version}
                    onChange={(e) => setData('version', e.target.value)}
                    placeholder="e.g. 1.0.0"
                    className="text-xs h-9 font-mono"
                  />
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Link with Serial Software Protection</Label>
                  <Select
                    value={String(data.serial_software_id)}
                    onValueChange={(val) => setData('serial_software_id', val === 'none' ? '' : val)}
                  >
                    <SelectTrigger className="text-xs h-9">
                      <SelectValue placeholder="Select serial software to link (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">Standalone (No Serial App)</SelectItem>
                      {serialSoftwares.map((sw) => (
                        <SelectItem key={sw.id} value={String(sw.id)}>
                          {sw.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <p className="text-[11px] text-zinc-500">
                    If linked, purchasing or activating this tool automatically generates an active Serial License for this software.
                  </p>
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Download URL</Label>
                  <Input
                    type="url"
                    value={data.download_url}
                    onChange={(e) => setData('download_url', e.target.value)}
                    placeholder="https://example.com/downloads/tool-installer.exe"
                    className="text-xs h-9"
                  />
                </div>

                {/* Pricing settings */}
                <div className="sm:col-span-2 p-3 rounded-lg border border-black/10 dark:border-white/10 space-y-3 bg-zinc-50 dark:bg-zinc-900/50">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-xs font-semibold">Requires Payment</Label>
                      <p className="text-[11px] text-zinc-500">Enable if this tool is paid rather than free.</p>
                    </div>
                    <Switch
                      checked={data.requires_payment}
                      onCheckedChange={(val) => setData('requires_payment', val)}
                    />
                  </div>

                  {data.requires_payment && (
                    <div className="grid grid-cols-2 gap-3 pt-2">
                      <div className="space-y-1">
                        <Label className="text-xs font-semibold">Price</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={data.price}
                          onChange={(e) => setData('price', parseFloat(e.target.value) || 0)}
                          className="text-xs h-9"
                        />
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs font-semibold">Currency</Label>
                        <Input
                          value={data.currency}
                          onChange={(e) => setData('currency', e.target.value)}
                          placeholder="USD"
                          className="text-xs h-9 uppercase font-mono"
                        />
                      </div>
                      <div className="space-y-1 col-span-2">
                        <Label className="text-xs font-semibold">WhatsApp Number (For Payment Proof)</Label>
                        <Input
                          value={data.whatsapp_number}
                          onChange={(e) => setData('whatsapp_number', e.target.value)}
                          placeholder="+201000000000"
                          className="text-xs h-9"
                        />
                      </div>
                      <div className="space-y-1 col-span-2">
                        <Label className="text-xs font-semibold">Payment Instructions</Label>
                        <Textarea
                          value={data.payment_instructions}
                          onChange={(e) => setData('payment_instructions', e.target.value)}
                          placeholder="Bank / Vodafone Cash / PayPal details..."
                          rows={2}
                          className="text-xs"
                        />
                      </div>
                    </div>
                  )}
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Key Features (One feature per line)</Label>
                  <Textarea
                    value={data.features_text}
                    onChange={(e) => setData('features_text', e.target.value)}
                    placeholder="Extract unlimited contacts&#10;Export to CSV/Excel&#10;Automated anti-ban delay"
                    rows={3}
                    className="text-xs font-sans"
                  />
                </div>

                <div className="flex items-center justify-between sm:col-span-2 pt-2">
                  <div>
                    <Label className="text-xs font-semibold">Publish to Store</Label>
                    <p className="text-[11px] text-zinc-500">Visible to clients in the software store immediately.</p>
                  </div>
                  <Switch
                    checked={data.is_published}
                    onCheckedChange={(val) => setData('is_published', val)}
                  />
                </div>
              </div>

              <DialogFooter className="pt-4 border-t border-black/10 dark:border-white/10">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setIsCreateOpen(false)}
                  className="text-xs"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={processing}
                  size="sm"
                  className="bg-black text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90 text-xs font-semibold"
                >
                  {processing ? 'Saving...' : 'Add Tool to Store'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        {/* Edit Modal */}
        <Dialog open={!!editingTool} onOpenChange={(open) => !open && setEditingTool(null)}>
          <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <DialogHeader>
              <DialogTitle className="text-lg font-bold text-black dark:text-white">
                Edit Store Tool: {editingTool?.name}
              </DialogTitle>
              <DialogDescription className="text-xs text-zinc-500">
                Update store listing attributes, download link, or pricing.
              </DialogDescription>
            </DialogHeader>

            <form onSubmit={handleSaveEdit} className="space-y-4 pt-2">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Tool Name *</Label>
                  <Input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    className="text-xs h-9"
                  />
                  {errors.name && <span className="text-xs text-red-500">{errors.name}</span>}
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Tagline / Short Summary</Label>
                  <Input
                    value={data.tagline}
                    onChange={(e) => setData('tagline', e.target.value)}
                    className="text-xs h-9"
                  />
                </div>

                <div className="space-y-1">
                  <Label className="text-xs font-semibold">Category</Label>
                  <Input
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                    className="text-xs h-9"
                  />
                </div>

                <div className="space-y-1">
                  <Label className="text-xs font-semibold">Version</Label>
                  <Input
                    value={data.version}
                    onChange={(e) => setData('version', e.target.value)}
                    className="text-xs h-9 font-mono"
                  />
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Link with Serial Software Protection</Label>
                  <Select
                    value={String(data.serial_software_id)}
                    onValueChange={(val) => setData('serial_software_id', val === 'none' ? '' : val)}
                  >
                    <SelectTrigger className="text-xs h-9">
                      <SelectValue placeholder="Select serial software to link (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">Standalone (No Serial App)</SelectItem>
                      {serialSoftwares.map((sw) => (
                        <SelectItem key={sw.id} value={String(sw.id)}>
                          {sw.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Download URL</Label>
                  <Input
                    type="url"
                    value={data.download_url}
                    onChange={(e) => setData('download_url', e.target.value)}
                    className="text-xs h-9"
                  />
                </div>

                {/* Pricing settings */}
                <div className="sm:col-span-2 p-3 rounded-lg border border-black/10 dark:border-white/10 space-y-3 bg-zinc-50 dark:bg-zinc-900/50">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-xs font-semibold">Requires Payment</Label>
                      <p className="text-[11px] text-zinc-500">Enable if this tool is paid rather than free.</p>
                    </div>
                    <Switch
                      checked={data.requires_payment}
                      onCheckedChange={(val) => setData('requires_payment', val)}
                    />
                  </div>

                  {data.requires_payment && (
                    <div className="grid grid-cols-2 gap-3 pt-2">
                      <div className="space-y-1">
                        <Label className="text-xs font-semibold">Price</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={data.price}
                          onChange={(e) => setData('price', parseFloat(e.target.value) || 0)}
                          className="text-xs h-9"
                        />
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs font-semibold">Currency</Label>
                        <Input
                          value={data.currency}
                          onChange={(e) => setData('currency', e.target.value)}
                          className="text-xs h-9 uppercase font-mono"
                        />
                      </div>
                      <div className="space-y-1 col-span-2">
                        <Label className="text-xs font-semibold">WhatsApp Number</Label>
                        <Input
                          value={data.whatsapp_number}
                          onChange={(e) => setData('whatsapp_number', e.target.value)}
                          className="text-xs h-9"
                        />
                      </div>
                      <div className="space-y-1 col-span-2">
                        <Label className="text-xs font-semibold">Payment Instructions</Label>
                        <Textarea
                          value={data.payment_instructions}
                          onChange={(e) => setData('payment_instructions', e.target.value)}
                          rows={2}
                          className="text-xs"
                        />
                      </div>
                    </div>
                  )}
                </div>

                <div className="space-y-1 sm:col-span-2">
                  <Label className="text-xs font-semibold">Key Features (One feature per line)</Label>
                  <Textarea
                    value={data.features_text}
                    onChange={(e) => setData('features_text', e.target.value)}
                    rows={3}
                    className="text-xs font-sans"
                  />
                </div>

                <div className="flex items-center justify-between sm:col-span-2 pt-2">
                  <div>
                    <Label className="text-xs font-semibold">Publish to Store</Label>
                    <p className="text-[11px] text-zinc-500">Visible to clients in the software store immediately.</p>
                  </div>
                  <Switch
                    checked={data.is_published}
                    onCheckedChange={(val) => setData('is_published', val)}
                  />
                </div>
              </div>

              <DialogFooter className="pt-4 border-t border-black/10 dark:border-white/10">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setEditingTool(null)}
                  className="text-xs"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={processing}
                  size="sm"
                  className="bg-black text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90 text-xs font-semibold"
                >
                  {processing ? 'Saving...' : 'Update Tool'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        {/* Delete Confirmation */}
        <Dialog open={!!deletingTool} onOpenChange={(open) => !open && setDeletingTool(null)}>
          <DialogContent className="border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950">
            <DialogHeader>
              <DialogTitle className="text-base font-bold text-black dark:text-white">
                Delete Store Tool
              </DialogTitle>
              <DialogDescription className="text-xs text-zinc-500">
                Are you sure you want to remove <strong className="text-black dark:text-white">{deletingTool?.name}</strong> from the store?
                This tool will be soft-deleted and will immediately disappear from the public store.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter className="pt-3">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setDeletingTool(null)}
                className="text-xs"
              >
                Cancel
              </Button>
              <Button
                variant="destructive"
                size="sm"
                onClick={handleDelete}
                className="text-xs font-semibold"
              >
                Delete Tool
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AdminSidebarLayout>
  );
}
