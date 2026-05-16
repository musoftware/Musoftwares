import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';

export default function PnL({ filters, incomeBreakdown, totalIncome, expenseBreakdown, totalExpenses, netProfit, tenantStats }) {
    const { data, setData, get } = useForm({
        from: filters.from || '',
        to: filters.to || '',
    });

    const handleFilter = (e) => {
        e.preventDefault();
        get('/admin/reports/pnl');
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
    };

    return (
        <div className="p-6 max-w-4xl mx-auto">
            <Head title="P&L Report" />
            <h1 className="text-3xl font-bold mb-6">Profit & Loss Report</h1>

            {/* Date Range Picker */}
            <form onSubmit={handleFilter} className="mb-8 flex space-x-4 items-end">
                <div>
                    <label className="block text-sm font-medium mb-1">From Date</label>
                    <input
                        type="date"
                        value={data.from}
                        onChange={e => setData('from', e.target.value)}
                        className="border rounded px-3 py-2"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">To Date</label>
                    <input
                        type="date"
                        value={data.to}
                        onChange={e => setData('to', e.target.value)}
                        className="border rounded px-3 py-2"
                    />
                </div>
                <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded h-[42px]">
                    Filter
                </button>
            </form>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                {/* Income Breakdown */}
                <div className="bg-white p-6 rounded shadow">
                    <h2 className="text-xl font-bold mb-4 border-b pb-2">Income Breakdown</h2>
                    <ul className="space-y-2 font-mono">
                        {Object.entries(incomeBreakdown).map(([source, amount]) => (
                            <li key={source} className="flex justify-between">
                                <span>{source.padEnd(20, '\u00A0')}</span>
                                <span>{formatCurrency(amount)}</span>
                            </li>
                        ))}
                        <li className="flex justify-between font-bold border-t pt-2 mt-2">
                            <span>Total Income</span>
                            <span>{formatCurrency(totalIncome)}</span>
                        </li>
                    </ul>
                </div>

                {/* Expense Breakdown */}
                <div className="bg-white p-6 rounded shadow">
                    <h2 className="text-xl font-bold mb-4 border-b pb-2">Expense Breakdown</h2>
                    <ul className="space-y-2 font-mono">
                        {Object.entries(expenseBreakdown).map(([type, amount]) => (
                            <li key={type} className="flex justify-between">
                                <span>{type.padEnd(20, '\u00A0')}</span>
                                <span>{formatCurrency(amount)}</span>
                            </li>
                        ))}
                        <li className="flex justify-between font-bold border-t pt-2 mt-2">
                            <span>Total Expenses</span>
                            <span>{formatCurrency(totalExpenses)}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {/* Net Profit */}
            <div className="bg-white p-6 rounded shadow mb-8">
                <div className="flex justify-between items-center text-2xl font-bold">
                    <span>Net Profit</span>
                    <span className={netProfit >= 0 ? 'text-green-600' : 'text-red-600'}>
                        {formatCurrency(netProfit)}
                    </span>
                </div>
            </div>

            {/* Tenant Stats */}
            <div className="bg-white p-6 rounded shadow">
                <h2 className="text-xl font-bold mb-4 border-b pb-2">Tenant Revenue Stats</h2>
                <table className="w-full text-left">
                    <thead>
                        <tr>
                            <th className="pb-2">Tenant Name</th>
                            <th className="pb-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tenantStats.map((tenant, idx) => (
                            <tr key={idx} className="border-t">
                                <td className="py-2">{tenant.tenant_name}</td>
                                <td className="py-2 text-right">{formatCurrency(tenant.revenue)}</td>
                            </tr>
                        ))}
                        {tenantStats.length === 0 && (
                            <tr>
                                <td colSpan="2" className="py-4 text-center text-gray-500">No tenant stats found for this period.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
