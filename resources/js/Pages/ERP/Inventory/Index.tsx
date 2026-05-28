import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { Package, Plus, Edit, Trash2, AlertCircle, ListPlus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export default function Index({ products }: { products: any }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('inventory');

    const t = (key: string, fallback: string) => {
        // @ts-ignore
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            // @ts-ignore
            return window.__(key);
        }
        return fallback;
    };

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
                    <div className="mb-6 flex items-center justify-between">
                        <h3 className="text-2xl font-semibold text-gray-900">
                            {t('erp.inventory_items', 'Inventory Items')}
                        </h3>
                        <Link
                            href={route('erp.inventory.products.create')}
                            className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            {t('erp.add_product', 'Add Product')}
                        </Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {products.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-gray-500">
                                <Package className="h-12 w-12 text-gray-400 mb-4" />
                                <p className="text-lg">{t('erp.no_products_found', 'No products found')}</p>
                                <p className="text-sm mt-2">{t('erp.add_first_product', 'Add your first product to start tracking inventory.')}</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.product_name', 'Name')}</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.sku', 'SKU')}</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.price', 'Price')}</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.stock', 'Stock')}</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.actions', 'Actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {products.data.map((product: any) => (
                                            <tr key={product.id}>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <div className="text-sm font-medium text-gray-900">{product.name}</div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <div className="text-sm text-gray-500">{product.sku || '-'}</div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <div className="text-sm text-gray-900">
                                                        {product.price} {product.currency?.code}
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <div className="flex items-center space-x-2">
                                                        <span className="text-sm font-medium text-gray-900">{product.stock_quantity}</span>
                                                        {product.reorder_level !== null && product.stock_quantity <= product.reorder_level && (
                                                            <Badge variant="destructive" className="ml-2 flex items-center">
                                                                <AlertCircle className="mr-1 h-3 w-3" />
                                                                {t('erp.low_stock', 'Low Stock')}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                    <Link
                                                        href={route('erp.inventory.products.adjust', product.id)}
                                                        className="mr-3 inline-flex items-center text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        <ListPlus className="mr-1 h-4 w-4" />
                                                        {t('erp.adjust_stock', 'Adjust')}
                                                    </Link>
                                                    <Link
                                                        href={route('erp.inventory.products.edit', product.id)}
                                                        className="mr-3 inline-flex items-center text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(product.id)}
                                                        className="inline-flex items-center text-red-600 hover:text-red-900"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
