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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Ticket, Plus, Pencil, Trash2, CheckCircle, XCircle } from 'lucide-react';
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

export default function Index({ vouchers }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingVoucher, setEditingVoucher] = useState<any>(null);
    const [formData, setFormData] = useState({ ...emptyForm });

    const set = (key: string, value: any) =>
        setFormData((prev) => ({ ...prev, [key]: value }));

    const resetForm = () => setFormData({ ...emptyForm });

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        router.post(route('admin.vouchers.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                resetForm();
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        router.put(route('admin.vouchers.update', editingVoucher?.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingVoucher(null);
                resetForm();
            },
        });
    };

    const openEditModal = (voucher) => {
        setEditingVoucher(voucher);
        setFormData({
            name: voucher.name ?? '',
            description: voucher.description ?? '',
            spend_amount: voucher.spend_amount ?? '',
            spend_currency: voucher.spend_currency?.id ?? voucher.spend_currency ?? '',
            reward_amount: voucher.reward_amount ?? '',
            reward_currency: voucher.reward_currency?.id ?? voucher.reward_currency ?? '',
            type: voucher.type ?? 'fixed',
            reward_percentage: voucher.reward_percentage ?? '',
            max_uses_per_user: voucher.max_uses_per_user ?? '',
            max_total_uses: voucher.max_total_uses ?? '',
            starts_at: voucher.starts_at ? voucher.starts_at.slice(0, 16) : '',
            expires_at: voucher.expires_at ? voucher.expires_at.slice(0, 16) : '',
            is_active: voucher.is_active ?? true,
            admin_notes: voucher.admin_notes ?? '',
        });
        setIsEditOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this voucher?')) {
            router.delete(route('admin.vouchers.destroy', id));
        }
    };

    const renderFormFields = () => (
        <div className="space-y-4 max-h-[65vh] overflow-y-auto p-1 pe-2">
            {/* Name */}
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

            {/* Description */}
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

            {/* Type */}
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

            {/* Spend */}
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
                    <Label htmlFor="spend_currency">{__('general.spend_currency_id')}</Label>
                    <Input
                        id="spend_currency"
                        type="number"
                        min="1"
                        value={formData.spend_currency}
                        onChange={(e) => set('spend_currency', e.target.value)}
                        placeholder={__('general.currency_id')}
                        required
                    />
                </div>
            </div>

            {/* Reward */}
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
                    <Label htmlFor="reward_currency">{__('general.reward_currency_id')}</Label>
                    <Input
                        id="reward_currency"
                        type="number"
                        min="1"
                        value={formData.reward_currency}
                        onChange={(e) => set('reward_currency', e.target.value)}
                        placeholder={__('general.currency_id')}
                        required
                    />
                </div>
            </div>

            {/* Percentage */}
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

            {/* Usage Limits */}
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

            {/* Dates */}
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

            {/* Admin Notes */}
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

            {/* Active toggle */}
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

    const items = vouchers?.data ?? vouchers ?? [];

    return (
        <AdminSidebarLayout title={__('general.vouchers')} header="Vouchers Manager">
            <Head title={__('general.admin_vouchers')} />

            {/* Header bar */}
            <div className="mb-6 flex items-center justify-between">
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

            {/* Table */}
            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-start text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">{__('general.name')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.type')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.spend_reward')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.uses')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.expires')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.status')}</th>
                            <th className="p-4 font-medium text-gray-600 text-end">{__('general.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((v) => (
                            <tr key={v.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    <div className="font-medium text-gray-900">{v.name}</div>
                                    {v.description && (
                                        <div className="text-xs text-gray-400 truncate max-w-[180px]">{v.description}</div>
                                    )}
                                </td>
                                <td className="p-4">
                                    <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700 capitalize">
                                        {v.type}
                                    </span>
                                </td>
                                <td className="p-4 text-sm text-gray-700">
                                    <span className="font-medium">{parseFloat(v.spend_amount).toFixed(2)}</span>
                                    {v.spend_currency && v.spend_currency.currency && (
                                        <span className="text-gray-400 ms-1">{v.spend_currency.currency}</span>
                                    )}
                                    <span className="mx-2 text-gray-400">→</span>
                                    <span className="font-medium">
                                        {v.type === 'percentage'
                                            ? `${parseFloat(v.reward_percentage ?? 0).toFixed(2)}%`
                                            : parseFloat(v.reward_amount).toFixed(2)}
                                    </span>
                                    {v.reward_currency && v.type !== 'percentage' && v.reward_currency.currency && (
                                        <span className="text-gray-400 ms-1">{v.reward_currency.currency}</span>
                                    )}
                                </td>
                                <td className="p-4 text-sm text-gray-700">
                                    {v.current_uses ?? 0}
                                    {v.max_total_uses ? ` / ${v.max_total_uses}` : ' / ∞'}
                                </td>
                                <td className="p-4 text-sm text-gray-500">
                                    {v.expires_at
                                        ? new Date(v.expires_at).toLocaleDateString()
                                        : <span className="text-gray-400">{__('general.never')}</span>}
                                </td>
                                <td className="p-4">
                                    {v.is_active ? (
                                        <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800">
                                            <CheckCircle className="h-3 w-3" /> {__('general.active')}</span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800">
                                            <XCircle className="h-3 w-3" /> {__('general.inactive')}</span>
                                    )}
                                </td>
                                <td className="p-4 space-x-2 text-end">
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(v)}>
                                        <Pencil className="h-3.5 w-3.5 me-1" /> {__('general.edit')}</Button>
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(v.id)}>
                                        <Trash2 className="h-3.5 w-3.5 me-1" /> {__('general.delete')}</Button>
                                </td>
                            </tr>
                        ))}
                        {items.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-8 text-center text-gray-400">
                                    <Ticket className="mx-auto mb-2 h-8 w-8 opacity-30" />{__('general.no_vouchers_found')}</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination links */}
            {vouchers?.links && (
                <div className="mt-4 flex justify-center gap-1">
                    {vouchers.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`px-3 py-1 rounded text-sm border ${
                                link.active
                                    ? 'bg-black text-white border-black'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 disabled:opacity-40'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {/* Edit Modal */}
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
        </AdminSidebarLayout>
    );
}
