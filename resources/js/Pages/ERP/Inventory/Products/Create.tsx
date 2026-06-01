import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/InputError';
import { __ } from '@/lib/i18n';

export default function Create({ currencies, categories, hasMultiCurrency, baseCurrencyId }: { currencies: any[], categories: any[], hasMultiCurrency: boolean, baseCurrencyId: number }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('inventory');

    const t = (key: string, fallback: string) => {
        // @ts-ignore
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            // @ts-ignore
            return window.__(key);
        }
        return fallback;
    };

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        sku: '',
        description: '',
        price: '',
        currency_id: baseCurrencyId || (currencies.length > 0 ? currencies[0].id : ''),
        category_id: '',
        barcode: '',
        uom: 'piece',
        tax_rate: '0',
        image: null as File | null,
        stock_quantity: '0',
        reorder_level: '',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.inventory.products.store'));
    };

    return (
        <ERPLayout title={t('erp.add_product', 'Add Product')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={t('erp.add_product', 'Add Product')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center">
                        <Link
                            href={route('erp.inventory.index')}
                            className="mr-4 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            {t('erp.back_to_inventory', 'Back')}
                        </Link>
                        <h3 className="text-2xl font-semibold text-gray-900">
                            {t('erp.add_product', 'Add Product')}
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

                                {hasMultiCurrency && (
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
                                                    {currency.currency} - {currency.symbol}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.currency_id} className="mt-2" />
                                    </div>
                                )}

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
                                    <label htmlFor="category_id" className="block text-sm font-medium text-gray-700">
                                        {t('erp.category', 'Category')}
                                    </label>
                                    <select
                                        id="category_id"
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">{t('erp.select_category', 'Select Category (Optional)')}</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.category_id} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="barcode" className="block text-sm font-medium text-gray-700">
                                        {t('erp.barcode', 'Barcode / EAN')}
                                    </label>
                                    <input
                                        type="text"
                                        id="barcode"
                                        value={data.barcode}
                                        onChange={(e) => setData('barcode', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.barcode} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="uom" className="block text-sm font-medium text-gray-700">
                                        {t('erp.uom', 'Unit of Measure (Qty Type)')}
                                    </label>
                                    <input
                                        type="text"
                                        id="uom"
                                        value={data.uom}
                                        onChange={(e) => setData('uom', e.target.value)}
                                        placeholder={__('general.piece_kg_box')}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.uom} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="tax_rate" className="block text-sm font-medium text-gray-700">
                                        {t('erp.tax_rate', 'Tax Rate (%)')}
                                    </label>
                                    <input
                                        type="number"
                                        id="tax_rate"
                                        step="0.01"
                                        value={data.tax_rate}
                                        onChange={(e) => setData('tax_rate', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.tax_rate} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="image" className="block text-sm font-medium text-gray-700">
                                        {t('erp.product_image', 'Product Image')}
                                    </label>
                                    <input
                                        type="file"
                                        id="image"
                                        onChange={(e) => setData('image', e.target.files ? e.target.files[0] : null)}
                                        className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        accept="image/*"
                                    />
                                    <InputError message={errors.image} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="stock_quantity" className="block text-sm font-medium text-gray-700">
                                        {t('erp.initial_stock', 'Initial Stock')}
                                    </label>
                                    <input
                                        type="number"
                                        id="stock_quantity"
                                        step="0.01"
                                        value={data.stock_quantity}
                                        onChange={(e) => setData('stock_quantity', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <InputError message={errors.stock_quantity} className="mt-2" />
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
                                    {t('erp.save', 'Save')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
