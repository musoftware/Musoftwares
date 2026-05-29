import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/InputError';

export default function Edit({ product, currencies, stockLogs }: { product: any, currencies: any[], stockLogs?: any[] }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('inventory');

    const t = (key: string, fallback: string) => {
        // @ts-ignore
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            // @ts-ignore
            return window.__(key);
        }
        return fallback;
    };

    const { data, setData, put, processing, errors } = useForm({
        name: product.name || '',
        sku: product.sku || '',
        description: product.description || '',
        price: product.price || '',
        currency_id: product.currency_id || '',
        reorder_level: product.reorder_level || '',
        is_active: product.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('erp.inventory.products.update', product.id));
    };

    return (
        <ERPLayout title={t('erp.edit_product', 'Edit Product')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={t('erp.edit_product', 'Edit Product')} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center">
                        <Link
                            href={route('erp.inventory.index')}
                            className="mr-4 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            {t('erp.back_to_inventory', 'Back')}
                        </Link>
                        <h3 className="text-2xl font-semibold text-gray-900">
                            {t('erp.edit_product', 'Edit Product')}
                        </h3>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div className="col-span-1 md:col-span-2">
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                        {t('erp.product_name', 'Product Name')}
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="sku" className="block text-sm font-medium text-gray-700">
                                        {t('erp.sku', 'SKU')}
                                    </label>
                                    <input
                                        type="text"
                                        id="sku"
                                        value={data.sku}
                                        onChange={(e) => setData('sku', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.sku} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="currency_id" className="block text-sm font-medium text-gray-700">
                                        {t('erp.currency', 'Currency')}
                                    </label>
                                    <select
                                        id="currency_id"
                                        value={data.currency_id}
                                        onChange={(e) => setData('currency_id', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    >
                                        <option value="">{t('erp.select_currency', 'Select Currency')}</option>
                                        {currencies.map((currency) => (
                                            <option key={currency.id} value={currency.id}>
                                                {currency.code} - {currency.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.currency_id} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="price" className="block text-sm font-medium text-gray-700">
                                        {t('erp.price', 'Price')}
                                    </label>
                                    <input
                                        type="number"
                                        id="price"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    <InputError message={errors.price} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="reorder_level" className="block text-sm font-medium text-gray-700">
                                        {t('erp.reorder_level', 'Reorder Level')}
                                    </label>
                                    <input
                                        type="number"
                                        id="reorder_level"
                                        step="0.01"
                                        value={data.reorder_level}
                                        onChange={(e) => setData('reorder_level', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.reorder_level} className="mt-2" />
                                </div>
                                
                                <div className="col-span-1 md:col-span-2">
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                                        {t('erp.description', 'Description')}
                                    </label>
                                    <textarea
                                        id="description"
                                        rows={3}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    ></textarea>
                                    <InputError message={errors.description} className="mt-2" />
                                </div>
                            </div>

                            <div className="mt-6 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    {t('erp.save_changes', 'Save Changes')}
                                </button>
                            </div>
                        </form>
                    </div>

                    {stockLogs && stockLogs.length > 0 && (
                        <div className="mt-8 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h4 className="text-lg font-medium text-gray-900 mb-4">{t('erp.stock_history', 'Stock History')}</h4>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.date', 'Date')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.user', 'User')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.change', 'Change')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.current_stock', 'Current Stock')}</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{t('erp.reason', 'Reason')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {stockLogs.map((log: any) => (
                                                <tr key={log.id}>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                        {new Date(log.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                        {log.user?.name || '-'}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                        <span className={log.change_amount > 0 ? 'text-green-600' : 'text-red-600'}>
                                                            {log.change_amount > 0 ? '+' : ''}{log.change_amount}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
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
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </ERPLayout>
    );
}
