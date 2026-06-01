import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Store, TrendingUp, AlertTriangle, Activity } from 'lucide-react';
import { BranchSwitcher } from '@/Components/ERP/BranchSwitcher';
import { formatMoney } from '@/lib/utils';
import { useERPMenu } from '@/hooks/useERPMenu';

interface Props {
    activeBranchId: number | null;
    branches: Array<{ id: number; name: string; type: string }>;
    metrics: {
        revenue: number;
        transfers_sent: number;
        transfers_received: number;
        alerts: number;
    };
}

export default function BranchDashboard({ activeBranchId, branches, metrics }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('branches');

    return (
        <ERPLayout 
            title={__('general.branch_dashboard')}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('general.branch_dashboard_erp')} />

            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.branch_operations')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('general.realtime_overview_of_your_local_branch_health_and_kpis')}</p>
                </div>

                <div className="flex items-center gap-4">
                    <BranchSwitcher branches={branches} activeBranchId={activeBranchId} />
                </div>
            </div>

            {/* KPIs */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <MetricCard 
                    label={__('general.local_revenue')}
                    value={formatMoney(metrics.revenue, 'USD')}
                    icon={TrendingUp}
                />
                <MetricCard 
                    label={__('general.transfers_sent')}
                    value={metrics.transfers_sent}
                    icon={Activity}
                />
                <MetricCard 
                    label={__('general.transfers_received')}
                    value={metrics.transfers_received}
                    icon={Store}
                />
                <MetricCard 
                    label={__('general.operational_alerts')}
                    value={metrics.alerts}
                    icon={AlertTriangle}
                />
            </div>

            {/* Main Content Area */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-8">
                    <div className="bg-white border rounded-xl p-6 shadow-sm">
                        <h3 className="font-medium text-lg mb-4">{__('general.realtime_activity')}</h3>
                        <div className="text-sm text-slate-500">{__('general.websocket_realtime_feed_will_display_incoming_transfers_bookings_and_inventory_updates_here')}</div>
                    </div>
                </div>

                <div className="space-y-8">
                    <div className="bg-white border rounded-xl p-6 shadow-sm">
                        <h3 className="font-medium text-lg mb-4">{__('general.branch_health')}</h3>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500">{__('general.system_status')}</span>
                                <span className="text-emerald-600 font-medium">Online</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500">{__('general.low_stock_items')}</span>
                                <span className="text-rose-600 font-medium">12 Items</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
