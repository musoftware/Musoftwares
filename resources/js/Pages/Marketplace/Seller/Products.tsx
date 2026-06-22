import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';

export default function SellerProducts({ products }: any) {
    return (
        <MarketplaceLayout>
            <Head title="My Products" />
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <ModulePageHeader 
                    title="My Products"
                    description="Manage your marketplace listings and services."
                    actions={
                        <Link
                            href="/marketplace/services/create"
                            className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs h-9 px-3.5 rounded-lg transition-colors shadow-sm"
                        >
                            <Plus className="w-3.5 h-3.5 me-1.5" /> Publish New
                        </Link>
                    }
                />

                <OperationalCard noPadding>
                    <div className="divide-y divide-slate-100">
                        {products.data.map((product: any) => (
                            <div key={product.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                <div className="space-y-1">
                                    <div className="font-medium text-slate-900 text-sm">
                                        {product.title}
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-slate-500">
                                        <span>Category: {product.category?.name || 'Uncategorized'}</span>
                                        <span>•</span>
                                        <span>Created: {formatDate(product.created_at)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <StatusBadge status={product.status || 'pending'} size="sm" />
                                </div>
                            </div>
                        ))}
                        {products.data.length === 0 && (
                            <div className="p-8 text-center text-slate-500">
                                You haven't listed any products yet.
                            </div>
                        )}
                    </div>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
