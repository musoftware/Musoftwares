import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head, useForm } from '@inertiajs/react';
import { Monitor, Plus, Edit2, Trash2 } from 'lucide-react';
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

export default function FixedAssetIndex({ assets, categories }: { assets: any[], categories: any[] }) {
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [isCatOpen, setIsCatOpen] = useState(false);
    
    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        code: '',
        serial_number: '',
        purchase_date: '',
        purchase_cost: '',
        salvage_value: '',
        asset_category_id: '',
        location: '',
        status: 'active',
    });

    const catForm = useForm({
        name: '',
        code: '',
        depreciation_method: 'straight_line',
        useful_life_years: 5,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.assets.fixed-assets.store'), {
            onSuccess: () => {
                setIsAddOpen(false);
                reset();
                toast.success('Fixed Asset created successfully');
            },
        });
    };

    const submitCat = (e: React.FormEvent) => {
        e.preventDefault();
        catForm.post(route('erp.assets.categories.store'), {
            onSuccess: () => {
                setIsCatOpen(false);
                catForm.reset();
                toast.success('Category created successfully');
            },
        });
    };

    return (
        <ERPLayout title="Fixed Assets">
            <Head title="Fixed Assets" />
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Fixed Assets</h2>
                        <p className="text-muted-foreground">Manage your company's physical assets and equipment.</p>
                    </div>
                    <div className="flex space-x-2">
                        <Button variant="outline" onClick={() => setIsCatOpen(true)}>
                            Add Category
                        </Button>
                        <Button onClick={() => setIsAddOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" /> Add Asset
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Asset Register</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Asset Name</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Purchase Date</TableHead>
                                    <TableHead>Cost</TableHead>
                                    <TableHead>Current Value</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assets.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                            No fixed assets found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    assets.map((asset) => (
                                        <TableRow key={asset.id}>
                                            <TableCell className="font-medium">
                                                {asset.name}
                                                {asset.code && <div className="text-xs text-muted-foreground">{asset.code}</div>}
                                            </TableCell>
                                            <TableCell>{asset.category?.name}</TableCell>
                                            <TableCell>{new Date(asset.purchase_date).toLocaleDateString()}</TableCell>
                                            <TableCell>${Number(asset.purchase_cost).toLocaleString(undefined, { minimumFractionDigits: 2 })}</TableCell>
                                            <TableCell>${Number(asset.current_value).toLocaleString(undefined, { minimumFractionDigits: 2 })}</TableCell>
                                            <TableCell>
                                                <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                                    {asset.status}
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

            {/* Add Asset Dialog */}
            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent className="max-w-xl">
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Register New Asset</DialogTitle>
                        </DialogHeader>
                        <div className="grid grid-cols-2 gap-4 py-4">
                            <div className="space-y-2">
                                <Label>Asset Name</Label>
                                <Input value={data.name} onChange={e => setData('name', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label>Category</Label>
                                <select 
                                    className="w-full border rounded p-2"
                                    value={data.asset_category_id}
                                    onChange={e => setData('asset_category_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select category</option>
                                    {categories.map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Asset Code / Tag</Label>
                                <Input value={data.code} onChange={e => setData('code', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Serial Number</Label>
                                <Input value={data.serial_number} onChange={e => setData('serial_number', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Purchase Date</Label>
                                <Input type="date" value={data.purchase_date} onChange={e => setData('purchase_date', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label>Purchase Cost</Label>
                                <Input type="number" step="0.01" value={data.purchase_cost} onChange={e => setData('purchase_cost', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label>Salvage Value</Label>
                                <Input type="number" step="0.01" value={data.salvage_value} onChange={e => setData('salvage_value', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Location</Label>
                                <Input value={data.location} onChange={e => setData('location', e.target.value)} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsAddOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>Register Asset</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Add Category Dialog */}
            <Dialog open={isCatOpen} onOpenChange={setIsCatOpen}>
                <DialogContent>
                    <form onSubmit={submitCat}>
                        <DialogHeader>
                            <DialogTitle>Add Asset Category</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="space-y-2">
                                <Label>Category Name</Label>
                                <Input value={catForm.data.name} onChange={e => catForm.setData('name', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label>Depreciation Method</Label>
                                <select 
                                    className="w-full border rounded p-2"
                                    value={catForm.data.depreciation_method}
                                    onChange={e => catForm.setData('depreciation_method', e.target.value)}
                                >
                                    <option value="straight_line">Straight Line</option>
                                    <option value="declining_balance">Declining Balance</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Useful Life (Years)</Label>
                                <Input type="number" value={catForm.data.useful_life_years} onChange={e => catForm.setData('useful_life_years', Number(e.target.value))} required />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCatOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={catForm.processing}>Save Category</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ERPLayout>
    );
}
