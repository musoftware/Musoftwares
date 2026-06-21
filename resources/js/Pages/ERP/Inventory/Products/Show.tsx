import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Plus, Minus } from 'lucide-react';
import Pagination from '@/components/Pagination';
import { __ } from '@/lib/i18n';

export default function Show({ product, stockLogs }: { product: any, stockLogs: any }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('inventory');

    const t = (key: string, fallback: string) => {
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            return window.__(key);
        }
        return fallback;
    };

    return (
        <ERPLayout title={t('erp.product_details', 'Product Details')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={product.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="mb-6 flex items-center justify-between">
                        <div className="flex items-center">
                            <Link
                                href={route('erp.inventory.index')}
                                className="me-4 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
                            >
                                <ArrowLeft className="me-1 h-4 w-4" />
                                {t('erp.back_to_inventory', 'Back')}
                            </Link>
                            <h3 className="text-2xl font-semibold text-gray-900">
                                {product.name}
                            </h3>
                        </div>
                        <div className="flex space-x-3">
                            <Link
                                href={route('erp.inventory.products.adjust', product.id)}
                                className="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <Plus className="me-1 h-4 w-4" />
                                {t('erp.adjust_stock', 'Adjust Stock')}
                            </Link>
                            <Link
                                href={route('erp.inventory.products.edit', product.id)}
                                className="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <Edit className="me-1 h-4 w-4" />
                                {t('erp.edit', 'Edit')}
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        
                        {/* Details Card */}
                        <div className="col-span-1 lg:col-span-1">
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">{t('erp.details', 'Details')}</h4>
                                    
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2 lg:grid-cols-1">
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.sku', 'SKU')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{product.sku || '-'}</dd>
                                        </div>
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.price', 'Price')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {product.currency?.symbol} {product.price}
                                            </dd>
                                        </div>
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.current_stock', 'Current Stock')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-semibold text-indigo-600">
                                                {product.stock_quantity}
                                            </dd>
                                        </div>
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.reorder_level', 'Reorder Level')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{product.reorder_level || '-'}</dd>
                                        </div>
                                        <div className="sm:col-span-2 lg:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.status', 'Status')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {product.is_active ? (
                                                    <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                        {t('erp.active', 'Active')}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                        {t('erp.inactive', 'Inactive')}
                                                    </span>
                                                )}
                                            </dd>
                                        </div>
                                        <div className="sm:col-span-2 lg:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">{t('erp.description', 'Description')}</dt>
                                            <dd className="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                                {product.description || '-'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {/* Stock History */}
                        <div className="col-span-1 lg:col-span-2">
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">{t('erp.stock_history', 'Stock History')}</h4>
                                    
                                    {stockLogs && stockLogs.data && (stockLogs.data as any).length > 0 ? (
                                        <>
                                            <div className="overflow-x-auto">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.date', 'Date')}</th>
                                                            <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.user', 'User')}</th>
                                                            <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.change', 'Change')}</th>
                                                            <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.current_stock', 'Current Stock')}</th>
                                                            <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.reason', 'Reason')}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-200 bg-white">
                                                        {(stockLogs.data as any).map((log: any) => (
                                                            <tr key={log.id}>
                                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                                    {new Date(log.created_at).toLocaleString()}
                                                                </td>
                                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                                    {log.user?.name || '-'}
                                                                </td>
                                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                                    <span className={log.change_amount > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'}>
                                                                        {log.change_amount > 0 ? '+' : ''}{log.change_amount}
                                                                    </span>
                                                                </td>
                                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 font-medium">
                                                                    {log.new_quantity}
                                                                </td>
                                                                <td className="px-6 py-4 text-sm text-gray-500">
                                                                    {log.reason}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div className="mt-4">
                                                <Pagination links={stockLogs.links} />
                                            </div>
                                        </>
                                    ) : (
                                        <div className="text-center py-8 text-gray-500">
                                            {t('erp.no_stock_history', 'No stock history available.')}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
