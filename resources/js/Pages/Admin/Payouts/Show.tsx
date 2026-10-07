import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Plus, Trash, ArrowLeft, CheckCircle } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

export default function Show({ payout }: any) {
    const isPaid = payout.status === 'paid';

    const [notes, setNotes] = useState(payout.notes || '');
    const [tax, setTax] = useState(payout.tax || 0);
    const [items, setItems] = useState(payout.items || []);
    const [pendingMarkPaid, setPendingMarkPaid] = useState(false);
    const [saving, setSaving] = useState(false);

    const handleAddItem = () => {
        setItems([...items, { description: '', qty: 1, amount: 0 }]);
    };

    const handleItemChange = (index: number, field: string, value: any) => {
        const newItems = [...items];
        newItems[index][field] = value;
        setItems(newItems);
    };

    const handleRemoveItem = (index: number) => {
        const newItems = [...items];
        newItems.splice(index, 1);
        setItems(newItems);
    };

    const handleSave = () => {
        setSaving(true);
        router.put(route('admin.payouts.update', payout.id), {
            notes,
            tax,
            items,
        }, {
            onSuccess: () => toastSuccess(__('general.saved')),
            onError: () => toastError(__('general.error_occurred')),
            onFinish: () => setSaving(false),
        });
    };

    const confirmMarkPaid = () => {
        setPendingMarkPaid(false);
        router.post(route('admin.payouts.mark-paid', payout.id), {}, {
            preserveScroll: true,
            onSuccess: () => toastSuccess(__('general.payout_marked_paid')),
            onError: () => toastError(__('general.error_occurred')),
        });
    };

    const calculateSubtotal = () => {
        return items.reduce((sum: number, item: any) => sum + (item.qty * item.amount), 0);
    };

    const calculateTotal = () => {
        return calculateSubtotal() + Number(tax);
    };

    return (
        <AdminSidebarLayout title={__('admin.payouts_show_title', { id: payout.id })} header={__('admin.payouts_show_title', { id: payout.id })}>
            <div className="mb-6 flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.payouts.index')} className="text-muted-foreground hover:text-foreground" aria-label={__('admin.payouts_back_to_list')} title={__('admin.payouts_back_to_list')}>
                        <ArrowLeft className="h-5 w-5 rtl:rotate-180" />
                    </Link>
                    <h1 className="text-2xl font-bold">{__('admin.payouts_show_title', { id: payout.id })}</h1>
                    <StatusBadge status={payout.status} />
                </div>
                {!isPaid && (
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleSave} disabled={saving}>{saving ? __('general.saving') : __('general.save_changes')}</Button>
                        <Button onClick={() => setPendingMarkPaid(true)} className="bg-green-600 hover:bg-green-700">
                            <CheckCircle className="me-2 h-4 w-4" /> {__('general.mark_as_paid')}
                        </Button>
                    </div>
                )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('admin.payouts_items_title')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {items.map((item: any, index: number) => (
                                    <div key={index} className="flex items-start gap-4 p-4 border rounded-md">
                                        <div className="flex-1 space-y-2">
                                            <Label>{__('general.description')}</Label>
                                            <Input 
                                                value={item.description} 
                                                onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                                disabled={isPaid}
                                                placeholder={__('admin.payouts_item_placeholder')}
                                            />
                                        </div>
                                        <div className="w-24 space-y-2">
                                            <Label>{__('general.qty')}</Label>
                                            <Input 
                                                type="number" 
                                                min="1" 
                                                value={item.qty} 
                                                onChange={(e) => handleItemChange(index, 'qty', e.target.value)}
                                                disabled={isPaid}
                                            />
                                        </div>
                                        <div className="w-32 space-y-2">
                                            <Label>{__('general.amount')}</Label>
                                            <Input 
                                                type="number" 
                                                min="0" 
                                                step="0.01" 
                                                value={item.amount} 
                                                onChange={(e) => handleItemChange(index, 'amount', e.target.value)}
                                                disabled={isPaid}
                                            />
                                        </div>
                                        {!isPaid && (
                                            <div className="pt-8">
                                                <Button variant="ghost" size="icon" className="text-red-600" onClick={() => handleRemoveItem(index)} aria-label={__('admin.payouts_remove_item')} title={__('admin.payouts_remove_item')}>
                                                    <Trash className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            
                            {!isPaid && (
                                <Button variant="outline" className="mt-4 w-full" onClick={handleAddItem}>
                                    <Plus className="me-2 h-4 w-4" /> {__('general.add_item')}
                                </Button>
                            )}

                            <div className="mt-8 flex justify-end">
                                <div className="w-64 space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{__('general.subtotal')}</span>
                                        <span>{formatMoney(calculateSubtotal(), payout.currency_id)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm items-center">
                                        <span className="text-muted-foreground">{__('general.tax')}</span>
                                        {isPaid ? (
                                            <span>{formatMoney(payout.tax, payout.currency_id)}</span>
                                        ) : (
                                            <Input 
                                                type="number" 
                                                value={tax} 
                                                onChange={(e) => setTax(Number(e.target.value))} 
                                                className="w-24 h-8 text-end"
                                            />
                                        )}
                                    </div>
                                    <div className="flex justify-between font-bold text-lg pt-2 border-t">
                                        <span>{__('general.total')}</span>
                                        <span>{formatMoney(calculateTotal(), payout.currency_id)}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label className="text-muted-foreground text-xs uppercase">{__('general.customer')}</Label>
                                <div className="font-medium mt-1">{payout.user?.name}</div>
                                <div className="text-sm text-muted-foreground">{payout.user?.email}</div>
                            </div>
                            {payout.project && (
                                <div>
                                    <Label className="text-muted-foreground text-xs uppercase">{__('general.project')}</Label>
                                    <div className="font-medium mt-1">{payout.project.project_name}</div>
                                </div>
                            )}
                            <div>
                                <Label className="text-muted-foreground text-xs uppercase">{__('general.date')}</Label>
                                <div className="font-medium mt-1">{new Date(payout.created_at).toLocaleDateString()}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                disabled={isPaid}
                                placeholder={__('admin.payouts_notes_placeholder')}
                                rows={4}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingMarkPaid}
                title={__('general.mark_as_paid')}
                description={__('general.confirm_mark_payout_paid_desc')}
                confirmLabel={__('general.mark_as_paid')}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmMarkPaid}
                onCancel={() => setPendingMarkPaid(false)}
            />
        </AdminSidebarLayout>
    );
}
