import React, { useState } from 'react';
import { ERPLayout } from '@/Layouts/ERPLayout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowRightLeft, Plus } from 'lucide-react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { toast } from 'sonner';

export default function StockTransferIndex({ transfers, warehouses }: { transfers: any[], warehouses: any[] }) {
    const [isOpen, setIsOpen] = useState(false);
    
    const { data, setData, post, processing, reset, errors } = useForm({
        from_warehouse_id: '',
        to_warehouse_id: '',
        product_id: '',
        quantity: '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.warehouse.transfers.store'), {
            onSuccess: () => {
                setIsOpen(false);
                reset();
                toast.success('Transfer initiated successfully');
            },
        });
    };

    return (
        <ERPLayout>
            <Head title="Stock Transfers" />
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Stock Transfers</h2>
                        <p className="text-muted-foreground">Move inventory between warehouses.</p>
                    </div>
                    <Button onClick={() => setIsOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" /> New Transfer
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Transfers</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>From</TableHead>
                                    <TableHead>To</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transfers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                                            No transfers found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    transfers.map((t) => (
                                        <TableRow key={t.id}>
                                            <TableCell className="font-medium">{t.reference_number}</TableCell>
                                            <TableCell>{new Date(t.transfer_date).toLocaleDateString()}</TableCell>
                                            <TableCell>{t.from_warehouse?.name}</TableCell>
                                            <TableCell>{t.to_warehouse?.name}</TableCell>
                                            <TableCell>
                                                <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                                    {t.status}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={isOpen} onOpenChange={setIsOpen}>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Initiate Stock Transfer</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>From Warehouse</Label>
                                    <select 
                                        className="w-full border rounded p-2"
                                        value={data.from_warehouse_id}
                                        onChange={e => setData('from_warehouse_id', e.target.value)}
                                    >
                                        <option value="">Select source</option>
                                        {warehouses.map(w => (
                                            <option key={w.id} value={w.id}>{w.name}</option>
                                        ))}
                                    </select>
                                    {errors.from_warehouse_id && <p className="text-sm text-red-500">{errors.from_warehouse_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>To Warehouse</Label>
                                    <select 
                                        className="w-full border rounded p-2"
                                        value={data.to_warehouse_id}
                                        onChange={e => setData('to_warehouse_id', e.target.value)}
                                    >
                                        <option value="">Select destination</option>
                                        {warehouses.map(w => (
                                            <option key={w.id} value={w.id}>{w.name}</option>
                                        ))}
                                    </select>
                                    {errors.to_warehouse_id && <p className="text-sm text-red-500">{errors.to_warehouse_id}</p>}
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Product ID</Label>
                                <Input
                                    value={data.product_id}
                                    onChange={e => setData('product_id', e.target.value)}
                                    placeholder="Enter product ID to transfer"
                                />
                                {errors.product_id && <p className="text-sm text-red-500">{errors.product_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Quantity</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={data.quantity}
                                    onChange={e => setData('quantity', e.target.value)}
                                    placeholder="e.g. 50"
                                />
                                {errors.quantity && <p className="text-sm text-red-500">{errors.quantity}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Notes</Label>
                                <Input
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                    placeholder="Optional notes"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>Transfer Stock</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ERPLayout>
    );
}
