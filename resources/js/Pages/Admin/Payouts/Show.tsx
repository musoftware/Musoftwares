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
import { __ } from '@/lib/i18n';

export default function Show({ payout }: any) {
    const isPaid = payout.status === 'paid';

    const [notes, setNotes] = useState(payout.notes || '');
    const [tax, setTax] = useState(payout.tax || 0);
    const [items, setItems] = useState(payout.items || []);

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
        router.put(route('admin.payouts.update', payout.id), {
            notes,
            tax,
            items,
        });
    };

    const handleMarkPaid = () => {
        if (confirm('Are you sure you want to mark this payout as paid? This will credit the user\'s wallet and add an offsetting transaction to balance it.')) {
            router.post(route('admin.payouts.mark-paid', payout.id));
        }
    };

    const calculateSubtotal = () => {
        return items.reduce((sum: number, item: any) => sum + (item.qty * item.amount), 0);
    };

    const calculateTotal = () => {
        return calculateSubtotal() + Number(tax);
    };

    return (
        <AdminSidebarLayout title={`Payout #${payout.id}`} header={`Payout #${payout.id}`}>
            <div className="mb-6 flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.payouts.index')} className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <h1 className="text-2xl font-bold">Payout #{payout.id}</h1>
                    <StatusBadge status={payout.status} />
                </div>
                {!isPaid && (
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleSave}>Save Changes</Button>
                        <Button onClick={handleMarkPaid} className="bg-green-600 hover:bg-green-700">
                            <CheckCircle className="mr-2 h-4 w-4" /> Mark as Paid
                        </Button>
                    </div>
                )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Payout Items</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {items.map((item: any, index: number) => (
                                    <div key={index} className="flex items-start gap-4 p-4 border rounded-md">
                                        <div className="flex-1 space-y-2">
                                            <Label>Description</Label>
                                            <Input 
                                                value={item.description} 
                                                onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                                disabled={isPaid}
                                                placeholder="e.g. Website Development Milestone"
                                            />
                                        </div>
                                        <div className="w-24 space-y-2">
                                            <Label>Qty</Label>
                                            <Input 
                                                type="number" 
                                                min="1" 
                                                value={item.qty} 
                                                onChange={(e) => handleItemChange(index, 'qty', e.target.value)}
                                                disabled={isPaid}
                                            />
                                        </div>
                                        <div className="w-32 space-y-2">
                                            <Label>Amount</Label>
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
                                                <Button variant="ghost" size="icon" className="text-red-600" onClick={() => handleRemoveItem(index)}>
                                                    <Trash className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            
                            {!isPaid && (
                                <Button variant="outline" className="mt-4 w-full" onClick={handleAddItem}>
                                    <Plus className="mr-2 h-4 w-4" /> Add Item
                                </Button>
                            )}

                            <div className="mt-8 flex justify-end">
                                <div className="w-64 space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Subtotal</span>
                                        <span>{formatMoney(calculateSubtotal(), payout.currency_id)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm items-center">
                                        <span className="text-muted-foreground">Tax</span>
                                        {isPaid ? (
                                            <span>{formatMoney(payout.tax, payout.currency_id)}</span>
                                        ) : (
                                            <Input 
                                                type="number" 
                                                value={tax} 
                                                onChange={(e) => setTax(Number(e.target.value))} 
                                                className="w-24 h-8 text-right"
                                            />
                                        )}
                                    </div>
                                    <div className="flex justify-between font-bold text-lg pt-2 border-t">
                                        <span>Total</span>
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
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label className="text-muted-foreground text-xs uppercase">Customer</Label>
                                <div className="font-medium mt-1">{payout.user?.name}</div>
                                <div className="text-sm text-muted-foreground">{payout.user?.email}</div>
                            </div>
                            {payout.project && (
                                <div>
                                    <Label className="text-muted-foreground text-xs uppercase">Project</Label>
                                    <div className="font-medium mt-1">{payout.project.project_name}</div>
                                </div>
                            )}
                            <div>
                                <Label className="text-muted-foreground text-xs uppercase">Date</Label>
                                <div className="font-medium mt-1">{new Date(payout.created_at).toLocaleDateString()}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea 
                                value={notes} 
                                onChange={(e) => setNotes(e.target.value)} 
                                disabled={isPaid}
                                placeholder="Add any private notes here..."
                                rows={4}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
