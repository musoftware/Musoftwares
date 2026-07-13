import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

export default function SellerProducts({ products }: any) {
    return (
        <MarketplaceLayout>
            <Head title={__('general.my_products')} />
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <ModulePageHeader 
                    title={__('general.my_products')}
                    description={__('general.manage_your_marketplace_listings')}
                    actions={
                        <Link
                            href="/marketplace/services/create"
                            className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs h-9 px-3.5 rounded-lg transition-colors shadow-sm"
                        >
                            <Plus className="h-3.5 w-3.5 mr-1.5" />
                            {__('general.create_product')}
                        </Link>
                    }
                />

                <SellerNav />

                <OperationalCard noPadding>
                    <div className="divide-y divide-slate-100">
                        {products.data.map((product: any) => (
                            <div key={product.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                <div className="space-y-1">
                                    <div className="font-medium text-slate-900 text-sm">
                                        {product.title}
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-slate-500">
                                        <span>{__('general.category')}: {product.category?.name || __('general.uncategorized')}</span>
                                        <span>•</span>
                                        <span>{__('general.created')}: {formatDate(product.created_at)}</span>
                                     </div>
                                 </div>
                                 <div className="flex items-center gap-3">
                                     <StatusBadge status={product.status || 'pending'} size="sm" />
                                     <Link
                                         href={`/marketplace/services/${product.id}/edit`}
                                         className="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                     >
                                         {__('general.edit')}
                                     </Link>
                                     <Link
                                         href={`/marketplace/services/${product.id}`}
                                         className="text-slate-600 hover:text-slate-800 text-xs font-medium"
                                     >
                                         {__('general.preview')}
                                     </Link>
                                 </div>
                             </div>
                        ))}
                        {products.data.length === 0 && (
                            <div className="p-8 text-center text-slate-500">
                                {__('general.no_services_found_1')}
                            </div>
                        )}
                    </div>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
