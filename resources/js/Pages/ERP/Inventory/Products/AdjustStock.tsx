import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/InputError';

export default function AdjustStock({ product }: { product: any }) {
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
        change_amount: '',
        reason: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.inventory.products.store_adjustment', product.id));
    };

    return (
        <ERPLayout title={t('erp.adjust_stock', 'Adjust Stock')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={t('erp.adjust_stock', 'Adjust Stock')} />

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
                            {t('erp.adjust_stock', 'Adjust Stock')}: {product.name}
                        </h3>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        {t('erp.current_stock', 'Current Stock')}
                                    </label>
                                    <div className="mt-1 text-lg font-semibold text-gray-900">
                                        {product.stock_quantity}
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="change_amount" className="block text-sm font-medium text-gray-700">
                                        {t('erp.change_amount', 'Change Amount (+ or -)')}
                                    </label>
                                    <input
                                        type="number"
                                        id="change_amount"
                                        step="0.01"
                                        value={data.change_amount}
                                        onChange={(e) => setData('change_amount', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="e.g. 5, -3"
                                        required
                                    />
                                    <InputError message={errors.change_amount} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="reason" className="block text-sm font-medium text-gray-700">
                                        {t('erp.reason', 'Reason for Adjustment')}
                                    </label>
                                    <input
                                        type="text"
                                        id="reason"
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    <InputError message={errors.reason} className="mt-2" />
                                </div>
                            </div>

                            <div className="mt-6 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    {t('erp.save_adjustment', 'Save Adjustment')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
