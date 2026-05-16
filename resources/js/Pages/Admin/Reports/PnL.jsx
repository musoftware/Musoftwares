import { Head, useForm } from '@inertiajs/react';

export default function PnL({
    filters,
    incomeBreakdown,
    totalIncome,
    expenseBreakdown,
    totalExpenses,
    netProfit,
    tenantStats,
}) {
    const { data, setData, get } = useForm({
        from: filters.from || '',
        to: filters.to || '',
    });

    const handleFilter = (e) => {
        e.preventDefault();
        get('/admin/reports/pnl');
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount || 0);
    };

    return (
        <div className="mx-auto max-w-4xl p-6">
            <Head title="P&L Report" />
            <h1 className="mb-6 text-3xl font-bold">Profit & Loss Report</h1>

            {/* Date Range Picker */}
            <form
                onSubmit={handleFilter}
                className="mb-8 flex items-end space-x-4"
            >
                <div>
                    <label className="mb-1 block text-sm font-medium">
                        From Date
                    </label>
                    <input
                        type="date"
                        value={data.from}
                        onChange={(e) => setData('from', e.target.value)}
                        className="rounded border px-3 py-2"
                    />
                </div>
                <div>
                    <label className="mb-1 block text-sm font-medium">
                        To Date
                    </label>
                    <input
                        type="date"
                        value={data.to}
                        onChange={(e) => setData('to', e.target.value)}
                        className="rounded border px-3 py-2"
                    />
                </div>
                <button
                    type="submit"
                    className="h-[42px] rounded bg-blue-600 px-4 py-2 text-white"
                >
                    Filter
                </button>
            </form>

            <div className="mb-8 grid grid-cols-1 gap-8 md:grid-cols-2">
                {/* Income Breakdown */}
                <div className="rounded bg-white p-6 shadow">
                    <h2 className="mb-4 border-b pb-2 text-xl font-bold">
                        Income Breakdown
                    </h2>
                    <ul className="space-y-2 font-mono">
                        {Object.entries(incomeBreakdown).map(
                            ([source, amount]) => (
                                <li
                                    key={source}
                                    className="flex justify-between"
                                >
                                    <span>{source.padEnd(20, '\u00A0')}</span>
                                    <span>{formatCurrency(amount)}</span>
                                </li>
                            ),
                        )}
                        <li className="mt-2 flex justify-between border-t pt-2 font-bold">
                            <span>Total Income</span>
                            <span>{formatCurrency(totalIncome)}</span>
                        </li>
                    </ul>
                </div>

                {/* Expense Breakdown */}
                <div className="rounded bg-white p-6 shadow">
                    <h2 className="mb-4 border-b pb-2 text-xl font-bold">
                        Expense Breakdown
                    </h2>
                    <ul className="space-y-2 font-mono">
                        {Object.entries(expenseBreakdown).map(
                            ([type, amount]) => (
                                <li key={type} className="flex justify-between">
                                    <span>{type.padEnd(20, '\u00A0')}</span>
                                    <span>{formatCurrency(amount)}</span>
                                </li>
                            ),
                        )}
                        <li className="mt-2 flex justify-between border-t pt-2 font-bold">
                            <span>Total Expenses</span>
                            <span>{formatCurrency(totalExpenses)}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {/* Net Profit */}
            <div className="mb-8 rounded bg-white p-6 shadow">
                <div className="flex items-center justify-between text-2xl font-bold">
                    <span>Net Profit</span>
                    <span
                        className={
                            netProfit >= 0 ? 'text-green-600' : 'text-red-600'
                        }
                    >
                        {formatCurrency(netProfit)}
                    </span>
                </div>
            </div>

            {/* Tenant Stats */}
            <div className="rounded bg-white p-6 shadow">
                <h2 className="mb-4 border-b pb-2 text-xl font-bold">
                    Tenant Revenue Stats
                </h2>
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
                                <td className="py-2 text-right">
                                    {formatCurrency(tenant.revenue)}
                                </td>
                            </tr>
                        ))}
                        {tenantStats.length === 0 && (
                            <tr>
                                <td
                                    colSpan="2"
                                    className="py-4 text-center text-gray-500"
                                >
                                    No tenant stats found for this period.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
