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
  ChevronsUpDown } from
'lucide-react';

interface Software {
  id: number;
  name: string;
  default_status: string;
  total_devices: number;
  active_count: number;
  inactive_count: number;
  blocked_count: number;
  created_at: string;
  created_at_full: string;
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
    if (!confirm(__('Delete software ":name"? This will also delete all associated device records.', { name: sw.name }))) return;
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
                                            <TableCell colSpan={8} className="text-center py-16 text-muted-foreground">
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
                                                    <span className="font-medium">{sw.name}</span>
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
                            onClick={() => router.visit(route('admin.serial-devices.index', { software_id: sw.id }))}>
                            
                                                            <Eye className="w-4 h-4 me-2" />
                                                            {__('general.view_devices')}
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
        </AdminSidebarLayout>);

}