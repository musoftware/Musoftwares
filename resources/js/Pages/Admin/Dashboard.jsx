import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, recentInvoices, recentOrders, recentWithdrawals }) {
    const hasData = stats.totalClients > 0 || stats.activeTenants > 0 || stats.revenueThisMonth > 0;

    return (
        <div className="p-6">
            <Head title="Admin Dashboard" />
            <h1 className="text-3xl font-bold mb-6 font-sora">Admin Dashboard</h1>

            {!hasData ? (
                /* Welcome State (No Data) */
                <div className="bg-white p-8 rounded-[12px] shadow-lg border border-gray-100 max-w-3xl mx-auto text-center mt-12">
                    <h3 className="text-[24px] font-bold font-sora mb-6">Welcome to your ERP, Admin! 👋</h3>
                    <p className="text-gray-600 mb-8">Get started by setting up the platform:</p>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-left max-w-md mx-auto">
                        <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                            <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">1</div>
                            <span className="font-medium">Add your first client</span>
                        </div>
                        <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                            <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">2</div>
                            <span className="font-medium">Create your first invoice</span>
                        </div>
                        <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px] md:col-span-2">
                            <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">3</div>
                            <span className="font-medium">Set up your bank account</span>
                        </div>
                    </div>

                    <div className="flex justify-center space-x-4">
                        <Link href="/admin/clients" className="bg-indigo-600 text-white px-6 py-2 rounded-[8px] hover:bg-indigo-700 transition">
                            Add Client
                        </Link>
                        <Link href="#" className="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-[8px] hover:bg-gray-50 transition">
                            Create Invoice
                        </Link>
                    </div>
                </div>
            ) : (
                <>
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div className="bg-white p-6 rounded-lg shadow">
                            <h3 className="text-gray-500 text-sm font-medium">Total Clients</h3>
                            <p className="text-3xl font-bold font-sora">{stats.totalClients}</p>
                        </div>
                        <div className="bg-white p-6 rounded-lg shadow">
                            <h3 className="text-gray-500 text-sm font-medium">Active Tenants</h3>
                            <p className="text-3xl font-bold font-sora">{stats.activeTenants}</p>
                        </div>
                        <div className="bg-white p-6 rounded-lg shadow">
                            <h3 className="text-gray-500 text-sm font-medium">Revenue This Month</h3>
                            <p className="text-3xl font-bold font-sora">
                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(stats.revenueThisMonth || 0)}
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Recent Invoices */}
                        <div className="bg-white p-6 rounded-lg shadow">
                            <h3 className="text-lg font-bold mb-4 font-sora">Recent Invoices</h3>
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
                            <h3 className="text-lg font-bold mb-4 font-sora">Recent Orders</h3>
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
                            <h3 className="text-lg font-bold mb-4 font-sora">Recent Withdrawals</h3>
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
                        <h3 className="text-lg font-bold mb-4 font-sora">Quick Actions</h3>
                        <div className="space-x-4">
                            <Link href="/admin/reports/pnl" className="bg-blue-500 text-white px-4 py-2 rounded">P&L Report</Link>
                            <Link href="/admin/clients" className="bg-green-500 text-white px-4 py-2 rounded">View Clients</Link>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
