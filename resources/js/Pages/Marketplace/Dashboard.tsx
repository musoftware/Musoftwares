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

export default function MarketplaceDashboard({
    stats: initialStats,
    activePurchases: initialPurchases,
    activeSales: initialSales,
    listedGigs: initialGigs,
    categories = []
}: any) {
    const [selectedService, setSelectedService] = useState<any>(null);

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
        { id: 'orders', label: 'Orders', icon: Clock, href: '/marketplace/orders', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title="Service Workspace"
            workspaceName="Marketplace"
            tenantId="MKT-DRAFT"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="Service Workspace"
                    description="Manage your active service orders, listed gigs, and client sales in one place."
                    actions={
                        <div className="flex items-center gap-2">
                            <Link
                                href="/marketplace/services"
                                className="inline-flex items-center justify-center px-3.5 h-9 text-xs font-medium border border-slate-200 bg-white rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors text-slate-600 shadow-sm"
                            >
                                <Search className="w-3.5 h-3.5 mr-1.5 text-slate-500" /> Browse Services
                            </Link>
                            <Link
                                href="/marketplace/services/create"
                                className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs h-9 px-3.5 rounded-lg transition-colors shadow-sm"
                            >
                                <Plus className="w-3.5 h-3.5 mr-1.5" /> Publish Service
                            </Link>
                        </div>
                    }
                />

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <MetricCard 
                        label="Protected Escrow"
                        value={formatMoney(stats.lockedEscrow, 'USD')}
                        icon={Lock}
                    />
                    <MetricCard 
                        label="Active Orders"
                        value={stats.activeOrders}
                        icon={Clock}
                    />
                    <MetricCard 
                        label="Catalog Gigs"
                        value={stats.servicesListed}
                        icon={Layers}
                    />
                    <MetricCard 
                        label="Total Sales"
                        value={formatMoney(stats.totalSales, 'USD')}
                        icon={DollarSign}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        <OperationalCard title="Active Orders (As Seller)" description="Manage deliverables and track milestones for your clients." noPadding>
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
                                        title="No active client orders yet."
                                        description="Publish your packages or share your catalog to start receiving orders."
                                        action="/marketplace/services/create"
                                        actionLabel="Publish Service Gig"
                                    />
                                )}
                            </div>
                        </OperationalCard>

                        <OperationalCard title="My Purchases (As Buyer)" description="Track deliverables, files, and review services you bought." noPadding>
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
                                        title="No active orders yet."
                                        description="Explore verified, escrow-guaranteed services from top professionals."
                                        action="/marketplace/services"
                                        actionLabel="Browse Services"
                                    />
                                )}
                            </div>
                        </OperationalCard>

                        <OperationalCard title="My Service Catalog" description="Your publicly visible services and customized package offerings." noPadding>
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
                                            <span className="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Starting at</span>
                                        </div>
                                    </div>
                                ))}
                                {listedGigs.length === 0 && (
                                    <EmptyState 
                                        icon={Layers}
                                        title="No services listed yet."
                                        description="Create your professional services list to let clients purchase pricing packages."
                                        action="/marketplace/services/create"
                                        actionLabel="Create Service Listing"
                                    />
                                )}
                            </div>
                        </OperationalCard>
                    </div>

                    <div className="space-y-6">
                        <OperationalCard>
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">
                                Workspace Actions
                            </h4>
                            <div className="space-y-1.5">
                                <Link href="/marketplace/services" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <ShoppingCart className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Browse Gigs Catalog
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href="/marketplace/services/create" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Plus className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Publish Service Gig
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href="/marketplace/orders" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Clock className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Order History
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link href="/financial/withdrawals" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Wallet className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Withdraw Earnings
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>
                            </div>
                        </OperationalCard>

                        <div className="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-sm leading-relaxed text-slate-600 space-y-2">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-900">
                                <Lock className="h-4 w-4 text-slate-500" /> Escrow Protection
                            </div>
                            <p>
                                Every transaction runs under secure financial escrow. Client payments are protected securely on purchase and only unlocked upon client deliverable approvals.
                            </p>
                        </div>

                        <div className="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-sm leading-relaxed text-slate-600 space-y-2">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-900">
                                <AlertCircle className="h-4 w-4 text-slate-500" /> Getting Started
                            </div>
                            <p>
                                Create and publish your gig listings. Clients can purchase standard tiered packages directly, escrow funds are protected, and payouts clear immediately once work is approved.
                            </p>
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
