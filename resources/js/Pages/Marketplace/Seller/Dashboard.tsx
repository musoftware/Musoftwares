import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link } from '@inertiajs/react';
import {
    LayoutDashboard,
    Package,
    Wallet,
    DollarSign,
    Layers,
    ArrowUpRight
} from 'lucide-react';

import { formatMoney } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';

export default function SellerDashboard({ stats, recent_orders }: any) {
    return (
        <MarketplaceLayout>
            <Head title="Seller Dashboard" />
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <ModulePageHeader 
                    title="Seller Dashboard"
                    description="Overview of your seller account, sales, and products."
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <MetricCard 
                        label="Total Sales"
                        value={formatMoney(stats.total_sales, 'USD')}
                        icon={DollarSign}
                    />
                    <MetricCard 
                        label="Active Products"
                        value={stats.active_products}
                        icon={Layers}
                    />
                    <MetricCard 
                        label="Pending Payouts"
                        value={formatMoney(stats.pending_payouts, 'USD')}
                        icon={Wallet}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        <OperationalCard title="Recent Orders" description="Your latest marketplace orders." noPadding>
                            <div className="divide-y divide-slate-100">
                                {recent_orders.map((order: any) => (
                                    <div key={order.id} className="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                                        <div className="space-y-1">
                                            <div className="font-medium text-slate-900 text-sm">
                                                Order #{order.id}: {order.package?.service?.title || 'Unknown Service'}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span>Buyer: {order.buyer?.name}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-mono font-semibold text-slate-900">
                                                {formatMoney(order.amount, 'USD')}
                                            </span>
                                            <StatusBadge status={order.status} size="sm" />
                                        </div>
                                    </div>
                                ))}
                                {recent_orders.length === 0 && (
                                    <div className="p-8 text-center text-slate-500">
                                        No recent orders.
                                    </div>
                                )}
                            </div>
                        </OperationalCard>
                    </div>

                    <div className="space-y-6">
                        <OperationalCard>
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Quick Links</h4>
                            <div className="space-y-1.5">
                                <Link href="/seller/products" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Package className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Manage Products
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform" />
                                </Link>

                                <Link href="/seller/payouts" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <Wallet className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Payouts & Escrow
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform" />
                                </Link>
                                
                                <Link href="/financial/withdrawals" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-900 transition group text-sm font-medium bg-white">
                                    <span className="flex items-center gap-2">
                                        <DollarSign className="h-4 w-4 text-slate-400 group-hover:text-slate-600" /> Withdraw Funds
                                    </span>
                                    <ArrowUpRight className="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform" />
                                </Link>
                            </div>
                        </OperationalCard>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}
