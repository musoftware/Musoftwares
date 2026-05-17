import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ShoppingCart,
    Layers,
    Plus,
    CheckCircle2,
    Lock,
    DollarSign,
    Clock,
    ArrowUpRight,
    Search,
    BadgeAlert,
    Star
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { formatMoney, formatDate } from '@/lib/utils';
import { ServiceQuickView } from '@/Components/ContextualPanels';

export default function MarketplaceDashboard({ stats: initialStats, activePurchases: initialPurchases, listedGigs: initialGigs }: any) {
    const [selectedService, setSelectedService] = useState<any>(null);

    // Core financial state for marketplace activities from server
    const stats = initialStats || {
        lockedEscrow: 0,
        activeOrders: 0,
        servicesListed: 0,
        totalSales: 0
    };

    const activePurchases = initialPurchases || [];
    const listedGigs = initialGigs || [];

    return (
        <AuthenticatedLayout header="Marketplace Operations Hub">
            <Head title="Marketplace Hub" />

            <div className="max-w-5xl mx-auto space-y-6 pb-12 font-sans text-sm">
                
                {/* ─────────────────────────────────────────
                    MARKETPLACE KPI DECK
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Escrow Locked Hold */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Escrow Protected Balance</span>
                            <Lock className="h-4 w-4 text-indigo-500 animate-pulse" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {formatMoney(stats.lockedEscrow, 'USD')}
                            </span>
                            <span className="text-[10px] text-indigo-600 font-semibold block mt-1">
                                Secure holding active
                            </span>
                        </div>
                    </div>

                    {/* Active Gig Orders */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Active Orders</span>
                            <Clock className="h-4 w-4 text-amber-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {stats.activeOrders} Order
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-1">
                                Delivery expected in 2 days
                            </span>
                        </div>
                    </div>

                    {/* Active Services Listed */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Services Catalog</span>
                            <Layers className="h-4 w-4 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {stats.servicesListed} Catalog Gigs
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-1">
                                Publicly listed on marketplace
                            </span>
                        </div>
                    </div>

                    {/* Completed Sales Earnings */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Completed Earnings</span>
                            <DollarSign className="h-4 w-4 text-emerald-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {formatMoney(stats.totalSales, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold block mt-1">
                                100% payout cleared
                            </span>
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    DUAL PURCHASES & CATALOG SECTIONS
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                    
                    {/* 70% Primary Area */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        {/* Section 1: Purchases (As Buyer) */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-border/50 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-2">
                                    <ShoppingCart className="h-4 w-4 text-indigo-500" /> Active Service Purchases (As Buyer)
                                </h3>
                                <span className="bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[10px] font-bold px-2 py-0.5">
                                    Escrow Protection Guaranteed
                                </span>
                            </div>
                            
                            <div className="divide-y divide-border/40 text-xs">
                                {activePurchases.map((purchase: any) => (
                                    <div 
                                        key={purchase.id}
                                        onClick={() => setSelectedService(purchase)}
                                        className="p-4 hover:bg-slate-50/70 cursor-pointer transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <div className="font-semibold text-text-primary text-[13px] flex items-center gap-2">
                                                Order #{purchase.id}: {purchase.title}
                                            </div>
                                            <p className="text-[11px] text-text-secondary">Expected delivery scheduled for: {formatDate(purchase.deliveryDate)}</p>
                                            <div className="flex items-center gap-2 mt-1.5">
                                                <span className="text-[10px] font-bold uppercase bg-indigo-50 text-indigo-700 px-1.5 py-0.2 rounded border border-indigo-200">
                                                    {purchase.status}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="font-mono font-bold text-text-primary block text-[13px]">
                                                {formatMoney(purchase.amount, 'USD')}
                                            </span>
                                            <span className="text-[9px] text-emerald-600 font-semibold block mt-0.5">Escrow Active</span>
                                        </div>
                                    </div>
                                ))}
                                {activePurchases.length === 0 && (
                                    <div className="p-6 text-center text-gray-500">
                                        <p className="font-medium text-gray-700">No active purchases.</p>
                                        <p className="text-xs mt-1">Browse the directory to purchase verified services safely using Escrow.</p>
                                        <Link href="/marketplace/services" className="inline-block mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            Explore Services →
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Section 2: Catalog Gigs (As Seller) */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-border/50 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-2">
                                    <Layers className="h-4 w-4 text-indigo-500" /> Public Service Catalog (As Seller)
                                </h3>
                                <Link 
                                    href="#" 
                                    className="text-[11px] text-indigo-600 font-semibold hover:underline flex items-center gap-0.5"
                                >
                                    Publish New Service <Plus className="h-3.5 w-3.5" />
                                </Link>
                            </div>
                            
                            <div className="divide-y divide-border/40 text-xs">
                                {listedGigs.map((gig: any) => (
                                    <div 
                                        key={gig.id}
                                        className="p-4 hover:bg-slate-50/40 transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <div className="font-semibold text-text-primary text-[13px]">
                                                {gig.title}
                                            </div>
                                            <div className="flex items-center gap-1 text-[10px] text-text-secondary mt-0.5">
                                                <span className="flex items-center text-amber-500 font-bold gap-0.5">
                                                    ★ {gig.rating}
                                                </span>
                                                <span>({gig.reviews} verified reviews)</span>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="font-mono font-bold text-text-primary block text-[13px]">
                                                {formatMoney(gig.price, 'USD')}
                                            </span>
                                            <span className="text-[9px] text-text-secondary block mt-0.5">Base Price Tier</span>
                                        </div>
                                    </div>
                                ))}
                                {listedGigs.length === 0 && (
                                    <div className="p-6 text-center text-gray-500">
                                        <p className="font-medium text-gray-700">No listed services.</p>
                                        <p className="text-xs mt-1">Ready to sell? Create your first gig package and start receiving orders from clients.</p>
                                        <Link href="#" className="inline-block mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            Publish Your First Gig →
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>

                    {/* 30% Context & Shortcuts Deck */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* Marketplace Core Actions */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 border-b border-border/40 pb-2">
                                <Search className="h-3.5 w-3.5 text-indigo-500" /> Discovery Navigation
                            </h4>
                            <div className="grid grid-cols-1 gap-2 text-xs">
                                <Link
                                    href="/marketplace/services"
                                    className="flex items-center gap-2.5 p-3 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/20 text-text-primary transition group"
                                >
                                    <ShoppingCart className="h-4 w-4 text-indigo-600 shrink-0 animate-pulse" />
                                    <span className="font-semibold block text-[11px]">Browse Gigs Directory</span>
                                </Link>

                                <Link
                                    href="/marketplace/orders"
                                    className="flex items-center gap-2.5 p-3 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/20 text-text-primary transition group"
                                >
                                    <Clock className="h-4 w-4 text-indigo-600 shrink-0" />
                                    <span className="font-semibold block text-[11px]">All Purchases Ledger</span>
                                </Link>
                            </div>
                        </div>

                        {/* Onboarding Checklist for Marketplace Selling */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 border-b border-border/40 pb-2">
                                <ShoppingCart className="h-3.5 w-3.5 text-indigo-500" /> Seller Checklist
                            </h4>
                            <div className="space-y-3 text-xs leading-normal">
                                <div className="flex gap-2 text-text-primary">
                                    <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block">Create Service Gigs</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5">Describe your capabilities and setup pricing tiers.</p>
                                    </div>
                                </div>
                                <div className="flex gap-2 text-text-primary">
                                    <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block">Verify Payout Account</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5">Ensure Wise or Bank accounts are fully linked.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Escrow Guarantee Banner */}
                        <div className="bg-indigo-50/40 border border-indigo-100/50 rounded-xl p-3.5 text-[11px] leading-relaxed text-indigo-900">
                            <div className="flex items-center gap-1.5 font-bold mb-1">
                                <Lock className="h-4 w-4 text-indigo-600" />
                                Unified Escrow Guarantee
                            </div>
                            Musoftware Marketplace runs under full financial escrow custody protections. Client payments are protected securely on purchase and only unlocked upon client deliverable approvals.
                        </div>

                    </div>
                </div>

            </div>

            {/* Contextual Side Panel */}
            <ServiceQuickView
                isOpen={selectedService !== null}
                onClose={() => setSelectedService(null)}
                data={selectedService}
            />
        </AuthenticatedLayout>
    );
}
