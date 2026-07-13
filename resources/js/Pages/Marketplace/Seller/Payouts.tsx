import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import { DollarSign } from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

export default function SellerPayouts({ escrows }: any) {
    return (
        <MarketplaceLayout>
            <Head title={__('general.payouts_escrow')} />
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <ModulePageHeader 
                    title={__('general.payouts_escrow')}
                    description={__('general.view_funds_escrow')}
                    actions={
                        <Link
                            href="/financial/withdrawals"
                            className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs h-9 px-3.5 rounded-lg transition-colors shadow-sm"
                        >
                            <DollarSign className="w-3.5 h-3.5 me-1.5" /> {__('general.request_withdrawal')}
                        </Link>
                    }
                />

                <SellerNav />

                <OperationalCard noPadding>
                    <div className="divide-y divide-slate-100">
                        {escrows.data.map((escrow: any) => (
                            <div key={escrow.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                <div className="space-y-1">
                                    <div className="font-medium text-slate-900 text-sm">
                                        Escrow #{escrow.id} - Order #{escrow.order_id}
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-slate-500">
                                        <span>{__('general.service')}: {escrow.order?.package?.service?.title || 'Unknown'}</span>
                                        <span>•</span>
                                        <span>{__('general.created')}: {formatDate(escrow.created_at)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-xs font-mono font-semibold text-slate-900">
                                        {formatMoney(escrow.amount, 'USD')}
                                    </span>
                                    <StatusBadge status={escrow.status || 'held'} size="sm" />
                                </div>
                            </div>
                        ))}
                        {escrows.data.length === 0 && (
                            <div className="p-8 text-center text-slate-500">
                                {__('general.no_escrows_found')}
                            </div>
                        )}
                    </div>
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
