import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Input } from '@/Components/ui/input';
import { PackagePlus, Edit2, Package } from 'lucide-react';

export default function VendorProductsIndex({ products }: any) {
    const [search, setSearch] = useState('');

    const handleStockUpdate = (skuId: number, qty: number) => {
        // Wait, the API endpoint is products/{product}/stock, so we need product ID. 
        // For simplicity, let's assume the user edits stock in a modal. 
        // I'll build a basic placeholder for now.
        alert('Stock update modal will open here for SKU ' + skuId);
    };

    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <Head title={__('general.vendor_products')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.my_products')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_your_inventory_prices_and_affiliate_commissions')}</p>
                </div>
                <Link href={route('affiliate_pos.vendor.products.create')}>
                    <Button className="bg-blue-600 hover:bg-blue-700">
                        <PackagePlus className="w-4 h-4 mr-2" />{__('general.add_product')}</Button>
                </Link>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b p-4">
                    <div className="max-w-md">
                        <Input 
                            placeholder={__('general.search_products')} 
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="bg-white"
                        />
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">Product</TableHead>
                                <TableHead className="font-semibold text-gray-600">Price (EGP)</TableHead>
                                <TableHead className="font-semibold text-gray-600">Commission</TableHead>
                                <TableHead className="font-semibold text-gray-600">Status</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-32 text-center text-gray-500">{__('general.no_products_found_start_by_adding_one')}</TableCell>
                                </TableRow>
                            ) : (
                                products.data.map((product: any) => (
                                    <TableRow key={product.id}>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{product.name}</div>
                                            <div className="text-xs text-gray-500 mt-0.5">ID: {product.id}</div>
                                        </TableCell>
                                        <TableCell className="font-semibold">{product.price}</TableCell>
                                        <TableCell className="text-green-600 font-medium">{product.commission}</TableCell>
                                        <TableCell>
                                            <Badge variant={product.status === 'active' ? 'default' : 'secondary'} className={product.status === 'active' ? 'bg-green-100 text-green-800 hover:bg-green-100' : ''}>
                                                {product.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Link href={route('affiliate_pos.vendor.products.edit', { product: product.id })}>
                                                <Button variant="ghost" size="sm">
                                                    <Edit2 className="w-4 h-4 mr-2" /> Edit
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
