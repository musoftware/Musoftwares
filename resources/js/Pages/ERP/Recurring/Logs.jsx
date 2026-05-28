import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import Pagination from '@/Components/Pagination';
import { format } from 'date-fns';
import { ArrowLeft, CheckCircle2, XCircle } from 'lucide-react';

export default function Logs({ entry, logs }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={`${entry.title} — Execution History`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={`${entry.title} — Execution History`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('erp.recurring.index')}
                            className="text-sm text-indigo-600 hover:text-indigo-900 flex items-center"
                        >
                            <ArrowLeft className="w-4 h-4 mr-1" /> Back to Recurring Entries
                        </Link>
                    </div>

                    <PageHeader
                        title={`${entry.title} — Execution History`}
                        subtitle={`Managed by ${entry.frequency} schedule`}
                    />

                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Amount</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exchange Rate</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {logs.data.map((log) => (
                                    <tr key={log.id} className={log.status === 'failed' ? 'bg-red-50' : ''}>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {format(new Date(log.executed_at), 'MMM d, yyyy HH:mm')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <CurrencyDisplay amount={log.amount} currency={log.amount_currency} />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <CurrencyDisplay amount={log.business_amount} currency={log.business_currency} />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {parseFloat(log.exchange_rate).toFixed(4)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                {log.status === 'success' ? (
                                                    <CheckCircle2 className="w-4 h-4 text-green-500 mr-1" />
                                                ) : (
                                                    <XCircle className="w-4 h-4 text-red-500 mr-1" />
                                                )}
                                                <span className={`text-xs font-semibold uppercase ${log.status === 'success' ? 'text-green-800' : 'text-red-800'}`}>
                                                    {log.status}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {log.note || '-'}
                                        </td>
                                    </tr>
                                ))}
                                {logs.data.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-12 text-center text-sm text-gray-500">
                                            No execution history found for this entry.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-6">
                        <Pagination links={logs.links} />
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
