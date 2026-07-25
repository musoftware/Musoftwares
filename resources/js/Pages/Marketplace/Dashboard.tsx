import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    Plus,
    Search,
    ShoppingBag,
    Store,
    Star,
    MessageSquare,
    ArrowRight,
    Lock,
    DollarSign,
    Layers,
    TrendingUp,
    Sparkles,
    UserCheck,
    FileText,
    Check,
    ChevronRight,
    Briefcase,
    Edit,
    Trash2
} from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { useMarketplaceMode } from '@/Components/Marketplace/MarketplaceModeContext';
import { __ } from '@/lib/i18n';

interface OrderItem {
    id: number;
    title: string;
    sellerName?: string;
    sellerAvatar?: string;
    buyerName?: string;
    buyerAvatar?: string;
    amount: number;
    status: string;
    deliveryDate?: string;
    actionNeededText?: string;
}

interface GigItem {
    id: number;
    title: string;
    status: string;
    price: number;
    reviews: number;
    rating: number;
}

interface ActivityItem {
    id: number;
    orderId: number;
    serviceTitle: string;
    oldStatus: string;
    newStatus: string;
    changedByName: string;
    note?: string;
    timestamp: string;
}

interface Props {
    needsActionPurchases?: OrderItem[];
    activePurchases?: OrderItem[];
    buyerActivity?: ActivityItem[];
    buyerStats?: {
        lockedEscrow: number;
        activeOrders: number;
        totalSpent: number;
        completedCount: number;
    };
    needsActionSales?: OrderItem[];
    activeSales?: OrderItem[];
    listedGigs?: GigItem[];
    sellerStats?: {
        lockedEscrow: number;
        activeOrders: number;
        totalSales: number;
        servicesListed: number;
        completedOrders: number;
        completionRate: number;
    };
}

export default function MarketplaceDashboard({
    needsActionPurchases = [],
    activePurchases = [],
    buyerActivity = [],
    buyerStats = { lockedEscrow: 0, activeOrders: 0, totalSpent: 0, completedCount: 0 },
    needsActionSales = [],
    activeSales = [],
    listedGigs = [],
    sellerStats = { lockedEscrow: 0, activeOrders: 0, totalSales: 0, servicesListed: 0, completedOrders: 0, completionRate: 100 }
}: Props) {
    const { auth } = usePage().props as any;
    const { mode, setMode } = useMarketplaceMode();
    const isBuyer = mode === 'client';

    const handleDeleteGig = (id: number, title: string) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_service') || `Are you sure you want to delete "${title}"?`)) {
            router.delete(route('marketplace.services.destroy', id));
        }
    };


    return (
        <MarketplaceLayout>
            <Head title={isBuyer ? __('general.buyer_dashboard') || 'Buyer Dashboard' : __('general.seller_dashboard') || 'Seller Workspace'} />

            <div className="min-h-screen bg-slate-50/50 pb-16">
                
                {/* ── Top Operational Bar & Mode Switcher ───────────────── */}
                <div className="bg-white border-b border-slate-200">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            
                            {/* Dashboard Title & User Welcome */}
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-black text-slate-900 tracking-tight">
                                        {isBuyer ? (
                                            <span>{__('general.buyer_dashboard') || 'Buyer Dashboard'}</span>
                                        ) : (
                                            <span>{__('general.seller_workspace') || 'Seller Workspace'}</span>
                                        )}
                                    </h1>
                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                                        isBuyer 
                                            ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' 
                                            : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                    }`}>
                                        {isBuyer ? 'Buying Mode' : 'Seller Mode'}
                                    </span>
                                </div>
                                <p className="text-sm text-slate-500 font-medium">
                                    {isBuyer 
                                        ? (__('general.what_should_i_do_now') || 'Track your active orders, deliverable approvals, and seller messages.')
                                        : (__('general.what_work_should_i_do_now') || 'Manage client orders, work queue, revisions, and revenue.')
                                    }
                                </p>
                            </div>

                            {/* Mode Switcher Tabs & Quick CTA */}
                            <div className="flex items-center gap-3">
                                <div className="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                                    <button
                                        type="button"
                                        onClick={() => setMode('client')}
                                        className={`flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                            isBuyer
                                                ? 'bg-white text-indigo-700 shadow-xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        <ShoppingBag className="w-3.5 h-3.5" />
                                        <span>{__('general.buyer_dashboard') || 'Buyer'}</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setMode('seller')}
                                        className={`flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                            !isBuyer
                                                ? 'bg-white text-emerald-700 shadow-xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        <Store className="w-3.5 h-3.5" />
                                        <span>{__('general.seller_dashboard') || 'Seller'}</span>
                                    </button>
                                </div>

                                {isBuyer ? (
                                    <Link
                                        href="/marketplace/services"
                                        className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm"
                                    >
                                        <Search className="w-3.5 h-3.5" />
                                        <span>{__('general.browse_services') || 'Browse Services'}</span>
                                    </Link>
                                ) : (
                                    <Link
                                        href="/marketplace/services/create"
                                        className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors shadow-sm"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        <span>{__('general.create_a_gig') || 'Create a Gig'}</span>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Main Dashboard Workspace Content ─────────────────── */}
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-8 space-y-8">

                    {/* =================================================== */}
                    {/* 1. BUYER DASHBOARD CONTENT                          */}
                    {/* =================================================== */}
                    {isBuyer && (
                        <div className="space-y-8">
                            
                            {/* SECTION 1: Needs Your Action */}
                            {needsActionPurchases.length > 0 && (
                                <div className="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-6 space-y-4">
                                    <div className="flex items-center gap-2 text-amber-900 font-bold text-base">
                                        <AlertCircle className="w-5 h-5 text-amber-600 shrink-0" />
                                        <span>{__('general.needs_your_action') || 'Needs Your Action'}</span>
                                        <span className="ms-auto rounded-full bg-amber-200/80 text-amber-900 text-xs px-2.5 py-0.5 font-extrabold">
                                            {needsActionPurchases.length}
                                        </span>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {needsActionPurchases.map((order) => (
                                            <div key={order.id} className="bg-white border border-amber-200 rounded-xl p-4 shadow-xs flex flex-col justify-between space-y-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="space-y-1">
                                                        <span className="text-xs font-bold text-amber-700 uppercase tracking-wider">
                                                            {order.actionNeededText || 'Action Required'}
                                                        </span>
                                                        <h4 className="font-bold text-slate-900 text-sm line-clamp-1">
                                                            Order #{order.id}: {order.title}
                                                        </h4>
                                                        <p className="text-xs text-slate-500">
                                                            Seller: <span className="font-medium text-slate-700">{order.sellerName}</span>
                                                        </p>
                                                    </div>
                                                    <StatusBadge status={order.status} />
                                                </div>
                                                <div className="flex items-center justify-between border-t border-slate-100 pt-3">
                                                    <span className="text-xs font-semibold text-slate-900">
                                                        {formatMoney(order.amount, auth?.user?.currency)}
                                                    </span>
                                                    <Link
                                                        href={`/marketplace/orders/${order.id}`}
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors"
                                                    >
                                                        <span>Take Action</span>
                                                        <ArrowRight className="w-3.5 h-3.5" />
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* SECTION 2: Active Orders Queue */}
                            <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                                <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                                            <Clock className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h2 className="font-extrabold text-slate-900 text-base">
                                                {__('general.active_orders') || 'Active Orders'}
                                            </h2>
                                            <p className="text-xs text-slate-500">Track orders currently in progress with sellers</p>
                                        </div>
                                    </div>
                                    <Link href="/marketplace/orders" className="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-1">
                                        <span>View All Orders</span>
                                        <ChevronRight className="w-3.5 h-3.5" />
                                    </Link>
                                </div>

                                {activePurchases.length === 0 ? (
                                    <div className="p-12 text-center space-y-4">
                                        <div className="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500 mx-auto">
                                            <ShoppingBag className="w-8 h-8" />
                                        </div>
                                        <div className="space-y-1">
                                            <h3 className="font-bold text-slate-900 text-base">{__('general.no_orders_yet') || 'No Orders Yet'}</h3>
                                            <p className="text-xs text-slate-500 max-w-sm mx-auto">
                                                You don't have any active orders in progress right now. Explore professional services to get started!
                                            </p>
                                        </div>
                                        <Link
                                            href="/marketplace/services"
                                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-colors shadow-sm"
                                        >
                                            <Search className="w-4 h-4" />
                                            <span>{__('general.browse_services') || 'Browse Services'}</span>
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {activePurchases.map((order) => (
                                            <div key={order.id} className="p-5 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div className="space-y-1.5">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-bold text-slate-400">#{order.id}</span>
                                                        <Link href={`/marketplace/orders/${order.id}`} className="font-bold text-slate-900 text-sm hover:text-indigo-600 transition line-clamp-1">
                                                            {order.title}
                                                        </Link>
                                                    </div>
                                                    <div className="flex items-center gap-4 text-xs text-slate-500">
                                                        <span>Seller: <strong className="text-slate-700">{order.sellerName}</strong></span>
                                                        {order.deliveryDate && <span>Due: <strong className="text-slate-700">{formatDate(order.deliveryDate)}</strong></span>}
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-4 shrink-0">
                                                    <span className="font-extrabold text-slate-900 text-sm">
                                                        {formatMoney(order.amount, auth?.user?.currency)}
                                                    </span>
                                                    <StatusBadge status={order.status} />
                                                    <Link
                                                        href={`/marketplace/orders/${order.id}`}
                                                        className="px-3.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-bold text-xs text-slate-700 shadow-2xs transition-colors"
                                                    >
                                                        View Order
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* SECTION 3: Recent Activity Stream */}
                            {buyerActivity.length > 0 && (
                                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                                    <h3 className="font-extrabold text-slate-900 text-base">Recent Order Activity</h3>
                                    <div className="space-y-3">
                                        {buyerActivity.map((activity) => (
                                            <div key={activity.id} className="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                                <div className="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 mt-0.5">
                                                    <FileText className="w-3.5 h-3.5" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-slate-900 font-medium">
                                                        Order #{activity.orderId} ({activity.serviceTitle}) status changed to <span className="font-bold text-indigo-700 uppercase">{activity.newStatus}</span> by {activity.changedByName}.
                                                    </p>
                                                    {activity.note && <p className="text-slate-500 italic mt-0.5">"{activity.note}"</p>}
                                                    <span className="text-[10px] text-slate-400 mt-1 block">{activity.timestamp}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* SECTION 4: Statistics (At the bottom) */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-200">
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Spent</span>
                                    <p className="text-2xl font-black text-slate-900">{formatMoney(buyerStats.totalSpent, auth?.user?.currency)}</p>
                                </div>
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Purchases</span>
                                    <p className="text-2xl font-black text-indigo-600">{buyerStats.activeOrders}</p>
                                </div>
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Protected Escrow</span>
                                    <p className="text-2xl font-black text-emerald-600">{formatMoney(buyerStats.lockedEscrow, auth?.user?.currency)}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* =================================================== */}
                    {/* 2. SELLER DASHBOARD CONTENT                         */}
                    {/* =================================================== */}
                    {!isBuyer && (
                        <div className="space-y-8">
                            
                            {/* SECTION 1: Orders Need Attention (Seller) */}
                            {needsActionSales.length > 0 && (
                                <div className="rounded-2xl border border-rose-200 bg-rose-50/40 p-6 space-y-4">
                                    <div className="flex items-center gap-2 text-rose-900 font-bold text-base">
                                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                                        <span>{__('general.orders_need_attention') || 'Orders Need Attention'}</span>
                                        <span className="ms-auto rounded-full bg-rose-200 text-rose-900 text-xs px-2.5 py-0.5 font-extrabold">
                                            {needsActionSales.length}
                                        </span>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {needsActionSales.map((order) => (
                                            <div key={order.id} className="bg-white border border-rose-200 rounded-xl p-4 shadow-xs flex flex-col justify-between space-y-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="space-y-1">
                                                        <span className="text-xs font-bold text-rose-700 uppercase tracking-wider">
                                                            {order.actionNeededText || 'Work Submission Due'}
                                                        </span>
                                                        <h4 className="font-bold text-slate-900 text-sm line-clamp-1">
                                                            Order #{order.id}: {order.title}
                                                        </h4>
                                                        <p className="text-xs text-slate-500">
                                                            Client: <span className="font-medium text-slate-700">{order.buyerName}</span>
                                                        </p>
                                                    </div>
                                                    <StatusBadge status={order.status} />
                                                </div>
                                                <div className="flex items-center justify-between border-t border-slate-100 pt-3">
                                                    <span className="text-xs font-semibold text-slate-900">
                                                        {formatMoney(order.amount, auth?.user?.currency)}
                                                    </span>
                                                    <Link
                                                        href={`/marketplace/orders/${order.id}`}
                                                        className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-colors"
                                                    >
                                                        <span>Submit Work</span>
                                                        <ArrowRight className="w-3.5 h-3.5" />
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* SECTION 2: Seller Work Queue / Active Orders */}
                            <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                                <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                            <Briefcase className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h2 className="font-extrabold text-slate-900 text-base">
                                                {__('general.active_client_orders') || 'Active Client Orders'}
                                            </h2>
                                            <p className="text-xs text-slate-500">Deliverables and client orders currently assigned to you</p>
                                        </div>
                                    </div>
                                    <Link href="/marketplace/orders" className="text-xs font-bold text-emerald-600 hover:text-emerald-800 transition-colors flex items-center gap-1">
                                        <span>Manage All Orders</span>
                                        <ChevronRight className="w-3.5 h-3.5" />
                                    </Link>
                                </div>

                                {activeSales.length === 0 ? (
                                    <div className="p-12 text-center space-y-4">
                                        <div className="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 mx-auto">
                                            <Store className="w-8 h-8" />
                                        </div>
                                        <div className="space-y-1">
                                            <h3 className="font-bold text-slate-900 text-base">{__('general.no_client_orders_yet') || 'No Active Orders'}</h3>
                                            <p className="text-xs text-slate-500 max-w-sm mx-auto">
                                                You don't have any incoming client orders to deliver right now. Keep your Gigs updated!
                                            </p>
                                        </div>
                                        <Link
                                            href="/marketplace/services/create"
                                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-sm"
                                        >
                                            <Plus className="w-4 h-4" />
                                            <span>{__('general.create_a_gig') || 'Create a Gig'}</span>
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {activeSales.map((order) => (
                                            <div key={order.id} className="p-5 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div className="space-y-1.5">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-bold text-slate-400">#{order.id}</span>
                                                        <Link href={`/marketplace/orders/${order.id}`} className="font-bold text-slate-900 text-sm hover:text-emerald-600 transition line-clamp-1">
                                                            {order.title}
                                                        </Link>
                                                    </div>
                                                    <div className="flex items-center gap-4 text-xs text-slate-500">
                                                        <span>Client: <strong className="text-slate-700">{order.buyerName}</strong></span>
                                                        {order.deliveryDate && <span>Due: <strong className="text-slate-700">{formatDate(order.deliveryDate)}</strong></span>}
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-4 shrink-0">
                                                    <span className="font-extrabold text-slate-900 text-sm">
                                                        {formatMoney(order.amount, auth?.user?.currency)}
                                                    </span>
                                                    <StatusBadge status={order.status} />
                                                    <Link
                                                        href={`/marketplace/orders/${order.id}`}
                                                        className="px-3.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 font-bold text-xs text-emerald-800 transition-colors"
                                                    >
                                                        Deliver / Workspace
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* SECTION 3: My Published Services / Gigs */}
                            <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="font-extrabold text-slate-900 text-base">{__('general.my_gigs') || 'My Services & Gigs'}</h3>
                                        <p className="text-xs text-slate-500">Services you offer in the marketplace catalog</p>
                                    </div>
                                    <Link
                                        href="/marketplace/services/create"
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-xs"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        <span>New Gig</span>
                                    </Link>
                                </div>

                                {listedGigs.length === 0 ? (
                                    <div className="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 space-y-3">
                                        <p className="text-xs text-slate-500">You haven't created any marketplace services yet.</p>
                                        <Link
                                            href="/marketplace/services/create"
                                            className="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:underline"
                                        >
                                            <Plus className="w-3.5 h-3.5" />
                                            <span>Publish Your First Service</span>
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                        {listedGigs.map((gig) => (
                                            <div key={gig.id} className="border border-slate-200 rounded-xl p-4 bg-slate-50/50 hover:bg-white hover:border-indigo-200 transition space-y-3 flex flex-col justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <h4 className="font-bold text-slate-900 text-sm line-clamp-2">{gig.title}</h4>
                                                        <div className="flex items-center gap-1 shrink-0">
                                                            <Link
                                                                href={route('marketplace.services.edit', gig.id)}
                                                                className="p-1 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-100 transition-colors"
                                                                title={__('general.edit') || 'Edit'}
                                                            >
                                                                <Edit className="w-4 h-4" />
                                                            </Link>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteGig(gig.id, gig.title)}
                                                                className="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 transition-colors"
                                                                title={__('general.delete') || 'Delete'}
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200/60">
                                                    <span className="font-bold text-slate-900">From {formatMoney(gig.price, auth?.user?.currency)}</span>
                                                    <div className="flex items-center gap-1 text-amber-600 font-bold">
                                                        <Star className="w-3.5 h-3.5 fill-current" />
                                                        <span>{gig.rating > 0 ? gig.rating.toFixed(1) : 'New'}</span>
                                                        <span className="text-slate-400 font-normal">({gig.reviews})</span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* SECTION 4: Revenue & Analytics (At the bottom) */}
                            <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-200">
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sales</span>
                                    <p className="text-2xl font-black text-emerald-600">{formatMoney(sellerStats.totalSales, auth?.user?.currency)}</p>
                                </div>
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Held Escrow</span>
                                    <p className="text-2xl font-black text-slate-900">{formatMoney(sellerStats.lockedEscrow, auth?.user?.currency)}</p>
                                </div>
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completion Rate</span>
                                    <p className="text-2xl font-black text-indigo-600">{sellerStats.completionRate}%</p>
                                </div>
                                <div className="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-1">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Services</span>
                                    <p className="text-2xl font-black text-slate-900">{sellerStats.servicesListed}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </MarketplaceLayout>
    );
}
