import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Package, Plus, Edit, Trash2, AlertCircle, ListPlus, Search, MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import TextInput from '@/Components/TextInput';
import { Badge } from '@/components/ui/badge';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import Pagination from '@/Components/Pagination';
import { formatMoney as formatCurrency } from '@/lib/utils';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { ManageCategoriesModal } from './Components/ManageCategoriesModal';

export default function Index({ products, hasInventoryFeature, filters }: { products: any, hasInventoryFeature: boolean, filters?: any }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('inventory');
    const [search, setSearch] = useState(filters?.search || '');

    const t = (key: string, fallback: string) => {
        // @ts-ignore
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            // @ts-ignore
            return window.__(key);
        }
        return fallback;
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('erp.inventory.index'), { search }, { preserveState: true });
    };

    if (!hasInventoryFeature) {
        return (
            <ERPLayout title={t('erp.inventory', 'Inventory')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
                <Head title={t('erp.inventory', 'Inventory')} />
                <UpgradeOverlay 
                    title={t('erp.unlock_inventory', 'Unlock ERP Inventory')}
                    description={t('erp.inventory_upgrade_desc', 'Upgrade your subscription to track stock, manage products, and streamline your supply chain directly from your dashboard.')}
                    module="erp-inventory"
                    icon={Package}
                    priceText={t('erp.upgrade_now', 'Upgrade Now')}
                />
            </ERPLayout>
        );
    }

    const { delete: destroy } = useForm();

    const handleDelete = (id: number) => {
        if (confirm(t('erp.confirm_delete_product', 'Are you sure you want to delete this product?'))) {
            destroy(route('erp.inventory.products.destroy', id));
        }
    };

    return (
        <ERPLayout title={t('erp.inventory', 'Inventory')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={t('erp.inventory', 'Inventory')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <h3 className="text-2xl font-semibold text-gray-900 w-full sm:w-auto">
                            {t('erp.inventory_items', 'Inventory Items')}
                        </h3>
                        <div className="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <form onSubmit={handleSearch} className="relative w-full sm:w-64">
                                <TextInput
                                    type="text"
                                    name="search"
                                    value={search}
                                    className="w-full pl-9 pr-4 py-2"
                                    placeholder={t('erp.search_products', 'Search products...')}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Search className="h-4 w-4 text-gray-400" />
                                </div>
                            </form>
                            <ManageCategoriesModal />
                            <Link
                                href={route('erp.inventory.products.create')}
                                className="inline-flex w-full sm:w-auto items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                <span className="whitespace-nowrap">{t('erp.add_product', 'Add Product')}</span>
                            </Link>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {products.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-gray-500">
                                <Package className="h-12 w-12 text-gray-400 mb-4" />
                                <p className="text-lg">{t('erp.no_products_found', 'No products found')}</p>
                                <p className="text-sm mt-2">{t('erp.add_first_product', 'Add your first product to start tracking inventory.')}</p>
                            </div>
                        ) : (
                            <div>
                                <div className="hidden md:block overflow-hidden">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 w-16"></th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.product_name', 'Name')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.category', 'Category')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.sku', 'SKU / Barcode')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.price', 'Price')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.stock', 'Stock')}</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.actions', 'Actions')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {products.data.map((product: any) => (
                                                <tr key={product.id}>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        {product.image_path ? (
                                                            <img src={`/storage/${product.image_path}`} alt={product.name} className="h-10 w-10 object-cover rounded-md border" />
                                                        ) : (
                                                            <div className="h-10 w-10 bg-gray-100 flex items-center justify-center rounded-md border text-gray-400">
                                                                <Package className="h-5 w-5" />
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="text-sm font-medium text-gray-900">{product.name}</div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="text-sm text-gray-500">{product.category ? product.category.name : '-'}</div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="text-sm text-gray-500">{product.sku || '-'}</div>
                                                        {product.barcode && <div className="text-xs text-gray-400">{product.barcode}</div>}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="text-sm text-gray-900">
                                                            {formatCurrency(product.price, product.currency)}
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="flex items-center space-x-2">
                                                            <span className="text-sm font-medium text-gray-900">{product.stock_quantity} <span className="text-xs text-gray-500 font-normal">{product.uom || 'piece'}</span></span>
                                                            {product.reorder_level !== null && product.stock_quantity <= product.reorder_level && (
                                                                <Badge variant="destructive" className="ml-2 flex items-center">
                                                                    <AlertCircle className="mr-1 h-3 w-3" />
                                                                    {t('erp.low_stock', 'Low Stock')}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                        <Dialog>
                                                            <DialogTrigger asChild>
                                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent className="sm:max-w-xs">
                                                                <DialogHeader>
                                                                    <DialogTitle>{t('erp.actions', 'Actions')}</DialogTitle>
                                                                </DialogHeader>
                                                                <div className="flex flex-col gap-2 py-2">
                                                                    <Button variant="outline" className="justify-start" onClick={() => router.get(route('erp.inventory.products.adjust', product.id))}>
                                                                        <ListPlus className="mr-2 h-4 w-4" />
                                                                        {t('erp.adjust_stock', 'Adjust')}
                                                                    </Button>
                                                                    <Button variant="outline" className="justify-start" onClick={() => router.get(route('erp.inventory.products.edit', product.id))}>
                                                                        <Edit className="mr-2 h-4 w-4" />
                                                                        {t('erp.edit', 'Edit')}
                                                                    </Button>
                                                                    <Button variant="destructive" className="justify-start" onClick={() => handleDelete(product.id)}>
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        {t('erp.delete', 'Delete')}
                                                                    </Button>
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div className="md:hidden divide-y divide-gray-200">
                                    {products.data.map((product: any) => (
                                        <div key={product.id} className="p-4 bg-white space-y-3">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">{product.name}</h4>
                                                    <p className="text-xs text-gray-500 mt-1">{t('erp.sku', 'SKU')}: {product.sku || '-'}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-bold text-gray-900">{formatCurrency(product.price, product.currency)}</p>
                                                    <div className="flex justify-between items-center text-sm">
                                                        <span className="text-gray-500">{t('erp.stock', 'Stock')}:</span>
                                                        <span className="font-medium text-gray-900">{product.stock_quantity} <span className="text-xs text-gray-500 font-normal">{product.uom || 'piece'}</span></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {product.reorder_level !== null && product.stock_quantity <= product.reorder_level && (
                                                <div className="flex items-center">
                                                    <Badge variant="destructive" className="flex items-center">
                                                        <AlertCircle className="mr-1 h-3 w-3" />
                                                        {t('erp.low_stock', 'Low Stock')}
                                                    </Badge>
                                                </div>
                                            )}
                                            
                                            <div className="flex items-center justify-end pt-2 border-t border-gray-100">
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <span className="sr-only">{__('general.open_menu')}</span>
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent className="max-w-[calc(100vw-32px)] sm:max-w-xs">
                                                        <DialogHeader>
                                                            <DialogTitle>{t('erp.actions', 'Actions')}</DialogTitle>
                                                        </DialogHeader>
                                                        <div className="flex flex-col gap-2 py-2">
                                                            <Button variant="outline" className="justify-start" onClick={() => router.get(route('erp.inventory.products.adjust', product.id))}>
                                                                <ListPlus className="mr-2 h-4 w-4" />
                                                                {t('erp.adjust_stock', 'Adjust')}
                                                            </Button>
                                                            <Button variant="outline" className="justify-start" onClick={() => router.get(route('erp.inventory.products.edit', product.id))}>
                                                                <Edit className="mr-2 h-4 w-4" />
                                                                {t('erp.edit', 'Edit')}
                                                            </Button>
                                                            <Button variant="destructive" className="justify-start" onClick={() => handleDelete(product.id)}>
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                {t('erp.delete', 'Delete')}
                                                            </Button>
                                                        </div>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        {products.data.length > 0 && (
                            <div className="p-4 border-t">
                                <Pagination links={products.links} />
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
