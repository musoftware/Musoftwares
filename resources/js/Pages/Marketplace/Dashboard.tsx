import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ShoppingCart,
    Layers,
    Plus,
    Lock,
    DollarSign,
    Clock,
    ArrowUpRight,
    Search,
    Briefcase,
    AlertCircle,
    Wallet,
    LayoutDashboard
} from 'lucide-react';

import { formatMoney, formatDate } from '@/lib/utils';
import { ServiceQuickView } from '@/Components/ContextualPanels';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { useMarketplaceMode } from '@/Components/Marketplace/MarketplaceModeContext';
import { __ } from '@/lib/i18n';

export default function MarketplaceDashboard({
    stats: initialStats,
    activePurchases: initialPurchases,
    activeSales: initialSales,
    listedGigs: initialGigs,
    categories = []
}: any) {
    const [selectedService, setSelectedService] = useState<any>(null);

    const marketplaceModeContext = useMarketplaceMode();
    const mode = marketplaceModeContext?.mode || 'client';
    const isClient = mode === 'client';

    const stats = initialStats || {
        lockedEscrow: 0,
        activeOrders: 0,
        servicesListed: 0,
        totalSales: 0
    };

    const activePurchases = initialPurchases || [];
    const activeSales = initialSales || [];
    const listedGigs = initialGigs || [];

    const menuItems = [
        { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/marketplace', isActive: true },
        { id: 'services', label: 'Services', icon: Layers, href: '/marketplace/services', isActive: false },
        { id: 'orders', label: 'Orders', icon: Clock, href: mode === 'seller' ? '/marketplace/orders?tab=sales' : '/marketplace/orders', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title={__('general.service_workspace')}
            workspaceName="Marketplace"
            tenantId="MKT-DRAFT"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title={__('general.service_workspace')}
                    description={__('general.manage_your_active_service_orders_listed_gigs_and_client_sales_in_one_place')}
                    actions={
                        <div className="flex items-center gap-2">
                            <Link
                                href="/marketplace/services"
                                className="inline-flex items-center justify-center px-3.5 h-9 text-xs font-medium border border-slate-200 bg-white rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors text-slate-600 shadow-sm"
                            >
                                <Search className="w-3.5 h-3.5 mr-1.5 text-slate-500" />{__('general.browse_services')}</Link>
                            <Link
                                href="/marketplace/services/create"
                                className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs h-9 px-3.5 rounded-lg transition-colors shadow-sm"
                            >
                                <Plus className="w-3.5 h-3.5 mr-1.5" />{__('general.publish_service')}</Link>
                        </div>
                    }
                />

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <MetricCard 
                        label={__('general.protected_escrow')}
                        value={formatMoney(stats.lockedEscrow, 'USD')}
                        icon={Lock}
                    />
                    <MetricCard 
                        label={__('general.active_orders')}
                        value={stats.activeOrders}
                        icon={Clock}
                    />
                    <MetricCard 
                        label={__('general.catalog_gigs')}
                        value={stats.servicesListed}
                        icon={Layers}
                    />
                    <MetricCard 
                        label={__('general.total_sales')}
                        value={formatMoney(stats.totalSales, 'USD')}
                        icon={DollarSign}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        {!isClient && (
                            <OperationalCard title={__('general.active_orders_as_seller')} description={__('general.manage_deliverables_and_track_milestones_for_your_clients')} noPadding>
                            <div className="divide-y divide-slate-100">
                                {activeSales.map((sale: any) => (
                                    <div key={sale.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                        <div className="space-y-1">
                                            <Link href={`/marketplace/orders/${sale.id}`} className="font-medium text-slate-900 text-sm hover:text-indigo-600 transition flex items-center gap-2">
                                                Order #{sale.id}: {sale.title}
                                            </Link>
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span>Client: {sale.buyerName}</span>
                                                <span>•</span>
                                                <span>Due: {formatDate(sale.deliveryDate)}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-mono font-semibold text-slate-900">
                                                {formatMoney(sale.amount, 'USD')}
                                            </span>
                                            <StatusBadge status={sale.status} size="sm" />
                                        </div>
                                    </div>
                                ))}
                                {activeSales.length === 0 && (
                                    <EmptyState 
                                        icon={Briefcase}
                                        title={__('general.no_active_client_orders_yet')}
                                        description={__('general.publish_your_packages_or_share_your_catalog_to_start_receiving_orders')}
                                        action="/marketplace/services/create"
                                        actionLabel="Publish Service Gig"
                                    />
                                )}
                            </div>
                        </OperationalCard>
                        )}

                        {isClient && (
                        <OperationalCard title={__('general.my_purchases_as_buyer')} description={__('general.track_deliverables_files_and_review_services_you_bought')} noPadding>
                            <div className="divide-y divide-slate-100">
                                {activePurchases.map((purchase: any) => (
                                    <div key={purchase.id} onClick={() => setSelectedService(purchase)} className="p-4 hover:bg-slate-50/50 cursor-pointer transition flex items-center justify-between">
                                        <div className="space-y-1">
                                            <div className="font-medium text-slate-900 text-sm flex items-center gap-2">
                                                Order #{purchase.id}: {purchase.title}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span>Provider: {purchase.sellerName}</span>
                                                <span>•</span>
                                                <span>Est. Delivery: {formatDate(purchase.deliveryDate)}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-mono font-semibold text-slate-900">
                                                {formatMoney(purchase.amount, 'USD')}
                                            </span>
                                            <StatusBadge status={purchase.status} size="sm" />
                                        </div>
                                    </div>
                                ))}
                                {activePurchases.length === 0 && (
                                    <EmptyState 
                                        icon={ShoppingCart}
                                        title={__('general.no_active_orders_yet')}
                                        description={__('general.explore_verified_escrow_guaranteed_services_from_top_professionals')}
                                        action="/marketplace/services"
                                        actionLabel="Browse Services"
                                    />
                                )}
                            </div>
                        </OperationalCard>
                        )}

                        {!isClient && (
                        <OperationalCard title={__('general.my_service_catalog')} description={__('general.your_publicly_visible_services_and_customized_package_offerings')} noPadding>
                            <div className="divide-y divide-slate-100">
                                {listedGigs.map((gig: any) => (
                                    <div key={gig.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                        <div className="space-y-1">
                                            <Link href={`/marketplace/services/${gig.id}`} className="font-medium text-slate-900 text-sm hover:text-indigo-600 transition">
                                                {gig.title}
                                            </Link>
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span className="flex items-center text-amber-500 font-semibold gap-0.5">
                                                    ★ {gig.rating.toFixed(1)}
                                                </span>
                                                <span>({gig.reviews} reviews)</span>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="font-mono font-bold text-slate-900 block text-sm">
                                                {formatMoney(gig.price, 'USD')}
                                            </span>
                                            <span className="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">{__('general.starting_at')}</span>
                                        </div>
                                    </div>
                                ))}
                                {listedGigs.length === 0 && (
                                    <EmptyState 
                                        icon={Layers}
                                        title={__('general.no_services_listed_yet')}
                                        description={__('general.create_your_professional_services_list_to_let_clients_purchase_pricing_packages')}
                                        action="/marketplace/services/create"
                                        actionLabel="Create Service Listing"
                                    />
                                )}
                            </div>
                        </OperationalCard>
                        )}
                    </div>

                    <div className="space-y-6">
                        <OperationalCard>
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">{__('general.workspace_actions')}</h4>
                            <div className="space-y-1.5">
                                <Link href="/marketplace/services" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <ShoppingCart className="h-4 w-4 text-slate-400 group-hover:text-slate-600" />{__('general.browse_gigs_catalog')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href="/marketplace/services/create" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Plus className="h-4 w-4 text-slate-400 group-hover:text-slate-600" />{__('general.publish_service_gig')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href={mode === 'seller' ? "/marketplace/orders?tab=sales" : "/marketplace/orders"} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Clock className="h-4 w-4 text-slate-400 group-hover:text-slate-600" />{__('general.order_history')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href="/financial/withdrawals" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Wallet className="h-4 w-4 text-slate-400 group-hover:text-slate-600" />{__('general.withdraw_earnings')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>
                            </div>
                        </OperationalCard>

                        <div className="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-sm leading-relaxed text-slate-600 space-y-2">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-900">
                                <Lock className="h-4 w-4 text-slate-500" />{__('general.escrow_protection')}</div>
                            <p>{__('general.every_transaction_runs_under_secure_financial_escrow_client_payments_are_protected_securely_on_purchase_and_only_unlocked_upon_client_deliverable_approvals')}</p>
                        </div>

                        <div className="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-sm leading-relaxed text-slate-600 space-y-2">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-900">
                                <AlertCircle className="h-4 w-4 text-slate-500" />{__('general.getting_started')}</div>
                            <p>{__('general.create_and_publish_your_gig_listings_clients_can_purchase_standard_tiered_packages_directly_escrow_funds_are_protected_and_payouts_clear_immediately_once_work_is_approved')}</p>
                        </div>
                    </div>
                </div>

            
            </div>
            <ServiceQuickView
                isOpen={selectedService !== null}
                onClose={() => setSelectedService(null)}
                data={selectedService}
            />
        </WorkspaceLayout>
    );
}
