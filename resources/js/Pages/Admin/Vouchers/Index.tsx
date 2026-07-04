import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Card } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Ticket, Plus, Pencil, Trash2, CheckCircle, XCircle, MoreHorizontal } from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { __ } from '@/lib/i18n';

const emptyForm = {
    name: '',
    description: '',
    spend_amount: '',
    spend_currency: '',
    reward_amount: '',
    reward_currency: '',
    type: 'fixed',
    reward_percentage: '',
    max_uses_per_user: '',
    max_total_uses: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
    admin_notes: '',
};

interface VoucherRow {
    id: number;
    name: string;
    description?: string;
    spend_amount: number | string;
    spend_currency: any;
    reward_amount: number | string;
    reward_currency: any;
    type: 'fixed' | 'percentage';
    reward_percentage: number | string;
    max_uses_per_user?: number;
    max_total_uses?: number;
    current_uses?: number;
    starts_at?: string;
    expires_at?: string;
    is_active: boolean;
    admin_notes?: string;
    created_at?: string;
}

export default function Index({ vouchers, currencies = [] }: { vouchers: any; currencies?: any[] }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingVoucher, setEditingVoucher] = useState<VoucherRow | null>(null);
    const [pendingDelete, setPendingDelete] = useState<VoucherRow | null>(null);
    const [formData, setFormData] = useState({ ...emptyForm });

    const set = (key: string, value: any) =>
        setFormData((prev) => ({ ...prev, [key]: value }));

    const resetForm = () => setFormData({ ...emptyForm });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.vouchers.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                resetForm();
                toastSuccess(__('general.created') || 'Voucher created');
            },
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('admin.vouchers.update', editingVoucher?.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingVoucher(null);
                resetForm();
                toastSuccess(__('general.updated') || 'Voucher updated');
            },
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const openEditModal = (voucher: VoucherRow) => {
        setEditingVoucher(voucher);
        setFormData({
            name: voucher.name ?? '',
            description: voucher.description ?? '',
            spend_amount: String(voucher.spend_amount ?? ''),
            spend_currency: voucher.spend_currency?.id ?? voucher.spend_currency ?? '',
            reward_amount: String(voucher.reward_amount ?? ''),
            reward_currency: voucher.reward_currency?.id ?? voucher.reward_currency ?? '',
            type: voucher.type ?? 'fixed',
            reward_percentage: String(voucher.reward_percentage ?? ''),
            max_uses_per_user: String(voucher.max_uses_per_user ?? ''),
            max_total_uses: String(voucher.max_total_uses ?? ''),
            starts_at: voucher.starts_at ? voucher.starts_at.slice(0, 16) : '',
            expires_at: voucher.expires_at ? voucher.expires_at.slice(0, 16) : '',
            is_active: voucher.is_active ?? true,
            admin_notes: voucher.admin_notes ?? '',
        });
        setIsEditOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const id = pendingDelete.id;
        setPendingDelete(null);
        router.delete(route('admin.vouchers.destroy', id), {
            onSuccess: () => toastSuccess(__('general.deleted') || 'Voucher deleted'),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const renderFormFields = () => (
        <div className="space-y-4 max-h-[65vh] overflow-y-auto p-1 pe-2">
            <div>
                <Label htmlFor="name">{__('general.voucher_name')}</Label>
                <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => set('name', e.target.value)}
                    placeholder={__('general.e_g_summer_bonus')}
                    required
                />
            </div>

            <div>
                <Label htmlFor="description">{__('general.description')}</Label>
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    rows={2}
                    value={formData.description}
                    onChange={(e) => set('description', e.target.value)}
                    placeholder={__('general.optional_description')}
                />
            </div>

            <div>
                <Label htmlFor="type">{__('general.reward_type')}</Label>
                <select
                    id="type"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    value={formData.type}
                    onChange={(e) => set('type', e.target.value)}
                    required
                >
                    <option value="fixed">{__('general.fixed_amount')}</option>
                    <option value="percentage">{__('general.percentage')}</option>
                </select>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="spend_amount">{__('general.spend_amount')}</Label>
                    <Input
                        id="spend_amount"
                        type="number"
                        step="0.0001"
                        min="0"
                        value={formData.spend_amount}
                        onChange={(e) => set('spend_amount', e.target.value)}
                        required
                    />
                </div>
                <div>
                    <Label htmlFor="spend_currency">{__('general.spend_currency')}</Label>
                    <CurrencySelect
                        currencies={currencies}
                        value={formData.spend_currency}
                        onChange={(val) => set('spend_currency', val)}
                    />
                </div>
            </div>

            {formData.type === 'fixed' && (
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="reward_amount">{__('general.reward_amount')}</Label>
                        <Input
                            id="reward_amount"
                            type="number"
                            step="0.0001"
                            min="0"
                            value={formData.reward_amount}
                            onChange={(e) => set('reward_amount', e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <Label htmlFor="reward_currency">{__('general.reward_currency')}</Label>
                        <CurrencySelect
                            currencies={currencies}
                            value={formData.reward_currency}
                            onChange={(val) => set('reward_currency', val)}
                        />
                    </div>
                </div>
            )}

            {formData.type === 'percentage' && (
                <div>
                    <Label htmlFor="reward_percentage">Reward Percentage (%)</Label>
                    <Input
                        id="reward_percentage"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={formData.reward_percentage}
                        onChange={(e) => set('reward_percentage', e.target.value)}
                        placeholder={__('general.e_g_10')}
                    />
                </div>
            )}

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="max_uses_per_user">{__('general.max_uses_user')}</Label>
                    <Input
                        id="max_uses_per_user"
                        type="number"
                        min="1"
                        value={formData.max_uses_per_user}
                        onChange={(e) => set('max_uses_per_user', e.target.value)}
                        placeholder={__('general.unlimited')}
                    />
                </div>
                <div>
                    <Label htmlFor="max_total_uses">{__('general.max_total_uses')}</Label>
                    <Input
                        id="max_total_uses"
                        type="number"
                        min="1"
                        value={formData.max_total_uses}
                        onChange={(e) => set('max_total_uses', e.target.value)}
                        placeholder={__('general.unlimited')}
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="starts_at">{__('general.starts_at')}</Label>
                    <Input
                        id="starts_at"
                        type="datetime-local"
                        value={formData.starts_at}
                        onChange={(e) => set('starts_at', e.target.value)}
                    />
                </div>
                <div>
                    <Label htmlFor="expires_at">{__('general.expires_at')}</Label>
                    <Input
                        id="expires_at"
                        type="datetime-local"
                        value={formData.expires_at}
                        onChange={(e) => set('expires_at', e.target.value)}
                    />
                </div>
            </div>

            <div>
                <Label htmlFor="admin_notes">{__('general.admin_notes')}</Label>
                <textarea
                    id="admin_notes"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    rows={2}
                    value={formData.admin_notes}
                    onChange={(e) => set('admin_notes', e.target.value)}
                    placeholder={__('general.internal_notes')}
                />
            </div>

            <div className="flex items-center space-x-2">
                <input
                    type="checkbox"
                    id="is_active"
                    checked={formData.is_active}
                    onChange={(e) => set('is_active', e.target.checked)}
                    className="rounded border-gray-300 text-black focus:ring-black"
                />
                <Label htmlFor="is_active">{__('general.voucher_is_active')}</Label>
            </div>
        </div>
    );

    const items = vouchers?.data ?? (Array.isArray(vouchers) ? vouchers : []) ?? [];
    const paginationLinks = vouchers?.links;

    return (
        <AdminSidebarLayout title={__('general.vouchers')} header="Vouchers Manager">
            <Head title={__('general.admin_vouchers')} />

            <div className="mb-6 flex items-center justify-end gap-4">
                <div className="flex items-center gap-2 text-gray-500 text-sm">
                    <Ticket className="h-4 w-4" />
                    <span>{items.length} voucher{items.length !== 1 ? 's' : ''}</span>
                </div>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger render={<Button><Plus className="me-2 h-4 w-4" />{__('general.create_voucher')}</Button>} />
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{__('general.create_new_voucher')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit}>
                            {renderFormFields()}
                            <DialogFooter className="mt-6">
                                <Button type="button" variant="outline" onClick={() => { setIsCreateOpen(false); resetForm(); }}>
                                    {__('general.cancel')}</Button>
                                <Button type="submit">{__('general.save_voucher')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {items.length === 0 ? (
                <EmptyState
                    icon={Ticket}
                    title={__('general.no_vouchers_found') || 'No vouchers found'}
                    description={__('general.create_first_voucher') || 'Create your first voucher to reward customers.'}
                />
            ) : (
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{__('general.name')}</TableHead>
                                    <TableHead>{__('general.type')}</TableHead>
                                    <TableHead>{__('general.spend_reward')}</TableHead>
                                    <TableHead>{__('general.uses')}</TableHead>
                                    <TableHead>{__('general.expires')}</TableHead>
                                    <TableHead className="text-center">{__('general.status')}</TableHead>
                                    <TableHead className="text-end">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.map((v: VoucherRow) => (
                                    <TableRow key={v.id}>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{v.name}</div>
                                            {v.description && (
                                                <div className="text-xs text-gray-400 truncate max-w-[180px]">{v.description}</div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${
                                                v.type === 'fixed'
                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                    : 'bg-blue-50 text-blue-700 border border-blue-200'
                                            }`}>
                                                {v.type}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-700">
                                            <span className="font-medium">{parseFloat(String(v.spend_amount)).toFixed(2)}</span>
                                            {v.spend_currency && v.spend_currency.currency && (
                                                <span className="text-gray-400 ms-1">{v.spend_currency.currency}</span>
                                            )}
                                            <span className="mx-2 text-gray-400">→</span>
                                            <span className="font-medium">
                                                {v.type === 'percentage'
                                                    ? `${parseFloat(String(v.reward_percentage ?? 0)).toFixed(2)}%`
                                                    : parseFloat(String(v.reward_amount)).toFixed(2)}
                                            </span>
                                            {v.reward_currency && v.type !== 'percentage' && v.reward_currency.currency && (
                                                <span className="text-gray-400 ms-1">{v.reward_currency.currency}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-700">
                                            {v.current_uses ?? 0}
                                            {v.max_total_uses ? ` / ${v.max_total_uses}` : ' / ∞'}
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {v.expires_at
                                                ? new Date(v.expires_at).toLocaleDateString()
                                                : <span className="text-gray-400">{__('general.never')}</span>}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <StatusBadge status={v.is_active ? 'active' : 'inactive'} />
                                        </TableCell>
                                        <TableCell className="text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">{__('general.actions')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => openEditModal(v)}>
                                                        <Pencil className="h-4 w-4 me-2" /> {__('general.edit')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => setPendingDelete(v)} className="text-red-600 focus:text-red-600">
                                                        <Trash2 className="h-4 w-4 me-2" /> {__('general.delete')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            )}

            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-center gap-1">
                    {paginationLinks.map((link: any, i: number) => (
                        <Button
                            key={i}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                        >
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Button>
                    ))}
                </div>
            )}

            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{__('general.edit_voucher')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        {renderFormFields()}
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => { setIsEditOpen(false); resetForm(); }}>
                                {__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete_voucher') || 'Delete voucher?'}
                description={__('general.confirm_delete_voucher_desc') || `This will permanently delete voucher "${pendingDelete?.name}".`}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}