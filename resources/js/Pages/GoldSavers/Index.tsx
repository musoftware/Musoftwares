import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Plus, Trash2, TrendingUp, Coins } from 'lucide-react';

interface GoldSaverRecord {
    id: number;
    carat: string;
    gram_price: number;
    additional_price: number;
    grams: number;
    tax: number;
    bought_date: string;
    zakat: boolean;
    buyer_price: number;
    buy2sell_rate: number;
}

export default function Index({ records }: { records: GoldSaverRecord[] }) {
    const { data, setData, post, processing, reset, errors } = useForm({
        carat: '21',
        gram_price: '',
        additional_price: '',
        grams: '',
        tax: '',
        bought_date: new Date().toISOString().split('T')[0],
        zakat: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('isaas.gold-savers.store'), {
            onSuccess: () => reset(),
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this record?')) {
            router.delete(route('isaas.gold-savers.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Gold Savers</h2>}
        >
            <Head title="Gold Savers - iSAAS" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    
                    {/* Header Section */}
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                                <Coins className="h-8 w-8 text-yellow-500" />
                                iSAAS Gold Tracker
                            </h1>
                            <p className="text-slate-500 mt-1">Manage and track the value of your gold savings over time.</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Add New Record Form */}
                        <div className="md:col-span-1">
                            <Card className="border-0 shadow-sm ring-1 ring-slate-200/50">
                                <CardHeader>
                                    <CardTitle>Add New Record</CardTitle>
                                    <CardDescription>Enter the details of your gold purchase.</CardDescription>
                                </CardHeader>
                                <form onSubmit={handleSubmit}>
                                    <CardContent className="space-y-4">
                                        
                                        <div className="space-y-2">
                                            <Label htmlFor="carat">Carat</Label>
                                            <Select 
                                                value={data.carat} 
                                                onValueChange={(v) => setData('carat', v)}
                                            >
                                                <SelectTrigger id="carat">
                                                    <SelectValue placeholder="Select carat" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="14">14k</SelectItem>
                                                    <SelectItem value="18">18k</SelectItem>
                                                    <SelectItem value="21">21k</SelectItem>
                                                    <SelectItem value="22">22k</SelectItem>
                                                    <SelectItem value="24">24k</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.carat && <p className="text-xs text-red-500">{errors.carat}</p>}
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="grams">Grams</Label>
                                                <Input id="grams" type="number" step="0.001" value={data.grams} onChange={e => setData('grams', e.target.value)} required />
                                                {errors.grams && <p className="text-xs text-red-500">{errors.grams}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="gram_price">Price per Gram</Label>
                                                <Input id="gram_price" type="number" step="0.001" value={data.gram_price} onChange={e => setData('gram_price', e.target.value)} required />
                                                {errors.gram_price && <p className="text-xs text-red-500">{errors.gram_price}</p>}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="additional_price">Making Charge (Add.)</Label>
                                                <Input id="additional_price" type="number" step="0.001" value={data.additional_price} onChange={e => setData('additional_price', e.target.value)} required />
                                                {errors.additional_price && <p className="text-xs text-red-500">{errors.additional_price}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="tax">Tax / VAT</Label>
                                                <Input id="tax" type="number" step="0.001" value={data.tax} onChange={e => setData('tax', e.target.value)} required />
                                                {errors.tax && <p className="text-xs text-red-500">{errors.tax}</p>}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="bought_date">Purchase Date</Label>
                                            <Input id="bought_date" type="date" value={data.bought_date} onChange={e => setData('bought_date', e.target.value)} required />
                                            {errors.bought_date && <p className="text-xs text-red-500">{errors.bought_date}</p>}
                                        </div>

                                        <div className="flex items-center space-x-2 pt-2">
                                            <Checkbox 
                                                id="zakat" 
                                                checked={data.zakat} 
                                                onCheckedChange={(checked) => setData('zakat', checked as boolean)} 
                                            />
                                            <Label htmlFor="zakat" className="font-normal cursor-pointer">Eligible for Zakat</Label>
                                        </div>

                                    </CardContent>
                                    <CardFooter>
                                        <Button type="submit" className="w-full" disabled={processing}>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Add Record
                                        </Button>
                                    </CardFooter>
                                </form>
                            </Card>
                        </div>

                        {/* Records Table */}
                        <div className="md:col-span-2">
                            <Card className="border-0 shadow-sm ring-1 ring-slate-200/50 h-full">
                                <CardHeader>
                                    <CardTitle>Your Portfolio</CardTitle>
                                    <CardDescription>A complete log of your gold savings.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {records.length === 0 ? (
                                        <div className="text-center py-12 text-slate-500">
                                            <TrendingUp className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                                            <p>No gold records found.</p>
                                            <p className="text-sm mt-1">Add your first purchase to start tracking.</p>
                                        </div>
                                    ) : (
                                        <div className="rounded-md border overflow-hidden">
                                            <Table>
                                                <TableHeader className="bg-slate-50">
                                                    <TableRow>
                                                        <TableHead>Date</TableHead>
                                                        <TableHead>Specs</TableHead>
                                                        <TableHead className="text-right">Total Paid</TableHead>
                                                        <TableHead className="text-right">Buy/Sell Rate</TableHead>
                                                        <TableHead></TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {records.map((record) => (
                                                        <TableRow key={record.id}>
                                                            <TableCell className="font-medium whitespace-nowrap">
                                                                {new Date(record.bought_date).toLocaleDateString()}
                                                            </TableCell>
                                                            <TableCell>
                                                                <div className="text-sm font-medium">{record.grams}g of {record.carat}k</div>
                                                                <div className="text-xs text-slate-500">
                                                                    {record.gram_price}/g + {record.additional_price} mk + {record.tax} tax
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-right font-semibold">
                                                                {record.buyer_price.toLocaleString()}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                                    record.buy2sell_rate > 1.2 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                                                }`}>
                                                                    {record.buy2sell_rate}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="icon" 
                                                                    className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                                                    onClick={() => handleDelete(record.id)}
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
