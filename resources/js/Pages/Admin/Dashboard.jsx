import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, recentInvoices, recentOrders, recentWithdrawals }) {
    return (
        <div className="p-6">
            <Head title="Admin Dashboard" />
            <h1 className="text-3xl font-bold mb-6">Admin Dashboard</h1>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-gray-500 text-sm font-medium">Total Clients</h3>
                    <p className="text-3xl font-bold">{stats.totalClients}</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-gray-500 text-sm font-medium">Active Tenants</h3>
                    <p className="text-3xl font-bold">{stats.activeTenants}</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-gray-500 text-sm font-medium">Revenue This Month</h3>
                    <p className="text-3xl font-bold">
                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(stats.revenueThisMonth || 0)}
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Recent Invoices */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-bold mb-4">Recent Invoices</h3>
                    <ul className="space-y-3">
                        {recentInvoices.map((invoice) => (
                            <li key={invoice.id} className="border-b pb-2">
                                <p className="font-medium">#{invoice.number} - {invoice.client?.name}</p>
                                <p className="text-sm text-gray-500">
                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: invoice.currency_code }).format(invoice.total)} - {invoice.status}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Recent Orders */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-bold mb-4">Recent Orders</h3>
                    <ul className="space-y-3">
                        {recentOrders.map((order) => (
                            <li key={order.id} className="border-b pb-2">
                                <p className="font-medium">Order #{order.id}</p>
                                <p className="text-sm text-gray-500">
                                    {order.buyer?.name} → {order.seller?.name} <br/>
                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: order.currency_code }).format(order.amount)}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Recent Withdrawals */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-bold mb-4">Recent Withdrawals</h3>
                    <ul className="space-y-3">
                        {recentWithdrawals.map((withdrawal) => (
                            <li key={withdrawal.id} className="border-b pb-2">
                                <p className="font-medium">Wallet {withdrawal.wallet_id}</p>
                                <p className="text-sm text-gray-500">
                                    {withdrawal.description} <br/>
                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(withdrawal.amount)}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="mt-8">
                <h3 className="text-lg font-bold mb-4">Quick Actions</h3>
                <div className="space-x-4">
                    <Link href="/admin/reports/pnl" className="bg-blue-500 text-white px-4 py-2 rounded">P&L Report</Link>
                    <Link href="/admin/clients" className="bg-green-500 text-white px-4 py-2 rounded">View Clients</Link>
                </div>
            </div>
        </div>
    );
}
