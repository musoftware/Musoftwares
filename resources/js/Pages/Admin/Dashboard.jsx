import { Head, Link } from '@inertiajs/react';

export default function Dashboard({
    stats,
    recentInvoices,
    recentOrders,
    recentWithdrawals,
}) {
    return (
        <div className="p-6">
            <Head title="Admin Dashboard" />
            <h1 className="mb-6 text-3xl font-bold">Admin Dashboard</h1>

            {/* Stats Cards */}
            <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="text-sm font-medium text-gray-500">
                        Total Clients
                    </h3>
                    <p className="text-3xl font-bold">{stats.totalClients}</p>
                </div>
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="text-sm font-medium text-gray-500">
                        Active Tenants
                    </h3>
                    <p className="text-3xl font-bold">{stats.activeTenants}</p>
                </div>
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="text-sm font-medium text-gray-500">
                        Revenue This Month
                    </h3>
                    <p className="text-3xl font-bold">
                        {new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD',
                        }).format(stats.revenueThisMonth || 0)}
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {/* Recent Invoices */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-bold">Recent Invoices</h3>
                    <ul className="space-y-3">
                        {recentInvoices.map((invoice) => (
                            <li key={invoice.id} className="border-b pb-2">
                                <p className="font-medium">
                                    #{invoice.number} - {invoice.client?.name}
                                </p>
                                <p className="text-sm text-gray-500">
                                    {new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: invoice.currency_code,
                                    }).format(invoice.total)}{' '}
                                    - {invoice.status}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Recent Orders */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-bold">Recent Orders</h3>
                    <ul className="space-y-3">
                        {recentOrders.map((order) => (
                            <li key={order.id} className="border-b pb-2">
                                <p className="font-medium">Order #{order.id}</p>
                                <p className="text-sm text-gray-500">
                                    {order.buyer?.name} → {order.seller?.name}{' '}
                                    <br />
                                    {new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: order.currency_code,
                                    }).format(order.amount)}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Recent Withdrawals */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-bold">
                        Recent Withdrawals
                    </h3>
                    <ul className="space-y-3">
                        {recentWithdrawals.map((withdrawal) => (
                            <li key={withdrawal.id} className="border-b pb-2">
                                <p className="font-medium">
                                    Wallet {withdrawal.wallet_id}
                                </p>
                                <p className="text-sm text-gray-500">
                                    {withdrawal.description} <br />
                                    {new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'USD',
                                    }).format(withdrawal.amount)}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="mt-8">
                <h3 className="mb-4 text-lg font-bold">Quick Actions</h3>
                <div className="space-x-4">
                    <Link
                        href="/admin/reports/pnl"
                        className="rounded bg-blue-500 px-4 py-2 text-white"
                    >
                        P&L Report
                    </Link>
                    <Link
                        href="/admin/clients"
                        className="rounded bg-green-500 px-4 py-2 text-white"
                    >
                        View Clients
                    </Link>
                </div>
            </div>
        </div>
    );
}
