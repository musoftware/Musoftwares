import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
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
    Wallet
} from 'lucide-react';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { formatMoney, formatDate } from '@/lib/utils';
import { ServiceQuickView } from '@/Components/ContextualPanels';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

export default function MarketplaceDashboard({
    stats: initialStats,
    activePurchases: initialPurchases,
    activeSales: initialSales,
    listedGigs: initialGigs,
    categories = []
}: any) {
    const [selectedService, setSelectedService] = useState<any>(null);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    // Compute marketplace statistics
    const stats = initialStats || {
        lockedEscrow: 0,
        activeOrders: 0,
        servicesListed: 0,
        totalSales: 0
    };

    const activePurchases = initialPurchases || [];
    const activeSales = initialSales || [];
    const listedGigs = initialGigs || [];

    // Inertia form submission for creating a new service gig
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        category_id: categories?.[0]?.id || '',
    });

    const handlePublish = (e: React.FormEvent) => {
        e.preventDefault();
        post('/marketplace/services', {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-slate-900 font-sans">
                            Service Workspace
                        </h1>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Manage your active service orders, listed gigs, and client sales in one place.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href="/marketplace/services"
                            className="inline-flex items-center justify-center px-3.5 h-9 text-xs font-semibold border border-slate-200 bg-white rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors text-slate-700"
                        >
                            <Search className="w-3.5 h-3.5 mr-1.5 text-slate-400" /> Browse Services
                        </Link>
                        <Button
                            onClick={() => setIsCreateModalOpen(true)}
                            className="bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs h-9 px-3.5 rounded-lg flex items-center justify-center transition-colors"
                        >
                            <Plus className="w-3.5 h-3.5 mr-1.5" /> Publish Service
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Service Workspace" />

            <div className="max-w-[1200px] mx-auto space-y-8 pb-16 font-sans text-sm">
                
                {/* ─────────────────────────────────────────
                    COMPACT STATS STRIP
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Escrow Protected */}
                    <div className="bg-white border border-slate-200/80 rounded-xl p-4 shadow-sm hover:border-slate-300 transition-colors">
                        <div className="flex justify-between items-center text-slate-500 text-xs font-medium">
                            <span>Protected Escrow</span>
                            <Lock className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="mt-2">
                            <span className="font-mono text-2xl font-bold text-slate-900 block">
                                {formatMoney(stats.lockedEscrow, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold block mt-1">
                                Secure client funds active
                            </span>
                        </div>
                    </div>

                    {/* Active Gigs/Orders */}
                    <div className="bg-white border border-slate-200/80 rounded-xl p-4 shadow-sm hover:border-slate-300 transition-colors">
                        <div className="flex justify-between items-center text-slate-500 text-xs font-medium">
                            <span>Active Orders</span>
                            <Clock className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="mt-2">
                            <span className="font-mono text-2xl font-bold text-slate-900 block">
                                {stats.activeOrders}
                            </span>
                            <span className="text-[10px] text-slate-500 block mt-1">
                                In progress as seller
                            </span>
                        </div>
                    </div>

                    {/* Listed Services */}
                    <div className="bg-white border border-slate-200/80 rounded-xl p-4 shadow-sm hover:border-slate-300 transition-colors">
                        <div className="flex justify-between items-center text-slate-500 text-xs font-medium">
                            <span>Catalog Gigs</span>
                            <Layers className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="mt-2">
                            <span className="font-mono text-2xl font-bold text-slate-900 block">
                                {stats.servicesListed}
                            </span>
                            <span className="text-[10px] text-slate-500 block mt-1">
                                Publicly visible catalog
                            </span>
                        </div>
                    </div>

                    {/* Completed Earnings */}
                    <div className="bg-white border border-slate-200/80 rounded-xl p-4 shadow-sm hover:border-slate-300 transition-colors">
                        <div className="flex justify-between items-center text-slate-500 text-xs font-medium">
                            <span>Total Sales</span>
                            <DollarSign className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="mt-2">
                            <span className="font-mono text-2xl font-bold text-slate-900 block">
                                {formatMoney(stats.totalSales, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold block mt-1">
                                100% payout cleared
                            </span>
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    TWO COLUMN LIGHT WORKSPACE
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-8">
                    
                    {/* Primary Operations (Left 7 Columns) */}
                    <div className="lg:col-span-7 space-y-8">
                        
                        {/* SECTION 1: Active Sales (As Seller) */}
                        <div className="bg-white border border-slate-200/60 rounded-xl shadow-xs overflow-hidden">
                            <div className="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/[0.15]">
                                <div>
                                    <h3 className="font-semibold text-sm text-slate-900 flex items-center gap-2">
                                        <Briefcase className="h-4 w-4 text-slate-500" /> Active Orders (As Seller)
                                    </h3>
                                    <p className="text-[11px] text-slate-400 mt-0.5">
                                        Manage deliverables and track milestones for your clients.
                                    </p>
                                </div>
                            </div>
                            
                            <div className="divide-y divide-slate-100">
                                {activeSales.map((sale: any) => (
                                    <div 
                                        key={sale.id}
                                        className="p-4 hover:bg-slate-50/30 transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <Link 
                                                href={`/marketplace/orders/${sale.id}`}
                                                className="font-medium text-slate-900 text-sm hover:text-slate-800 transition flex items-center gap-2"
                                            >
                                                Order #{sale.id}: {sale.title}
                                            </Link>
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span>Client: {sale.buyerName}</span>
                                                <span>•</span>
                                                <span>Due: {formatDate(sale.deliveryDate)}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-mono font-semibold text-slate-950">
                                                {formatMoney(sale.amount, 'USD')}
                                            </span>
                                            <span className={`text-[10px] font-medium px-2 py-0.5 rounded-full border ${
                                                sale.status === 'processing' 
                                                ? 'bg-indigo-50 border-indigo-150 text-indigo-700' 
                                                : 'bg-amber-50 border-amber-150 text-amber-700'
                                            }`}>
                                                {sale.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                                {activeSales.length === 0 && (
                                    <div className="p-8 text-center bg-slate-50/10 border border-dashed border-slate-200/80 rounded-xl m-4">
                                        <div className="mx-auto w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                            <Briefcase className="h-4 w-4 text-slate-400" />
                                        </div>
                                        <p className="text-xs font-medium text-slate-700">No active client orders yet.</p>
                                        <p className="text-[11px] text-slate-400 mt-1 max-w-[280px] mx-auto">
                                            Publish your packages or share your catalog to start receiving orders.
                                        </p>
                                        <div className="mt-4">
                                            <Button 
                                                onClick={() => setIsCreateModalOpen(true)}
                                                size="sm" 
                                                variant="outline"
                                                className="text-xs border-slate-200 hover:border-slate-350 hover:bg-slate-50 h-8"
                                            >
                                                Publish Service Gig
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* SECTION 2: Active Purchases (As Buyer) */}
                        <div className="bg-white border border-slate-200/60 rounded-xl shadow-xs overflow-hidden">
                            <div className="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/[0.15]">
                                <div>
                                    <h3 className="font-semibold text-sm text-slate-900 flex items-center gap-2">
                                        <ShoppingCart className="h-4 w-4 text-slate-500" /> My Purchases (As Buyer)
                                    </h3>
                                    <p className="text-[11px] text-slate-400 mt-0.5">
                                        Track deliverables, files, and review services you bought.
                                    </p>
                                </div>
                            </div>
                            
                            <div className="divide-y divide-slate-100">
                                {activePurchases.map((purchase: any) => (
                                    <div 
                                        key={purchase.id}
                                        onClick={() => setSelectedService(purchase)}
                                        className="p-4 hover:bg-slate-50/30 cursor-pointer transition flex items-center justify-between"
                                    >
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
                                            <span className="text-xs font-mono font-semibold text-slate-955">
                                                {formatMoney(purchase.amount, 'USD')}
                                            </span>
                                            <span className={`text-[10px] font-medium px-2 py-0.5 rounded-full border ${
                                                purchase.status === 'processing' 
                                                ? 'bg-indigo-50 border-indigo-150 text-indigo-700' 
                                                : 'bg-amber-50 border-amber-150 text-amber-700'
                                            }`}>
                                                {purchase.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                                {activePurchases.length === 0 && (
                                    <div className="p-8 text-center bg-slate-50/10 border border-dashed border-slate-200/80 rounded-xl m-4">
                                        <div className="mx-auto w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                            <ShoppingCart className="h-4 w-4 text-slate-400" />
                                        </div>
                                        <p className="text-xs font-medium text-slate-700">No active orders yet.</p>
                                        <p className="text-[11px] text-slate-400 mt-1 max-w-[280px] mx-auto">
                                            Explore verified, escrow-guaranteed services from top professionals.
                                        </p>
                                        <div className="mt-4">
                                            <Link
                                                href="/marketplace/services"
                                                className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3.5 h-8 text-xs font-semibold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-colors"
                                            >
                                                Browse Services
                                            </Link>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* SECTION 3: My Service Catalog (As Seller) */}
                        <div className="bg-white border border-slate-200/60 rounded-xl shadow-xs overflow-hidden">
                            <div className="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/[0.15]">
                                <div>
                                    <h3 className="font-semibold text-sm text-slate-900 flex items-center gap-2">
                                        <Layers className="h-4 w-4 text-slate-500" /> My Service Catalog
                                    </h3>
                                    <p className="text-[11px] text-slate-400 mt-0.5">
                                        Your publicly visible services and customized package offerings.
                                    </p>
                                </div>
                            </div>
                            
                            <div className="divide-y divide-slate-100">
                                {listedGigs.map((gig: any) => (
                                    <div 
                                        key={gig.id}
                                        className="p-4 hover:bg-slate-50/30 transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <Link
                                                href={`/marketplace/services/${gig.id}`}
                                                className="font-medium text-slate-900 text-sm hover:text-slate-800 transition"
                                            >
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
                                            <span className="text-[10px] text-slate-400 block">Starting at</span>
                                        </div>
                                    </div>
                                ))}
                                {listedGigs.length === 0 && (
                                    <div className="p-8 text-center bg-slate-50/10 border border-dashed border-slate-200/80 rounded-xl m-4">
                                        <div className="mx-auto w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                            <Layers className="h-4 w-4 text-slate-400" />
                                        </div>
                                        <p className="text-xs font-medium text-slate-700">No services listed yet.</p>
                                        <p className="text-[11px] text-slate-400 mt-1 max-w-[280px] mx-auto">
                                            Create your professional services list to let clients purchase pricing packages.
                                        </p>
                                        <div className="mt-4">
                                            <Button 
                                                onClick={() => setIsCreateModalOpen(true)}
                                                size="sm" 
                                                variant="outline"
                                                className="text-xs border-slate-200 hover:border-slate-350 hover:bg-slate-50 h-8"
                                            >
                                                Create Service Listing
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>

                    {/* Secondary Navigation & Guides (Right 3 Columns) */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* Quick Actions Panel */}
                        <div className="bg-white border border-slate-200/60 rounded-xl p-4 shadow-xs space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Workspace Actions
                            </h4>
                            <div className="space-y-1.5">
                                <Link
                                    href="/marketplace/services"
                                    className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700 transition group text-xs font-medium bg-white"
                                >
                                    <span className="flex items-center gap-2">
                                        <ShoppingCart className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" /> Browse Gigs Catalog
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <button
                                    onClick={() => setIsCreateModalOpen(true)}
                                    className="w-full flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700 transition group text-xs font-medium bg-white"
                                >
                                    <span className="flex items-center gap-2">
                                        <Plus className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" /> Publish Service Gig
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </button>

                                <Link
                                    href="/marketplace/orders"
                                    className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700 transition group text-xs font-medium bg-white"
                                >
                                    <span className="flex items-center gap-2">
                                        <Clock className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" /> Order History
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>

                                <Link
                                    href="/financial/withdrawals"
                                    className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700 transition group text-xs font-medium bg-white"
                                >
                                    <span className="flex items-center gap-2">
                                        <Wallet className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" /> Withdraw Earnings
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </Link>
                            </div>
                        </div>

                        {/* Onboarding checklist details */}
                        <div className="bg-slate-50/50 border border-slate-200/50 rounded-xl p-4 text-[11px] leading-relaxed text-slate-600 space-y-2.5">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-800">
                                <Lock className="h-3.5 w-3.5 text-slate-500" /> Escrow Protection
                            </div>
                            <p>
                                Every transaction runs under secure financial escrow. Client payments are protected securely on purchase and only unlocked upon client deliverable approvals.
                            </p>
                        </div>

                        <div className="bg-slate-50/50 border border-slate-200/50 rounded-xl p-4 text-[11px] leading-relaxed text-slate-600 space-y-2.5">
                            <div className="flex items-center gap-1.5 font-semibold text-slate-800">
                                <AlertCircle className="h-3.5 w-3.5 text-slate-500" /> Getting Started
                            </div>
                            <p>
                                Create and publish your gig listings. Clients can purchase standard tiered packages directly, escrow funds are protected, and payouts clear immediately once work is approved.
                            </p>
                        </div>

                    </div>
                </div>

            </div>

            {/* Publish Service Dialog */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="sm:max-w-md bg-white border border-slate-200 shadow-xl rounded-xl">
                    <DialogHeader>
                        <DialogTitle className="text-slate-900 font-semibold text-base font-sans">
                            Publish a Service
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 mt-1">
                            Create a public service catalog listing. You can define tiered packages and delivery times in the next step.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handlePublish} className="space-y-4 py-2">
                        <div className="space-y-1.5">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                Service Title
                            </label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={e => setData('title', e.target.value)}
                                placeholder="e.g. Design a Premium Figma Landing Page"
                                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-0 transition-colors placeholder:text-slate-400"
                                required
                            />
                            {errors.title && (
                                <span className="text-xs text-rose-600 mt-1 block">{errors.title}</span>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                Category
                            </label>
                            <select
                                value={data.category_id}
                                onChange={e => setData('category_id', e.target.value)}
                                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-0 transition-colors"
                                required
                            >
                                <option value="" disabled>Select category...</option>
                                {categories.map((cat: any) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                            {errors.category_id && (
                                <span className="text-xs text-rose-600 mt-1 block">{errors.category_id}</span>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                                Description
                            </label>
                            <textarea
                                value={data.description}
                                onChange={e => setData('description', e.target.value)}
                                placeholder="Describe what capabilities and scope this service offers..."
                                className="w-full min-h-[100px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-0 transition-colors resize-none placeholder:text-slate-400"
                                required
                            />
                            {errors.description && (
                                <span className="text-xs text-rose-600 mt-1 block">{errors.description}</span>
                            )}
                        </div>

                        <DialogFooter className="mt-6 flex flex-row justify-end gap-2 border-t border-slate-100 pt-4 -mx-4 -mb-4 bg-slate-50/50 rounded-b-xl px-4">
                            <Button 
                                type="button" 
                                variant="outline" 
                                onClick={() => setIsCreateModalOpen(false)}
                                className="text-xs font-semibold h-9 rounded-lg border-slate-200 hover:border-slate-300 hover:bg-slate-50 bg-white"
                            >
                                Cancel
                            </Button>
                            <Button 
                                type="submit" 
                                disabled={processing}
                                className="bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs h-9 rounded-lg px-4 disabled:opacity-50 transition-colors"
                            >
                                {processing ? 'Publishing...' : 'Publish Draft'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Contextual Side Panel */}
            <ServiceQuickView
                isOpen={selectedService !== null}
                onClose={() => setSelectedService(null)}
                data={selectedService}
            />
        </AuthenticatedLayout>
    );
}
